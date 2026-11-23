<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Team;
use App\Models\WorkspaceImage;
use App\Services\WorkspaceImageStorage;
use App\Traits\SchemaManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class WorkspaceImageController extends Controller
{
    use SchemaManagement;

    public function index(Request $request)
    {
        return Inertia::render('Assets/Index', [
            'canUpload' => $this->can($request, 'create'),
            'canDelete' => $this->can($request, 'delete'),
        ]);
    }

    public function list(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);

        return response()->json(WorkspaceImage::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(18, ['id', 'name', 'bytes'], page: $filters['page'] ?? 1));
    }

    public function store(Request $request, WorkspaceImageStorage $storage)
    {
        abort_unless($this->can($request, 'create'), 403);
        $data = $request->validate([
            'image' => ['required', File::image()->max(5120), 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=4096,max_height=4096'],
        ]);
        $upload = $data['image'];
        $name = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', mb_scrub($upload->getClientOriginalName()))) ?: 'Untitled image';
        $image = new WorkspaceImage(['name' => mb_substr($name, 0, 255), 'mime' => $upload->getMimeType(), 'bytes' => $upload->getSize()]);
        $image->id = $image->newUniqueId();
        $key = $image->storageKey($this->workspace($request)->id);
        try {
            $storage->put($key, $upload->get(), $image->mime);
        } catch (RuntimeException) {
            abort(503, 'Image storage is temporarily unavailable.');
        }
        try {
            $image->save();
        } catch (\Throwable $error) {
            try {
                $storage->delete($key);
            } catch (RuntimeException) {
                // Preserve the original database error.
            }

            throw $error;
        }

        return response()->json($image->only('id', 'name', 'bytes'), 201);
    }

    public function show(Request $request, WorkspaceImage $image, WorkspaceImageStorage $storage)
    {
        return $this->respond($storage, $image, $this->workspace($request)->id);
    }

    public function destroy(Request $request, WorkspaceImage $image, WorkspaceImageStorage $storage)
    {
        abort_unless($this->can($request, 'delete'), 403);
        DB::transaction(function () use ($image) {
            $image = WorkspaceImage::withTrashed()->whereKey($image->id)->lockForUpdate()->firstOrFail();
            if (Event::where('cover_image_id', $image->id)->exists()) {
                throw ValidationException::withMessages(['image' => 'Remove this cover from its events before deleting the image.']);
            }
            if (! $image->trashed()) {
                $image->delete();
            }
        });
        try {
            $storage->delete($image->storageKey($this->workspace($request)->id));
        } catch (RuntimeException) {
            abort(503, 'Image storage is temporarily unavailable. Retry to finish deleting the image.');
        }

        return response()->noContent();
    }

    public function cover(int $workspace, string $cover, WorkspaceImageStorage $storage)
    {
        DB::statement('SET search_path TO public');
        try {
            $team = Team::findOrFail($workspace);
            abort_if($team->suspended_at || ! $this->isTenantSchema($team->schema_name), 404);
            $this->setSchema($team->schema_name);
            $image = Event::where('cover_public_id', $cover)->where('status', 'published')->firstOrFail()->coverImage;
            abort_unless($image, 404);

            return $this->respond($storage, $image, $workspace);
        } finally {
            DB::statement('SET search_path TO public');
        }
    }

    private function respond(WorkspaceImageStorage $storage, WorkspaceImage $image, int $workspace)
    {
        try {
            $bytes = $storage->get($image->storageKey($workspace));
        } catch (RuntimeException) {
            abort(503, 'Image storage is temporarily unavailable.');
        }
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => $image->mime,
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function can(Request $request, string $permission): bool
    {
        return $request->user()->hasTeamPermission($this->workspace($request), $permission);
    }

    private function workspace(Request $request): Team
    {
        return $request->attributes->get('api_workspace');
    }
}
