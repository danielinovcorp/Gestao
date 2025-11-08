<!-- resources/js/Components/ui/data-table/DataTable.vue -->
<script setup lang="ts" generic="TData, TValue">
import type { ColumnDef } from "@tanstack/vue-table";
import {
    FlexRender,
    getCoreRowModel,
    getSortedRowModel,
    getPaginationRowModel,
    useVueTable,
} from "@tanstack/vue-table";
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { computed } from "vue";

interface Props {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    pagination?: any;
}

const props = defineProps<Props>();

const table = useVueTable({
    data: props.data,
    columns: props.columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    manualPagination: true,
    pageCount: props.pagination?.last_page || 1,
});
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead
                        v-for="header in table.getHeaderGroups()[0].headers"
                        :key="header.id"
                        :class="
                            header.column.getCanSort()
                                ? 'cursor-pointer select-none'
                                : ''
                        "
                        @click="header.column.getToggleSortingHandler()"
                    >
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                    <TableCell
                        v-for="cell in row.getVisibleCells()"
                        :key="cell.id"
                    >
                        <FlexRender
                            :render="cell.column.columnDef.cell"
                            :props="cell.getContext()"
                        />
                    </TableCell>
                </TableRow>
                <TableRow v-if="props.data.length === 0">
                    <TableCell
                        :colspan="props.columns.length"
                        class="h-24 text-center"
                    >
                        Sem resultados.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <!-- Paginação -->
    <div
        v-if="props.pagination?.last_page > 1"
        class="flex items-center justify-between mt-4"
    >
        <div class="text-sm text-muted-foreground">
            Mostrando {{ props.pagination.from }} a {{ props.pagination.to }} de
            {{ props.pagination.total }}
        </div>
        <div class="space-x-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!props.pagination.prev_page_url"
                @click="$inertia.visit(props.pagination.prev_page_url)"
            >
                Anterior
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="!props.pagination.next_page_url"
                @click="$inertia.visit(props.pagination.next_page_url)"
            >
                Próximo
            </Button>
        </div>
    </div>
</template>
