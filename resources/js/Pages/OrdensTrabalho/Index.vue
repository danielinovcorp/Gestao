<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
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
    Form,
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

// Props vindas do controller
const props = defineProps<{
    ordens: any;
    filters: { search?: string; estado?: string };
    clientes: Array<{ id: number; nome: string }>;
    responsaveis: Array<{ id: number; name: string }>;
    estados: string[];
}>();

// ----------------------- Filtros -----------------------
const search = ref(props.filters.search || "");
const estado = ref(props.filters.estado || "");

watch([search, estado], () => {
    router.get(
        route("ordens.index"),
        { search: search.value, estado: estado.value },
        { preserveState: true, replace: true },
    );
});

// ----------------------- Dialog + Form -----------------------
const showDialog = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);

const form = useForm({
    cliente_id: null as number | null,
    responsavel_id: null as number | null,
    descricao: "",
    data_inicio: "",
    data_fim: "",
    estado: "pendente",
    observacoes: "",
});

function openCreate() {
    isEditing.value = false;
    currentId.value = null;
    form.reset();
    form.estado = "pendente";
    showDialog.value = true;
}

function openEdit(row: any) {
    isEditing.value = true;
    currentId.value = row.id;
    form.reset();
    form.cliente_id = row.cliente_id;
    form.responsavel_id = row.responsavel_id;
    form.descricao = row.descricao ?? "";
    form.data_inicio = row.data_inicio ?? "";
    form.data_fim = row.data_fim ?? "";
    form.estado = row.estado ?? "pendente";
    form.observacoes = row.observacoes ?? "";
    showDialog.value = true;
}

function submit() {
    if (isEditing.value && currentId.value) {
        form.put(route("ordens.update", currentId.value), {
            onSuccess: () => (showDialog.value = false),
        });
    } else {
        form.post(route("ordens.store"), {
            onSuccess: () => (showDialog.value = false),
        });
    }
}

function removeRow(id: number) {
    if (!confirm("Deseja remover esta OT?")) return;
    router.delete(route("ordens.destroy", id));
}
</script>

<template>
    <Head title="Ordens de Trabalho" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Ordens de Trabalho
            </h2>
        </template>

        <div class="p-6 space-y-6">
            <!-- Filtros -->
            <div
                class="grid gap-3 sm:grid-cols-3 bg-white p-4 rounded-xl shadow"
            >
                <Input
                    v-model="search"
                    placeholder="Pesquisar por número ou descrição..."
                />
                <Select v-model="estado">
                    <SelectTrigger
                        ><SelectValue placeholder="Estado"
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem
                            v-for="e in props.estados"
                            :key="e"
                            :value="e"
                        >
                            {{ e === "em_execucao" ? "em execução" : e }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <div class="flex justify-end">
                    <Button @click="openCreate">+ Nova OT</Button>
                </div>
            </div>

            <!-- Tabela -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Número
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Cliente
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Responsável
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Estado
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Início
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase"
                            >
                                Fim
                            </th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="row in props.ordens.data"
                            :key="row.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-2 font-medium">
                                {{ row.numero }}
                            </td>
                            <td class="px-4 py-2">{{ row.cliente?.nome }}</td>
                            <td class="px-4 py-2">
                                {{ row.responsavel?.name }}
                            </td>
                            <td class="px-4 py-2 capitalize">
                                {{
                                    row.estado === "em_execucao"
                                        ? "em execução"
                                        : row.estado
                                }}
                            </td>
                            <td class="px-4 py-2">
                                {{ row.data_inicio ?? "—" }}
                            </td>
                            <td class="px-4 py-2">{{ row.data_fim ?? "—" }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <Button variant="outline" @click="openEdit(row)"
                                    >Editar</Button
                                >
                                <Button
                                    variant="destructive"
                                    @click="removeRow(row.id)"
                                    >Remover</Button
                                >
                            </td>
                        </tr>
                        <tr v-if="!props.ordens.data.length">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-gray-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginação simples -->
                <div class="flex items-center justify-between p-4">
                    <div class="text-sm text-gray-500">
                        Página {{ props.ordens.current_page }} de
                        {{ props.ordens.last_page }}
                    </div>
                    <div class="space-x-2">
                        <Button
                            variant="outline"
                            :disabled="!props.ordens.prev_page_url"
                            @click="
                                router.get(
                                    props.ordens.prev_page_url!,
                                    {},
                                    { preserveState: true },
                                )
                            "
                            >Anterior</Button
                        >
                        <Button
                            variant="outline"
                            :disabled="!props.ordens.next_page_url"
                            @click="
                                router.get(
                                    props.ordens.next_page_url!,
                                    {},
                                    { preserveState: true },
                                )
                            "
                            >Próxima</Button
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog -->
        <Dialog v-model:open="showDialog">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? "Editar OT" : "Nova OT"
                    }}</DialogTitle>
                </DialogHeader>

                <Form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField name="cliente_id">
                            <FormItem>
                                <FormLabel>Cliente</FormLabel>
                                <Select v-model="form.cliente_id">
                                    <SelectTrigger
                                        ><SelectValue
                                            placeholder="Selecione o cliente"
                                    /></SelectTrigger>
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
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField name="responsavel_id">
                            <FormItem>
                                <FormLabel>Responsável</FormLabel>
                                <Select v-model="form.responsavel_id">
                                    <SelectTrigger
                                        ><SelectValue
                                            placeholder="Selecione o responsável"
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="u in props.responsaveis"
                                            :key="u.id"
                                            :value="u.id"
                                        >
                                            {{ u.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <FormField name="descricao">
                        <FormItem>
                            <FormLabel>Descrição</FormLabel>
                            <FormControl
                                ><Textarea v-model="form.descricao" rows="4"
                            /></FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <FormField name="data_inicio">
                            <FormItem>
                                <FormLabel>Data Início</FormLabel>
                                <FormControl
                                    ><Input
                                        type="date"
                                        v-model="form.data_inicio"
                                /></FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                        <FormField name="data_fim">
                            <FormItem>
                                <FormLabel>Data Fim</FormLabel>
                                <FormControl
                                    ><Input type="date" v-model="form.data_fim"
                                /></FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                        <FormField name="estado">
                            <FormItem>
                                <FormLabel>Estado</FormLabel>
                                <Select v-model="form.estado">
                                    <SelectTrigger
                                        ><SelectValue
                                            placeholder="Selecione o estado"
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="e in props.estados"
                                            :key="e"
                                            :value="e"
                                        >
                                            {{
                                                e === "em_execucao"
                                                    ? "em execução"
                                                    : e
                                            }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <FormField name="observacoes">
                        <FormItem>
                            <FormLabel>Observações</FormLabel>
                            <FormControl
                                ><Textarea v-model="form.observacoes" rows="3"
                            /></FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showDialog = false"
                            >Cancelar</Button
                        >
                        <Button :disabled="form.processing" type="submit">
                            {{ isEditing ? "Guardar" : "Criar" }}
                        </Button>
                    </div>
                </Form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
