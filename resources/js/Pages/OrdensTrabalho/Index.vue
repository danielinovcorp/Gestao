<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

// Props vindas do controller
const props = defineProps<{
    ordens: any;
    filters: {
        search?: string;
        estado?: string;
        cliente_id?: number;
        prioridade?: string;
    };
    clientes: Array<{ id: number; nome: string }>;
    servicos: Array<{
        id: number;
        nome: string;
        descricao?: string;
        preco?: number;
    }>;
    estados: Record<string, string>;
    prioridades: Record<string, string>;
    estatisticas: {
        total: number;
        pendentes: number;
        em_execucao: number;
        concluidas: number;
        urgentes: number;
    };
}>();

// ----------------------- Filtros -----------------------
const search = ref(props.filters.search || "");
const estado = ref(props.filters.estado || "");
const cliente_id = ref<number | null>(props.filters.cliente_id || null);
const prioridade = ref(props.filters.prioridade || "");

watch([search, estado, cliente_id, prioridade], () => {
    router.get(
        route("ordens.index"),
        {
            search: search.value,
            estado: estado.value,
            cliente_id: cliente_id.value,
            prioridade: prioridade.value,
        },
        { preserveState: true, replace: true },
    );
});

// ----------------------- Dialog + Form -----------------------
const showDialog = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);

const form = useForm({
    cliente_id: null as number | null,
    servico_id: null as number | null,
    descricao: "",
    data_inicio: "",
    data_fim: "",
    estado: "pendente",
    prioridade: "media",
    observacoes: "",
});

// Computed para inputs de data (converte ISO ↔ YYYY-MM-DD)
const dataInicioInput = computed({
    get: () => (form.data_inicio ? form.data_inicio.split("T")[0] : ""),
    set: (val: string) => {
        form.data_inicio = val ? `${val}T00:00:00.000000Z` : "";
    },
});

const dataFimInput = computed({
    get: () => (form.data_fim ? form.data_fim.split("T")[0] : ""),
    set: (val: string) => {
        form.data_fim = val ? `${val}T00:00:00.000000Z` : "";
    },
});

function openCreate() {
    isEditing.value = false;
    currentId.value = null;
    form.reset();
    form.estado = "pendente";
    form.prioridade = "media";
    showDialog.value = true;
}

function openEdit(row: any) {
    isEditing.value = true;
    currentId.value = row.id;
    form.reset();
    form.cliente_id = row.cliente_id;
    form.servico_id = row.servico_id;
    form.descricao = row.descricao ?? "";
    form.data_inicio = row.data_inicio ?? "";
    form.data_fim = row.data_fim ?? "";
    form.estado = row.estado ?? "pendente";
    form.prioridade = row.prioridade ?? "media";
    form.observacoes = row.observacoes ?? "";
    showDialog.value = true;
}

