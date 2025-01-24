<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventTemplate;
use App\Models\FieldTemplate;
use Illuminate\Support\Facades\DB;

class DefaultEventTemplateSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Create field templates based on original schema
            $fields = [
                [
                    'label' => 'Name',
                    'fieldId' =>'name',
                    'type' => 'text',
                    'required' => true,
                    'validation_rules' => json_encode(['max:255'])
                ],
                [
                    'label' => 'Description',
                    'fieldId' =>'description',
                    'type' => 'textarea',
                    'required' => false,
                    'validation_rules' => json_encode(['max:1000'])
                ],
                [
                    'label' => 'Type',
                    'fieldId' =>'type',
                    'type' => 'select',
                    'required' => false,
                    'options' => json_encode(['conference', 'workshop', 'concert']),
                    'validation_rules' => json_encode(['in:conference,workshop,concert'])
                ],
                [
                    'label' => 'Status',
                    'fieldId' =>'status',
                    'type' => 'select',
                    'required' => true,
                    'options' => json_encode(['draft', 'published', 'cancelled']),
                    'validation_rules' => json_encode(['in:draft,published,cancelled'])
                ],
                [
                    'label' => 'Start Date',
                    'fieldId' =>'start_date',
                    'type' => 'datetime',
                    'required' => true
                ],
                [
                    'label' => 'End Date',
                    'fieldId' =>'end_date',
                    'type' => 'datetime',
                    'required' => false
                ],
                [
                    'label' => 'Timezone',
                    'fieldId' =>'timezone',
                    'type' => 'text',
                    'required' => false,
                    'options' => json_encode(['UTC']),
                    'validation_rules' => json_encode(['max:50'])
                ],
                [
                    'label' => 'Venue Name',
                    'fieldId' =>'venue_name',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => json_encode(['max:255'])
                ],
                [
                    'label' => 'Address',
                    'fieldId' =>'address',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => json_encode(['max:500'])
                ],
                [
                    'label' => 'City',
                    'fieldId' =>'city',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => json_encode(['max:100'])
                ],
                [
                    'label' => 'Country',
                    'fieldId' =>'country',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => json_encode(['max:100'])
                ],
                [
                    'label' => 'Is Virtual',
                    'fieldId' =>'is_virtual',
                    'type' => 'boolean',
                    'required' => false
                ],
                [
                    'label' => 'Virtual URL',
                    'fieldId' =>'virtual_url',
                    'type' => 'url',
                    'required' => false,
                    'validation_rules' => json_encode(['url', 'max:255'])
                ],
                [
                    'label' => 'Capacity',
                    'fieldId' =>'capacity',
                    'type' => 'number',
                    'required' => false,
                    'validation_rules' => json_encode(['integer', 'min:0'])
                ],
                [
                    'label' => 'Registration Required',
                    'fieldId' =>'registration_required',
                    'type' => 'boolean',
                    'required' => false
                ],
                [
                    'label' => 'Registration Deadline',
                    'fieldId' =>'registration_deadline',
                    'type' => 'datetime',
                    'required' => false
                ],
                [
                    'label' => 'Price',
                    'fieldId' =>'price',
                    'type' => 'decimal',
                    'required' => false,
                    'validation_rules' => json_encode(['numeric', 'min:0'])
                ],
                [
                    'label' => 'Organizer Name',
                    'fieldId' =>'organizer_name',
                    'type' => 'text',
                    'required' => false,
                    'validation_rules' => json_encode(['max:255'])
                ],
                [
                    'label' => 'Organizer Email',
                    'fieldId' =>'organizer_email',
                    'type' => 'email',
                    'required' => false,
                    'validation_rules' => json_encode(['email', 'max:255'])
                ],
                [
                    'label' => 'Is Recurring',
                    'fieldId' =>'is_recurring',
                    'type' => 'boolean',
                    'required' => false
                ]
            ];

            // Create field templates
            $fieldTemplates = collect($fields)->map(function ($field) {
                return FieldTemplate::firstOrCreate(
                    ['fieldId' => $field['fieldId']],
                    $field
                );
            });

            // Create default event template
            EventTemplate::firstOrCreate([
                'name' =>'Default Event Template',
                'description' => 'Comprehensive event template with all standard fields'
            ], [
                'field_configurations' => $fieldTemplates->pluck('id')->toArray()
            ]);
        });
    }
}
