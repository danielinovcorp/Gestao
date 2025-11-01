<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Form as UiForm,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/components/ui/form";
import ConfigTabs from "../_ConfigTabs.vue";

const props = defineProps<{
    items: {
        data: { id: number; nome: string }[];
        current_page: number;
        last_page: number;
        links: any[];
        per_page: number;
        total: number;
    };
    filters: { q?: string; per_page?: number };
}>();

const q = ref(props.filters.q ?? "");
const open = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ nome: "" });

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
    open.value = true;
}
function openEdit(item: { id: number; nome: string }) {
    isEditing.value = true;
    editingId.value = item.id;
    form.reset();
    form.nome = item.nome;
    open.value = true;
}
function submit() {
    if (isEditing.value && editingId.value) {
        form.put(route("config.paises.update", editingId.value), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    } else {
        form.post(route("config.paises.store"), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    }
}
function destroyItem(id: number) {
    if (!confirm("Remover este país?")) return;
    router.delete(route("config.paises.destroy", id), { preserveScroll: true });
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
            <div class="flex items-center gap-2">
                <Input
                    v-model="q"
                    placeholder="Pesquisar país..."
                    class="w-80"
                    @keyup.enter="search"
                />
                <Button @click="search">Filtrar</Button>
                <div class="flex-1"></div>
                <Button @click="openCreate">Novo</Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <!-- Cabeçalho cinza -->
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
                                Nome
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>

                    <!-- Corpo branco -->
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr
                            v-for="row in props.items.data"
                            :key="row.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ row.id }}
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
                                colspan="3"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex items-center justify-between text-sm text-slate-600"
            >
                <div>Total: {{ props.items.total }}</div>
                <div>
                    Página {{ props.items.current_page }} de
                    {{ props.items.last_page }}
                </div>
            </div>
        </div>

        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-[480px]">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? "Editar País" : "Novo País"
                    }}</DialogTitle>
                </DialogHeader>

                <UiForm @submit.prevent="submit" class="space-y-4">
                    <FormField name="nome">
                        <FormItem>
                            <FormLabel>Nome</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="form.nome"
                                    placeholder="Ex.: Portugal"
                                />
                            </FormControl>
                            <FormMessage v-if="form.errors.nome">{{
                                form.errors.nome
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="open = false"
                            >Cancelar</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? "A guardar..."
                                    : isEditing
                                      ? "Guardar"
                                      : "Criar"
                            }}
                        </Button>
                    </div>
                </UiForm>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
