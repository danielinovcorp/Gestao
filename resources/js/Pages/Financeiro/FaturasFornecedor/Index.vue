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
    faturas: { data: any[]; links: any[] };
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
    encomenda_fornecedor_id: "",
    valor_total: "",
    estado: "pendente",
    documento: null as File | null,
});

// Formulário para edição
const editForm = reactive({
    numero: "",
    data_fatura: "",
    data_vencimento: "",
    fornecedor_id: "",
    encomenda_fornecedor_id: "",
    valor_total: "",
    estado: "pendente",
    documento: null as File | null,
    comprovativo: null as File | null,
});

function applyFilters() {
    router.get(
        route("financeiro.faturas-fornecedor.index"),
        {
            estado: estado.value !== "all" ? estado.value : undefined,
            fornecedor:
                fornecedor.value !== "all" ? fornecedor.value : undefined,
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
        encomenda_fornecedor_id: "",
        valor_total: "",
        estado: "pendente",
        documento: null,
    });
}

function cancelCreate() {
    creating.value = false;
}

function startEdit(fatura: any) {
    editingId.value = fatura.id;
    Object.assign(editForm, {
        numero: fatura.numero,
        data_fatura: fatura.data_fatura,
        data_vencimento: fatura.data_vencimento || "",
        fornecedor_id: String(fatura.fornecedor_id),
        encomenda_fornecedor_id: fatura.encomenda_fornecedor_id
            ? String(fatura.encomenda_fornecedor_id)
            : "",
        valor_total: fatura.valor_total_raw
            ? String(fatura.valor_total_raw)
            : "",
        estado: fatura.estado,
        documento: null,
        comprovativo: null,
    });
}

function cancelEdit() {
    editingId.value = null;
}

function onEstadoChange(newEstado: string, isEdit = false) {
    if (newEstado === "paga") {
        showComprovativoDialog.value = true;
    }
}

function submitCreate() {
    const formData = new FormData();

    // Converter valor "none" para null
    const encomendaId =
        createForm.encomenda_fornecedor_id === "none"
            ? ""
            : createForm.encomenda_fornecedor_id;

    formData.append("numero", createForm.numero);
    formData.append("data_fatura", createForm.data_fatura);
    formData.append("data_vencimento", createForm.data_vencimento || "");
    formData.append("fornecedor_id", createForm.fornecedor_id);
    formData.append("encomenda_fornecedor_id", encomendaId);
    formData.append(
        "valor_total",
        createForm.valor_total
            ? String(createForm.valor_total).replace(",", ".")
            : "0",
    );
    formData.append("estado", createForm.estado);

    if (createForm.documento) {
        formData.append("documento", createForm.documento);
    }
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
        onError: (errors) => {
            console.log("Erros:", errors);
        },
    });
}

function submitEdit() {
    if (!editingId.value) return;

    const formData = new FormData();

    // Converter valor "none" para null
    const encomendaId =
        editForm.encomenda_fornecedor_id === "none"
            ? ""
            : editForm.encomenda_fornecedor_id;

    formData.append("numero", editForm.numero);
    formData.append("data_fatura", editForm.data_fatura);
    formData.append("data_vencimento", editForm.data_vencimento || "");
    formData.append("fornecedor_id", editForm.fornecedor_id);
    formData.append("encomenda_fornecedor_id", encomendaId);
    formData.append(
        "valor_total",
        editForm.valor_total
            ? String(editForm.valor_total).replace(",", ".")
            : "0",
    );
    formData.append("estado", editForm.estado);
    formData.append("_method", "put");

    if (editForm.documento) {
        formData.append("documento", editForm.documento);
    }
    if (editForm.comprovativo) {
        formData.append("comprovativo", editForm.comprovativo);
    }

    router.post(
        route("financeiro.faturas-fornecedor.update", editingId.value),
        formData,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
            onError: (errors) => {
                console.log("Erros:", errors);
            },
        },
    );
}

function deleteFatura(id: number) {
    if (confirm("Tem certeza que deseja remover esta fatura?")) {
        router.delete(route("financeiro.faturas-fornecedor.destroy", id), {
            preserveScroll: true,
        });
    }
}

