<script setup>
import { computed, ref } from 'vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    data: {
        type: Array,
        required: true
    },
    columns: {
        type: Array,
        required: true
    },
    paginate: {
        type: Boolean,
        default: false
    },
    rowsPerPage: {
        type: Number,
        default: 10
    }
})

const currentPage = ref(1)

const totalPages = computed(() => {
    return Math.ceil(props.data.length / props.rowsPerPage)
})

const onPageChange = (page) => {
    currentPage.value = page
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="table-auto w-full">
            <thead>
            <tr>
                <th v-for="col in columns" :key="col.field" class="px-4 py-2 bg-gray-200 text-left">
                    {{ col.header }}
                </th>
                <th v-if="$slots['actions']" class="w-28 bg-gray-200 text-center">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="row in data" :key="row.id" class="border-b">
                <td v-for="col in columns" :key="col.field" class="px-4 py-2">
                    <template v-if="col.format === 'date'">
                        {{ formatDate(row[col.field]) }}
                    </template>
                    <template v-else>
                        {{ row[col.field] }}
                    </template>
                </td>
                <td>
                    <slot name="actions" :row="row" />
                </td>
            </tr>
            </tbody>
        </table>
        <div v-if="paginate" class="flex justify-end mt-4">
            <Pagination
                :current-page="currentPage"
                :total-pages="totalPages"
                @page-change="onPageChange"
            />
        </div>
    </div>
</template>
