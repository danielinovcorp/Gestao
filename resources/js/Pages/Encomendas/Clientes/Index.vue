<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import DataTable from "@/Components/custom/DataTable.vue"; // usa o teu wrapper
import { columns } from "./columns"; // ver abaixo
const title = "Encomendas – Cliente";

const props = defineProps<{
    orders: {
        data: Array<{
            id: number;
            data: string | null;
            numero: string | null;
            validade: string | null;
            cliente: string | null;
            total: number;
            estado: "rascunho" | "fechado";
        }>;
        links: any[];
        meta: any;
    };
}>();

function fechar(id: number) {
    router.patch(route("encomendas.clientes.close", id));
}
function converter(id: number) {
    router.post(route("encomendas.clientes.convert", id));
}
</script>

<template>
    <Head :title="title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ title }}
                </h2>
                <Link :href="route('encomendas.clientes.create')"
                    ><Button>Nova Encomenda</Button></Link
                >
            </div>
        </template>

        <div class="p-6">
            <div
                v-if="!props.orders.data.length"
                class="rounded-xl border bg-white p-8 text-center text-slate-500"
            >
                Sem encomendas ainda.
            </div>
            <div v-else class="rounded-xl border bg-white p-4">
                <DataTable
                    :columns="columns({ fechar, converter })"
                    :data="props.orders.data"
                />
                <!-- se teu DataTable não faz paginação automática, renderiza os links aqui -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>
