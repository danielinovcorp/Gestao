<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, reactive } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
    Search,
    Plus,
    Filter,
    Download,
    Save,
    X,
    Trash2,
} from "lucide-vue-next";

const props = defineProps<{
    movimentos: { data: any[]; links: any[] };
    clientes: { id: number; nome: string }[];
    filters: { cliente?: string };
    saldo: string;
    saldo_raw: number;
}>();

const cliente = ref(props.filters.cliente ?? "all");
const creating = ref(false);

// Formulário para criação
const createForm = reactive({
    cliente_id: "",
    data: new Date().toISOString().split("T")[0], // Data atual
    descricao: "",
    documento_tipo: "",
    documento_numero: "",
    debito: "",
    credito: "",
});

function applyFilters() {
    router.get(
        route("financeiro.conta-corrente-clientes"),
        {
            cliente: cliente.value !== "all" ? cliente.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    cliente.value = "all";
    applyFilters();
}

function startCreate() {
    creating.value = true;
    Object.assign(createForm, {
        cliente_id: cliente.value !== "all" ? cliente.value : "",
        data: new Date().toISOString().split("T")[0],
        descricao: "",
        documento_tipo: "",
        documento_numero: "",
        debito: "",
        credito: "",
    });
}

function cancelCreate() {
    creating.value = false;
}

function onDebitoInput() {
    if (createForm.debito) {
        createForm.credito = "";
    }
}

function onCreditoInput() {
    if (createForm.credito) {
        createForm.debito = "";
    }
}

function submitCreate() {
    try {
        // Converter para string primeiro, depois para número
        const debitoValue = createForm.debito
            ? parseFloat(String(createForm.debito).replace(",", "."))
            : 0;
        const creditoValue = createForm.credito
            ? parseFloat(String(createForm.credito).replace(",", "."))
            : 0;

        const formData = {
            ...createForm,
            debito: debitoValue,
            credito: creditoValue,
        };

        // Garantir que apenas um campo seja preenchido
        if (formData.debito > 0 && formData.credito > 0) {
            formData.credito = 0;
        }

        // Validação final
        if (!formData.cliente_id) {
            alert("Selecione um cliente");
            return;
        }

        if (formData.debito <= 0 && formData.credito <= 0) {
            alert("Preencha pelo menos um valor (débito ou crédito)");
            return;
        }

        console.log("Enviando dados:", formData);

        router.post(
            route("financeiro.conta-corrente-clientes.store"),
            formData,
            {
                preserveScroll: true,
                onSuccess: () => {
                    creating.value = false;
                    Object.assign(createForm, {
                        cliente_id: "",
                        data: new Date().toISOString().split("T")[0],
                        descricao: "",
                        documento_tipo: "",
                        documento_numero: "",
                        debito: "",
                        credito: "",
                    });
                },
                onError: (errors) => {
                    console.log("Erros:", errors);
                    alert("Erro ao salvar: " + JSON.stringify(errors));
                },
            },
        );
    } catch (error) {
        console.error("Erro no submit:", error);
        alert("Erro ao processar os dados");
    }
}

function deleteMovimento(id: number) {
    if (confirm("Tem certeza que deseja remover este lançamento?")) {
        router.delete(route("financeiro.conta-corrente-clientes.destroy", id), {
            preserveScroll: true,
        });
    }
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat("pt-PT", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

function getSaldoColor(saldo: number) {
    const saldoNum = typeof saldo === "string" ? parseFloat(saldo) : saldo;
    return saldoNum >= 0 ? "text-green-700" : "text-red-700";
}
</script>

<template>
    <Head title="Conta Corrente Clientes" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1
                        class="text-2xl font-semibold leading-tight text-gray-800"
                    >
                        Conta Corrente Clientes
                    </h1>
                </div>
            </div>
        </template>

        <div class="px-6 md:px-8 py-6 space-y-6">
            <!-- Filtros e Saldo -->
            <div class="rounded-2xl border bg-white p-4 shadow-sm">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Filtro Cliente -->
                    <div class="w-full lg:w-64">
                        <Select
                            v-model="cliente"
                            @update:model-value="applyFilters"
                        >
                            <SelectTrigger class="h-11">
                                <SelectValue placeholder="Todos os clientes" />
                            </SelectTrigger>
                            <SelectContent class="max-h-72">
                                <SelectItem value="all"
                                    >Todos os clientes</SelectItem
                                >
                                <SelectItem
                                    v-for="c in clientes"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.nome }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Saldo -->
                    <div class="flex-1 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Saldo Atual</p>
                            <p
                                class="text-2xl font-bold"
                                :class="getSaldoColor(saldo_raw)"
                            >
                                {{ saldo }} €
                            </p>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            class="h-11"
                            @click="clearFilters"
                            :disabled="cliente === 'all'"
                        >
                            <Filter class="h-4 w-4 mr-2" />
                            Limpar
                        </Button>
                        <Button
                            class="h-11 font-medium"
                            @click="startCreate"
                            :disabled="creating"
                        >
                            Novo Lançamento
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Movimentos da Conta Corrente
                        <Badge variant="secondary" class="ml-2">
                            {{ movimentos.data.length }}
                        </Badge>
                    </h3>
                </div>

                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Data
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Cliente
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Documento
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Descrição
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Débito
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Crédito
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <!-- Linha de criação -->
                        <tr v-if="creating" class="bg-blue-50">
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.data"
                                    type="date"
                                    class="h-9"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Select v-model="createForm.cliente_id">
                                    <SelectTrigger class="h-9"
                                        ><SelectValue placeholder="Cliente"
                                    /></SelectTrigger>
                                    <SelectContent class="max-h-72">
                                        <SelectItem
                                            v-for="c in clientes"
                                            :key="c.id"
                                            :value="String(c.id)"
                                            >{{ c.nome }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Select v-model="createForm.documento_tipo">
                                        <SelectTrigger class="h-9 w-32"
                                            ><SelectValue placeholder="Tipo"
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="fatura"
                                                >Fatura</SelectItem
                                            >
                                            <SelectItem value="recibo"
                                                >Recibo</SelectItem
                                            >
                                            <SelectItem value="nota_credito"
                                                >Nota Crédito</SelectItem
                                            >
                                            <SelectItem value="ajuste"
                                                >Ajuste</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        v-model="createForm.documento_numero"
                                        placeholder="Número"
                                        class="h-9 flex-1"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.descricao"
                                    placeholder="Descrição"
                                    class="h-9"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.debito"
                                    type="number"
                                    step="0.01"
                                    class="h-9 text-right"
                                    @input="onDebitoInput"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.credito"
                                    type="number"
                                    step="0.01"
                                    class="h-9 text-right"
                                    @input="onCreditoInput"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        @click="submitCreate"
                                        :disabled="
                                            !createForm.cliente_id ||
                                            (!createForm.debito &&
                                                !createForm.credito)
                                        "
                                    >
                                        <Save class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="cancelCreate"
                                        ><X class="h-4 w-4"
                                    /></Button>
                                </div>
                            </td>
                        </tr>

                        <!-- Sem resultados -->
                        <tr v-if="movimentos.data.length === 0 && !creating">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center">
                                    <p
                                        class="text-lg font-medium text-gray-900 mb-2"
                                    >
                                        Nenhum movimento encontrado
                                    </p>
                                    <p class="text-gray-500 mb-4">
                                        {{
                                            cliente !== "all"
                                                ? "Tente ajustar o filtro"
                                                : "Adicione o primeiro lançamento"
                                        }}
                                    </p>
                                    <Button
                                        v-if="cliente === 'all'"
                                        @click="startCreate"
                                    >
                                        <Plus class="h-4 w-4 mr-2" />
                                        Adicionar Lançamento
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <!-- Linhas normais -->
                        <tr
                            v-for="movimento in movimentos.data"
                            :key="movimento.id"
                            class="group hover:bg-slate-50"
                        >
                            <td
                                class="px-4 py-2 text-sm text-slate-600 font-medium"
                            >
                                {{ movimento.data }}
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ movimento.cliente }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <div
                                    v-if="
                                        movimento.documento_tipo ||
                                        movimento.documento_numero
                                    "
                                >
                                    <Badge variant="outline" class="mr-2">{{
                                        movimento.documento_tipo || "Doc"
                                    }}</Badge>
                                    {{ movimento.documento_numero }}
                                </div>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ movimento.descricao || "—" }}
                            </td>
                            <td
                                class="px-4 py-2 text-sm text-right font-medium"
                            >
                                <span
                                    v-if="movimento.debito_raw > 0"
                                    class="text-red-600"
                                    >{{ movimento.debito }} €</span
                                >
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td
                                class="px-4 py-2 text-sm text-right font-medium"
                            >
                                <span
                                    v-if="movimento.credito_raw > 0"
                                    class="text-green-600"
                                    >{{ movimento.credito }} €</span
                                >
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-2">
                                <div
                                    class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="text-red-600 hover:text-red-700"
                                        @click="deleteMovimento(movimento.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginação -->
                <div
                    v-if="movimentos.links?.length > 3"
                    class="p-4 border-t flex items-center justify-between text-sm"
                >
                    <div class="text-slate-600">
                        Mostrando {{ movimentos.from }}–{{ movimentos.to }} de
                        {{ movimentos.total }}
                    </div>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in movimentos.links"
                            :key="link.label"
                            :disabled="!link.url"
                            @click="router.get(link.url)"
                            :class="[
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-white border hover:bg-slate-50',
                                !link.url
                                    ? 'opacity-50 cursor-not-allowed'
                                    : '',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
