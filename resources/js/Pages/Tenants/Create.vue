<script setup>
import { useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const form = useForm({
    name: "",
});

const submit = () => {
    form.post(route("tenants.store"));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6">Criar Nova Empresa</h1>

            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <Label>Nome da Empresa</Label>
                        <Input
                            v-model="form.name"
                            placeholder="Ex: Minha Empresa Lda"
                            class="mt-1"
                        />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        Criar Empresa
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
