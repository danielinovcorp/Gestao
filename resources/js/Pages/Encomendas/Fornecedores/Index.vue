<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import DataTable from "@/Components/custom/DataTable.vue";
const title = "Encomendas – Fornecedores";

const props = defineProps<{
    orders: {
        data: Array<{
            id: number;
            numero: string | null;
            fornecedor: string | null;
            total: number;
            estado: string;
            data: string | null;
            origem: number | null;
        }>;
        links: any[];
        meta: any;
    };
}>();

const columns = [
    { accessorKey: "data", header: "Data" },
    { accessorKey: "numero", header: "Número" },
    { accessorKey: "fornecedor", header: "Fornecedor" },
    {
        accessorKey: "total",
        header: "Valor Total",
        cell: ({ row }: any) =>
            new Intl.NumberFormat("pt-PT", {
                style: "currency",
                currency: "EUR",
            }).format(row.original.total),
    },
    { accessorKey: "estado", header: "Estado" },
    { accessorKey: "origem", header: "Encomenda Cliente (Origem)" },
];
</script>

<template>
    <Head :title="title" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <div class="p-6">
            <div
                v-if="!props.orders.data.length"
                class="rounded-xl border bg-white p-8 text-center text-slate-500"
            >
                Sem encomendas de fornecedor.
            </div>
            <div v-else class="rounded-xl border bg-white p-4">
                <DataTable :columns="columns" :data="props.orders.data" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
