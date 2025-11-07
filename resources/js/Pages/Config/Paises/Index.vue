<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import ConfigTabs from "../_ConfigTabs.vue";

const props = defineProps<{
    items: {
        data: { id: number; nome: string; iso: string }[];
        current_page: number;
        last_page: number;
        links: any[];
        per_page: number;
        total: number;
    };
    filters: { q?: string; per_page?: number };
}>();

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const q = ref(props.filters.q ?? "");
const open = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    nome: "",
    iso: "",
});

// Debounce search
let searchTimeout: NodeJS.Timeout;
watch(q, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 500);
});

function search() {
    router.get(
        route("config.paises.index"),
        { q: q.value },
        { preserveState: true, replace: true },
    );
}

function openCreate() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(item: { id: number; nome: string; iso: string }) {
    isEditing.value = true;
    editingId.value = item.id;
    form.reset();
    form.clearErrors();
    form.nome = item.nome;
    form.iso = item.iso;
    open.value = true;
}

function submit() {
    console.log("📤 Submit iniciado:", {
        isEditing: isEditing.value,
        editingId: editingId.value,
        formData: { nome: form.nome, iso: form.iso },
    });

    // Validação básica no frontend
    if (!form.nome.trim()) {
        alert("Por favor, preencha o nome do país");
        return;
    }
    if (!form.iso.trim()) {
        alert("Por favor, preencha o código ISO");
        return;
    }

    if (isEditing.value && editingId.value) {
        form.put(route("config.paises.update", editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                console.log("✅ País editado com sucesso");
                open.value = false;
                form.reset();
            },
            onError: (errors) => {
                console.error("❌ Erro ao editar:", errors);
                const firstError = Object.values(errors)[0];
                alert(firstError || "Erro ao editar país");
            },
        });
    } else {
        form.post(route("config.paises.store"), {
            preserveScroll: true,
            onSuccess: () => {
                console.log("✅ País criado com sucesso");
                open.value = false;
                form.reset();
            },
            onError: (errors) => {
                console.error("❌ Erro ao criar:", errors);
                const firstError = Object.values(errors)[0];
                alert(firstError || "Erro ao criar país");
            },
        });
    }
}

function destroyItem(id: number) {
    if (!confirm("Tem certeza que deseja remover este país?")) return;

    router.delete(route("config.paises.destroy", id), {
        preserveScroll: true,
        onSuccess: () => {
            console.log("🗑️ País removido com sucesso");
        },
        onError: (error) => {
            alert(error.message || "Erro ao remover país");
        },
    });
}
</script>

<template>
    <Head title="Configurações - Países" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-2">
                <h2 class="text-2xl font-semibold leading-tight">
                    Configurações
                </h2>
                <ConfigTabs />
            </div>
        </template>

        <div class="p-6 space-y-4">
            <!-- Flash Messages -->
            <div
                v-if="flashSuccess"
                class="p-4 bg-green-100 border border-green-400 text-green-700 rounded"
            >
                {{ flashSuccess }}
            </div>
            <div
                v-if="flashError"
                class="p-4 bg-red-100 border border-red-400 text-red-700 rounded"
            >
                {{ flashError }}
            </div>

            <div class="flex items-center gap-2">
                <Input
                    v-model="q"
                    placeholder="Pesquisar país ou código..."
                    class="w-80"
                />
                <Button @click="search">Filtrar</Button>
                <div class="flex-1"></div>
                <Button @click="openCreate">Novo País</Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                #
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Código
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Nome
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
                            v-for="row in props.items.data"
                            :key="row.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ row.id }}
                            </td>
                            <td class="px-4 py-2 text-sm font-mono font-bold">
                                {{ row.iso }}
                            </td>
                            <td class="px-4 py-2 text-sm">{{ row.nome }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="openEdit(row)"
                                        >Editar</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="destroyItem(row.id)"
                                        >Remover</Button
                                    >
                                </div>
                            </td>
                        </tr>

                        <tr v-if="props.items.data.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhum país encontrado
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div
                class="flex items-center justify-between text-sm text-slate-600"
            >
                <div>Total: {{ props.items.total }} países</div>
                <div class="flex items-center gap-4">
                    <div>
                        Página {{ props.items.current_page }} de
                        {{ props.items.last_page }}
                    </div>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in props.items.links"
                            :key="link.label"
                            size="sm"
                            variant="ghost"
                            :disabled="!link.url"
                            :class="{ 'font-bold': link.active }"
                            @click="router.get(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog para Criar/Editar - FORM SIMPLES -->
        <Dialog v-model:open="open">
            <DialogContent
                class="sm:max-w-[480px]"
                description="Formulário para adicionar ou editar países"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? "Editar País" : "Novo País"
                    }}</DialogTitle>
                </DialogHeader>

                <!-- ✅ CORREÇÃO: FORM HTML SIMPLES -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Nome -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium"
                            >Nome do País *</label
                        >
                        <Input
                            v-model="form.nome"
                            placeholder="Ex.: Portugal"
                            :disabled="form.processing"
                        />
                        <p v-if="form.errors.nome" class="text-sm text-red-600">
                            {{ form.errors.nome }}
                        </p>
                    </div>

                    <!-- ISO -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium"
                            >Código ISO (2 letras) *</label
                        >
                        <Input
                            v-model="form.iso"
                            placeholder="Ex.: PT"
                            :disabled="form.processing"
                            class="uppercase font-mono"
                            maxlength="2"
                        />
                        <p v-if="form.errors.iso" class="text-sm text-red-600">
                            {{ form.errors.iso }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="open = false"
                            :disabled="form.processing"
                            >Cancelar</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? "A guardar..."
                                    : isEditing
                                      ? "Guardar Alterações"
                                      : "Criar País"
                            }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
