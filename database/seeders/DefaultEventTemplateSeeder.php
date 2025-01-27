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
                    'validation_rules' => ['max:255'],
                ],
                [
                    'label' => 'Description',
                    'field_id' => 'description',
                    'type' => 'textarea',
                    'required' => false,
                    'validation_rules' => ['max:1000'],
                ],
                [
                    'label' => 'Type',
                    'field_id' => 'type',
                    'type' => 'select',
                    'required' => false,
                    'options' => ['conference', 'workshop', 'concert'], // Use array for options
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
                    'type' => 'datetime',
                    'required' => true,
                ],
                [
                    'label' => 'End Date',
                    'field_id' => 'end_date',
                    'type' => 'datetime',
                    'required' => false,
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
                    'label' => 'Is Virtual',
                    'field_id' => 'is_virtual',
                    'type' => 'boolean',
                    'required' => false,
                ],
                [
                    'label' => 'Virtual URL',
                    'field_id' => 'virtual_url',
                    'type' => 'url',
                    'required' => false,
                    'validation_rules' => ['url', 'max:255'],
                ],
                [
                    'label' => 'Capacity',
                    'field_id' => 'capacity',
                    'type' => 'number',
                    'required' => false,
                    'validation_rules' => ['integer', 'min:0'],
                ],
                [
                    'label' => 'Registration Required',
                    'field_id' => 'registration_required',
                    'type' => 'boolean',
                    'required' => false,
                ],
                [
                    'label' => 'Registration Deadline',
                    'field_id' => 'registration_deadline',
                    'type' => 'datetime',
                    'required' => false,
                ],
                [
                    'label' => 'Price',
                    'field_id' => 'price',
                    'type' => 'decimal',
                    'required' => false,
                    'validation_rules' => ['numeric', 'min:0'],
                ],
                [
                    'label' => 'Organizer Name',
                    'field_id' => 'organizer_name',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => ['max:255'],
                ],
                [
                    'label' => 'Organizer Email',
                    'field_id' => 'organizer_email',
                    'type' => 'email',
                    'required' => false,
                    'validation_rules' => ['email', 'max:255'],
                ],
                [
                    'label' => 'Is Recurring',
                    'field_id' => 'is_recurring',
                    'type' => 'boolean',
                    'required' => false,
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
