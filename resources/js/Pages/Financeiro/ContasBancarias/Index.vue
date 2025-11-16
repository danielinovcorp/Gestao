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
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { Search, Plus, Filter, Download, Save, X, Edit } from "lucide-vue-next";

const props = defineProps<{
    contas: { data: any[]; links: any[] };
    filters: { search?: string; ativo?: string };
}>();

const search = ref(props.filters.search ?? "");
const ativo = ref(props.filters.ativo ?? "all");
const debounceTimer = ref<NodeJS.Timeout>();
const editingId = ref<number | null>(null);
const creating = ref(false);

// Formulário para criação
const createForm = reactive({
    banco: "",
    titular: "",
    iban: "",
    swift_bic: "",
    numero_conta: "",
    saldo_abertura: "0",
    ativo: true,
    notas: "",
});

// Formulário para edição
const editForm = reactive({
    banco: "",
    titular: "",
    iban: "",
    swift_bic: "",
    numero_conta: "",
    saldo_abertura: "0",
    ativo: true,
    notas: "",
});

function applyFilters() {
    router.get(
        route("financeiro.contas-bancarias"),
        {
            search: search.value || undefined,
            ativo: ativo.value !== "all" ? ativo.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = "";
    ativo.value = "all";
    applyFilters();
}

function onSearchInput() {
    clearTimeout(debounceTimer.value);
    debounceTimer.value = setTimeout(applyFilters, 500);
}

function startCreate() {
    creating.value = true;
    Object.assign(createForm, {
        banco: "",
        titular: "",
        iban: "",
        swift_bic: "",
        numero_conta: "",
        saldo_abertura: "0",
        ativo: true,
        notas: "",
    });
}

function cancelCreate() {
    creating.value = false;
}

function submitCreate() {
    router.post(route("financeiro.contas-bancarias.store"), createForm, {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            Object.assign(createForm, {
                banco: "",
                titular: "",
                iban: "",
                swift_bic: "",
                numero_conta: "",
                saldo_abertura: "0",
                ativo: true,
                notas: "",
            });
        },
    });
}

function startEdit(conta: any) {
    editingId.value = conta.id;
    Object.assign(editForm, {
        banco: conta.banco || "",
        titular: conta.titular || "",
        iban: conta.iban ? conta.iban.replace(/\s/g, "") : "",
        swift_bic: conta.swift_bic || "",
        numero_conta: conta.numero_conta || "",
        saldo_abertura: conta.saldo_abertura
            ? conta.saldo_abertura.replace(/\./g, "").replace(",", ".")
            : "0",
        ativo: conta.ativo,
        notas: conta.notas || "",
    });
}

function cancelEdit() {
    editingId.value = null;
}

function submitEdit() {
    if (!editingId.value) return;

    router.put(
        route("financeiro.contas-bancarias.update", editingId.value),
        editForm,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
        },
    );
}

function deleteConta(id: number) {
    if (confirm("Tem certeza que deseja remover esta conta?")) {
        router.delete(route("financeiro.contas-bancarias.destroy", id), {
            preserveScroll: true,
        });
    }
}

function formatIban(iban: string) {
    if (!iban) return "";
    const cleanIban = iban.replace(/\s+/g, "");
    return cleanIban.replace(/(.{4})/g, "$1 ").trim();
}

