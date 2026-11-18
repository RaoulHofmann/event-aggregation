<?php

namespace App\Http\Controllers;

use App\Integrations\Inbound\CsvEvents;
use App\Integrations\Inbound\ImportException;
use App\Integrations\Inbound\ImportOptions;
use App\Integrations\Inbound\ImportWriter;
use App\Integrations\Inbound\PublicFeedClient;
use App\Integrations\Inbound\SourceTemplates;
use App\Jobs\SyncInboundEvents;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InboundImportController extends Controller
{
    private const PROVIDERS = ['ics', 'wordpress', 'eventbrite'];

    private function owner(Request $request): void
    {
        abort_unless($request->user()->ownsTeam($request->user()->currentTeam), 403);
    }

    public function index(Request $request)
    {
        $this->owner($request);

        return Inertia::render('Integrations/Imports', [
            'connections' => ProviderConnection::whereIn('provider', self::PROVIDERS)->orderBy('id')->get(),
            'eventTemplates' => EventTemplate::where('source', 'eventnest')->orderBy('name')->get(),
            'fieldTemplates' => FieldTemplate::orderBy('label')->get(),
            'starterTemplates' => SourceTemplates::DEFINITIONS,
        ]);
    }

    public function store(Request $request, string $provider, ImportOptions $options, PublicFeedClient $feeds)
    {
        return $this->save($request, $provider, $options, $feeds);
    }

    public function update(Request $request, int $connection, ImportOptions $options, PublicFeedClient $feeds)
    {
        $this->owner($request);
        $record = ProviderConnection::whereIn('provider', self::PROVIDERS)->findOrFail($connection);

        return $this->save($request, $record->provider, $options, $feeds, $record);
    }

    private function save(Request $request, string $provider, ImportOptions $options, PublicFeedClient $feeds, ?ProviderConnection $record = null)
    {
        $this->owner($request);
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        $key = $provider === 'eventbrite' ? 'api_key' : 'feed_url';
        $rules = [$key => [! $record || $record->status === 'disconnected' ? 'required' : 'nullable', 'string', 'max:4096']];
        if ($provider === 'eventbrite') {
            $rules['organization_id'] = ['required', 'regex:/^[0-9]{1,30}$/D'];
        }
        $sourceFields = SourceTemplates::DEFINITIONS[$provider] ?? [];
        $rules['create_template'] = $sourceFields ? ['sometimes', 'boolean'] : ['prohibited'];
        $data = $options->validate($request, $rules, array_keys($sourceFields));
        $secret = $data[$key] ?? null;
        if ($secret && $provider !== 'eventbrite') {
            try {
                $feeds->hostname($secret);
            } catch (ImportException $error) {
                throw ValidationException::withMessages([$key => $error->getMessage()]);
            }
            if ($provider === 'wordpress' && parse_url($secret, PHP_URL_QUERY) !== null) {
                throw ValidationException::withMessages([$key => 'Enter the WordPress site URL without a query string.']);
            }
        }

        $record = DB::transaction(function () use ($record, $provider, $data, $key, $secret) {
            $active = $record ? ProviderConnection::whereKey($record->id)->lockForUpdate()->firstOrFail() : new ProviderConnection(['provider' => $provider]);
            $previous = $active->settings ?? [];
            $credentials = $active->credentials ?? [];
            $secret ??= $credentials[$key] ?? null;
            if (! $secret) {
                throw ValidationException::withMessages([$key => 'Enter the connection details to reconnect.']);
            }
            $identity = match ($provider) {
                'eventbrite' => $data['organization_id'],
                'wordpress' => hash('sha256', rtrim($secret, '/')),
                default => hash('sha256', strtolower(parse_url($secret, PHP_URL_HOST)).(parse_url($secret, PHP_URL_PATH) ?: '/')),
            };
            if (isset($previous['identity']) && $previous['identity'] !== $identity) {
                throw ValidationException::withMessages([$key => 'Add a new connection when changing the source. This keeps existing imports linked to the right events.']);
            }
            $templateId = $data['template_id'] ?? null;
            $mapping = $data['field_mapping'] ?? [];
            $starterId = $previous['starter_template_id'] ?? null;
            $starterMapping = $previous['starter_template_mapping'] ?? [];
            if ($data['create_template'] ?? false) {
                if (! $starterId || ! EventTemplate::whereKey($starterId)->exists()) {
                    [$starterId, $starterMapping] = app(SourceTemplates::class)->create($provider, $data['name']);
                }
                $templateId = $starterId;
                $mapping = $active->template_id === $starterId ? ($active->field_mapping ?? []) : $starterMapping;
            }
            $active->fill([
                'name' => $data['name'],
                'credentials' => [$key => $secret],
                'settings' => array_filter([
                    'identity' => $identity, 'revision' => (string) Str::uuid(),
                    'timezone' => $data['timezone'], 'organization_id' => $data['organization_id'] ?? null,
                    'starter_template_id' => $starterId, 'starter_template_mapping' => $starterMapping,
                ]),
                'template_id' => $templateId,
                'field_mapping' => $mapping,
                'status' => 'queued',
                'last_error' => null,
            ])->save();

            return $active;
        });

        SyncInboundEvents::dispatch($request->user()->current_team_id, $record->id);

        return to_route('integrations.imports.index')->with('success', 'Import queued. New events arrive as drafts.');
    }

    public function previewCsv(Request $request, CsvEvents $csv)
    {
        $this->owner($request);
        $data = $request->validate(['file' => ['required', 'file', 'max:1024'], 'timezone' => ['required', 'timezone']]);
        $result = $this->parseCsv($csv, $data);

        return response()->json([
            'count' => count($result['events']),
            'sample' => array_map(fn ($event) => array_intersect_key($event->fields, array_flip(['title', 'starts_at', 'ends_at', 'timezone'])), array_slice($result['events'], 0, 8)),
            'errors' => array_slice($result['errors'], 0, 20),
        ]);
    }

    public function importCsv(Request $request, ImportOptions $options, CsvEvents $csv, ImportWriter $writer)
    {
        $this->owner($request);
        $data = $options->validate($request, ['name' => ['nullable'], 'file' => ['required', 'file', 'max:1024'], 'create_template' => ['prohibited']]);
        $result = $this->parseCsv($csv, $data);
        if ($result['errors'] || ! $result['events']) {
            throw ValidationException::withMessages(['file' => $result['errors'][0] ?? 'The file contains no event rows.']);
        }
        $count = DB::transaction(function () use ($writer, $data, $result) {
            DB::select("SELECT pg_advisory_xact_lock(hashtext(current_schema()), hashtext('csv_import'))");
            $connection = ProviderConnection::firstOrCreate(['provider' => 'csv'], ['name' => 'CSV imports', 'credentials' => [], 'status' => 'connected']);

            return $writer->write($connection, $result['events'], isset($data['template_id']) ? (int) $data['template_id'] : null, $data['field_mapping'] ?? []);
        });

        return to_route('integrations.imports.index')->with('success', "{$count} CSV events processed. Duplicate rows already imported were matched to their existing records.");
    }

    private function parseCsv(CsvEvents $csv, array $data): array
    {
        try {
            return $csv->read($data['file']->getRealPath(), $data['timezone']);
        } catch (ImportException $error) {
            throw ValidationException::withMessages(['file' => $error->getMessage()]);
        }
    }
}
