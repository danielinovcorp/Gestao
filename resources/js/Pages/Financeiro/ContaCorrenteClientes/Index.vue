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
        const debitoValue = createForm.debito ? 
            parseFloat(String(createForm.debito).replace(',', '.')) : 0;
        const creditoValue = createForm.credito ? 
            parseFloat(String(createForm.credito).replace(',', '.')) : 0;
        
        const formData = { 
            ...createForm,
            debito: debitoValue,
            credito: creditoValue
        };
        
        // Garantir que apenas um campo seja preenchido
        if (formData.debito > 0 && formData.credito > 0) {
            formData.credito = 0;
        }

        // Validação final
        if (!formData.cliente_id) {
            alert('Selecione um cliente');
            return;
        }

        if (formData.debito <= 0 && formData.credito <= 0) {
            alert('Preencha pelo menos um valor (débito ou crédito)');
            return;
        }

        console.log('Enviando dados:', formData);

        router.post(route("financeiro.conta-corrente-clientes.store"), formData, {
            preserveScroll: true,
            onSuccess: () => {
                creating.value = false;
                Object.assign(createForm, {
                    cliente_id: "",
                    data: new Date().toISOString().split('T')[0],
                    descricao: "",
                    documento_tipo: "",
                    documento_numero: "",
                    debito: "",
                    credito: "",
                });
            },
            onError: (errors) => {
                console.log('Erros:', errors);
                alert('Erro ao salvar: ' + JSON.stringify(errors));
            }
        });
    } catch (error) {
        console.error('Erro no submit:', error);
        alert('Erro ao processar os dados');
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
            <div class="rounded-2xl border bg-white shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Movimentos da Conta Corrente
                        <Badge variant="secondary" class="ml-2">
                            {{ movimentos.data.length }}
                        </Badge>
                    </h3>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[120px]">Data</TableHead>
                            <TableHead class="w-[200px]">Cliente</TableHead>
                            <TableHead>Documento</TableHead>
                            <TableHead>Descrição</TableHead>
                            <TableHead class="text-right w-[120px]"
                                >Débito</TableHead
                            >
                            <TableHead class="text-right w-[120px]"
                                >Crédito</TableHead
                            >
                            <TableHead class="w-[80px]">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Linha de criação -->
                        <TableRow v-if="creating" class="bg-blue-50">
                            <TableCell>
                                <Input
                                    v-model="createForm.data"
                                    type="date"
                                    class="h-9"
                                />
                            </TableCell>
                            <TableCell>
                                <Select v-model="createForm.cliente_id">
                                    <SelectTrigger class="h-9">
                                        <SelectValue
                                            placeholder="Selecione o cliente"
                                        />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-72">
                                        <SelectItem
                                            v-for="c in clientes"
                                            :key="c.id"
                                            :value="String(c.id)"
                                        >
                                            {{ c.nome }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </TableCell>
                            <TableCell>
                                <div class="flex gap-2">
                                    <Select v-model="createForm.documento_tipo">
                                        <SelectTrigger class="h-9 w-32">
                                            <SelectValue placeholder="Tipo" />
                                        </SelectTrigger>
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
                            </TableCell>
                            <TableCell>
                                <Input
                                    v-model="createForm.descricao"
                                    placeholder="Descrição do movimento"
                                    class="h-9"
                                />
                            </TableCell>
                            <TableCell>
                                <Input
                                    v-model="createForm.debito"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0,00"
                                    class="h-9 text-right"
                                    @input="onDebitoInput"
                                />
                            </TableCell>
                            <TableCell>
                                <Input
                                    v-model="createForm.credito"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0,00"
                                    class="h-9 text-right"
                                    @input="onCreditoInput"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex gap-2">
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
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Estado vazio -->
                        <TableRow
                            v-if="movimentos.data.length === 0 && !creating"
                            class="hover:bg-transparent"
                        >
                            <TableCell
                                colspan="7"
                                class="py-12 text-center text-slate-500"
                            >
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <p
                                        class="text-lg font-medium text-gray-900 mb-2"
                                    >
                                        Nenhum movimento encontrado
                                    </p>
                                    <p class="text-gray-500 mb-4">
                                        {{
                                            cliente !== "all"
                                                ? "Tente ajustar o filtro de cliente"
                                                : "Comece adicionando o primeiro lançamento"
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
                            </TableCell>
                        </TableRow>

                        <!-- Linhas de movimentos -->
                        <TableRow
                            v-for="movimento in movimentos.data"
                            :key="movimento.id"
                            class="group hover:bg-gray-50"
                        >
                            <TableCell class="font-medium">
                                {{ movimento.data }}
                            </TableCell>
                            <TableCell>
                                {{ movimento.cliente }}
                            </TableCell>
                            <TableCell>
                                <div
                                    v-if="
                                        movimento.documento_tipo ||
                                        movimento.documento_numero
                                    "
                                >
                                    <Badge variant="outline" class="mr-2">
                                        {{ movimento.documento_tipo || "Doc" }}
                                    </Badge>
                                    {{ movimento.documento_numero }}
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </TableCell>
                            <TableCell>
                                {{ movimento.descricao || "-" }}
                            </TableCell>
                            <TableCell class="text-right font-medium">
                                <span
                                    v-if="movimento.debito_raw > 0"
                                    class="text-red-600"
                                >
                                    {{ movimento.debito }} €
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </TableCell>
                            <TableCell class="text-right font-medium">
                                <span
                                    v-if="movimento.credito_raw > 0"
                                    class="text-green-600"
                                >
                                    {{ movimento.credito }} €
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </TableCell>
                            <TableCell>
                                <div
                                    class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity"
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
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Paginação -->
                <div
                    v-if="movimentos.links && movimentos.links.length > 3"
                    class="p-4 border-t flex items-center justify-between"
                >
                    <p class="text-sm text-gray-700">
                        Mostrando {{ movimentos.data.length }} registros
                    </p>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in movimentos.links"
                            :key="link.label"
                            variant="outline"
                            size="sm"
                            :disabled="!link.url"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    link.active,
                            }"
                            @click="router.get(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
