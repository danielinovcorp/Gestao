<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, reactive } from "vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/Components/ui/select";
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from "@/Components/ui/table";
import { Badge } from "@/Components/ui/badge";
import { Label } from "@/components/ui/label";
import {
    Plus,
    Filter,
    Save,
    X,
    Edit,
    Trash2,
    FileText,
    Mail,
} from "lucide-vue-next";

const props = defineProps<{
    faturas: {
        data: any[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    fornecedores: { id: number; nome: string }[];
    encomendas: { id: number; numero: string }[];
    filters: { estado?: string; fornecedor?: string };
}>();

const estado = ref(props.filters.estado ?? "all");
const fornecedor = ref(props.filters.fornecedor ?? "all");
const creating = ref(false);
const editingId = ref<number | null>(null);
const showComprovativoDialog = ref(false);
const comprovativoFile = ref<File | null>(null);

// Formulário para criação
const createForm = reactive({
    numero: "",
    data_fatura: new Date().toISOString().split("T")[0],
    data_vencimento: "",
    fornecedor_id: "",
    encomenda_fornecedor_id: null as number | null,
    valor_total: "",
    estado: "pendente",
});

// Formulário para edição
const editForm = reactive({
    numero: "",
    data_fatura: "",
    data_vencimento: "",
    fornecedor_id: "",
    encomenda_fornecedor_id: null as number | null,
    valor_total: "",
    estado: "pendente",
    comprovativo: null as File | null,
});

// === FUNÇÃO SEGURA PARA CONVERTER d/m/Y → Y-m-d ===
function toIsoDate(dateStr: string | null | undefined): string {
    if (!dateStr || typeof dateStr !== "string") return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

    const parts = dateStr.split("/");
    if (parts.length !== 3) return "";

    const [day, month, year] = parts.map((p) => p.trim());
    const d = parseInt(day, 10);
    const m = parseInt(month, 10);
    const y = parseInt(year, 10);

    if (isNaN(d) || isNaN(m) || isNaN(y) || d < 1 || d > 31 || m < 1 || m > 12 || y < 1000) {
        return "";
    }

    return `${y}-${String(m).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
}

function applyFilters() {
    router.get(
        route("financeiro.faturas-fornecedor.index"),
        {
            estado: estado.value !== "all" ? estado.value : undefined,
            fornecedor: fornecedor.value !== "all" ? fornecedor.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    estado.value = "all";
    fornecedor.value = "all";
    applyFilters();
}

function startCreate() {
    creating.value = true;
    Object.assign(createForm, {
        numero: "",
        data_fatura: new Date().toISOString().split("T")[0],
        data_vencimento: "",
        fornecedor_id: "",
        encomenda_fornecedor_id: null,
        valor_total: "",
        estado: "pendente",
    });
    comprovativoFile.value = null;
}

function cancelCreate() {
    creating.value = false;
    comprovativoFile.value = null;
}

function startEdit(fatura: any) {
    editingId.value = fatura.id;
    Object.assign(editForm, {
        numero: fatura.numero || "",
        data_fatura: fatura.data_fatura ? toIsoDate(fatura.data_fatura) || new Date().toISOString().split("T")[0] : new Date().toISOString().split("T")[0],
        data_vencimento: fatura.data_vencimento ? toIsoDate(fatura.data_vencimento) : "",
        fornecedor_id: String(fatura.fornecedor_id),
        encomenda_fornecedor_id: fatura.encomenda_fornecedor_id ? Number(fatura.encomenda_fornecedor_id) : null,
        valor_total: fatura.valor_total_raw ? String(fatura.valor_total_raw) : "",
        estado: fatura.estado || "pendente",
        comprovativo: null,
    });
}

function cancelEdit() {
    editingId.value = null;
    editForm.comprovativo = null;
}

function onEstadoChange(newEstado: string) {
    if (newEstado === "paga") {
        showComprovativoDialog.value = true;
    }
}

function submitCreate() {
    const formData = new FormData();

    formData.append("data_fatura", toIsoDate(createForm.data_fatura));
    formData.append("data_vencimento", toIsoDate(createForm.data_vencimento));
    formData.append("numero", createForm.numero);
    formData.append("fornecedor_id", createForm.fornecedor_id);
    formData.append("encomenda_fornecedor_id", createForm.encomenda_fornecedor_id?.toString() || "");
    formData.append("valor_total", createForm.valor_total ? String(createForm.valor_total).replace(",", ".") : "0");
    formData.append("estado", createForm.estado);

    if (comprovativoFile.value) {
        formData.append("comprovativo", comprovativoFile.value);
    }

    router.post(route("financeiro.faturas-fornecedor.store"), formData, {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            comprovativoFile.value = null;
            showComprovativoDialog.value = false;
        },
        onError: (errors) => console.log("Erros:", errors),
    });
}

function submitEdit() {
    if (!editingId.value) return;

    if (!editForm.numero || !editForm.data_fatura || !editForm.fornecedor_id || !editForm.valor_total) {
        alert("Preencha todos os campos obrigatórios: Número, Data, Fornecedor e Valor.");
        return;
    }

    const formData = new FormData();
    formData.append("data_fatura", toIsoDate(editForm.data_fatura));
    formData.append("data_vencimento", toIsoDate(editForm.data_vencimento));
    formData.append("numero", editForm.numero);
    formData.append("fornecedor_id", editForm.fornecedor_id);
    formData.append("encomenda_fornecedor_id", editForm.encomenda_fornecedor_id !== null ? String(editForm.encomenda_fornecedor_id) : "");
    formData.append("valor_total", editForm.valor_total ? String(editForm.valor_total).replace(",", ".") : "0");
    formData.append("estado", editForm.estado);
    formData.append("_method", "put");

    if (editForm.comprovativo) {
        formData.append("comprovativo", editForm.comprovativo);
    }

    router.post(route("financeiro.faturas-fornecedor.update", editingId.value), formData, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.comprovativo = null;
        },
        onError: (errors) => console.log("Erros:", errors),
    });
}

function deleteFatura(id: number) {
    if (confirm("Tem certeza que deseja remover esta fatura?")) {
        router.delete(route("financeiro.faturas-fornecedor.destroy", id), { preserveScroll: true });
    }
}

function onComprovativoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.[0]) {
        if (editingId.value) {
            editForm.comprovativo = target.files[0];
        } else {
            comprovativoFile.value = target.files[0];
        }
    }
}

function confirmComprovativo() {
    showComprovativoDialog.value = false;
    if (editingId.value) {
        submitEdit();
    } else {
        submitCreate();
    }
}

function cancelComprovativo() {
    showComprovativoDialog.value = false;
    if (editingId.value) {
        editForm.estado = "pendente";
        editForm.comprovativo = null;
    } else {
        createForm.estado = "pendente";
        comprovativoFile.value = null;
    }
}
</script>

<template>
    <Head title="Faturas Fornecedores" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-semibold leading-tight text-gray-800">
                Faturas Fornecedores
            </h1>
        </template>

        <div class="px-6 md:px-8 py-6 space-y-6">
            <!-- Filtros -->
            <div class="rounded-2xl border bg-white p-4 shadow-sm">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="w-full lg:w-48">
                        <Select
                            v-model="estado"
                            @update:model-value="applyFilters"
                        >
                            <SelectTrigger class="h-11">
                                <SelectValue placeholder="Todos os estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="pendente"
                                    >Pendentes</SelectItem
                                >
                                <SelectItem value="paga">Pagas</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-full lg:w-64">
                        <Select
                            v-model="fornecedor"
                            @update:model-value="applyFilters"
                        >
                            <SelectTrigger class="h-11">
                                <SelectValue
                                    placeholder="Todos os fornecedores"
                                />
                            </SelectTrigger>
                            <SelectContent class="max-h-72">
                                <SelectItem value="all"
                                    >Todos os fornecedores</SelectItem
                                >
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

                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            class="h-11"
                            @click="clearFilters"
                            :disabled="estado === 'all' && fornecedor === 'all'"
                        >
                            <Filter class="h-4 w-4 mr-2" />
                            Limpar
                        </Button>
                        <Button
                            class="h-11 font-medium"
                            @click="startCreate"
                            :disabled="creating"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Nova Fatura
                        </Button>
                    </div>
                </div>
            </div>

            <!-- TABELA NO PADRÃO -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Lista de Faturas
                        <Badge variant="secondary" class="ml-2">
                            {{ faturas.data.length }}
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
                                Número
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Fornecedor
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Encomenda
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Documento
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Valor
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
                                    v-model="createForm.data_fatura"
                                    type="date"
                                    class="h-9"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.numero"
                                    placeholder="Número"
                                    class="h-9"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Select v-model="createForm.fornecedor_id">
                                    <SelectTrigger class="h-9"
                                        ><SelectValue placeholder="Fornecedor"
                                    /></SelectTrigger>
                                    <SelectContent class="max-h-72">
                                        <SelectItem
                                            v-for="f in fornecedores"
                                            :key="f.id"
                                            :value="String(f.id)"
                                            >{{ f.nome }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </td>
                            <td class="px-4 py-2">
                                <Select
                                    v-model="createForm.encomenda_fornecedor_id"
                                >
                                    <SelectTrigger class="h-9"
                                        ><SelectValue placeholder="Encomenda"
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null"
                                            >Nenhuma</SelectItem
                                        >
                                        <SelectItem
                                            v-for="e in encomendas"
                                            :key="e.id"
                                            :value="e.id"
                                            >{{ e.numero }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-500">
                                PDF gerado automaticamente
                            </td>
                            <td class="px-4 py-2">
                                <Input
                                    v-model="createForm.valor_total"
                                    type="number"
                                    step="0.01"
                                    class="h-9 text-right"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <Select
                                    v-model="createForm.estado"
                                    @update:model-value="
                                        (v) => onEstadoChange(v, false)
                                    "
                                >
                                    <SelectTrigger class="h-9"
                                        ><SelectValue
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pendente"
                                            >Pendente</SelectItem
                                        >
                                        <SelectItem value="paga"
                                            >Paga</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        @click="submitCreate"
                                        :disabled="
                                            !createForm.numero ||
                                            !createForm.data_fatura ||
                                            !createForm.fornecedor_id ||
                                            !createForm.valor_total
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

                        <!-- Linhas normais -->
                        <tr
                            v-for="fatura in faturas.data"
                            :key="fatura.id"
                            class="group hover:bg-slate-50"
                            :class="{ 'bg-yellow-50': editingId === fatura.id }"
                        >
                            <template v-if="editingId !== fatura.id">
                                <td class="px-4 py-2 text-sm text-slate-600">
                                    {{ fatura.data_fatura }}
                                </td>
                                <td class="px-4 py-2 text-sm font-medium">
                                    {{ fatura.numero }}
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    {{ fatura.fornecedor }}
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    {{ fatura.encomenda || "—" }}
                                </td>
                                <td class="px-4 py-2">
                                    <a
                                        v-if="fatura.documento_url"
                                        :href="fatura.documento_url"
                                        target="_blank"
                                        class="text-blue-600 hover:underline text-sm flex items-center gap-1"
                                    >
                                        <FileText class="h-4 w-4" />
                                        Fatura #{{ fatura.numero }}.pdf
                                    </a>
                                    <span v-else class="text-slate-400 text-sm"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-2 text-sm text-right font-medium"
                                >
                                    {{ fatura.valor_total }} €
                                </td>
                                <td class="px-4 py-2">
                                    <Badge
                                        :variant="
                                            fatura.estado === 'paga'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        :class="
                                            fatura.estado === 'paga'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-amber-100 text-amber-800'
                                        "
                                    >
                                        {{
                                            fatura.estado === "paga"
                                                ? "Paga"
                                                : "Pendente"
                                        }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-2">
                                    <div
                                        class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="startEdit(fatura)"
                                            ><Edit class="h-4 w-4"
                                        /></Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="text-red-600 hover:text-red-700"
                                            @click="deleteFatura(fatura.id)"
                                            ><Trash2 class="h-4 w-4"
                                        /></Button>
                                    </div>
                                </td>
                            </template>

                            <!-- Modo edição -->
                            <template v-else>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.data_fatura"
                                        type="date"
                                        class="h-9"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.numero"
                                        placeholder="Número"
                                        class="h-9"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Select v-model="editForm.fornecedor_id">
                                        <SelectTrigger class="h-9"
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent class="max-h-72">
                                            <SelectItem
                                                v-for="f in fornecedores"
                                                :key="f.id"
                                                :value="String(f.id)"
                                                >{{ f.nome }}</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="px-4 py-2">
                                    <Select
                                        v-model="
                                            editForm.encomenda_fornecedor_id
                                        "
                                    >
                                        <SelectTrigger class="h-9"
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null"
                                                >Nenhuma</SelectItem
                                            >
                                            <SelectItem
                                                v-for="e in encomendas"
                                                :key="e.id"
                                                :value="e.id"
                                                >{{ e.numero }}</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="px-4 py-2 text-sm text-slate-500">
                                    PDF gerado automaticamente
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="editForm.valor_total"
                                        type="number"
                                        step="0.01"
                                        class="h-9 text-right"
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <Select
                                        v-model="editForm.estado"
                                        @update:model-value="
                                            (v) => onEstadoChange(v, true)
                                        "
                                    >
                                        <SelectTrigger class="h-9"
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pendente"
                                                >Pendente</SelectItem
                                            >
                                            <SelectItem value="paga"
                                                >Paga</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2 justify-end">
                                        <Button
                                            size="sm"
                                            @click="submitEdit"
                                            :disabled="
                                                !editForm.numero ||
                                                !editForm.data_fatura ||
                                                !editForm.fornecedor_id ||
                                                !editForm.valor_total
                                            "
                                        >
                                            <Save class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="cancelEdit"
                                            ><X class="h-4 w-4"
                                        /></Button>
                                    </div>
                                </td>
                            </template>
                        </tr>

                        <!-- Sem resultados -->
                        <tr v-if="faturas.data.length === 0 && !creating">
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhuma fatura encontrada
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginação -->
                <div
                    v-if="faturas.links?.length > 3"
                    class="p-4 border-t flex items-center justify-between text-sm"
                >
                    <div class="text-slate-600">
                        Mostrando {{ faturas.from }}–{{ faturas.to }} de
                        {{ faturas.total }}
                    </div>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in faturas.links"
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

        <!-- Modal Comprovativo -->
        <div
            v-if="showComprovativoDialog"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">
                    Comprovativo de Pagamento
                </h3>
                <p class="text-gray-600 mb-4">
                    Pretende enviar o comprovativo ao Fornecedor?
                </p>
                <div class="space-y-4">
                    <Label for="comprovativo">Anexar comprovativo:</Label>
                    <Input
                        id="comprovativo"
                        type="file"
                        @change="onComprovativoChange"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <p class="text-sm text-gray-500">
                        O comprovativo será enviado por email ao fornecedor
                        automaticamente.
                    </p>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <Button variant="outline" @click="cancelComprovativo"
                        >Cancelar</Button
                    >
                    <Button @click="confirmComprovativo">
                        <Mail class="h-4 w-4 mr-2" />
                        Enviar Comprovativo
                    </Button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
