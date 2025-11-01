<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

import ConfigTabs from "../_ConfigTabs.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
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
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";

const props = defineProps<{
    items: {
        data: {
            id: number;
            referencia: string;
            nome: string;
            descricao: string | null;
            preco: number | string;
            foto_path: string | null;
            estado: "ativo" | "inativo";
            iva_id: number | null;
            iva_nome?: string | null;
            iva_percentagem?: number | string | null;
        }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        q?: string;
        iva_id?: number | null;
        estado?: string | null;
        per_page?: number;
    };
    ivaOptions: {
        id: number;
        label: string | null;
        percentagem: number | string | null;
    }[];
    filesRoute: string;
}>();

// --- filtros ---
const q = ref(props.filters.q ?? "");
const estado = ref<string | null>(props.filters.estado ?? null);
const iva_id = ref<number | null>(props.filters.iva_id ?? null);

function search() {
    router.get(
        route("artigos.index"),
        {
            q: q.value,
            estado: estado.value,
            iva_id: iva_id.value ?? "",
        },
        { preserveState: true, replace: true },
    );
}

// --- modal / form ---
const open = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

// para upload preview
const localPreview = ref<string | null>(null);

const form = useForm({
    referencia: "",
    nome: "",
    descricao: "",
    preco: "",
    iva_id: null as number | null,
    foto: null as File | null,
    observacoes: "",
    estado: "ativo" as "ativo" | "inativo",
    remove_foto: false as boolean,
});

function openCreate() {
    isEditing.value = false;
    editingId.value = null;
    localPreview.value = null;
    form.reset();
    form.estado = "ativo";
    open.value = true;
}
function openEdit(row: any) {
    isEditing.value = true;
    editingId.value = row.id;
    localPreview.value = null;
    form.reset();
    form.referencia = row.referencia;
    form.nome = row.nome;
    form.descricao = row.descricao ?? "";
    form.preco = String(row.preco ?? "");
    form.iva_id = row.iva_id ?? null;
    form.observacoes = "";
    form.estado = row.estado === "inativo" ? "inativo" : "ativo";
    form.remove_foto = false;
    open.value = true;
}
function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0] || null;
    form.foto = f;
    localPreview.value = f ? URL.createObjectURL(f) : null;
}
function removeFoto() {
    form.foto = null;
    localPreview.value = null;
    form.remove_foto = true;
}
function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };
    if (isEditing.value && editingId.value) {
        form.post(route("artigos.update", editingId.value), {
            ...options,
            _method: "put",
        });
    } else {
        form.post(route("artigos.store"), options);
    }
}
function destroyItem(id: number) {
    if (!confirm("Remover este artigo?")) return;
    router.delete(route("artigos.destroy", id), { preserveScroll: true });
}

const rows = computed(() => props.items.data ?? []);

function fotoSrc(row: any) {
    if (!row.foto_path) return null;
    // usa teu endpoint files.private.show
    const url = new URL(props.filesRoute);
    url.searchParams.set("path", row.foto_path);
    return url.toString();
}
</script>