function exportToCsv() {
    const headers = [
        "Banco",
        "Titular",
        "IBAN",
        "SWIFT/BIC",
        "Número Conta",
        "Saldo Abertura",
        "Estado",
    ];
    const csvContent = [
        headers.join(","),
        ...props.contas.data.map((conta) =>
            [
                `"${conta.banco || ""}"`,
                `"${conta.titular || ""}"`,
                `"${conta.iban}"`,
                `"${conta.swift_bic || ""}"`,
                `"${conta.numero_conta || ""}"`,
                conta.saldo_abertura
                    ? conta.saldo_abertura.replace(".", "").replace(",", ".")
                    : "0",
                conta.ativo ? "Ativa" : "Inativa",
            ].join(","),
        ),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute(
        "download",
        `contas-bancarias-${new Date().toISOString().split("T")[0]}.csv`,
    );
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<template>
    <Head title="Contas Bancárias" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1
                        class="text-2xl font-semibold leading-tight text-gray-800"
                    >
                        Contas Bancárias
                    </h1>
                </div>
            </div>
        </template>

        <div class="px-6 md:px-8 py-6 space-y-6">
            <!-- Filtros -->
            <div class="rounded-2xl border bg-white p-4 shadow-sm">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1 relative">
                        <Search
                            class="absolute left-3 top-3 h-4 w-4 text-gray-400"
                        />
                        <Input
                            v-model="search"
                            placeholder="Pesquisar por banco, titular ou IBAN..."
                            class="pl-10 h-11"
                            @input="onSearchInput"
                        />
                    </div>

                    <!-- Filtro Estado -->
                    <div class="w-full lg:w-48">
                        <Select
                            v-model="ativo"
                            @update:model-value="applyFilters"
                        >
                            <SelectTrigger class="h-11">
                                <SelectValue placeholder="Todos os estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="1">Ativas</SelectItem>
                                <SelectItem value="0">Inativas</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            class="h-11"
                            @click="clearFilters"
                            :disabled="!search && ativo === 'all'"
                        >
                            <Filter class="h-4 w-4 mr-2" />
                            Limpar
                        </Button>
                        <Button
                            variant="outline"
                            class="h-11"
                            @click="exportToCsv"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Exportar
                        </Button>
                        <Button
                            class="h-11 font-medium"
                            @click="startCreate"
                            :disabled="creating"
                        >
                            Nova Conta
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Resto do código permanece igual -->
            <!-- SUBSTITUA TODO O BLOCO DA TABELA POR ESTE -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Lista de Contas
                        <Badge variant="secondary" class="ml-2">
                            {{ contas.data.length }}
                        </Badge>
                    </h3>
                </div>

                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Banco
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Titular
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                IBAN
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                SWIFT/BIC
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Saldo Abertura
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
                        <!-- Linha de criação -->
                        <tr v-if="creating" class="bg-blue-50">
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.banco"
                                    placeholder="Nome do banco"
                                    class="h-9"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.titular"
                                    placeholder="Titular da conta"
                                    class="h-9"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.iban"
                                    placeholder="PT50 0000 0000 0000 0000 0000 0"
                                    class="h-9 font-mono"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.swift_bic"
                                    placeholder="EXMPLPT21"
                                    class="h-9 font-mono uppercase"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.saldo_abertura"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="h-9 text-right"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center space-x-2">
                                    <Switch
                                        id="create-ativo"
                                        v-model:checked="createForm.ativo"
                                    />
                                    <Label
                                        for="create-ativo"
                                        class="text-sm cursor-pointer"
                                    >
                                        {{
                                            createForm.ativo
                                                ? "Ativa"
                                                : "Inativa"
                                        }}
                                    </Label>
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        @click="submitCreate"
                                        :disabled="!createForm.iban"
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
                            </td>
                        </tr>

                        <!-- Sem resultados -->
                        <tr v-if="contas.data.length === 0 && !creating">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center">
                                    <p
                                        class="text-lg font-medium text-gray-900 mb-2"
                                    >
                                        Nenhuma conta encontrada
                                    </p>
                                    <p class="text-gray-500 mb-4">
                                        {{
                                            search || ativo !== "all"
                                                ? "Tente ajustar os filtros"
                                                : "Adicione a primeira conta"
                                        }}
                                    </p>
                                    <Button
                                        v-if="!search && ativo === 'all'"
                                        @click="startCreate"
                                    >
                                        <Plus class="h-4 w-4 mr-2" />
                                        Adicionar Conta
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <!-- Linhas normais -->
                        <tr
                            v-for="conta in contas.data"
                            :key="conta.id"
                            class="group hover:bg-slate-50"
                            :class="{ 'bg-yellow-50': editingId === conta.id }"
                        >
                            <template v-if="editingId !== conta.id">
                                <td
                                    class="px-4 py-2 text-sm text-slate-600 font-medium"
                                >
                                    {{ conta.banco || "—" }}
                                </td>
                                <td class="px-4 py-2 text-sm text-slate-600">
                                    {{ conta.titular || "—" }}
                                </td>
                                <td class="px-4 py-2 text-sm font-mono">
                                    {{ formatIban(conta.iban) }}
                                </td>
                                <td class="px-4 py-2 text-sm font-mono">
                                    {{ conta.swift_bic || "—" }}
                                </td>
                                <td
                                    class="px-4 py-2 text-sm text-right font-medium"
                                >
                                    {{ conta.saldo_abertura }} €
                                </td>
                                <td class="px-4 py-2">
                                    <Badge
                                        :variant="
                                            conta.ativo
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        :class="
                                            conta.ativo
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-800'
                                        "
                                    >
                                        {{ conta.ativo ? "Ativa" : "Inativa" }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-2">
                                    <div
                                        class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="startEdit(conta)"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="text-red-600 hover:text-red-700"
                                            @click="deleteConta(conta.id)"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </template>

                            <!-- Modo edição -->
                            <template v-else>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.banco"
                                        placeholder="Nome do banco"
                                        class="h-9"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.titular"
                                        placeholder="Titular da conta"
                                        class="h-9"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.iban"
                                        placeholder="PT50..."
                                        class="h-9 font-mono"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.swift_bic"
                                        placeholder="EXMPLPT21"
                                        class="h-9 font-mono uppercase"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.saldo_abertura"
                                        type="number"
                                        step="0.01"
                                        class="h-9 text-right"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="edit-ativo"
                                            v-model:checked="editForm.ativo"
                                        />
                                        <Label
                                            for="edit-ativo"
                                            class="text-sm cursor-pointer"
                                        >
                                            {{
                                                editForm.ativo
                                                    ? "Ativa"
                                                    : "Inativa"
                                            }}
                                        </Label>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2 justify-end">
                                        <Button
                                            size="sm"
                                            @click="submitEdit"
                                            :disabled="!editForm.iban"
                                        >
                                            <Save class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="cancelEdit"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginação -->
                <div
                    v-if="contas.links?.length > 3"
                    class="p-4 border-t flex items-center justify-between text-sm"
                >
                    <div class="text-slate-600">
                        Mostrando {{ contas.from }}–{{ contas.to }} de
                        {{ contas.total }}
                    </div>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in contas.links"
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
