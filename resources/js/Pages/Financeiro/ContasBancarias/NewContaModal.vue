<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
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
import { Switch } from "@/components/ui/switch";

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ (e: "close"): void; (e: "created"): void }>();

const internalOpen = ref(props.open);
watch(
    () => props.open,
    (v) => (internalOpen.value = v),
);

const form = useForm({
    banco: "",
    titular: "",
    iban: "",
    swift_bic: "",
    numero_conta: "",
    saldo_abertura: 0,
    ativo: true,
    notas: "",
});

function close() {
    emit("close");
    form.reset();
    form.clearErrors();
}
function submit() {
    form.post(route("financeiro.contas-bancarias.store"), {
        onSuccess: () => emit("created"),
    });
}
</script>

<template>
    <Dialog :open="internalOpen" @update:open="(v) => (v ? null : close())">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader
                ><DialogTitle>Nova Conta Bancária</DialogTitle></DialogHeader
            >

            <UiForm
                @submit.prevent="submit"
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
            >
                <FormField name="banco">
                    <FormItem
                        ><FormLabel>Banco</FormLabel
                        ><FormControl
                            ><Input v-model="form.banco" /></FormControl
                        ><FormMessage v-if="form.errors.banco">{{
                            form.errors.banco
                        }}</FormMessage></FormItem
                    >
                </FormField>
                <FormField name="titular">
                    <FormItem
                        ><FormLabel>Titular</FormLabel
                        ><FormControl
                            ><Input v-model="form.titular" /></FormControl
                        ><FormMessage v-if="form.errors.titular">{{
                            form.errors.titular
                        }}</FormMessage></FormItem
                    >
                </FormField>
                <FormField name="iban">
                    <FormItem
                        ><FormLabel>IBAN</FormLabel
                        ><FormControl
                            ><Input
                                v-model="form.iban"
                                class="font-mono" /></FormControl
                        ><FormMessage v-if="form.errors.iban">{{
                            form.errors.iban
                        }}</FormMessage></FormItem
                    >
                </FormField>
                <FormField name="swift_bic">
                    <FormItem
                        ><FormLabel>SWIFT/BIC</FormLabel
                        ><FormControl
                            ><Input v-model="form.swift_bic" /></FormControl
                    ></FormItem>
                </FormField>
                <FormField name="numero_conta">
                    <FormItem
                        ><FormLabel>Nº Conta</FormLabel
                        ><FormControl
                            ><Input v-model="form.numero_conta" /></FormControl
                    ></FormItem>
                </FormField>
                <FormField name="saldo_abertura">
                    <FormItem
                        ><FormLabel>Saldo Abertura</FormLabel
                        ><FormControl
                            ><Input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.saldo_abertura" /></FormControl
                        ><FormMessage v-if="form.errors.saldo_abertura">{{
                            form.errors.saldo_abertura
                        }}</FormMessage></FormItem
                    >
                </FormField>

                <div
                    class="md:col-span-2 flex items-center justify-between rounded-lg border p-3"
                >
                    <span class="text-sm">Conta Ativa</span>
                    <Switch v-model:checked="form.ativo" />
                </div>

                <div class="md:col-span-2 flex justify-end gap-2">
                    <Button type="button" variant="secondary" @click="close"
                        >Cancelar</Button
                    >
                    <Button type="submit">Criar</Button>
                </div>
            </UiForm>
        </DialogContent>
    </Dialog>
</template>
