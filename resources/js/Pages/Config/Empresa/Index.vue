<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

import ConfigTabs from "../_ConfigTabs.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
    Form as UiForm,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/components/ui/form";
import { Card, CardContent } from "@/components/ui/card";

const props = defineProps<{
    empresa: {
        id: number;
        nome: string | null;
        morada: string | null;
        codigo_postal: string | null;
        localidade: string | null;
        nif: string | null;
        logo_path: string | null;
    } | null;
    filesRoute: string;
}>();

const form = useForm({
    nome: props.empresa?.nome ?? "",
    morada: props.empresa?.morada ?? "",
    codigo_postal: props.empresa?.codigo_postal ?? "",
    localidade: props.empresa?.localidade ?? "",
    nif: props.empresa?.nif ?? "",
    logo: null as File | null,
    remove_logo: false as boolean,
    // se usares PUT via spoof:
    // _method: "PUT",
});

const localPreview = ref<string | null>(null);

function logoSrc() {
    if (!props.empresa?.logo_path) return null;
    const url = new URL(props.filesRoute);
    url.searchParams.set("path", props.empresa.logo_path);
    return url.toString();
}

function onLogoChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0] || null;
    form.logo = f;
    form.remove_logo = false;
    localPreview.value = f ? URL.createObjectURL(f) : null;
}

function removeLogo() {
    form.logo = null;
    form.remove_logo = true;
    localPreview.value = null;
}

function submit() {
    // opção A: método PUT direto
    form.put(route("config.empresa.update"), {
        forceFormData: true, // necessário para enviar o ficheiro
        preserveScroll: true,
    });

    // opção B (alternativa): POST + spoof
    // form.post(route("config.empresa.update"), {
    //   forceFormData: true,
    //   preserveScroll: true,
    // });
}

const currentLogo = computed(() => localPreview.value || logoSrc());
</script>

<template>
    <Head title="Configurações - Empresa" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-2">
                <h2 class="text-2xl font-semibold leading-tight">
                    Configurações
                </h2>
                <ConfigTabs />
            </div>
        </template>

        <div class="p-6 space-y-6">
            <Card>
                <CardContent class="p-6">
                    <!-- UiForm é apenas provider; o submit vem do <form> nativo -->
                    <UiForm>
                        <form
                            @submit.prevent="submit"
                            class="grid grid-cols-1 md:grid-cols-2 gap-6"
                        >
                            <!-- Logotipo -->
                            <FormField name="logo" class="md:col-span-2">
                                <FormItem>
                                    <FormLabel>Logotipo da Empresa</FormLabel>
                                    <FormControl>
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="h-16 w-16 rounded overflow-hidden bg-slate-100 border"
                                            >
                                                <img
                                                    v-if="currentLogo"
                                                    :src="currentLogo!"
                                                    class="h-full w-full object-contain"
                                                    alt="Logo"
                                                />
                                            </div>
                                            <Input
                                                type="file"
                                                accept="image/*"
                                                @change="onLogoChange"
                                            />
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                @click="removeLogo"
                                                >Remover</Button
                                            >
                                        </div>
                                    </FormControl>
                                    <FormMessage v-if="form.errors.logo">{{
                                        form.errors.logo
                                    }}</FormMessage>
                                </FormItem>
                            </FormField>

                            <!-- Campos -->
                            <FormField name="nome">
                                <FormItem>
                                    <FormLabel>Nome</FormLabel>
                                    <FormControl>
                                        <Input
                                            v-model="form.nome"
                                            placeholder="Ex.: Inovcorp, Lda."
                                        />
                                    </FormControl>
                                    <FormMessage v-if="form.errors.nome">{{
                                        form.errors.nome
                                    }}</FormMessage>
                                </FormItem>
                            </FormField>

                            <FormField name="nif">
                                <FormItem>
                                    <FormLabel>Número Contribuinte</FormLabel>
                                    <FormControl>
                                        <Input
                                            v-model="form.nif"
                                            placeholder="NIF/VAT"
                                        />
                                    </FormControl>
                                    <FormMessage v-if="form.errors.nif">{{
                                        form.errors.nif
                                    }}</FormMessage>
                                </FormItem>
                            </FormField>

                            <FormField name="morada" class="md:col-span-2">
                                <FormItem>
                                    <FormLabel>Morada</FormLabel>
                                    <FormControl>
                                        <Textarea
                                            v-model="form.morada"
                                            rows="2"
                                        />
                                    </FormControl>
                                    <FormMessage v-if="form.errors.morada">{{
                                        form.errors.morada
                                    }}</FormMessage>
                                </FormItem>
                            </FormField>

                            <FormField name="codigo_postal">
                                <FormItem>
                                    <FormLabel>Código Postal</FormLabel>
                                    <FormControl>
                                        <Input
                                            v-model="form.codigo_postal"
                                            placeholder="XXXX-XXX"
                                        />
                                    </FormControl>
                                    <FormMessage
                                        v-if="form.errors.codigo_postal"
                                        >{{
                                            form.errors.codigo_postal
                                        }}</FormMessage
                                    >
                                </FormItem>
                            </FormField>

                            <FormField name="localidade">
                                <FormItem>
                                    <FormLabel>Localidade</FormLabel>
                                    <FormControl>
                                        <Input v-model="form.localidade" />
                                    </FormControl>
                                    <FormMessage
                                        v-if="form.errors.localidade"
                                        >{{
                                            form.errors.localidade
                                        }}</FormMessage
                                    >
                                </FormItem>
                            </FormField>

                            <div class="md:col-span-2 flex justify-end gap-2">
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                >
                                    {{
                                        form.processing
                                            ? "A guardar..."
                                            : "Guardar alterações"
                                    }}
                                </Button>
                            </div>
                        </form>
                    </UiForm>
                </CardContent>
            </Card>

            <p class="text-sm text-slate-600">
                O logotipo e estes dados serão usados no login, cabeçalho e
                geração de PDFs.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