<template>
    <Head title="Configurações - Artigos" />

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
            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-2">
                <Input
                    v-model="q"
                    placeholder="Pesquisar (ref., nome, descrição)..."
                    class="w-80"
                    @keyup.enter="search"
                />

                <!-- Estado -->
                <Select v-model="estado">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todos</SelectItem>
                        <SelectItem value="ativo">Ativo</SelectItem>
                        <SelectItem value="inativo">Inativo</SelectItem>
                    </SelectContent>
                </Select>

                <!-- IVA -->
                <Select v-model="iva_id">
                    <SelectTrigger class="w-52">
                        <SelectValue placeholder="IVA" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Todos</SelectItem>
                        <SelectItem
                            v-for="o in ivaOptions"
                            :key="o.id"
                            :value="o.id"
                        >
                            {{ o.label ?? "IVA " + (o.percentagem ?? "") }}
                            <span v-if="o.percentagem != null">
                                ({{ o.percentagem }}%)</span
                            >
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button @click="search">Filtrar</Button>

                <div class="flex-1"></div>
                <Button @click="openCreate">Novo Artigo</Button>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-100">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Ref.
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Foto
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Nome
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Descrição
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Preço
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                IVA
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
                            v-for="row in rows"
                            :key="row.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ row.referencia }}
                            </td>
                            <td class="px-4 py-2">
                                <div
                                    class="h-10 w-10 rounded overflow-hidden bg-slate-100 border"
                                >
                                    <img
                                        v-if="fotoSrc(row)"
                                        :src="fotoSrc(row)!"
                                        alt=""
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ row.nome }}</td>
                            <td class="px-4 py-2 text-sm">
                                <span class="line-clamp-2 text-slate-700">{{
                                    row.descricao
                                }}</span>
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm font-medium"
                            >
                                {{
                                    Number(row.preco ?? 0).toLocaleString(
                                        "pt-PT",
                                        { style: "currency", currency: "EUR" },
                                    )
                                }}
                            </td>
                            <td class="px-4 py-2 text-right text-sm">
                                <span v-if="row.iva_percentagem != null"
                                    >{{ row.iva_percentagem }}%</span
                                >
                                <span v-else>—</span>
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

                        <tr v-if="rows.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dialog Form -->
        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-[720px]">
                <DialogHeader
                    ><DialogTitle>{{
                        isEditing ? "Editar Artigo" : "Novo Artigo"
                    }}</DialogTitle></DialogHeader
                >

                <UiForm
                    @submit.prevent="submit"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <FormField name="referencia">
                        <FormItem>
                            <FormLabel>Referência</FormLabel>
                            <FormControl
                                ><Input v-model="form.referencia"
                            /></FormControl>
                            <FormMessage v-if="form.errors.referencia">{{
                                form.errors.referencia
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="nome">
                        <FormItem>
                            <FormLabel>Nome</FormLabel>
                            <FormControl
                                ><Input v-model="form.nome"
                            /></FormControl>
                            <FormMessage v-if="form.errors.nome">{{
                                form.errors.nome
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="descricao">
                        <FormItem class="md:col-span-2">
                            <FormLabel>Descrição</FormLabel>
                            <FormControl
                                ><Textarea v-model="form.descricao" rows="3"
                            /></FormControl>
                            <FormMessage v-if="form.errors.descricao">{{
                                form.errors.descricao
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="preco">
                        <FormItem>
                            <FormLabel>Preço</FormLabel>
                            <FormControl
                                ><Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.preco"
                            /></FormControl>
                            <FormMessage v-if="form.errors.preco">{{
                                form.errors.preco
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="iva_id">
                        <FormItem>
                            <FormLabel>IVA</FormLabel>
                            <FormControl>
                                <Select v-model="form.iva_id">
                                    <SelectTrigger
                                        ><SelectValue
                                            placeholder="Selecione o IVA"
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="o in ivaOptions"
                                            :key="o.id"
                                            :value="o.id"
                                        >
                                            {{
                                                o.label ??
                                                "IVA " + (o.percentagem ?? "")
                                            }}
                                            <span v-if="o.percentagem != null">
                                                ({{ o.percentagem }}%)</span
                                            >
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormControl>
                            <FormMessage v-if="form.errors.iva_id">{{
                                form.errors.iva_id
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="foto">
                        <FormItem>
                            <FormLabel>Foto</FormLabel>
                            <FormControl>
                                <div class="flex items-center gap-4">
                                    <div
                                        class="h-16 w-16 rounded overflow-hidden bg-slate-100 border"
                                    >
                                        <img
                                            v-if="localPreview"
                                            :src="localPreview"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <Input
                                        type="file"
                                        accept="image/*"
                                        @change="onFileChange"
                                    />
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        @click="removeFoto"
                                        >Remover</Button
                                    >
                                </div>
                            </FormControl>
                            <FormMessage v-if="form.errors.foto">{{
                                form.errors.foto
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="observacoes">
                        <FormItem class="md:col-span-2">
                            <FormLabel>Observações</FormLabel>
                            <FormControl
                                ><Textarea v-model="form.observacoes" rows="2"
                            /></FormControl>
                            <FormMessage v-if="form.errors.observacoes">{{
                                form.errors.observacoes
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="estado">
                        <FormItem>
                            <FormLabel>Estado</FormLabel>
                            <FormControl>
                                <RadioGroup
                                    v-model="form.estado"
                                    class="flex gap-4"
                                >
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem
                                            id="ativo"
                                            value="ativo"
                                        />
                                        <label for="ativo" class="text-sm"
                                            >Ativo</label
                                        >
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem
                                            id="inativo"
                                            value="inativo"
                                        />
                                        <label for="inativo" class="text-sm"
                                            >Inativo</label
                                        >
                                    </div>
                                </RadioGroup>
                            </FormControl>
                            <FormMessage v-if="form.errors.estado">{{
                                form.errors.estado
                            }}</FormMessage>
                        </FormItem>
                    </FormField>

                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
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
