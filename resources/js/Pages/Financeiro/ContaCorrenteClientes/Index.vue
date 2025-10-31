<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from "@/components/ui/table";
import NewMovimentoModal from "./NewMovimentoModal.vue";

const props = defineProps<{
    movimentos: { data: any[] } | any;
    clientes: { id: number; nome: string }[];
    filters: { cliente?: string };
    saldo: string;
}>();

const cliente = ref(props.filters.cliente ?? "");
const openCreate = ref(false);

function applyFilters() {
    router.get(
        route("financeiro.conta-corrente-clientes"),
        { cliente: cliente.value || undefined },
        { preserveState: true, replace: true },
    );
}
function clearFilters() {
    cliente.value = "";
    applyFilters();
}
</script>

<template>
    <Head title="Conta Corrente Clientes" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-semibold leading-tight text-gray-800">
                Conta Corrente Clientes
            </h1>
        </template>

        <div class="px-6 md:px-8 py-6 space-y-6">
            <div
                class="rounded-2xl border bg-white p-3 md:p-4 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3"
            >
                <div class="w-full md:w-auto md:flex-1">
                    <Select v-model="cliente">
                        <SelectTrigger class="h-11">
                            <SelectValue placeholder="Cliente" />
                        </SelectTrigger>
                        <SelectContent class="max-h-72">
                            <SelectItem
                                v-for="c in clientes"
                                :key="c.id"
                                :value="String(c.id)"
                                >{{ c.nome }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center gap-2 self-end md:self-auto">
                    <div class="mr-4 text-sm text-slate-600">
                        Saldo:
                        <span
                            class="font-medium"
                            :class="
                                Number(
                                    props.saldo
                                        .replace('.', '')
                                        .replace(',', '.'),
                                ) >= 0
                                    ? 'text-green-700'
                                    : 'text-red-700'
                            "
                            >{{ saldo }}</span
                        >
                    </div>
                    <Button
                        variant="secondary"
                        class="h-10"
                        @click="clearFilters"
                        >Limpar</Button
                    >
                    <Button class="h-10" @click="applyFilters">Filtrar</Button>
                    <Button class="h-10 font-medium" @click="openCreate = true"
                        >Novo Lançamento</Button
                    >
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="text-slate-500">Data</TableHead>
                            <TableHead class="text-slate-500"
                                >Cliente</TableHead
                            >
                            <TableHead class="text-slate-500"
                                >Documento</TableHead
                            >
                            <TableHead class="text-slate-500"
                                >Descrição</TableHead
                            >
                            <TableHead class="text-right text-slate-500"
                                >Débito</TableHead
                            >
                            <TableHead class="text-right text-slate-500"
                                >Crédito</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-if="(movimentos.data ?? movimentos).length === 0"
                            class="hover:bg-transparent"
                        >
                            <TableCell
                                colspan="6"
                                class="py-12 text-center text-slate-500"
                                >Sem resultados</TableCell
                            >
                        </TableRow>
                        <TableRow
                            v-for="m in movimentos.data ?? movimentos"
                            :key="m.id"
                        >
                            <TableCell>{{ m.data }}</TableCell>
                            <TableCell>{{ m.cliente }}</TableCell>
                            <TableCell>{{ m.doc || "-" }}</TableCell>
                            <TableCell>{{ m.descricao || "-" }}</TableCell>
                            <TableCell class="text-right">{{
                                m.debito
                            }}</TableCell>
                            <TableCell class="text-right">{{
                                m.credito
                            }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <NewMovimentoModal
            :open="openCreate"
            :clientes="clientes"
            @close="openCreate = false"
            @created="
                () => {
                    openCreate = false;
                    router.reload({ only: ['movimentos', 'saldo'] });
                }
            "
        />
    </AuthenticatedLayout>
</template>
