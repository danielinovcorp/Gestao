<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm as useInertiaForm, Link } from "@inertiajs/vue3";
import {
    Form,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

const props = defineProps<{
    clientes: Array<{ id: number; nome: string }>;
    fornecedores: Array<{ id: number; nome: string }>;
}>();

const form = useInertiaForm({
    cliente_id: null as number | null,
    validade: null as string | null,
    estado: "rascunho" as "rascunho" | "fechado",
    linhas: [] as Array<{
        artigo_id: number | null;
        descricao: string;
        quantidade: number;
        preco: number;
        iva_id: number | null;
        fornecedor_id: number | null;
    }>,
});

function addLinha() {
    form.linhas.push({
        artigo_id: null,
        descricao: "",
        quantidade: 1,
        preco: 0,
        iva_id: null,
        fornecedor_id: null,
    });
}
function removeLinha(i: number) {
    form.linhas.splice(i, 1);
}

function submit(close = false) {
    form.estado = close ? "fechado" : "rascunho";
    form.post(route("encomendas.clientes.store"));
}

// (opcional) busca de artigos: troca por teu endpoint quando criares /api/artigos?q=
const busca = {
    q: "",
    resultados: [] as Array<{ id: number; referencia: string; nome: string }>,
};
async function pesquisar() {
    // const { data } = await axios.get('/api/artigos', { params:{ q: busca.q } })
    // busca.resultados = data
    busca.resultados = []; // placeholder até criares o endpoint
}
</script>

<template>
    <Head title="Nova Encomenda - Cliente" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Nova Encomenda – Cliente
                </h2>
                <Link
                    :href="route('encomendas.clientes.index')"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    Voltar à lista
                </Link>
            </div>
        </template>

        <div class="p-6">
            <Form class="space-y-6" @submit.prevent="submit(false)">
                <!-- Número (gerado ao FECHAR) -->
                <div>
                    <FormLabel>Número</FormLabel>
                    <Input
                        disabled
                        placeholder="Gerado ao fechar (EC-YYYY-####)"
                    />
                </div>

                <!-- Cliente -->
                <FormField name="cliente_id">
                    <FormItem>
                        <FormLabel>Cliente</FormLabel>
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
                                    >{{ c.nome }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <FormMessage v-if="form.errors.cliente_id">{{
                            form.errors.cliente_id
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <!-- Validade -->
                <FormField name="validade">
                    <FormItem>
                        <FormLabel>Validade</FormLabel>
                        <FormControl>
                            <Input type="date" v-model="form.validade" />
                        </FormControl>
                        <FormMessage v-if="form.errors.validade">{{
                            form.errors.validade
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <!-- Linhas dos Artigos -->
                <div class="rounded-xl border p-4 space-y-3">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <FormLabel
                                >Pesquisar Artigo (ref. ou nome)</FormLabel
                            >
                            <Input
                                v-model="busca.q"
                                @keydown.enter.prevent="pesquisar"
                                placeholder="Ex.: ABC123 ou 'Cabo HDMI'"
                            />
                        </div>
                        <Button type="button" @click="pesquisar"
                            >Pesquisar</Button
                        >
                    </div>

                    <div
                        v-if="busca.resultados.length"
                        class="text-sm text-slate-500"
                    >
                        Resultados (clique para adicionar)
                    </div>
                    <div v-if="busca.resultados.length" class="grid gap-1">
                        <button
                            v-for="a in busca.resultados"
                            :key="a.id"
                            type="button"
                            class="text-left px-3 py-2 rounded hover:bg-slate-50"
                            @click="
                                form.linhas.push({
                                    artigo_id: a.id,
                                    descricao: a.nome,
                                    quantidade: 1,
                                    preco: 0,
                                    iva_id: null,
                                    fornecedor_id: null,
                                })
                            "
                        >
                            <div class="font-medium">{{ a.nome }}</div>
                            <div class="text-xs text-slate-500">
                                Ref: {{ a.referencia }}
                            </div>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-slate-600">
                                    <th class="px-2 py-1 text-left">
                                        Artigo/Descrição
                                    </th>
                                    <th class="px-2 py-1 text-right">Qtd</th>
                                    <th class="px-2 py-1 text-right">Preço</th>
                                    <th class="px-2 py-1">Fornecedor</th>
                                    <th class="px-2 py-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(l, i) in form.linhas"
                                    :key="i"
                                    class="border-t"
                                >
                                    <td class="px-2 py-1">
                                        <Input
                                            v-model="l.descricao"
                                            placeholder="Descrição do artigo"
                                        />
                                    </td>
                                    <td class="px-2 py-1">
                                        <Input
                                            type="number"
                                            step="0.001"
                                            class="text-right"
                                            v-model.number="l.quantidade"
                                        />
                                    </td>
                                    <td class="px-2 py-1">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            class="text-right"
                                            v-model.number="l.preco"
                                        />
                                    </td>
                                    <td class="px-2 py-1">
                                        <Select v-model="l.fornecedor_id">
                                            <SelectTrigger
                                                ><SelectValue
                                                    placeholder="Fornecedor"
                                            /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="null"
                                                    >—</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="f in props.fornecedores"
                                                    :key="f.id"
                                                    :value="f.id"
                                                    >{{ f.nome }}</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td class="px-2 py-1 text-right">
                                        <Button
                                            variant="ghost"
                                            type="button"
                                            @click="removeLinha(i)"
                                            >Remover</Button
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="addLinha"
                            >Adicionar linha</Button
                        >
                        <div
                            v-if="form.errors.linhas"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.linhas }}
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing"
                        >Guardar rascunho</Button
                    >
                    <Button
                        type="button"
                        variant="secondary"
                        :disabled="form.processing"
                        @click="submit(true)"
                        >Guardar e Fechar</Button
                    >
                </div>
            </Form>
        </div>
    </AuthenticatedLayout>
</template>