function onDocumentoChange(event: Event, isEdit = false) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        if (isEdit) {
            editForm.documento = target.files[0];
        } else {
            createForm.documento = target.files[0];
        }
    }
}

function onComprovativoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        comprovativoFile.value = target.files[0];
    }
}

function confirmComprovativo() {
    showComprovativoDialog.value = false;
    // Continua com o submit normal
    if (editingId.value) {
        submitEdit();
    } else {
        submitCreate();
    }
}

function cancelComprovativo() {
    showComprovativoDialog.value = false;
    // Reverte o estado para pendente
    if (editingId.value) {
        editForm.estado = "pendente";
    } else {
        createForm.estado = "pendente";
    }
}
</script>

<template>
    <Head title="Faturas Fornecedores" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1
                        class="text-2xl font-semibold leading-tight text-gray-800"
                    >
                        Faturas Fornecedores
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Gerencie as faturas dos fornecedores
                    </p>
                </div>
            </div>
        </template>

        <div class="px-6 md:px-8 py-6 space-y-6">
            <!-- Filtros -->
            <div class="rounded-2xl border bg-white p-4 shadow-sm">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Filtro Estado -->
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

                    <!-- Filtro Fornecedor -->
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

                    <!-- Ações -->
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

            <!-- Tabela -->
            <div class="rounded-2xl border bg-white shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Lista de Faturas
                        <Badge variant="secondary" class="ml-2">
                            {{ faturas.data.length }}
                        </Badge>
                    </h3>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[120px]">Data</TableHead>
                            <TableHead class="w-[140px]">Número</TableHead>
                            <TableHead class="w-[200px]">Fornecedor</TableHead>
                            <TableHead>Encomenda</TableHead>
                            <TableHead>Documento</TableHead>
                            <TableHead class="text-right w-[120px]"
                                >Valor Total</TableHead
                            >
                            <TableHead class="w-[100px]">Estado</TableHead>
                            <TableHead class="w-[120px]">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Linha de criação -->
                        <TableRow v-if="creating" class="bg-blue-50">
                            <TableCell>
                                <Input
                                    v-model="createForm.data_fatura"
                                    type="date"
                                    class="h-9"
                                />
                            </TableCell>
                            <TableCell>
                                <Input
                                    v-model="createForm.numero"
                                    placeholder="Número"
                                    class="h-9"
                                />
                            </TableCell>
                            <TableCell>
                                <Select v-model="createForm.fornecedor_id">
                                    <SelectTrigger class="h-9">
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
                            </TableCell>
                            <TableCell>
                                <Select
                                    v-model="createForm.encomenda_fornecedor_id"
                                >
                                    <SelectTrigger class="h-9">
                                        <SelectValue
                                            placeholder="Encomenda (opcional)"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value=""
                                            >Nenhuma</SelectItem
                                        >
                                        <SelectItem
                                            v-for="e in encomendas"
                                            :key="e.id"
                                            :value="String(e.id)"
                                        >
                                            {{ e.numero }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </TableCell>
                            <TableCell>
                                <Input
                                    type="file"
                                    @change="onDocumentoChange($event, false)"
                                    class="h-9"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                />
                            </TableCell>
                            <TableCell>
                                <Input
                                    v-model="createForm.valor_total"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0,00"
                                    class="h-9 text-right"
                                />
                            </TableCell>
                            <TableCell>
                                <Select
                                    v-model="createForm.estado"
                                    @update:model-value="
                                        (val) => onEstadoChange(val, false)
                                    "
                                >
                                    <SelectTrigger class="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pendente"
                                            >Pendente</SelectItem
                                        >
                                        <SelectItem value="paga"
                                            >Paga</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </TableCell>
                            <TableCell>
                                <div class="flex gap-2">
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
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Estado vazio -->
                        <TableRow
                            v-if="faturas.data.length === 0 && !creating"
                            class="hover:bg-transparent"
                        >
                            <TableCell>
                                <Select
                                    v-model="createForm.encomenda_fornecedor_id"
                                >
                                    <SelectTrigger class="h-9">
                                        <SelectValue
                                            :placeholder="
                                                encomendas.length > 0
                                                    ? 'Encomenda (opcional)'
                                                    : 'Sem encomendas'
                                            "
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none"
                                            >Nenhuma encomenda</SelectItem
                                        >
                                        <SelectItem
                                            v-for="e in encomendas"
                                            :key="e.id"
                                            :value="String(e.id)"
                                        >
                                            {{ e.numero }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="encomendas.length === 0"
                                    class="text-xs text-gray-500 mt-1"
                                >
                                    Nenhuma encomenda de fornecedor disponível
                                </p>
                            </TableCell>
                        </TableRow>

                        <!-- Linhas de faturas -->
                        <TableRow
                            v-for="fatura in faturas.data"
                            :key="fatura.id"
                            class="group hover:bg-gray-50"
                            :class="{ 'bg-yellow-50': editingId === fatura.id }"
                        >
                            <!-- Modo visualização -->
                            <template v-if="editingId !== fatura.id">
                                <TableCell class="font-medium">
                                    {{ fatura.data_fatura }}
                                </TableCell>
                                <TableCell class="font-medium">
                                    {{ fatura.numero }}
                                </TableCell>
                                <TableCell>
                                    {{ fatura.fornecedor }}
                                </TableCell>
                                <TableCell>
                                    {{ fatura.encomenda || "-" }}
                                </TableCell>
                                <TableCell>
                                    <a
                                        v-if="fatura.documento_url"
                                        :href="fatura.documento_url"
                                        target="_blank"
                                        class="flex items-center gap-1 text-blue-600 hover:underline"
                                    >
                                        <FileText class="h-4 w-4" />
                                        Abrir
                                    </a>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell class="text-right font-medium">
                                    {{ fatura.valor_total }} €
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            fatura.estado === 'paga'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        :class="
                                            fatura.estado === 'paga'
                                                ? 'bg-green-100 text-green-800 hover:bg-green-100'
                                                : 'bg-amber-100 text-amber-800 hover:bg-amber-100'
                                        "
                                    >
                                        {{
                                            fatura.estado === "paga"
                                                ? "Paga"
                                                : "Pendente"
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div
                                        class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="startEdit(fatura)"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="text-red-600 hover:text-red-700"
                                            @click="deleteFatura(fatura.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </template>

                            <!-- Modo edição -->
                            <template v-else>
                                <TableCell>
                                    <Input
                                        v-model="editForm.data_fatura"
                                        type="date"
                                        class="h-9"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Input
                                        v-model="editForm.numero"
                                        placeholder="Número"
                                        class="h-9"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Select v-model="editForm.fornecedor_id">
                                        <SelectTrigger class="h-9">
                                            <SelectValue />
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
                                </TableCell>
                                <TableCell>
                                    <Select
                                        v-model="
                                            editForm.encomenda_fornecedor_id
                                        "
                                    >
                                        <SelectTrigger class="h-9">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none"
                                                >Nenhuma</SelectItem
                                            >
                                            <SelectItem
                                                v-for="e in encomendas"
                                                :key="e.id"
                                                :value="String(e.id)"
                                            >
                                                {{ e.numero }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </TableCell>
                                <TableCell>
                                    <Input
                                        type="file"
                                        @change="
                                            onDocumentoChange($event, true)
                                        "
                                        class="h-9"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    />
                                    <div
                                        v-if="fatura.documento_url"
                                        class="text-xs text-gray-500 mt-1"
                                    >
                                        Documento atual:
                                        <a
                                            :href="fatura.documento_url"
                                            target="_blank"
                                            class="text-blue-600 hover:underline"
                                        >
                                            Ver
                                        </a>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Input
                                        v-model="editForm.valor_total"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0,00"
                                        class="h-9 text-right"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Select
                                        v-model="editForm.estado"
                                        @update:model-value="
                                            (val) => onEstadoChange(val, true)
                                        "
                                    >
                                        <SelectTrigger class="h-9">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pendente"
                                                >Pendente</SelectItem
                                            >
                                            <SelectItem value="paga"
                                                >Paga</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </TableCell>
                                <TableCell>
                                    <div class="flex gap-2">
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
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </template>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Paginação -->
                <div
                    v-if="faturas.links && faturas.links.length > 3"
                    class="p-4 border-t flex items-center justify-between"
                >
                    <p class="text-sm text-gray-700">
                        Mostrando {{ faturas.data.length }} registros
                    </p>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in faturas.links"
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

        <!-- Modal simples para comprovativo -->
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
