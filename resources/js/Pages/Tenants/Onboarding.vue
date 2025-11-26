<script setup>
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const page = usePage();
const user = page.props.auth.user;

const step = ref(1);
const form = ref({
    name: "",
    primary_color: "#6366f1", // indigo-500
    logo: null,
    logoPreview: null,
    invites: [{ email: "" }, { email: "" }, { email: "" }],
});

const pickColor = (color) => {
    form.value.primary_color = color;
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.value.logo = file;
        form.value.logoPreview = URL.createObjectURL(file);
    }
};

const addInvite = () => {
    form.value.invites.push({ email: "" });
};

const submit = () => {
    const formData = new FormData();
    formData.append("name", form.value.name);
    formData.append("primary_color", form.value.primary_color);
    if (form.value.logo) formData.append("logo", form.value.logo);
    form.value.invites.forEach((i, idx) => {
        if (i.email) formData.append(`invites[${idx}][email]`, i.email);
    });

    router.post(route("tenants.onboarding.store"), formData, {
        forceFormData: true,
    });
};
</script>

<template>
    <div class="min-h-screen bg-white flex items-center justify-center p-6">
        <div class="max-w-2xl w-full">
            <!-- Header Notion-style -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-3">
                    Bem-vindo, {{ user.name.split(" ")[0] }}!
                </h1>
                <p class="text-xl text-gray-600">
                    Vamos configurar a tua primeira empresa em 3 passos
                </p>
            </div>

            <!-- Progress -->
            <div class="flex justify-center gap-4 mb-12">
                <div
                    :class="step >= 1 ? 'bg-indigo-600' : 'bg-gray-300'"
                    class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                >
                    1
                </div>
                <div
                    class="w-24 h-1 bg-gray-300 mt-5"
                    :class="step >= 2 ? 'bg-indigo-600' : ''"
                ></div>
                <div
                    :class="step >= 2 ? 'bg-indigo-600' : 'bg-gray-300'"
                    class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                >
                    2
                </div>
                <div
                    class="w-24 h-1 bg-gray-300 mt-5"
                    :class="step >= 3 ? 'bg-indigo-600' : ''"
                ></div>
                <div
                    :class="step >= 3 ? 'bg-indigo-600' : 'bg-gray-300'"
                    class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                >
                    3
                </div>
            </div>

            <!-- Passo 1 – Nome da empresa -->
            <div v-if="step === 1" class="space-y-8">
                <div>
                    <Label class="text-lg">Como se chama a tua empresa?</Label>
                    <Input
                        v-model="form.name"
                        placeholder="Ex: GospelBooks Lda"
                        class="mt-3 text-2xl h-14"
                        autofocus
                    />
                </div>

                <div class="flex justify-end">
                    <Button size="lg" :disabled="!form.name" @click="step = 2">
                        Continuar
                    </Button>
                </div>
            </div>

            <!-- Passo 2 – Branding -->
            <div v-if="step === 2" class="space-y-10">
                <div>
                    <Label class="text-lg">Logo da empresa (opcional)</Label>
                    <div class="mt-4 flex flex-col items-center">
                        <label class="cursor-pointer">
                            <input
                                type="file"
                                accept="image/*"
                                @change="onFileChange"
                                class="hidden"
                            />
                            <div
                                v-if="form.logoPreview"
                                class="w-32 h-32 rounded-2xl overflow-hidden border-4 border-dashed border-gray-300"
                            >
                                <img
                                    :src="form.logoPreview"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div
                                v-else
                                class="w-32 h-32 rounded-2xl bg-gray-100 border-4 border-dashed border-gray-300 flex items-center justify-center"
                            >
                                <span class="text-5xl text-gray-400">+</span>
                            </div>
                            <p class="text-center mt-3 text-sm text-gray-600">
                                Clique para fazer upload
                            </p>
                        </label>
                    </div>
                </div>

                <div>
                    <Label class="text-lg">Cor principal</Label>
                    <div class="grid grid-cols-8 gap-3 mt-4">
                        <button
                            v-for="color in [
                                '#ef4444',
                                '#f97316',
                                '#f59e0b',
                                '#84cc16',
                                '#10b981',
                                '#0ea5e9',
                                '#6366f1',
                                '#8b5cf6',
                                '#ec4899',
                            ]"
                            :key="color"
                            @click="pickColor(color)"
                            class="w-12 h-12 rounded-xl border-4 transition-all"
                            :class="
                                form.primary_color === color
                                    ? 'border-gray-900 scale-110'
                                    : 'border-transparent'
                            "
                            :style="{ backgroundColor: color }"
                        />
                    </div>
                </div>

                <div class="flex justify-between">
                    <Button variant="ghost" @click="step = 1">Voltar</Button>
                    <Button size="lg" @click="step = 3">Continuar</Button>
                </div>
            </div>

            <!-- Passo 3 – Convites -->
            <div v-if="step === 3" class="space-y-8">
                <div>
                    <h3 class="text-2xl font-semibold mb-6">
                        Convida a tua equipa (opcional)
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(invite, i) in form.invites"
                            :key="i"
                            class="flex gap-3"
                        >
                            <Input
                                v-model="invite.email"
                                type="email"
                                placeholder="email@exemplo.com"
                                class="flex-1"
                            />
                            <Button
                                v-if="i >= 3"
                                variant="ghost"
                                size="icon"
                                @click="form.invites.splice(i, 1)"
                                >×</Button
                            >
                        </div>
                    </div>
                    <Button variant="link" @click="addInvite" class="mt-3"
                        >+ Adicionar outro</Button
                    >
                </div>

                <div class="flex justify-between">
                    <Button variant="ghost" @click="step = 2">Voltar</Button>
                    <Button size="lg" @click="submit" class="px-12">
                        Finalizar e entrar
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
