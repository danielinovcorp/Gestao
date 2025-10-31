<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
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
import NewFornecedorFaturaModal from "@/Pages/Financeiro/FaturasFornecedor/NewFornecedorFaturaModal.vue";

const props = defineProps<{
    faturas: { data: any[]; links?: any[]; meta?: any } | any;
    fornecedores: { id: number; nome: string }[];
    filters: { estado?: string; fornecedor?: string };
}>();

const estado = ref(props.filters.estado ?? "");
const fornecedor = ref(props.filters.fornecedor ?? "");

const openCreate = ref(false);

function applyFilters() {
    router.get(
        route("financeiro.faturas-fornecedor.index"),
        {
            estado: estado.value || undefined,
            fornecedor: fornecedor.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    estado.value = "";
    fornecedor.value = "";
    applyFilters();
}
</script>

<template>
    <Head title="Faturas Fornecedores" />

    <AuthenticatedLayout>
        <!-- ✅ usar o header do layout para eliminar a faixa branca e padronizar -->
        <template #header>
            <h1 class="text-2xl font-semibold leading-tight text-gray-800">
                Faturas Fornecedores
            </h1>
        </template>

        <!-- conteúdo padrão com as mesmas margens das outras páginas -->
        <div class="px-6 md:px-8 py-6 space-y-6">
            <!-- CARD DE FILTROS + AÇÕES (mesmo look das outras páginas) -->
            <div
                class="rounded-2xl border bg-white p-3 md:p-4 shadow-sm flex flex-col gap-3 md:gap-0 md:flex-row md:items-center md:justify-between"
            >
                <div
                    class="w-full md:w-auto md:flex-1 grid grid-cols-1 md:grid-cols-2 gap-3"
                >
                    <div>
                        <Select v-model="estado">
                            <SelectTrigger class="h-11">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pendente"
                                    >Pendente</SelectItem
                                >
                                <SelectItem value="paga">Paga</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Select v-model="fornecedor">
                            <SelectTrigger class="h-11">
                                <SelectValue placeholder="Fornecedor" />
                            </SelectTrigger>
                            <SelectContent class="max-h-72">
                                <SelectItem
                                    v-for="f in fornecedores"
                                    :key="f.id"
                                    :value="String(f.id)"
                                >
                                    {{ f.nome }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
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
                        >Nova Fatura</Button
                    >
                </div>
            </div>

            <!-- TABELA -->
            <div class="overflow-x-auto rounded-2xl border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="text-slate-500">Data</TableHead>
                            <TableHead class="text-slate-500">Número</TableHead>
                            <TableHead class="text-slate-500"
                                >Fornecedor</TableHead
                            >
                            <TableHead class="text-slate-500"
                                >Encomenda</TableHead
                            >
                            <TableHead class="text-slate-500"
                                >Documento</TableHead
                            >
                            <TableHead class="text-right text-slate-500"
                                >Valor Total</TableHead
                            >
                            <TableHead class="text-slate-500">Estado</TableHead>
                            <TableHead class="w-40 text-slate-500"
                                >Ações</TableHead
                            >
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-if="(faturas.data ?? faturas).length === 0"
                            class="hover:bg-transparent"
                        >
                            <TableCell
                                colspan="8"
                                class="py-12 text-center text-slate-500"
                            >
                                Sem resultados
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="row in faturas.data ?? faturas"
                            :key="row.id"
                        >
                            <TableCell>{{ row.data_fatura }}</TableCell>
                            <TableCell>{{ row.numero }}</TableCell>
                            <TableCell>{{ row.fornecedor }}</TableCell>
                            <TableCell>{{ row.encomenda ?? "-" }}</TableCell>
                            <TableCell>
                                <a
                                    v-if="row.documento_url"
                                    :href="row.documento_url"
                                    class="text-indigo-600 hover:underline"
                                    target="_blank"
                                    >Abrir</a
                                >
                                <span v-else>-</span>
                            </TableCell>
                            <TableCell class="text-right">{{
                                row.valor_total
                            }}</TableCell>
                            <TableCell>
                                <span
                                    :class="
                                        row.estado === 'paga'
                                            ? 'text-green-700 bg-green-100 px-2 py-0.5 rounded-full text-xs'
                                            : 'text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full text-xs'
                                    "
                                >
                                    {{
                                        row.estado === "paga"
                                            ? "Paga"
                                            : "Pendente"
                                    }}
                                </span>
                            </TableCell>
                            <TableCell class="space-x-2">
                                <Link
                                    :href="
                                        route(
                                            'financeiro.faturas-fornecedor.edit',
                                            row.id,
                                        )
                                    "
                                >
                                    <Button size="sm" variant="outline"
                                        >Editar</Button
                                    >
                                </Link>
                                <Link
                                    as="button"
                                    method="delete"
                                    :href="
                                        route(
                                            'financeiro.faturas-fornecedor.destroy',
                                            row.id,
                                        )
                                    "
                                >
                                    <Button size="sm" variant="destructive"
                                        >Apagar</Button
                                    >
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- MODAL: Nova Fatura -->
        <NewFornecedorFaturaModal
            :open="openCreate"
            :fornecedores="fornecedores"
            @close="openCreate = false"
            @created="
                () => {
                    openCreate = false;
                    router.reload({ only: ['faturas'] });
                }
            "
        />
    </AuthenticatedLayout>
</template>
