<?php

namespace App\Integrations\Inbound;

use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportOptions
{
    public function validate(Request $request, array $additional = [], array $sourceFields = []): array
    {
        $data = $request->validate($additional + [
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'template_id' => ['nullable', 'integer', Rule::prohibitedIf($request->boolean('create_template')), Rule::exists('event_templates', 'id')->where('source', 'eventnest')->whereNull('deleted_at')],
            'field_mapping' => ['nullable', 'array'],
            'field_mapping.*' => ['nullable', Rule::in([...InboundSources::MAPPING, ...$sourceFields])],
        ]);

        $template = ! empty($data['template_id']) ? EventTemplate::findOrFail($data['template_id']) : null;
        $fields = FieldTemplate::whereIn('id', $template?->fields ?? [])->pluck('field_id')->all();
        if (array_diff(array_keys($data['field_mapping'] ?? []), $fields)) {
            throw ValidationException::withMessages(['field_mapping' => 'Map only fields in the selected template.']);
        }

        return $data;
    }
}
