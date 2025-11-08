<!-- resources/js/Pages/Propostas/Index.vue -->
<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PropostaDialog from "./PropostaDialog.vue";
import { format } from "date-fns"; // ADICIONE ISSO

// ATUALIZE $formatDate PARA TRATAR NULL
const $formatDate = (date: string | Date | null, fmt = "dd/MM/yyyy") => {
  if (!date || date === null) return "—";
  try {
    return format(new Date(date), fmt);
  } catch {
    return "—";
  }
};

const props = defineProps<{
    propostas: {
        data: Array<{
            id: number;
            numero: string;
            data_proposta: string;
            validade: string;
            cliente: { nome: string };
            total: number; // ADICIONE AQUI!
            estado: "rascunho" | "fechado";
        }>;
        current_page: number;
        last_page: number;
        total: number;
        links: any[];
    };
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? "");
let searchTimeout: NodeJS.Timeout;

function filtrar() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route("propostas.index"),
            { search: search.value },
            { preserveState: true, replace: true },
        );
    }, 500);
}

const open = ref(false);
function onSaved() {
    router.reload({ only: ["propostas"] }); // ← RECARREGA SÓ A LISTA
    open.value = false;
}

function fechar(id: number) {
    if (!confirm("Tem certeza que deseja fechar esta proposta?")) return;
    router.post(route("propostas.fechar", id), {}, { onSuccess: onSaved });
}
function pdf(id: number) {
    window.open(route("propostas.pdf", id), "_blank");
}
function converter(id: number) {
    router.post(route("propostas.converter", id));
}
</script>

<template>
    <Head title="Propostas" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-semibold leading-tight">Propostas</h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros + Nova Proposta -->
            <div class="flex items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Pesquisar por número ou cliente..."
                    class="w-80"
                    @input="filtrar"
                />
                <Button @click="filtrar">Filtrar</Button>
                <div class="flex-1"></div>
                <Button @click="open = true">Nova Proposta</Button>
            </div>

            <!-- Tabela (EXATAMENTE como Países e Entidades) -->
            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                #
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Número
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Data
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Validade
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Cliente
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Total
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Estado
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr
                            v-for="p in props.propostas.data"
                            :key="p.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ p.id }}
                            </td>
                            <td class="px-4 py-2 text-sm font-mono font-bold">
                                #{{ p.numero }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{
                                    p.data_proposta
                                        ? $formatDate(p.data_proposta)
                                        : "—"
                                }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ p.validade ? $formatDate(p.validade) : "—" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ p.cliente?.nome }}
                            </td>

                            <!-- TOTAL CORRETO -->
                            <td
                                class="px-4 py-2 text-sm text-right font-medium"
                            >
                                {{
                                    p.total
                                        ? Number(p.total).toFixed(2)
                                        : "0.00"
                                }}
                                €
                            </td>

                            <td class="px-4 py-2 text-sm">
                                <span
                                    :class="{
                                        'text-amber-600 font-medium':
                                            p.estado === 'rascunho',
                                        'text-emerald-600 font-medium':
                                            p.estado === 'fechado',
                                    }"
                                >
                                    {{
                                        p.estado === "rascunho"
                                            ? "Rascunho"
                                            : "Fechado"
                                    }}
                                </span>
                            </td>

                            <!-- AÇÕES -->
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="pdf(p.id)"
                                        >PDF</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="converter(p.id)"
                                        >Converter</Button
                                    >
                                    <Button
                                        v-if="p.estado === 'rascunho'"
                                        size="sm"
                                        variant="outline"
                                        @click="fechar(p.id)"
                                    >
                                        Fechar
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="props.propostas.data.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhuma proposta encontrada
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div
                class="flex items-center justify-between text-sm text-slate-600"
            >
                <div>Total: {{ props.propostas.total }} propostas</div>
                <div class="flex items-center gap-4">
                    <div>
                        Página {{ props.propostas.current_page }} de
                        {{ props.propostas.last_page }}
                    </div>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in props.propostas.links"
                            :key="link.label"
                            size="sm"
                            variant="ghost"
                            :disabled="!link.url"
                            :class="{ 'font-bold': link.active }"
                            @click="router.get(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <PropostaDialog
            :open="open"
            :onClose="() => (open = false)"
            @saved="onSaved"
        />
    </AuthenticatedLayout>
</template>
