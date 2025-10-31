<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

// shadcn form
import {
    Form,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/Components/ui/form";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";

import AsyncSelect from "@/Components/AsyncSelect.vue";

const props = defineProps<{ open: boolean; onClose: () => void }>();
const emit = defineEmits(["saved"]);

const linhas = ref([
    {
        artigo_id: null as number | null,
        fornecedor_id: null as number | null,
        quantidade: 1,
        preco_unitario: 0,
        preco_custo: null as number | null,
        referencia: null as string | null,
        descricao: null as string | null,
        subtotal: 0,
    },
]);

function addLinha() {
    linhas.value.push({
        artigo_id: null,
        fornecedor_id: null,
        quantidade: 1,
        preco_unitario: 0,
        preco_custo: null,
        referencia: null,
        descricao: null,
        subtotal: 0,
    });
}
function removeLinha(i: number) {
    linhas.value.splice(i, 1);
}

const form = useForm({
    cliente_id: null as number | null,
    estado: "rascunho",
    linhas: linhas.value,
});

function updateSubtotal(l: any) {
    l.subtotal = (Number(l.quantidade) || 0) * (Number(l.preco_unitario) || 0);
}
function total() {
    return linhas.value
        .reduce((a, l) => a + (Number(l.subtotal) || 0), 0)
        .toFixed(2);
}

function submit() {
    form.linhas = linhas.value;
    form.post(route("propostas.store"), {
        onSuccess: () => {
            emit("saved");
            props.onClose();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="onClose">
        <DialogContent class="max-w-4xl">
            <DialogHeader>
                <DialogTitle>Nova Proposta</DialogTitle>
            </DialogHeader>

            <Form @submit.prevent="submit" class="grid gap-4">
                <!-- Cliente -->
                <FormField name="cliente_id">
                    <FormItem>
                        <FormLabel>Cliente</FormLabel>
                        <FormControl>
                            <AsyncSelect
                                fetch-url="/ajax/clientes?q="
                                v-model="form.cliente_id"
                                placeholder="Pesquisar cliente por nome/NIF..."
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Linhas -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Linhas de Artigos</h3>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="addLinha"
                            >Adicionar linha</Button
                        >
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(l, i) in linhas"
                            :key="i"
                            class="grid grid-cols-12 gap-2 border rounded-md p-3"
                        >
                            <!-- Artigo -->
                            <div class="col-span-4">
                                <FormField :name="`linhas.${i}.artigo_id`">
                                    <FormItem>
                                        <FormLabel>Artigo</FormLabel>
                                        <FormControl>
                                            <AsyncSelect
                                                fetch-url="/ajax/artigos?q="
                                                v-model="l.artigo_id"
                                                @select="
                                                    (it: any) => {
                                                        l.referencia =
                                                            it.referencia;
                                                        l.descricao = it.nome;
                                                        if (!l.preco_unitario)
                                                            l.preco_unitario =
                                                                it.preco_venda ??
                                                                0;
                                                        updateSubtotal(l);
                                                    }
                                                "
                                                placeholder="Ref ou Nome"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>

                            <!-- Fornecedor -->
                            <div class="col-span-3">
                                <FormField :name="`linhas.${i}.fornecedor_id`">
                                    <FormItem>
                                        <FormLabel
                                            >Fornecedor (opcional)</FormLabel
                                        >
                                        <FormControl>
                                            <AsyncSelect
                                                fetch-url="/ajax/fornecedores?q="
                                                v-model="l.fornecedor_id"
                                                placeholder="Nome/NIF"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>

                            <!-- Quantidade -->
                            <div class="col-span-1">
                                <FormField :name="`linhas.${i}.quantidade`">
                                    <FormItem>
                                        <FormLabel>Qtd</FormLabel>
                                        <FormControl>
                                            <Input
                                                v-model.number="l.quantidade"
                                                type="number"
                                                min="0"
                                                step="0.001"
                                                @input="updateSubtotal(l)"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>

                            <!-- Preço -->
                            <div class="col-span-2">
                                <FormField :name="`linhas.${i}.preco_unitario`">
                                    <FormItem>
                                        <FormLabel>Preço</FormLabel>
                                        <FormControl>
                                            <Input
                                                v-model.number="
                                                    l.preco_unitario
                                                "
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                @input="updateSubtotal(l)"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>

                            <!-- Custo -->
                            <div class="col-span-2">
                                <FormField :name="`linhas.${i}.preco_custo`">
                                    <FormItem>
                                        <FormLabel>Custo (opcional)</FormLabel>
                                        <FormControl>
                                            <Input
                                                v-model.number="l.preco_custo"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>

                            <!-- Snapshot ref/nome -->
                            <div
                                class="col-span-10 text-xs text-muted-foreground"
                            >
                                <div>
                                    Ref: {{ l.referencia ?? "—" }} |
                                    {{ l.descricao ?? "—" }}
                                </div>
                            </div>

                            <!-- Subtotal -->
                            <div
                                class="col-span-2 text-right font-semibold self-end"
                            >
                                {{ (Number(l.subtotal) || 0).toFixed(2) }} €
                            </div>

                            <!-- Remover -->
                            <div class="col-span-12 text-right">
                                <Button
                                    variant="ghost"
                                    type="button"
                                    @click="removeLinha(i)"
                                    >Remover</Button
                                >
                            </div>
                        </div>
                    </div>

                    <div class="text-right text-sm">
                        <span class="font-semibold">Total:</span>
                        {{ total() }} €
                    </div>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <Button type="button" variant="outline" @click="onClose"
                        >Cancelar</Button
                    >
                    <Button type="submit">Guardar</Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
