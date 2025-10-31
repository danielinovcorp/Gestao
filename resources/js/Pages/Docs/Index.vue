<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
const props = defineProps<{ docs: any; filters: Record<string, any> }>();
</script>

<template>
    <Head title="Arquivos Digitais" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Arquivos Digitais
            </h2>
        </template>

        <div class="p-6">
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <p class="text-slate-600">
                    Página de Arquivos Digitais pronta. (Depois substituímos
                    pelo componente completo da lista.)
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
