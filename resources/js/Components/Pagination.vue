<script setup>
const props = defineProps({
    currentPage: {
        type: Number,
        required: true
    },
    totalPages: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['page-change'])

const onPageChange = (page) => {
    if (page >= 1 && page <= props.totalPages) {
        emit('page-change', page)
    }
}
</script>

<template>
    <div class="flex items-center space-x-2">
        <button
            @click="onPageChange(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 disabled:opacity-50"
        >
            Previous
        </button>
        <div v-for="page in totalPages" :key="page" class="px-4 py-2 rounded-md" :class="{
      'bg-blue-500 text-white': currentPage === page,
      'bg-gray-200 hover:bg-gray-300': currentPage !== page
    }" @click="onPageChange(page)">
            {{ page }}
        </div>
        <button
            @click="onPageChange(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 disabled:opacity-50"
        >
            Next
        </button>
    </div>
</template>
