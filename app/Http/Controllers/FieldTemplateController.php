<?php

namespace App\Http\Controllers;

use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('FieldTemplates/Index', [
            'field-templates' => FieldTemplate::get()->sortBy('label')->values(),
        ]);
    }

    public function data()
    {
        return FieldTemplate::get()->sortBy('label')->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255|unique:field_templates,label',
            'field_id' => 'required|string|max:255|unique:field_templates,label',
            'type' => 'required|in:text,number,date,datetime,email,url,select,boolean,textarea,decimal',
            'required' => 'boolean',
            'validation_rules' => 'nullable|array',
            'options' => 'nullable|array',
            'options.*' => 'string|max:255',
        ]);

        FieldTemplate::create([
            'label' => $validated['label'],
            'field_id' => strtolower($validated['field_id']),
            'type' => $validated['type'],
            'required' => $validated['required'] ?? false,
            'validation_rules' => $validated['validation_rules'] ?? null,
            'options' => $validated['type'] === 'select'
                ? json_encode($validated['options'])
                : null,
        ]);

        return redirect()->route('field-templates.index')
            ->with('success', 'Field template created successfully.');
    }

    public function update(Request $request, FieldTemplate $fieldTemplate)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255|unique:field_templates,label,' . $fieldTemplate->id,
            'type' => 'required|in:text,number,date,datetime,email,url,select,boolean,textarea,decimal',
            'required' => 'boolean',
            'validation_rules' => 'nullable|array',
            'options' => 'nullable|array',
            'options.*' => 'string|max:255',
        ]);

        $fieldTemplate->update([
            'label' => $validated['label'],
            'type' => $validated['type'],
            'required' => $validated['required'] ?? false,
            'validation_rules' => $validated['validation_rules'] ?? null,
            'options' => $validated['type'] === 'select'
                ? json_encode($validated['options'])
                : null,
        ]);

        return redirect()->route('field-templates.index')
            ->with('success', 'Field template updated successfully.');
    }

    public function destroy(FieldTemplate $fieldTemplate)
    {
        $fieldTemplate->delete();

        return redirect()->route('field-templates.index')
            ->with('success', 'Field template deleted successfully.');
    }
}
