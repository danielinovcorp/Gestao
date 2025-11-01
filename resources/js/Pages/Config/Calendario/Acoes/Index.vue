<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import ConfigTabs from "../../_ConfigTabs.vue";
import SwitchTabs from "../_Switch.vue";

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

const props = defineProps<{
    items: {
        data: { id: number; nome: string }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { q?: string };
}>();

const q = ref(props.filters.q ?? "");
const open = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ nome: "" });

function search() {
    router.get(
        route("config.calendario.acoes.index"),
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
function openEdit(row: { id: number; nome: string }) {
    isEditing.value = true;
    editingId.value = row.id;
    form.reset();
    form.nome = row.nome;
    open.value = true;
}
function submit() {
    if (isEditing.value && editingId.value) {
        form.put(route("config.calendario.acoes.update", editingId.value), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    } else {
        form.post(route("config.calendario.acoes.store"), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    }
}
function destroyItem(id: number) {
    if (!confirm("Remover esta ação?")) return;
    router.delete(route("config.calendario.acoes.destroy", id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Configurações - Calendário (Ações)" />
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
            <div class="flex items-center justify-between">
                <SwitchTabs />
                <div class="flex items-center gap-2">
                    <Input
                        v-model="q"
                        placeholder="Pesquisar ação..."
                        class="w-72"
                        @keyup.enter="search"
                    />
                    <Button @click="search">Filtrar</Button>
                    <Button @click="openCreate">Nova Ação</Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-100">
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
        </div>

        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-[520px]">
                <DialogHeader
                    ><DialogTitle>{{
                        isEditing ? "Editar Ação" : "Nova Ação"
                    }}</DialogTitle></DialogHeader
                >

                <UiForm @submit.prevent="submit" class="space-y-4">
                    <FormField name="nome">
                        <FormItem>
                            <FormLabel>Nome</FormLabel>
                            <FormControl
                                ><Input
                                    v-model="form.nome"
                                    placeholder="Ex.: Chamada, Email, Visita"
                            /></FormControl>
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
