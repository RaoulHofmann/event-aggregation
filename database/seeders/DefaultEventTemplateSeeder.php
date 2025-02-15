<?php

namespace Database\Seeders;

use App\Traits\SchemaManagement;
use Illuminate\Database\Seeder;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Support\Facades\DB;

class DefaultEventTemplateSeeder extends Seeder
{
    use SchemaManagement;

    public function run()
    {
        $this->setSchema('test_user_team');
        DB::transaction(function () {
            // Define field templates
            $fields = [
                [
                    'label' => 'Name',
                    'field_id' => 'name',
                    'type' => 'text',
                    'required' => true,
                    'validation_rules' => [],
                    'system_field' => true,
                ],
                [
                    'label' => 'Description',
                    'field_id' => 'description',
                    'type' => 'textarea',
                    'required' => false,
                    'validation_rules' => [],
                ],
                [
                    'label' => 'Type',
                    'field_id' => 'type',
                    'type' => 'select',
                    'required' => false,
                    'options' => ['conference', 'workshop', 'concert'],
                    'validation_rules' => ['in:conference,workshop,concert'],
                ],
                [
                    'label' => 'Status',
                    'field_id' => 'status',
                    'type' => 'select',
                    'required' => true,
                    'options' => ['draft', 'published', 'cancelled'],
                    'validation_rules' => ['in:draft,published,cancelled'],
                ],
                [
                    'label' => 'Start Date',
                    'field_id' => 'start_date',
                    'type' => 'datetime-local',
                    'required' => true,
                    'system_field' => true,
                ],
                [
                    'label' => 'End Date',
                    'field_id' => 'end_date',
                    'type' => 'datetime-local',
                    'required' => false,
                    'system_field' => true,
                ],
                [
                    'label' => 'Recurring',
                    'field_id' => 'is_recurring',
                    'type' => 'checkbox',
                    'required' => false,
                    'system_field' => true,
                ],
                [
                    'label' => 'Recurring Frequency',
                    'field_id' => 'recurring_frequency',
                    'type' => 'select',
                    'required' => false,
                    'options' => ['daily', 'weekly', 'monthly', 'yearly'],
                    'validation_rules' => ['in:daily,weekly,monthly,yearly'],
                    'system_field' => true,
                ],
                [
                    'label' => 'Recurrence Interval',
                    'field_id' => 'recurrence_interval',
                    'type' => 'number',
                    'required' => false,
                    'validation_rules' => ['integer', 'min:1'],
                    'system_field' => true,
                ],
                [
                    'label' => 'Recurrence Days',
                    'field_id' => 'recurrence_days',
                    'type' => 'select',
                    'multiple' => true,
                    'required' => false,
                    'options' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'validation_rules' => ['array', 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
                    'system_field' => true,
                ],
                [
                    'label' => 'Recurrence End Date',
                    'field_id' => 'recurrence_end_date',
                    'type' => 'datetime-local',
                    'required' => false,
                    'validation_rules' => ['date'],
                    'system_field' => true,
                ],
                [
                    'label' => 'Max Occurrences',
                    'field_id' => 'max_occurrences',
                    'type' => 'number',
                    'required' => false,
                    'validation_rules' => ['integer', 'min:1'],
                    'system_field' => true,
                ],
                [
                    'label' => 'Exclusion Dates',
                    'field_id' => 'exclusion_dates',
                    'type' => 'date',
                    'required' => false,
                    'multiple' => true,
                    'system_field' => true,
                ],
                [
                    'label' => 'Image',
                    'field_id' => 'image',
                    'type' => 'file',
                    'required' => false,
                    'validation_rules' => ['.jpeg,.png,.jpg'],
                    'system_field' => true,
                    'accept' => 'image/*',
                ],
                [
                    'label' => 'Timezone',
                    'field_id' => 'timezone',
                    'type' => 'text',
                    'required' => false,
                    'options' => ['UTC'],
                    'validation_rules' => ['max:50'],
                ],
                [
                    'label' => 'Venue Name',
                    'field_id' => 'venue_name',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => ['max:255'],
                ],
                [
                    'label' => 'Address',
                    'field_id' => 'address',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => ['max:500'],
                ],
                [
                    'label' => 'City',
                    'field_id' => 'city',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => ['max:100'],
                ],
                [
                    'label' => 'Country',
                    'field_id' => 'country',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => ['max:100'],
                ],
                [
                    'label' => 'Price',
                    'field_id' => 'price',
                    'type' => 'number',
                    'step' => '0.01',
                    'required' => false,
                    'validation_rules' => ['numeric', 'min:0'],
                ],
            ];

            // Create field templates
            $fieldTemplates = collect($fields)->map(function ($field) {
                // Save each field template
                return FieldTemplate::firstOrCreate(
                    ['field_id' => $field['field_id']],
                    [
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'required' => $field['required'] ?? false,
                        'system_field' => $field['system_field'] ?? false,
                        'validation_rules' => $field['validation_rules'] ?? null,
                        'options' => $field['options'] ?? null,
                    ]
                );
            });

            $fieldList = $fieldTemplates->pluck('id')->toArray();

            // Create default event template
            EventTemplate::firstOrCreate([
                'name' => 'Default Event Template',
                'description' => 'Comprehensive event template with all standard fields',
            ], [
                'fields' => $fieldList,
                'layout' => array_chunk($fieldList, (count($fieldList) / 2)),
            ]);
        });
    }
}
