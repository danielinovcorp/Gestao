<script setup>
import { Head, usePage, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { format } from "date-fns";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// Props do Inertia
const props = defineProps({
    docs: Object,
    filters: Object,
});

// Estado local dos filtros
const search = ref(props.filters.q ?? "");
const type = ref(props.filters.type ?? "");
const from = ref(props.filters.from ?? "");
const to = ref(props.filters.to ?? "");

// Tipos de documentos (ajuste conforme suas models)
const docTypes = [
    { value: "App\\Models\\Proposta", label: "Proposta" },
    { value: "App\\Models\\SalesOrder", label: "Encomenda Cliente" },
    { value: "App\\Models\\PurchaseOrder", label: "Encomenda Fornecedor" },
    { value: "App\\Models\\FornecedorFatura", label: "Fatura Fornecedor" },
];

// Debounce na busca
let timeout;
watch(search, (val) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

const applyFilters = () => {
    router.get(
        route("docs.index"),
        {
            q: search.value || null,
            type: type.value || null,
            from: from.value || null,
            to: to.value || null,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = "";
    type.value = "";
    from.value = "";
    to.value = "";
    applyFilters();
};

const formatDate = (date) => {
    return date ? format(new Date(date), "dd/MM/yyyy HH:mm") : "-";
};

const formatSize = (bytes) => {
    if (!bytes) return "-";
    const units = ["B", "KB", "MB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${units[i]}`;
};

const preview = (doc) => {
    window.open(route("docs.preview", doc.id), "_blank");
};

const download = (doc) => {
    window.location.href = route("docs.download", doc.id);
};
</script>

<template>
    <Head title="Arquivo Digital" />
    <AuthenticatedLayout>
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Arquivo Digital
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ docs.total }} documento(s) encontrado(s)
                </p>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border bg-white p-4 shadow-sm mb-4">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3"
                >
                    <div>
                        <label class="text-xs font-medium">Busca</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Título, cliente, nº documento..."
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-medium">Tipo</label>
                        <select
                            v-model="type"
                            @change="applyFilters"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="t in docTypes"
                                :key="t.value"
                                :value="t.value"
                            >
                                {{ t.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium">De</label>
                        <input
                            v-model="from"
                            type="date"
                            @change="applyFilters"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-medium">Até</label>
                        <input
                            v-model="to"
                            type="date"
                            @change="applyFilters"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="flex items-end">
                        <button
                            @click="clearFilters"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground hover:bg-accent"
                        >
                            Limpar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabela -->
            <div class="rounded-xl border bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-muted-foreground"
                                >
                                    Título
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-muted-foreground"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-muted-foreground"
                                >
                                    Gerado em
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-muted-foreground"
                                >
                                    Por
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-muted-foreground"
                                >
                                    Tamanho
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-muted-foreground"
                                >
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="doc in docs.data"
                                :key="doc.id"
                                class="border-b hover:bg-muted/50 transition-colors"
                            >
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded bg-red-100 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-red-600"
                                                >PDF</span
                                            >
                                        </div>
                                        <span class="font-medium">{{
                                            doc.title
                                        }}</span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-muted-foreground"
                                >
                                    {{
                                        docTypes.find(
                                            (t) =>
                                                t.value ===
                                                doc.documentable_type,
                                        )?.label || "Desconhecido"
                                    }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-muted-foreground"
                                >
                                    {{ formatDate(doc.created_at) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-muted-foreground"
                                >
                                    {{ doc.uploader?.name || "Sistema" }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-muted-foreground"
                                >
                                    {{ formatSize(doc.size) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <button
                                        @click="preview(doc)"
                                        class="text-primary hover:underline text-xs mr-3"
                                    >
                                        Ver
                                    </button>
                                    <button
                                        @click="download(doc)"
                                        class="text-primary hover:underline text-xs"
                                    >
                                        Baixar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Sem resultados -->
                    <div
                        v-if="docs.data.length === 0"
                        class="p-8 text-center text-muted-foreground"
                    >
                        Nenhum documento encontrado.
                    </div>
                </div>

                <!-- Paginação -->
                <div
                    class="border-t px-4 py-3 flex items-center justify-between text-sm"
                >
                    <div class="text-muted-foreground">
                        Mostrando {{ docs.from }}–{{ docs.to }} de
                        {{ docs.total }}
                    </div>
                    <div class="flex gap-1">
                        <button
                            v-for="link in docs.links"
                            :key="link.label"
                            :disabled="!link.url"
                            @click="router.get(link.url)"
                            :class="[
                                'px-3 py-1 rounded-md text-xs',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-background border hover:bg-accent',
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