function submit() {
    if (isEditing.value && currentId.value) {
        form.put(route("ordens.update", currentId.value), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route("ordens.store"), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    }
}

function removeRow(id: number) {
    if (!confirm("Deseja remover esta Ordem de Trabalho?")) return;
    router.delete(route("ordens.destroy", id));
}

// ----------------------- Helpers -----------------------

// Formatar data para exibição: 10/11/2025
function formatDate(isoDate: string | null): string {
    if (!isoDate) return "—";
    const [date] = isoDate.split("T");
    const [year, month, day] = date.split("-");
    return `${day}/${month}/${year}`;
}

// Badge de prioridade
function getPrioridadeBadge(prioridade: string) {
    const classes = {
        urgente: "bg-red-100 text-red-800 border-red-200",
        alta: "bg-orange-100 text-orange-800 border-orange-200",
        media: "bg-yellow-100 text-yellow-800 border-yellow-200",
        baixa: "bg-green-100 text-green-800 border-green-200",
    };
    return classes[prioridade] || "bg-gray-100 text-gray-800 border-gray-200";
}

// Badge de estado
function getEstadoBadge(estado: string) {
    const classes = {
        pendente: "bg-yellow-100 text-yellow-800 border-yellow-200",
        agendada: "bg-blue-100 text-blue-800 border-blue-200",
        em_execucao: "bg-purple-100 text-purple-800 border-purple-200",
        concluida: "bg-green-100 text-green-800 border-green-200",
        cancelada: "bg-red-100 text-red-800 border-red-200",
    };
    return classes[estado] || "bg-gray-100 text-gray-800 border-gray-200";
}

// Formatar preço
function formatPreco(preco: number) {
    return new Intl.NumberFormat("pt-PT", {
        style: "currency",
        currency: "EUR",
    }).format(preco);
}
</script>

<template>
    <Head title="Ordens de Trabalho" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Ordens de Trabalho
            </h2>
        </template>

        <div class="p-6 space-y-6">
            <!-- Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div
                    class="bg-white p-4 rounded-lg shadow border-l-4 border-gray-400"
                >
                    <div class="text-2xl font-bold text-gray-700">
                        {{ estatisticas.total }}
                    </div>
                    <div class="text-sm text-gray-600">Total</div>
                </div>
                <div
                    class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-400"
                >
                    <div class="text-2xl font-bold text-yellow-700">
                        {{ estatisticas.pendentes }}
                    </div>
                    <div class="text-sm text-gray-600">Pendentes</div>
                </div>
                <div
                    class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-400"
                >
                    <div class="text-2xl font-bold text-purple-700">
                        {{ estatisticas.em_execucao }}
                    </div>
                    <div class="text-sm text-gray-600">Em Execução</div>
                </div>
                <div
                    class="bg-white p-4 rounded-lg shadow border-l-4 border-green-400"
                >
                    <div class="text-2xl font-bold text-green-700">
                        {{ estatisticas.concluidas }}
                    </div>
                    <div class="text-sm text-gray-600">Concluídas</div>
                </div>
                <div
                    class="bg-white p-4 rounded-lg shadow border-l-4 border-red-400"
                >
                    <div class="text-2xl font-bold text-red-700">
                        {{ estatisticas.urgentes }}
                    </div>
                    <div class="text-sm text-gray-600">Urgentes</div>
                </div>
            </div>

            <!-- Filtros -->
            <div
                class="grid gap-3 md:grid-cols-5 bg-white p-4 rounded-xl shadow"
            >
                <Input
                    v-model="search"
                    placeholder="Pesquisar por OT, cliente, serviço..."
                />
                <Select v-model="estado">
                    <SelectTrigger>
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos os estados</SelectItem>
                        <SelectItem
                            v-for="(label, value) in estados"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="cliente_id">
                    <SelectTrigger>
                        <SelectValue placeholder="Cliente" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Todos os clientes</SelectItem>
                        <SelectItem
                            v-for="cliente in clientes"
                            :key="cliente.id"
                            :value="cliente.id"
                        >
                            {{ cliente.nome }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="prioridade">
                    <SelectTrigger>
                        <SelectValue placeholder="Prioridade" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all"
                            >Todas as prioridades</SelectItem
                        >
                        <SelectItem
                            v-for="(label, value) in prioridades"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <div class="flex justify-end">
                    <Button
                        @click="openCreate"
                        class="bg-blue-600 hover:bg-blue-700"
                    >
                        + Nova OT
                    </Button>
                </div>
            </div>

            <!-- Tabela -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Número
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Cliente
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Serviço
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Prioridade
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Estado
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Início
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Fim
                            </th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="row in props.ordens.data"
                            :key="row.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ row.numero }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ row.cliente?.nome }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ row.servico?.nome }}
                                </div>
                                <div
                                    v-if="row.servico?.descricao"
                                    class="text-sm text-gray-500"
                                >
                                    {{ row.servico.descricao }}
                                </div>
                                <div
                                    v-if="row.servico?.preco"
                                    class="text-sm text-green-600"
                                >
                                    {{ formatPreco(row.servico.preco) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    :class="getPrioridadeBadge(row.prioridade)"
                                >
                                    {{ prioridades[row.prioridade] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    :class="getEstadoBadge(row.estado)"
                                >
                                    {{ estados[row.estado] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ formatDate(row.data_inicio) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ formatDate(row.data_fim) }}
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openEdit(row)"
                                >
                                    Editar
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="removeRow(row.id)"
                                >
                                    Remover
                                </Button>
                            </td>
                        </tr>

                        <tr v-if="!props.ordens.data.length">
                            <td
                                colspan="8"
                                class="px-4 py-8 text-center text-gray-500"
                            >
                                Nenhuma ordem de trabalho encontrada
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginação -->
                <div class="flex items-center justify-between p-4 border-t">
                    <div class="text-sm text-gray-500">
                        Mostrando {{ props.ordens.from }} a
                        {{ props.ordens.to }} de
                        {{ props.ordens.total }} resultados
                    </div>
                    <div class="space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!props.ordens.prev_page_url"
                            @click="
                                router.get(
                                    props.ordens.prev_page_url!,
                                    {},
                                    { preserveState: true },
                                )
                            "
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!props.ordens.next_page_url"
                            @click="
                                router.get(
                                    props.ordens.next_page_url!,
                                    {},
                                    { preserveState: true },
                                )
                            "
                        >
                            Próxima
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog para Criar/Editar -->
        <Dialog v-model:open="showDialog">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing
                            ? "Editar Ordem de Trabalho"
                            : "Nova Ordem de Trabalho"
                    }}</DialogTitle>
                </DialogHeader>

                <!-- FORMULÁRIO -->
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Cliente *</label>
                            <Select v-model="form.cliente_id" required>
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione o cliente"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="cliente in clientes"
                                        :key="cliente.id"
                                        :value="cliente.id"
                                    >
                                        {{ cliente.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div
                                v-if="form.errors.cliente_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.cliente_id }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Serviço *</label>
                            <Select v-model="form.servico_id" required>
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione o serviço"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="servico in servicos"
                                        :key="servico.id"
                                        :value="servico.id"
                                    >
                                        {{ servico.nome }}
                                        <span
                                            v-if="servico.preco"
                                            class="text-green-600 ml-2"
                                        >
                                            {{ formatPreco(servico.preco) }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div
                                v-if="form.errors.servico_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.servico_id }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium"
                            >Descrição do Trabalho *</label
                        >
                        <Textarea
                            v-model="form.descricao"
                            rows="3"
                            placeholder="Descreva em detalhe o trabalho a ser realizado..."
                            required
                        />
                        <div
                            v-if="form.errors.descricao"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.descricao }}
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium"
                                >Data Início</label
                            >
                            <Input type="date" v-model="dataInicioInput" />
                            <div
                                v-if="form.errors.data_inicio"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.data_inicio }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Data Fim</label>
                            <Input
                                type="date"
                                v-model="dataFimInput"
                                :min="dataInicioInput"
                            />
                            <div
                                v-if="form.errors.data_fim"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.data_fim }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium"
                                >Prioridade *</label
                            >
                            <Select v-model="form.prioridade" required>
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione a prioridade"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(label, value) in prioridades"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div
                                v-if="form.errors.prioridade"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.prioridade }}
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Estado *</label>
                            <Select v-model="form.estado" required>
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione o estado"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(label, value) in estados"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div
                                v-if="form.errors.estado"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.estado }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Observações</label>
                        <Textarea
                            v-model="form.observacoes"
                            rows="2"
                            placeholder="Observações adicionais..."
                        />
                        <div
                            v-if="form.errors.observacoes"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.observacoes }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showDialog = false"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 hover:bg-blue-700"
                        >
                            {{
                                isEditing ? "Guardar Alterações" : "Criar Ordem"
                            }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
