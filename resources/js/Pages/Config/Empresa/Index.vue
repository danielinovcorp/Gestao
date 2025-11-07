<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ConfigTabs from "../_ConfigTabs.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent } from "@/components/ui/card";

const page = usePage();
// CORREÇÃO: Acessar a propriedade 'company'
const empresa = computed(() => page.props.company); 
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    nome: empresa.value?.nome ?? "",
    morada: empresa.value?.morada ?? "",
    codigo_postal: empresa.value?.codigo_postal ?? "",
    localidade: empresa.value?.localidade ?? "",
    nif: empresa.value?.nif ?? "",
    logo: null as File | null,
    remove_logo: false as boolean,
});

const localPreview = ref<string | null>(null);

function logoSrc() {
    if (localPreview.value) return localPreview.value;
    if (empresa.value?.logo_url) return empresa.value.logo_url;
    return "/images/logo-gestao-128.svg";
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
    console.log('📤 Dados do formulário antes do submit:', {
        nome: form.nome,
        nif: form.nif,
        morada: form.morada,
        codigo_postal: form.codigo_postal,
        localidade: form.localidade,
        tem_logo: !!form.logo,
        remove_logo: form.remove_logo
    });

    // Validação frontend
    if (!form.nome || !form.nome.trim()) {
        alert('Por favor, preencha o nome da empresa');
        return;
    }

    // CRÍTICO: Transforma o formulário para incluir o _method: 'put' e 'remove_logo'
    form.transform((d) => ({
        ...d,
        _method: 'put', // 👈 INFORMA O LARAVEL PARA USAR O MÉTODO PUT
        remove_logo: d.remove_logo ? 1 : 0,
    }));

    // CORREÇÃO CRÍTICA: Mudar para form.post para garantir que o arquivo seja enviado corretamente.
    form.post(route("config.empresa.update"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('✅ Empresa atualizada com sucesso');
            localPreview.value = null;
            setTimeout(() => {
                // Recarrega apenas a propriedade 'company' para atualizar os dados no Inertia
                router.reload({ only: ['company'] }); 
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ Erros de validação:', errors);
            const firstError = Object.values(errors)[0];
            alert('Erro: ' + (firstError || 'Erro ao atualizar dados da empresa'));
        }
    });
}

const currentLogo = computed(() => logoSrc());
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
            <div v-if="flashSuccess" class="p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ flashError }}
            </div>

            <Card>
                <CardContent class="p-6">
                    <form
                        @submit.prevent="submit"
                        enctype="multipart/form-data"
                        class="grid grid-cols-1 md:grid-cols-2 gap-6"
                    >
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-medium">Logotipo da Empresa</label>
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 rounded overflow-hidden bg-slate-100 border">
                                    <img
                                        v-if="currentLogo"
                                        :src="currentLogo"
                                        class="h-full w-full object-contain"
                                        alt="Logo da empresa"
                                    />
                                </div>
                                <Input
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    @change="onLogoChange"
                                />
                                <Button
                                    type="button"
                                    variant="secondary"
                                    @click="removeLogo"
                                >
                                    Remover
                                </Button>
                            </div>
                            <p v-if="form.errors.logo" class="text-sm text-red-600">
                                {{ form.errors.logo }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nome da Empresa *</label>
                            <Input
                                v-model="form.nome"
                                placeholder="Ex.: Inovcorp, Lda."
                                :disabled="form.processing"
                                required
                            />
                            <p v-if="form.errors.nome" class="text-sm text-red-600">
                                {{ form.errors.nome }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Número Contribuinte</label>
                            <Input
                                v-model="form.nif"
                                placeholder="NIF/VAT"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.nif" class="text-sm text-red-600">
                                {{ form.errors.nif }}
                            </p>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-medium">Morada</label>
                            <Textarea
                                v-model="form.morada"
                                rows="2"
                                placeholder="Endereço completo da empresa"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.morada" class="text-sm text-red-600">
                                {{ form.errors.morada }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Código Postal</label>
                            <Input
                                v-model="form.codigo_postal"
                                placeholder="XXXX-XXX"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.codigo_postal" class="text-sm text-red-600">
                                {{ form.errors.codigo_postal }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Localidade</label>
                            <Input
                                v-model="form.localidade"
                                placeholder="Cidade"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.localidade" class="text-sm text-red-600">
                                {{ form.errors.localidade }}
                            </p>
                        </div>

                        <div class="md:col-span-2 flex justify-end gap-2 pt-4">
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
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>