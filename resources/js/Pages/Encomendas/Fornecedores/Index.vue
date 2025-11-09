<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import DataTable from "@/Components/custom/DataTable.vue";
import { columns } from "./columns";

const title = "Encomendas – Fornecedores";

const props = defineProps<{
    orders: {
        data: Array<{
            id: number;
            numero: string | null;
            data: string;
            fornecedor: { nome: string };
            origem_numero: string | null;
            total: number;
            estado: "rascunho" | "pendente" | "fechado" | "paga";
        }>;
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        estado?: string;
    };
}>();

// Filtros
const search = ref(props.filters.search || "");
const estado = ref(props.filters.estado || "");

function aplicarFiltros() {
    router.get(
        route("encomendas.fornecedores.index"),
        {
            search: search.value || null,
            estado: estado.value || null,
        },
        { preserveState: true, replace: true },
    );
}

function limparFiltros() {
    search.value = "";
    estado.value = "";
    aplicarFiltros();
}

// AÇÕES (COM CONFIRMAÇÃO)
function fechar(id: number) {
    if (!confirm("Tem certeza que deseja fechar esta encomenda?")) return;

    router.patch(
        route("encomendas.fornecedores.close", id),
        {},
        {
            preserveState: true,
            replace: true,
            onSuccess: () => {
                alert("Encomenda fechada com sucesso!");
            },
            onError: (errors) => {
                console.error(errors);
                alert("Erro ao fechar a encomenda.");
            },
        },
    );
}

function marcarPaga(id: number) {
    if (!confirm("Marcar esta encomenda como paga?")) return;

    router.patch(
        route("encomendas.fornecedores.markPaid", id),
        {},
        {
            preserveState: true,
            replace: true,
            onSuccess: () => {
                alert("Encomenda marcada como paga!");
            },
        },
    );
}

function downloadPdf(id: number) {
    window.open(route("encomendas.fornecedores.pdf", id), "_blank");
}
</script>

<template>
    <Head :title="title" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Encomendas – Fornecedores
            </h2>
        </template>

        <div class="p-6 space-y-6">
            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <Input
                    v-model="search"
                    placeholder="Pesquisar por número ou fornecedor..."
                    class="w-full sm:w-80"
                    @keyup.enter="aplicarFiltros"
                />
                <Select v-model="estado">
                    <SelectTrigger class="w-full sm:w-44">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Todos</SelectItem>
                        <SelectItem value="rascunho">Rascunho</SelectItem>
                        <SelectItem value="pendente">Pendente</SelectItem>
                        <SelectItem value="fechado">Fechado</SelectItem>
                        <SelectItem value="paga">Paga</SelectItem>
                    </SelectContent>
                </Select>
                <Button @click="aplicarFiltros" class="w-full sm:w-auto"
                    >Filtrar</Button
                >
                <Button
                    variant="secondary"
                    @click="limparFiltros"
                    class="w-full sm:w-auto"
                >
                    Limpar
                </Button>
            </div>

            <!-- Lista de Encomendas (com botões nativos) -->
            <div
                v-if="!props.orders.data.length"
                class="rounded-xl border bg-white p-12 text-center"
            >
                <p class="text-slate-500">
                    Nenhuma encomenda de fornecedor encontrada.
                </p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="order in props.orders.data"
                    :key="order.id"
                    class="rounded-lg border bg-white p-4 hover:shadow-sm transition-shadow"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <!-- Informações -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-lg">
                                    {{ order.numero || "Rascunho" }}
                                </span>
                                <Badge
                                    :class="{
                                        'bg-yellow-100 text-yellow-800':
                                            order.estado === 'rascunho',
                                        'bg-orange-100 text-orange-800':
                                            order.estado === 'pendente',
                                        'bg-green-100 text-green-800':
                                            order.estado === 'fechado',
                                        'bg-blue-100 text-blue-800':
                                            order.estado === 'paga',
                                    }"
                                    class="text-xs"
                                >
                                    {{
                                        order.estado === "rascunho"
                                            ? "Rascunho"
                                            : order.estado === "pendente"
                                              ? "Pendente"
                                              : order.estado === "fechado"
                                                ? "Fechado"
                                                : "Paga"
                                    }}
                                </Badge>
                            </div>
                            <p class="text-sm text-slate-600 mt-1">
                                <strong>Fornecedor:</strong>
                                {{ order.fornecedor.nome }}
                            </p>
                            <p class="text-sm text-slate-600">
                                <strong>Origem:</strong>
                                {{
                                    order.origem_numero
                                        ? `EC-${order.origem_numero}`
                                        : "—"
                                }}
                            </p>
                            <p class="text-sm text-slate-600">
                                <strong>Data:</strong> {{ order.data }} |
                                <strong>Total:</strong>
                                {{
                                    new Intl.NumberFormat("pt-PT", {
                                        style: "currency",
                                        currency: "EUR",
                                    }).format(order.total)
                                }}
                            </p>
                        </div>

                        <!-- Ações (NATIVAS, SEM h()) -->
                        <div class="flex gap-2 justify-end">
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="downloadPdf(order.id)"
                            >
                                PDF
                            </Button>

                            <Button
                                v-if="order.estado === 'rascunho'"
                                size="sm"
                                variant="outline"
                                @click="fechar(order.id)"
                            >
                                Fechar
                            </Button>

                            <Button
                                v-if="order.estado === 'fechado'"
                                size="sm"
                                variant="outline"
                                @click="marcarPaga(order.id)"
                            >
                                Paga
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <div
                v-if="props.orders.links.length > 3"
                class="flex justify-center mt-6"
            >
                <div class="flex gap-1">
                    <template
                        v-for="link in props.orders.links"
                        :key="link.label"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 rounded border text-sm"
                            :class="{
                                'bg-blue-600 text-white border-blue-600':
                                    link.active,
                                'bg-white text-slate-700 border-slate-300 hover:bg-slate-50':
                                    !link.active,
                            }"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 text-slate-400 text-sm"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
