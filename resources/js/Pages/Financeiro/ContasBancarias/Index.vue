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
            ativo: ativo.value !== "all" ? ativo.value : undefined 
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
        saldo_abertura: conta.saldo_abertura ? 
            conta.saldo_abertura.replace(/\./g, "").replace(",", ".") : "0",
        ativo: conta.ativo,
        notas: conta.notas || "",
    });
}

function cancelEdit() {
    editingId.value = null;
}

function submitEdit() {
    if (!editingId.value) return;

    router.put(route("financeiro.contas-bancarias.update", editingId.value), editForm, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function deleteConta(id: number) {
    if (confirm('Tem certeza que deseja remover esta conta?')) {
        router.delete(route("financeiro.contas-bancarias.destroy", id), {
            preserveScroll: true,
        });
    }
}

function formatIban(iban: string) {
    if (!iban) return '';
    const cleanIban = iban.replace(/\s+/g, '');
    return cleanIban.replace(/(.{4})/g, '$1 ').trim();
}

function exportToCsv() {
    const headers = ['Banco', 'Titular', 'IBAN', 'SWIFT/BIC', 'Número Conta', 'Saldo Abertura', 'Estado'];
    const csvContent = [
        headers.join(','),
        ...props.contas.data.map(conta => [
            `"${conta.banco || ''}"`,
            `"${conta.titular || ''}"`,
            `"${conta.iban}"`,
            `"${conta.swift_bic || ''}"`,
            `"${conta.numero_conta || ''}"`,
            conta.saldo_abertura ? conta.saldo_abertura.replace('.', '').replace(',', '.') : '0',
            conta.ativo ? 'Ativa' : 'Inativa'
        ].join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `contas-bancarias-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
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
                    <h1 class="text-2xl font-semibold leading-tight text-gray-800">
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
                        <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                        <Input
                            v-model="search"
                            placeholder="Pesquisar por banco, titular ou IBAN..."
                            class="pl-10 h-11"
                            @input="onSearchInput"
                        />
                    </div>

                    <!-- Filtro Estado -->
                    <div class="w-full lg:w-48">
                        <Select v-model="ativo" @update:model-value="applyFilters">
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
            <div class="rounded-2xl border bg-white shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Lista de Contas
                        <Badge variant="secondary" class="ml-2">
                            {{ contas.data.length }}
                        </Badge>
                    </h3>
                </div>
                
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[200px]">Banco</TableHead>
                            <TableHead class="w-[200px]">Titular</TableHead>
                            <TableHead>IBAN</TableHead>
                            <TableHead>SWIFT/BIC</TableHead>
                            <TableHead class="text-right">Saldo Abertura</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-[100px]">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Linha de criação -->
                        <TableRow v-if="creating" class="bg-blue-50">
                            <TableCell>
                                <Input v-model="createForm.banco" placeholder="Nome do banco" />
                            </TableCell>
                            <TableCell>
                                <Input v-model="createForm.titular" placeholder="Titular da conta" />
                            </TableCell>
                            <TableCell>
                                <Input 
                                    v-model="createForm.iban" 
                                    placeholder="PT50 0000 0000 0000 0000 0000 0" 
                                    class="font-mono"
                                />
                            </TableCell>
                            <TableCell>
                                <Input 
                                    v-model="createForm.swift_bic" 
                                    placeholder="EXMPLPT21" 
                                    class="font-mono uppercase"
                                />
                            </TableCell>
                            <TableCell>
                                <Input 
                                    v-model="createForm.saldo_abertura" 
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="text-right"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center space-x-2">
                                    <Switch
                                        id="create-ativo"
                                        v-model:checked="createForm.ativo"
                                    />
                                    <Label for="create-ativo" class="text-sm cursor-pointer">
                                        {{ createForm.ativo ? 'Ativa' : 'Inativa' }}
                                    </Label>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex gap-2">
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
                            </TableCell>
                        </TableRow>
                        
                        <!-- Estado vazio -->
                        <TableRow
                            v-if="contas.data.length === 0 && !creating"
                            class="hover:bg-transparent"
                        >
                            <TableCell
                                colspan="7"
                                class="py-12 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center justify-center">
                                    <p class="text-lg font-medium text-gray-900 mb-2">
                                        Nenhuma conta encontrada
                                    </p>
                                    <p class="text-gray-500 mb-4">
                                        {{
                                            search || ativo !== 'all'
                                                ? 'Tente ajustar os filtros de pesquisa'
                                                : 'Comece adicionando a primeira conta bancária'
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
                            </TableCell>
                        </TableRow>
                        
                        <!-- Linhas de contas -->
                        <TableRow
                            v-for="conta in contas.data"
                            :key="conta.id"
                            class="group hover:bg-gray-50"
                            :class="{ 'bg-yellow-50': editingId === conta.id }"
                        >
                            <!-- Modo visualização -->
                            <template v-if="editingId !== conta.id">
                                <TableCell class="font-medium">
                                    {{ conta.banco || '-' }}
                                </TableCell>
                                <TableCell>
                                    {{ conta.titular || '-' }}
                                </TableCell>
                                <TableCell class="font-mono text-sm">
                                    {{ formatIban(conta.iban) }}
                                </TableCell>
                                <TableCell class="font-mono text-sm">
                                    {{ conta.swift_bic || '-' }}
                                </TableCell>
                                <TableCell class="text-right font-medium">
                                    {{ conta.saldo_abertura }} €
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="conta.ativo ? 'default' : 'secondary'"
                                        :class="conta.ativo 
                                            ? 'bg-green-100 text-green-800 hover:bg-green-100' 
                                            : 'bg-gray-100 text-gray-800 hover:bg-gray-100'"
                                    >
                                        {{ conta.ativo ? 'Ativa' : 'Inativa' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
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
                                </TableCell>
                            </template>

                            <!-- Modo edição -->
                            <template v-else>
                                <TableCell>
                                    <Input v-model="editForm.banco" placeholder="Nome do banco" />
                                </TableCell>
                                <TableCell>
                                    <Input v-model="editForm.titular" placeholder="Titular da conta" />
                                </TableCell>
                                <TableCell>
                                    <Input 
                                        v-model="editForm.iban" 
                                        placeholder="PT50 0000 0000 0000 0000 0000 0" 
                                        class="font-mono"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Input 
                                        v-model="editForm.swift_bic" 
                                        placeholder="EXMPLPT21" 
                                        class="font-mono uppercase"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Input 
                                        v-model="editForm.saldo_abertura" 
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="text-right"
                                    />
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="edit-ativo"
                                            v-model:checked="editForm.ativo"
                                        />
                                        <Label for="edit-ativo" class="text-sm cursor-pointer">
                                            {{ editForm.ativo ? 'Ativa' : 'Inativa' }}
                                        </Label>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex gap-2">
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
                                </TableCell>
                            </template>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Seção de notas para criação -->
                <div 
                    v-if="creating" 
                    class="p-4 border-t bg-blue-50"
                >
                    <Label for="create-notas" class="text-sm font-medium">Notas</Label>
                    <Textarea
                        id="create-notas"
                        v-model="createForm.notas"
                        placeholder="Observações adicionais..."
                        rows="2"
                        class="mt-2"
                    />
                </div>

                <!-- Seção de notas para edição -->
                <div 
                    v-if="editingId"
                    class="p-4 border-t bg-yellow-50"
                >
                    <Label for="edit-notas" class="text-sm font-medium">Notas</Label>
                    <Textarea
                        id="edit-notas"
                        v-model="editForm.notas"
                        placeholder="Observações adicionais..."
                        rows="2"
                        class="mt-2"
                    />
                </div>

                <!-- Paginação -->
                <div 
                    v-if="contas.links && contas.links.length > 3" 
                    class="p-4 border-t flex items-center justify-between"
                >
                    <p class="text-sm text-gray-700">
                        Mostrando {{ contas.data.length }} registros
                    </p>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in contas.links"
                            :key="link.label"
                            variant="outline"
                            size="sm"
                            :disabled="!link.url"
                            :class="{
                                'bg-primary text-primary-foreground': link.active,
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