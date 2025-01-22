<script setup>
import {useForm} from '@inertiajs/vue3';
import {ref} from "vue";

const props = defineProps({
    customFields: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    timezone: '',
    venue_name: '',
    address: '',
    city: '',
    country: '',
    latitude: '',
    longitude: '',
    is_virtual: false,
    virtual_url: '',
    capacity: null,
    registration_required: false,
    registration_deadline: '',
    price: null,
    organizer_id: '',
    organizer_name: '',
    organizer_email: '',
    custom_fields: {},
    featured_image: '',
    status: 'draft',
    type: ''
});

const formFields = Object.keys(form.data());
const activeTab = ref('location');

const submit = () => {
    form.post(route('events.store'));
};
</script>

<template>
    <div class="max-w-7xl mx-auto p-6 rounded-lg shadow">
        <form @submit.prevent="submit">
            <!-- Tab Navigation -->
            <div class="mb-6">
                <button @click="activeTab = 'basic'" :class="{'bg-blue-600': activeTab === 'basic'}"
                        class="px-6 py-2 rounded-md text-white">
                    Basic Information
                </button>
                <button @click="activeTab = 'datetime'" :class="{'bg-blue-600': activeTab === 'datetime'}"
                        class="px-6 py-2 rounded-md text-white">
                    Date & Time
                </button>
                <button @click="activeTab = 'location'" :class="{'bg-blue-600': activeTab === 'location'}"
                        class="px-6 py-2 rounded-md text-white">
                    Location
                </button>
                <button @click="activeTab = 'details'" :class="{'bg-blue-600': activeTab === 'details'}"
                        class="px-6 py-2 rounded-md text-white">
                    Event Details
                </button>
                <button @click="activeTab = 'custom'" :class="{'bg-blue-600': activeTab === 'custom'}"
                        class="px-6 py-2 rounded-md text-white">
                    Additional Information
                </button>
            </div>

            <!-- Basic Information Tab -->
            <div v-if="activeTab === 'basic'" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Basic Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" v-model="form.name"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea v-model="form.description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Date & Time Tab -->
            <div v-if="activeTab === 'datetime'" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Date & Time</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="datetime-local" v-model="form.start_date"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="datetime-local" v-model="form.end_date"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Location Tab -->
            <div v-if="activeTab === 'location'" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Location</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Venue Name</label>
                        <input type="text" v-model="form.venue_name"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" v-model="form.address"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" v-model="form.city"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" v-model="form.country"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Latitude</label>
                        <input type="text" v-model="form.latitude"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Longitude</label>
                        <input type="text" v-model="form.longitude"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Virtual URL -->
                <div v-if="form.is_virtual" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Virtual URL</label>
                        <input type="url" v-model="form.virtual_url"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Event Details Tab -->
            <div v-if="activeTab === 'details'" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Event Details</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select v-model="form.type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="conference">Conference</option>
                            <option value="workshop">Workshop</option>
                            <option value="concert">Concert</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Capacity</label>
                        <input type="number" v-model="form.capacity"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" v-model="form.price"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Custom Fields Tab -->
            <div v-if="activeTab === 'custom' && customFields.length" class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800">Additional Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="field in customFields" :key="field.id">
                        <label class="block text-sm font-medium text-gray-700">{{ field.name }}</label>
                        <select v-if="field.type === 'select'" :name="field.name" :id="field.id">
                            <option v-for="option in field.options.split(',') ?? []" :value="option">{{
                                    option
                                }}
                            </option>
                        </select>
                        <input v-else :type="field.type"
                               v-model="form.custom_fields[field.id]"
                               :required="field.required"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md shadow-sm transition-colors">
                    Create Event
                </button>
            </div>
        </form>
    </div>
</template>
