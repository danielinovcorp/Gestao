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
import NewContaModal from "./NewContaModal.vue";

const props = defineProps<{
    contas: { data: any[] } | any;
    filters: { ativo?: string };
}>();

const ativo = ref(props.filters.ativo ?? "");
const openCreate = ref(false);

function applyFilters() {
    router.get(
        route("financeiro.contas-bancarias"),
        { ativo: ativo.value || undefined },
        { preserveState: true, replace: true },
    );
}
function clearFilters() {
    ativo.value = "";
    applyFilters();
}
</script>

<template>
    <Head title="Contas Bancárias" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-semibold leading-tight text-gray-800">
                Contas Bancárias
            </h1>
        </template>

        <div class="px-6 md:px-8 py-6 space-y-6">
            <div
                class="rounded-2xl border bg-white p-3 md:p-4 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3"
            >
                <div class="w-full md:w-auto md:flex-1">
                    <Select v-model="ativo">
                        <SelectTrigger class="h-11">
                            <SelectValue placeholder="Estado (Ativa/Inativa)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">Ativas</SelectItem>
                            <SelectItem value="0">Inativas</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center gap-2 self-end md:self-auto">
                    <Button
                        variant="secondary"
                        class="h-10"
                        @click="clearFilters"
                        >Limpar</Button
                    >
                    <Button class="h-10" @click="applyFilters">Filtrar</Button>
                    <Button class="h-10 font-medium" @click="openCreate = true"
                        >Nova Conta</Button
                    >
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="text-slate-500">Banco</TableHead>
                            <TableHead class="text-slate-500"
                                >Titular</TableHead
                            >
                            <TableHead class="text-slate-500">IBAN</TableHead>
                            <TableHead class="text-slate-500"
                                >SWIFT/BIC</TableHead
                            >
                            <TableHead class="text-right text-slate-500"
                                >Saldo Abertura</TableHead
                            >
                            <TableHead class="text-slate-500">Estado</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-if="(contas.data ?? contas).length === 0"
                            class="hover:bg-transparent"
                        >
                            <TableCell
                                colspan="6"
                                class="py-12 text-center text-slate-500"
                                >Sem resultados</TableCell
                            >
                        </TableRow>
                        <TableRow
                            v-for="c in contas.data ?? contas"
                            :key="c.id"
                        >
                            <TableCell>{{ c.banco ?? "-" }}</TableCell>
                            <TableCell>{{ c.titular ?? "-" }}</TableCell>
                            <TableCell class="font-mono text-sm">{{
                                c.iban
                            }}</TableCell>
                            <TableCell class="font-mono text-sm">{{
                                c.swift_bic ?? "-"
                            }}</TableCell>
                            <TableCell class="text-right">{{
                                c.saldo_abertura
                            }}</TableCell>
                            <TableCell>
                                <span
                                    :class="
                                        c.ativo
                                            ? 'text-green-700 bg-green-100 px-2 py-0.5 rounded-full text-xs'
                                            : 'text-slate-700 bg-slate-200 px-2 py-0.5 rounded-full text-xs'
                                    "
                                >
                                    {{ c.ativo ? "Ativa" : "Inativa" }}
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <NewContaModal
            :open="openCreate"
            @close="openCreate = false"
            @created="
                () => {
                    openCreate = false;
                    router.reload({ only: ['contas'] });
                }
            "
        />
    </AuthenticatedLayout>
</template>
