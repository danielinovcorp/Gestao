<!-- resources/js/Components/custom/DataTable.vue -->
<script setup lang="ts">
import { h } from "vue";

interface Column {
    accessorKey?: string;
    header: string;
    id?: string;
    cell?: (ctx: { row: { original: any } }) => any;
    meta?: Record<string, any>;
}

const props = defineProps<{
    columns: Column[];
    data: any[];
}>();

function renderCell(col: Column, row: any) {
    if (typeof col.cell === "function") {
        return col.cell({ row: { original: row } });
    }
    if (col.accessorKey) {
        const keys = col.accessorKey.split(".");
        let value = row;
        for (const key of keys) {
            value = value?.[key];
        }
        return value ?? "—";
    }
    return "";
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-slate-600 bg-slate-50">
                    <th
                        v-for="col in props.columns"
                        :key="col.id || col.accessorKey || col.header"
                        class="px-3 py-2 font-medium"
                        :class="col.meta?.class"
                    >
                        {{ col.header }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, i) in props.data"
                    :key="i"
                    class="border-t hover:bg-slate-50"
                >
                    <td
                        v-for="col in props.columns"
                        :key="
                            (col.id || col.accessorKey || col.header) + '-' + i
                        "
                        class="px-3 py-2 align-top"
                        :class="col.meta?.class"
                    >
                        <component
                            :is="renderCell(col, row)"
                            v-if="typeof col.cell === 'function'"
                        />
                        <span v-else>{{ renderCell(col, row) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
