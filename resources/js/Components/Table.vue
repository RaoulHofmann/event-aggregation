<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
const props = defineProps({
        data: { type: Array, required: true },
        columns: { type: Array, required: true },
        paginate: Boolean,
        rowsPerPage: { type: Number, default: 10 },
    }),
    currentPage = ref(1);
const totalPages = computed(() => Math.max(1, Math.ceil(props.data.length / props.rowsPerPage))),
    rows = computed(() =>
        props.paginate
            ? props.data.slice(
                  (currentPage.value - 1) * props.rowsPerPage,
                  currentPage.value * props.rowsPerPage,
              )
            : props.data,
    );
watch(
    () => props.data,
    () => (currentPage.value = Math.min(currentPage.value, totalPages.value)),
);
const display = (v, f) => {
    if (v == null || v === '') return '—';
    if (['date', 'datetime'].includes(f)) {
        const d = new Date(v);
        if (!Number.isNaN(d.getTime())) return f === 'datetime' ? d.toLocaleString() : d.toLocaleDateString();
    }
    return typeof v === 'boolean'
        ? v
            ? 'Yes'
            : 'No'
        : typeof v === 'object'
          ? Object.values(v).join(', ')
          : v;
};
</script>
<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th
                        v-for="c in columns"
                        :key="c.field"
                        class="whitespace-nowrap px-6 py-3.5 text-xs font-medium text-slate-500"
                    >
                        {{ c.header }}
                    </th>
                    <th v-if="$slots.actions" class="px-6 py-3 text-right text-xs font-medium text-slate-500">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in rows"
                    :key="row.id"
                    class="border-b border-slate-100 last:border-0 hover:bg-slate-50"
                >
                    <td v-for="c in columns" :key="c.field" class="max-w-xs px-6 py-4">
                        <span class="line-clamp-2 break-words">{{ display(row[c.field], c.format) }}</span>
                    </td>
                    <td v-if="$slots.actions" class="w-24 px-4 py-2"><slot name="actions" :row="row" /></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div
        v-if="paginate && data.length"
        class="flex items-center justify-between border-t border-slate-100 px-6 py-4 text-xs text-slate-500"
    >
        <span
            >{{ (currentPage - 1) * rowsPerPage + 1 }}–{{
                Math.min(currentPage * rowsPerPage, data.length)
            }}
            of {{ data.length }} events</span
        >
        <div class="flex items-center gap-3">
            <button
                aria-label="Previous page"
                class="rounded border p-1.5 disabled:opacity-30"
                :disabled="currentPage === 1"
                @click="currentPage--"
            >
                <ChevronLeft class="size-4" /></button
            >{{ currentPage }} / {{ totalPages
            }}<button
                aria-label="Next page"
                class="rounded border p-1.5 disabled:opacity-30"
                :disabled="currentPage >= totalPages"
                @click="currentPage++"
            >
                <ChevronRight class="size-4" />
            </button>
        </div>
    </div>
</template>
