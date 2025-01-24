<script setup>
import { ref } from 'vue'
import NavLink from '@/Components/NavLink.vue'

const isOpen = ref(false)
</script>

<template>
    <div class="inline-flex justify-center">
        <button
            @click="isOpen = !isOpen"
            class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-300 focus:border-gray-300 dark:focus:border-gray-700 transition duration-150 ease-in-out"
        >
            Templates
            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="isOpen"
                @click.outside="isOpen = false"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
            >
                <div class="rounded-md ring-1 ring-black ring-opacity-5 py-1 bg-white">
                    <NavLink
                        :href="route('field-templates.index')"
                        :active="route().current('field-templates')"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                        Field Templates
                    </NavLink>
                    <NavLink
                        :href="route('event-templates.index')"
                        :active="route().current('event-templates')"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                        Event Templates
                    </NavLink>
                </div>
            </div>
        </transition>
    </div>
</template>
