<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import PropostaDialog from "./PropostaDialog.vue";

const props = defineProps<{ propostas: any; filters: any }>();

// filtros
const search = ref(props.filters.search ?? "");
function filtrar() {
    router.get(
        route("propostas.index"),
        { search: search.value },
        { preserveState: true },
    );
}

// modal nova proposta
const open = ref(false);
function onSaved() {
    router.reload({ only: ["propostas"] });
}

// ações
function fechar(id: number) {
    router.post(route("propostas.fechar", id));
}
function pdf(id: number) {
    window.location.href = route("propostas.pdf", id);
}
function converter(id: number) {
    router.post(route("propostas.converter", id));
}
</script>

<template>
    <Head title="Propostas" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">Propostas</h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- 🔍 Filtros -->
            <form @submit.prevent="filtrar" class="flex gap-2">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Pesquisar..."
                    class="max-w-xs"
                />
                <Button type="submit" variant="default">Filtrar</Button>
                <Button type="button" variant="default" @click="open = true"
                    >Nova Proposta</Button
                >
            </form>

            <!-- 📋 Tabela -->
            <div
                class="rounded-xl border bg-white shadow-sm mt-4 overflow-hidden"
            >
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 text-left">Data</th>
                            <th class="px-3 py-2 text-left">Número</th>
                            <th class="px-3 py-2 text-left">Validade</th>
                            <th class="px-3 py-2 text-left">Cliente</th>
                            <th class="px-3 py-2 text-right">Valor Total</th>
                            <th class="px-3 py-2 text-left">Estado</th>
                            <th class="px-3 py-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in props.propostas.data"
                            :key="p.id"
                            class="border-t hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-3 py-2">
                                {{ p.data_proposta ?? "—" }}
                            </td>
                            <td class="px-3 py-2">#{{ p.numero }}</td>
                            <td class="px-3 py-2">{{ p.validade ?? "—" }}</td>
                            <td class="px-3 py-2">{{ p.cliente?.nome }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ Number(p.valor_total).toFixed(2) }} €
                            </td>
                            <td class="px-3 py-2">
                                <span
                                    :class="{
                                        'text-amber-600 font-medium':
                                            p.estado === 'rascunho',
                                        'text-emerald-600 font-medium':
                                            p.estado === 'fechado',
                                    }"
                                >
                                    {{ p.estado }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="pdf(p.id)"
                                    >PDF</Button
                                >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="converter(p.id)"
                                    >Converter</Button
                                >
                                <Button
                                    v-if="p.estado === 'rascunho'"
                                    size="sm"
                                    @click="fechar(p.id)"
                                    >Fechar</Button
                                >
                            </td>
                        </tr>

                        <!-- Sem resultados -->
                        <tr v-if="props.propostas.data.length === 0">
                            <td
                                colspan="7"
                                class="text-center py-6 text-slate-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal de criação -->
            <PropostaDialog
                :open="open"
                :onClose="() => (open = false)"
                @saved="onSaved"
            />
        </div>
    </AuthenticatedLayout>
</template>
