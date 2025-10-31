<script setup lang="ts">
import { h } from "vue";

interface Column {
    accessorKey?: string;
    header: string;
    id?: string;
    // opcional: função de render (recebe { row: { original } })
    cell?: (ctx: { row: { original: any } }) => any;
}

const props = defineProps<{
    columns: Column[];
    data: any[];
}>();

function renderCell(col: Column, row: any) {
    if (typeof col.cell === "function") {
        // devolve um VNode criado com h() lá no columns.ts
        return col.cell({ row: { original: row } });
    }
    if (col.accessorKey) return row[col.accessorKey as keyof typeof row];
    return "";
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th
                        v-for="col in props.columns"
                        :key="col.id || col.accessorKey || col.header"
                        class="px-3 py-2"
                    >
                        {{ col.header }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in props.data" :key="i" class="border-t">
                    <td
                        v-for="col in props.columns"
                        :key="
                            (col.id || col.accessorKey || col.header) + '-' + i
                        "
                        class="px-3 py-2 align-top"
                    >
                        <!-- se a coluna tem cell() (VNode), renderiza; senão, texto -->
                        <component
                            v-if="typeof col.cell === 'function'"
                            :is="renderCell(col, row)"
                        />
                        <span v-else>{{ renderCell(col, row) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
