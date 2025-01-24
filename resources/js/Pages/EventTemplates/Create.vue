<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    field_configurations: []
});

const availableFields = ref([]);
const selectedFields = ref([]);

onMounted(async () => {
    // Fetch available field templates
    const response = await axios.get(route('event-templates.create'));
    availableFields.value = response.data;
});

console.log(availableFields)

const toggleFieldSelection = (field) => {
    const index = selectedFields.value.findIndex(f => f.id === field.id);
    if (index > -1) {
        selectedFields.value.splice(index, 1);
    } else {
        selectedFields.value.push(field);
    }
};

const saveEventTemplate = () => {
    form.field_configurations = selectedFields.value.map(field => field.id);

    form.post(route('event-templates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedFields.value = [];
        }
    });
};
</script>

<template>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <form @submit.prevent="saveEventTemplate" class="space-y-6">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Template Name</label>
                        <input
                            v-model="form.name"
                            placeholder="Enter template name"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            v-model="form.description"
                            placeholder="Enter template description"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        ></textarea>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium mb-4">Select Fields</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div
                            v-for="field in availableFields"
                            :key="field.id"
                            @click="toggleFieldSelection(field)"
                            class="p-4 border rounded-lg cursor-pointer"
                            :class="{
                                'bg-blue-100': selectedFields.some(f => f.id === field.id)
                            }"
                        >
                            <div class="flex justify-between items-center">
                                <span>{{ field.name }}</span>
                                <span class="text-sm text-gray-500">{{ field.type }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md"
                >
                    Save Event Template
                </button>
            </form>
        </div>
    </div>
</template>
