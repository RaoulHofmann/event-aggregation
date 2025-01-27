<?php

namespace App\Http\Controllers;

use App\Models\FieldTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        // Log all request data to check what's coming through
        Log::alert("Request Data", $request->all());

        $validated = $request->validate([
            'label' => 'required|string|max:255|unique:field_templates,label',
            'field_id' => 'required|string|max:255|unique:field_templates,field_id',
            'type' => 'required|in:text,number,date,datetime,email,url,select,boolean,textarea,decimal',
            'required' => 'boolean',
            'validation_rules' => 'nullable|array',
            'options' => 'nullable|array',
            'options.*' => 'string|max:255',
        ]);

        // Create the new field template
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

        // Flash success message
        session()->flash('success', 'Field template created successfully.');

        // Redirect back to the index page
        return redirect()->route('field-templates.index');
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
