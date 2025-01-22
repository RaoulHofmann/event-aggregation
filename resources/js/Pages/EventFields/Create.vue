<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    type: 'text',
    required: false,
    options: [], // For select type
    event_id: null // If needed
});

const showOptionsInput = ref(false);
const optionInput = ref('');

const addOption = () => {
    if (optionInput.value.trim()) {
        form.options.push(optionInput.value.trim());
        optionInput.value = '';
    }
};

const removeOption = (index) => {
    form.options.splice(index, 1);
};

const saveField = () => {
    form.post(route('event-fields.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset(); // Reset the form after a successful save
            showOptionsInput.value = false;
        }
    });
};
</script>

<template>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <form @submit.prevent="saveField" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field Name</label>
                        <input
                            v-model="form.name"
                            placeholder="Enter field name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field Type</label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            @change="showOptionsInput = form.type === 'select'"
                            required
                        >
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                            <option value="select">Select</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.required"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <span class="ml-2">Required Field</span>
                        </label>
                    </div>
                </div>

                <!-- Options input for select type -->
                <div v-if="showOptionsInput" class="space-y-4">
                    <div class="flex gap-2">
                        <input
                            v-model="optionInput"
                            placeholder="Enter option"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <button
                            type="button"
                            @click="addOption"
                            class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                        >
                            Add Option
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="(option, index) in form.options"
                            :key="index"
                            class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
                        >
                            <span>{{ option }}</span>
                            <button
                                type="button"
                                @click="removeOption(index)"
                                class="text-red-500 hover:text-red-700"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors"
                    >
                        Save Field
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
