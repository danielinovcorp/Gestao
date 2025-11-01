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
        data: { id: number; nome: string; cor_hex: string | null }[];
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

// form: nome + cor_hex
const form = useForm({ nome: "", cor_hex: "#4338CA" }); // default indigo-600

function search() {
    router.get(
        route("config.calendario.tipos.index"),
        { q: q.value },
        { preserveState: true, replace: true },
    );
}
function openCreate() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.cor_hex = "#4338CA";
    open.value = true;
}
function openEdit(row: { id: number; nome: string; cor_hex: string | null }) {
    isEditing.value = true;
    editingId.value = row.id;
    form.reset();
    form.nome = row.nome;
    form.cor_hex = row.cor_hex ?? "#4338CA";
    open.value = true;
}
function submit() {
    if (isEditing.value && editingId.value) {
        form.put(route("config.calendario.tipos.update", editingId.value), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    } else {
        form.post(route("config.calendario.tipos.store"), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    }
}
function destroyItem(id: number) {
    if (!confirm("Remover este tipo?")) return;
    router.delete(route("config.calendario.tipos.destroy", id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Configurações - Calendário (Tipos)" />
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
                        placeholder="Pesquisar tipo..."
                        class="w-72"
                        @keyup.enter="search"
                    />
                    <Button @click="search">Filtrar</Button>
                    <Button @click="openCreate">Novo Tipo</Button>
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
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Cor
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
                            <td class="px-4 py-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-5 w-5 rounded-full border"
                                        :style="{
                                            backgroundColor:
                                                row.cor_hex ?? '#fff',
                                        }"
                                    ></span>
                                    <span class="text-slate-600">{{
                                        row.cor_hex || "-"
                                    }}</span>
                                </div>
                            </td>
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
                        isEditing ? "Editar Tipo" : "Novo Tipo"
                    }}</DialogTitle></DialogHeader
                >

                <UiForm @submit.prevent="submit" class="space-y-4">
                    <FormField name="nome">
                        <FormItem>
                            <FormLabel>Nome</FormLabel>
                            <FormControl
                                ><Input
                                    v-model="form.nome"
                                    placeholder="Ex.: Reunião"
                            /></FormControl>
                            <FormMessage v-if="form.errors.nome">{{
                                form.errors.nome
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="cor_hex">
                        <FormItem>
                            <FormLabel>Cor</FormLabel>
                            <FormControl>
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="form.cor_hex"
                                        type="color"
                                        class="h-9 w-12 rounded border cursor-pointer"
                                    />
                                    <Input
                                        v-model="form.cor_hex"
                                        placeholder="#4338CA"
                                        class="w-36"
                                    />
                                </div>
                            </FormControl>
                            <FormMessage v-if="form.errors.cor_hex">{{
                                form.errors.cor_hex
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
