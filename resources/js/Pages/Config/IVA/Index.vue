<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

import ConfigTabs from "../_ConfigTabs.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription, // ✅ IMPORT para acessibilidade
} from "@/components/ui/dialog";
import {
    Form as UiForm,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/components/ui/form";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

const props = defineProps<{
    items: {
        data: {
            id: number;
            label: string;
            percentagem: number | string | null;
            estado?: "ativo" | "inativo";
        }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { q?: string; estado?: string | null; per_page?: number };
    meta: {
        hasEstado: boolean;
        labelCol: string | null;
        percentCol: string | null;
    };
}>();

const q = ref(props.filters.q ?? "");
// Evitar value="" em shadcn Select
const estado = ref<string | null>(
    props.meta.hasEstado
        ? (props.filters.estado ?? "") === "" || props.filters.estado == null
            ? "all"
            : String(props.filters.estado)
        : null,
);

// filtro
function search() {
    router.get(
        route("config.iva.index"),
        {
            q: q.value,
            estado: estado.value === "all" ? "" : (estado.value ?? ""),
        },
        { preserveState: true, replace: true },
    );
}

// modal
const open = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    label: "",
    percentagem: "",
    estado: "ativo" as "ativo" | "inativo",
});

function openCreate() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.estado = "ativo";
    open.value = true;
}

function openEdit(row: any) {
    isEditing.value = true;
    editingId.value = row.id;
    form.reset();
    form.label = row.label ?? "";
    form.percentagem = row.percentagem != null ? String(row.percentagem) : "";
    if (props.meta.hasEstado)
        form.estado = row.estado === "inativo" ? "inativo" : "ativo";
    open.value = true;
}

// ⚠️ Handler para o Form do vee-validate/shadcn (sem .prevent!)
function onSubmit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    // normaliza vírgula→ponto antes do submit
    const payload = {
        ...form.data(),
        percentagem: form.percentagem
            ? String(form.percentagem).replace(",", ".")
            : "",
    };

    if (isEditing.value && editingId.value) {
        form.transform(() => payload).put(
            route("config.iva.update", editingId.value),
            options,
        );
    } else {
        form.transform(() => payload).post(route("config.iva.store"), options);
    }
}

function destroyItem(id: number) {
    if (!confirm("Remover esta taxa de IVA?")) return;
    router.delete(route("config.iva.destroy", id), { preserveScroll: true });
}

const rows = computed(() => props.items.data ?? []);
</script>

<template>
    <Head title="Configurações - IVA" />
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
            <div class="flex flex-wrap items-center gap-2">
                <Input
                    v-model="q"
                    placeholder="Pesquisar IVA..."
                    class="w-72"
                    @keyup.enter="search"
                />

                <Select v-if="props.meta.hasEstado" v-model="estado">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="ativo">Ativo</SelectItem>
                        <SelectItem value="inativo">Inativo</SelectItem>
                    </SelectContent>
                </Select>

                <Button @click="search">Filtrar</Button>
                <div class="flex-1"></div>
                <Button @click="openCreate">Novo IVA</Button>
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
                                {{
                                    props.meta.labelCol
                                        ? props.meta.labelCol === "nome"
                                            ? "Nome"
                                            : "Descrição"
                                        : "Etiqueta"
                                }}
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Percentagem
                            </th>
                            <th
                                v-if="props.meta.hasEstado"
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
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
                            v-for="row in rows"
                            :key="row.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ row.id }}
                            </td>
                            <td class="px-4 py-2 text-sm">{{ row.label }}</td>
                            <td class="px-4 py-2 text-right text-sm">
                                {{
                                    row.percentagem != null &&
                                    row.percentagem !== ""
                                        ? Number(row.percentagem).toFixed(2) +
                                          "%"
                                        : "—"
                                }}
                            </td>
                            <td
                                v-if="props.meta.hasEstado"
                                class="px-4 py-2 text-right text-sm"
                            >
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                                    :class="
                                        row.estado === 'ativo'
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-slate-200 text-slate-700'
                                    "
                                >
                                    {{ row.estado ?? "—" }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="openEdit(row)"
                                    >
                                        Editar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="destroyItem(row.id)"
                                    >
                                        Remover
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td
                                :colspan="props.meta.hasEstado ? 5 : 4"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dialog -->
        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-[520px]">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? "Editar IVA" : "Novo IVA"
                    }}</DialogTitle>
                    <!-- ✅ Descrição para eliminar o warning de acessibilidade -->
                    <DialogDescription>
                        Preencha os campos abaixo e confirme para
                        {{
                            isEditing
                                ? "guardar as alterações"
                                : "criar a taxa de IVA"
                        }}.
                    </DialogDescription>
                </DialogHeader>

                <!-- ⚠️ Sem .prevent aqui -->
                <UiForm @submit="onSubmit" class="space-y-4">
                    <FormField name="label">
                        <FormItem>
                            <FormLabel>
                                {{
                                    props.meta.labelCol
                                        ? props.meta.labelCol === "nome"
                                            ? "Nome"
                                            : "Descrição"
                                        : "Etiqueta"
                                }}
                            </FormLabel>
                            <FormControl>
                                <Input
                                    v-model="form.label"
                                    placeholder="Ex.: Normal, Intermédio, Reduzido..."
                                />
                            </FormControl>
                            <FormMessage v-if="form.errors.label">
                                {{ form.errors.label }}
                            </FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField name="percentagem">
                        <FormItem>
                            <FormLabel>Percentagem (%)</FormLabel>
                            <FormControl>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    v-model="form.percentagem"
                                    placeholder="23"
                                />
                            </FormControl>
                            <FormMessage v-if="form.errors.percentagem">
                                {{ form.errors.percentagem }}
                            </FormMessage>
                        </FormItem>
                    </FormField>

                    <FormField v-if="props.meta.hasEstado" name="estado">
                        <FormItem>
                            <FormLabel>Estado</FormLabel>
                            <FormControl>
                                <RadioGroup
                                    v-model="form.estado"
                                    class="flex gap-6"
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
                            <FormMessage v-if="form.errors.estado">
                                {{ form.errors.estado }}
                            </FormMessage>
                        </FormItem>
                    </FormField>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="open = false"
                        >
                            Cancelar
                        </Button>
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
