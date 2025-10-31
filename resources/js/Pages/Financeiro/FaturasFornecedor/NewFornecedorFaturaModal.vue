<script setup lang="ts">
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
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
    fornecedores: { id: number; nome: string }[];
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "created"): void;
}>();

const internalOpen = ref(props.open);
watch(
    () => props.open,
    (v) => (internalOpen.value = v),
);

const form = useForm({
    numero: "",
    data_fatura: "",
    data_vencimento: "",
    fornecedor_id: "",
    encomenda_fornecedor_id: "",
    valor_total: 0,
    estado: "pendente",
    documento: null as File | null,
    comprovativo: null as File | null,
});

const showComprovativoPrompt = ref(false);
watch(
    () => form.estado,
    (val, old) => {
        if (old !== "paga" && val === "paga")
            showComprovativoPrompt.value = true;
    },
);

function onFile(e: Event, key: "documento" | "comprovativo") {
    const t = e.target as HTMLInputElement;
    if (t.files && t.files[0]) form[key] = t.files[0];
}

function close() {
    emit("close");
    form.reset();
    form.clearErrors();
    showComprovativoPrompt.value = false;
}

function submit() {
    form.post(route("financeiro.faturas-fornecedor.store"), {
        forceFormData: true,
        onSuccess: () => emit("created"),
    });
}
</script>

<template>
    <Dialog :open="internalOpen" @update:open="(val) => (val ? null : close())">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Nova Fatura de Fornecedor</DialogTitle>
            </DialogHeader>

            <UiForm
                @submit.prevent="submit"
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
            >
                <FormField name="numero">
                    <FormItem>
                        <FormLabel>Número</FormLabel>
                        <FormControl
                            ><Input
                                v-model="form.numero"
                                placeholder="FT-2025-001"
                        /></FormControl>
                        <FormMessage v-if="form.errors.numero">{{
                            form.errors.numero
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="valor_total">
                    <FormItem>
                        <FormLabel>Valor Total</FormLabel>
                        <FormControl
                            ><Input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.valor_total"
                        /></FormControl>
                        <FormMessage v-if="form.errors.valor_total">{{
                            form.errors.valor_total
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="data_fatura">
                    <FormItem>
                        <FormLabel>Data da Fatura</FormLabel>
                        <FormControl
                            ><Input type="date" v-model="form.data_fatura"
                        /></FormControl>
                        <FormMessage v-if="form.errors.data_fatura">{{
                            form.errors.data_fatura
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="data_vencimento">
                    <FormItem>
                        <FormLabel>Data de Vencimento</FormLabel>
                        <FormControl
                            ><Input type="date" v-model="form.data_vencimento"
                        /></FormControl>
                        <FormMessage v-if="form.errors.data_vencimento">{{
                            form.errors.data_vencimento
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="fornecedor_id">
                    <FormItem>
                        <FormLabel>Fornecedor</FormLabel>
                        <FormControl>
                            <Select v-model="form.fornecedor_id">
                                <SelectTrigger
                                    ><SelectValue
                                        placeholder="Selecionar fornecedor"
                                /></SelectTrigger>
                                <SelectContent class="max-h-72">
                                    <SelectItem
                                        v-for="f in fornecedores"
                                        :key="f.id"
                                        :value="String(f.id)"
                                    >
                                        {{ f.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </FormControl>
                        <FormMessage v-if="form.errors.fornecedor_id">{{
                            form.errors.fornecedor_id
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="documento">
                    <FormItem>
                        <FormLabel>Documento (anexo)</FormLabel>
                        <FormControl
                            ><Input
                                type="file"
                                @change="(e) => onFile(e, 'documento')"
                        /></FormControl>
                        <FormMessage v-if="form.errors.documento">{{
                            form.errors.documento
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <FormField name="estado">
                    <FormItem>
                        <FormLabel>Estado</FormLabel>
                        <FormControl>
                            <Select v-model="form.estado">
                                <SelectTrigger
                                    ><SelectValue
                                        placeholder="Selecionar estado"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pendente"
                                        >Pendente de Pagamento</SelectItem
                                    >
                                    <SelectItem value="paga">Paga</SelectItem>
                                </SelectContent>
                            </Select>
                        </FormControl>
                        <FormMessage v-if="form.errors.estado">{{
                            form.errors.estado
                        }}</FormMessage>
                    </FormItem>
                </FormField>

                <div
                    v-if="showComprovativoPrompt"
                    class="md:col-span-2 rounded-lg border p-3 bg-yellow-50"
                >
                    <div class="font-medium mb-2">
                        Pretende enviar o comprovativo ao Fornecedor?
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm block mb-1"
                                >Anexar Comprovativo</label
                            >
                            <Input
                                type="file"
                                @change="(e) => onFile(e, 'comprovativo')"
                            />
                        </div>
                        <div class="self-end text-sm text-gray-600">
                            O email será enviado automaticamente ao gravar.
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                    <Button type="button" variant="secondary" @click="close"
                        >Cancelar</Button
                    >
                    <Button type="submit">Criar</Button>
                </div>
            </UiForm>
        </DialogContent>
    </Dialog>
</template>
