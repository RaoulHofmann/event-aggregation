<?php

namespace App\Http\Controllers;

use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FieldTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('FieldTemplates/Index', ['fieldTemplates' => FieldTemplate::orderBy('label')->get()]);
    }

    public function get()
    {
        return FieldTemplate::orderBy('label')->get();
    }

    private function data(Request $request, ?FieldTemplate $field = null): array
    {
        $rules = ['label' => ['required', 'string', 'max:255', Rule::unique('field_templates')->ignore($field?->id)], 'type' => ['required', 'in:text,number,date,datetime,email,url,select,boolean,textarea,decimal,image'], 'required' => ['boolean'], 'validation_rules' => ['nullable', 'array'], 'options' => ['nullable', 'array'], 'options.*' => ['string', 'max:255']];
        if (! $field) {
            $rules['field_id'] = ['required', 'string', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/', 'unique:field_templates,field_id'];
        }

        return $request->validate($rules);
    }

    public function store(Request $request)
    {
        FieldTemplate::create($this->data($request));

        return to_route('field-templates.index')->with('success', 'Field created.');
    }

    public function update(Request $request, FieldTemplate $fieldTemplate)
    {
        abort_if($fieldTemplate->system_field, 403);
        $fieldTemplate->update($this->data($request, $fieldTemplate));

        return to_route('field-templates.index')->with('success', 'Field updated.');
    }

    public function destroy(FieldTemplate $fieldTemplate)
    {
        abort_if($fieldTemplate->system_field, 403);
        if (EventTemplate::whereJsonContains('fields', $fieldTemplate->id)->exists()) {
            return back()->withErrors(['delete' => 'Remove this field from its templates first.']);
        }$fieldTemplate->delete();

        return back()->with('success', 'Field removed.');
    }
}
