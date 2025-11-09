<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { format } from "date-fns";

// Formatador de data seguro
const $formatDate = (date: string | Date | null, fmt = "dd/MM/yyyy") => {
    if (!date) return "—";
    try {
        return format(new Date(date), fmt);
    } catch {
        return "—";
    }
};

const title = "Encomendas – Cliente";

const props = defineProps<{
    orders: {
        /* ... */
    };
    filters: { search?: string; estado?: string };
    clientes: Array<{ id: number; nome: string }>;
    fornecedores: Array<{ id: number; nome: string }>;
    artigos: Array<{
        id: number;
        referencia: string;
        nome: string;
        preco: number;
    }>;
}>();

// Filtros
const search = ref(props.filters.search || "");
const estado = ref(props.filters.estado || "");

function aplicarFiltros() {
    router.get(
        route("encomendas.clientes.index"),
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

// AÇÕES
function fechar(id: number) {
    if (!confirm("Fechar esta encomenda?")) return;
    router.patch(
        route("encomendas.clientes.close", id),
        {},
        {
            preserveState: true,
            replace: true,
            onSuccess: () => alert("Encomenda fechada!"),
        },
    );
}

function converter(id: number) {
    if (
        !confirm(
            "Converter esta encomenda para fornecedor?\nSerão criadas encomendas por fornecedor.",
        )
    )
        return;
    router.post(
        route("encomendas.clientes.convert", id),
        {},
        {
            preserveState: true,
            replace: true,
            onSuccess: (page) => {
                const success = page.props.flash?.success;
                if (success) alert(success);
            },
            onError: () => alert("Erro ao converter."),
        },
    );
}

function downloadPdf(id: number) {
    window.open(route("encomendas.clientes.pdf", id), "_blank");
}

// === MODAL NOVA ENCOMENDA ===
const dialogOpen = ref(false);

const form = useForm({
    cliente_id: null,
    data_proposta: new Date().toISOString().split("T")[0],
    validade: null,
    estado: "rascunho",
    linhas: [] as Array<{
        artigo_id: number | null;
        descricao: string;
        quantidade: number;
        preco: number;
        fornecedor_id: number | null;
        total: number;
    }>,
});

const total = ref(0);

watch(
    () => form.linhas,
    (linhas) => {
        total.value = linhas.reduce((sum, l) => sum + l.total, 0);
    },
    { deep: true },
);

function abrirDialog() {
    form.reset();
    form.cliente_id = null;
    form.data_proposta = new Date().toISOString().split("T")[0];
    form.linhas = [];
    dialogOpen.value = true;
}

function adicionarLinha() {
    form.linhas.push({
        artigo_id: null,
        descricao: "",
        quantidade: 1,
        preco: 0,
        fornecedor_id: null,
        total: 0,
    });
}

function removerLinha(index: number) {
    form.linhas.splice(index, 1);
}

function atualizarLinha(index: number) {
    const linha = form.linhas[index];
    const artigo = props.artigos?.find((a) => a.id === linha.artigo_id);
    if (artigo) {
        linha.descricao = artigo.nome;
        linha.preco = artigo.preco;
    }
    linha.total = linha.quantidade * linha.preco;
}

function salvar(rascunho: boolean) {
    form.estado = rascunho ? "rascunho" : "fechado";
    form.post(route("encomendas.clientes.store"), {
        onSuccess: () => {
            dialogOpen.value = false;
            router.reload({ only: ["orders"] });
        },
    });
}

function fecharModal() {
    dialogOpen.value = false;
    form.reset();
}
</script>

<template>
    <Head :title="title" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-semibold leading-tight">
                Encomendas – Cliente
            </h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros + Nova -->
            <div class="flex flex-col sm:flex-row items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Pesquisar..."
                    class="w-full sm:w-80"
                    @keyup.enter="aplicarFiltros"
                />
                <Select v-model="estado">
                    <SelectTrigger class="w-full sm:w-40">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Todos</SelectItem>
                        <SelectItem value="rascunho">Rascunho</SelectItem>
                        <SelectItem value="fechado">Fechado</SelectItem>
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
                <div class="flex-1"></div>
                <Button @click="abrirDialog">Nova Encomenda</Button>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-xl border bg-white">
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
                                Valor Total
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
                            v-for="order in props.orders.data"
                            :key="order.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ order.data || "-" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ order.numero || "-" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ order.validade || "-" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ order.cliente || "-" }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                {{
                                    new Intl.NumberFormat("pt-PT", {
                                        style: "currency",
                                        currency: "EUR",
                                    }).format(order.total)
                                }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    :class="{
                                        'bg-yellow-100 text-yellow-800':
                                            order.estado === 'rascunho',
                                        'bg-green-100 text-green-800':
                                            order.estado === 'fechado',
                                    }"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{
                                        order.estado === "rascunho"
                                            ? "Rascunho"
                                            : "Fechado"
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
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
                                        v-else
                                        size="sm"
                                        variant="outline"
                                        @click="converter(order.id)"
                                    >
                                        Converter
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.orders.data.length">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhuma encomenda encontrada
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div
                v-if="props.orders.links.length > 3"
                class="flex justify-center"
            >
                <div class="flex gap-1">
                    <template
                        v-for="link in props.orders.links"
                        :key="link.label"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1 rounded border"
                            :class="{
                                'bg-blue-500 text-white border-blue-500':
                                    link.active,
                                'bg-white text-slate-700 border-slate-300 hover:bg-slate-50':
                                    !link.active,
                            }"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1 text-slate-400"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL NOVA ENCOMENDA (INLINE) -->
        <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
            <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Nova Encomenda</DialogTitle>
                </DialogHeader>

                <div class="space-y-6 py-4">
                    <!-- Cliente e Datas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label>Cliente *</Label>
                            <Select v-model="form.cliente_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione o cliente"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in props.clientes"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.cliente_id"
                                class="text-red-600 text-xs mt-1"
                            >
                                {{ form.errors.cliente_id }}
                            </p>
                        </div>
                        <div>
                            <Label>Data da Proposta *</Label>
                            <Input type="date" v-model="form.data_proposta" />
                            <p
                                v-if="form.errors.data_proposta"
                                class="text-red-600 text-xs mt-1"
                            >
                                {{ form.errors.data_proposta }}
                            </p>
                        </div>
                        <div>
                            <Label>Validade</Label>
                            <Input type="date" v-model="form.validade" />
                        </div>
                    </div>

                    <!-- Linhas -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <Label>Linhas da Encomenda</Label>
                            <Button size="sm" @click="adicionarLinha"
                                >+ Adicionar Linha</Button
                            >
                        </div>

                        <div
                            v-for="(linha, i) in form.linhas"
                            :key="i"
                            class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-2 border-b pb-2"
                        >
                            <Select
                                v-model="linha.artigo_id"
                                @update:model-value="atualizarLinha(i)"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Artigo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="a in props.artigos"
                                        :key="a.id"
                                        :value="a.id"
                                    >
                                        {{ a.referencia }} - {{ a.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Input
                                v-model="linha.descricao"
                                placeholder="Descrição"
                                disabled
                            />

                            <Input
                                type="number"
                                step="0.001"
                                v-model.number="linha.quantidade"
                                @input="atualizarLinha(i)"
                                placeholder="Qtd"
                            />

                            <Input
                                type="number"
                                step="0.01"
                                v-model.number="linha.preco"
                                @input="atualizarLinha(i)"
                                placeholder="Preço"
                            />

                            <Select v-model="linha.fornecedor_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Fornecedor" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="f in props.fornecedores"
                                        :key="f.id"
                                        :value="f.id"
                                    >
                                        {{ f.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <div class="flex items-center gap-2">
                                <span class="font-medium">
                                    {{
                                        new Intl.NumberFormat("pt-PT", {
                                            style: "currency",
                                            currency: "EUR",
                                        }).format(linha.total)
                                    }}
                                </span>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="removerLinha(i)"
                                    >×</Button
                                >
                            </div>
                        </div>

                        <p
                            v-if="form.errors['linhas']"
                            class="text-red-600 text-xs mt-1"
                        >
                            {{ form.errors["linhas"] }}
                        </p>
                    </div>

                    <!-- Total -->
                    <div class="text-right">
                        <p class="text-lg font-semibold">
                            Total:
                            {{
                                new Intl.NumberFormat("pt-PT", {
                                    style: "currency",
                                    currency: "EUR",
                                }).format(total)
                            }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="secondary" @click="fecharModal"
                        >Cancelar</Button
                    >
                    <Button @click="salvar(true)" :disabled="form.processing">
                        Salvar como Rascunho
                    </Button>
                    <Button @click="salvar(false)" :disabled="form.processing">
                        Fechar e Salvar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
