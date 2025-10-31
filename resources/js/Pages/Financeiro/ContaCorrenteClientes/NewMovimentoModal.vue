<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogClose,
} from "@/components/ui/dialog";
import {
    Form as UiForm,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

const props = defineProps<{
    open: boolean;
    clientes: { id: number; nome: string }[];
}>();
const emit = defineEmits<{ (e: "close"): void; (e: "created"): void }>();

const internalOpen = ref(props.open);
watch(
    () => props.open,
    (v) => (internalOpen.value = v),
);
watch(internalOpen, (v) => {
    if (!v) {
        emit("close");
        form.reset();
        form.clearErrors();
    }
});

/** ⚠️ NUNCA use chave "data" dentro do useForm (colide com form.data()) */
const form = useForm({
    cliente_id: "",
    data_lancamento: "", // yyyy-MM-dd
    descricao: "",
    documento_tipo: "",
    documento_numero: "",
    debito: 0,
    credito: 0,
});

function submit() {
    // mapeia data_lancamento -> data (backend)
    form.transform((payload: any) => ({
        ...payload,
        data: payload.data_lancamento,
    })).post(route("financeiro.conta-corrente-clientes.store"), {
        onSuccess: () => emit("created"),
    });
}
</script>

<template>
    <Dialog v-model:open="internalOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogClose
                class="absolute right-4 top-4 rounded-md opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                aria-label="Fechar"
            />
            <DialogHeader>
                <DialogTitle>Novo Lançamento</DialogTitle>
                <DialogDescription id="novo-lancamento-desc">
                    Registe um débito ou crédito para o cliente selecionado.
                </DialogDescription>
            </DialogHeader>

            <UiForm
                @submit.prevent="submit"
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
            >
                <FormField name="cliente_id">
                    <FormItem>
                        <FormLabel>Cliente</FormLabel>
                        <FormControl>
                            <Select v-model="form.cliente_id">
                                <SelectTrigger
                                    ><SelectValue
                                        placeholder="Selecionar cliente"
                                /></SelectTrigger>
                                <SelectContent class="max-h-72">
                                    <SelectItem
                                        v-for="c in props.clientes"
                                        :key="c.id"
                                        :value="String(c.id)"
                                    >
                                        {{ c.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </FormControl>
                        <FormMessage v-if="form.errors.cliente_id">{{
                            form.errors.cliente_id
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="data_lancamento">
                    <FormItem>
                        <FormLabel>Data</FormLabel>
                        <FormControl>
                            <!-- valor deve ser yyyy-MM-dd -->
                            <Input type="date" v-model="form.data_lancamento" />
                        </FormControl>
                        <FormMessage v-if="form.errors.data">{{
                            form.errors.data
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="documento_tipo">
                    <FormItem>
                        <FormLabel>Documento</FormLabel>
                        <FormControl>
                            <Select v-model="form.documento_tipo">
                                <SelectTrigger
                                    ><SelectValue placeholder="Tipo"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="fatura"
                                        >Fatura</SelectItem
                                    >
                                    <SelectItem value="recibo"
                                        >Recibo</SelectItem
                                    >
                                    <SelectItem value="nota_credito"
                                        >Nota de Crédito</SelectItem
                                    >
                                    <SelectItem value="ajuste"
                                        >Ajuste</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormControl>
                    </FormItem>
                </FormField>

                <FormField name="documento_numero">
                    <FormItem>
                        <FormLabel>Número</FormLabel>
                        <FormControl
                            ><Input v-model="form.documento_numero"
                        /></FormControl>
                    </FormItem>
                </FormField>

                <FormField name="descricao" class="md:col-span-2">
                    <FormItem>
                        <FormLabel>Descrição</FormLabel>
                        <FormControl
                            ><Input v-model="form.descricao"
                        /></FormControl>
                    </FormItem>
                </FormField>

                <FormField name="debito">
                    <FormItem>
                        <FormLabel>Débito</FormLabel>
                        <FormControl
                            ><Input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.debito"
                        /></FormControl>
                    </FormItem>
                </FormField>

                <FormField name="credito">
                    <FormItem>
                        <FormLabel>Crédito</FormLabel>
                        <FormControl
                            ><Input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.credito"
                        /></FormControl>
                    </FormItem>
                </FormField>

                <div class="md:col-span-2 flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="internalOpen = false"
                        >Cancelar</Button
                    >
                    <Button type="submit">Inserir</Button>
                </div>
            </UiForm>
        </DialogContent>
    </Dialog>
</template>
