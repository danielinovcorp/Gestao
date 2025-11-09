<script setup lang="ts">
import ConfigTabs from "@/Pages/Config/_ConfigTabs.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

const props = defineProps<{
    items: {
        data: {
            id: number;
            created_at: string;
            utilizador: string | null;
            menu: string | null;
            acao: string | null;
            user_agent: string | null;
            ip: string | null;
        }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        q?: string;
        user_id?: number | null;
        menu?: string | null;
        acao?: string | null;
        from?: string | null;
        to?: string | null;
        per_page?: number;
    };
    userOptions: { id: number; name: string }[];
    menuOptions: string[];
}>();

// filtros
const q = ref(props.filters.q ?? "");
const user_id = ref<number | null>(props.filters.user_id ?? null);
const menu = ref<string | null>(props.filters.menu ?? null);
const acao = ref<string | null>(props.filters.acao ?? null);
const from = ref<string | null>(props.filters.from ?? null);
const to = ref<string | null>(props.filters.to ?? null);

function search() {
    router.get(
        route("logs.index"),
        {
            q: q.value,
            user_id: user_id.value ?? "",
            menu: menu.value ?? "",
            acao: acao.value ?? "",
            from: from.value ?? "",
            to: to.value ?? "",
        },
        { preserveState: true, replace: true },
    );
}

const rows = computed(() => props.items.data ?? []);

function fmtDate(dt: string) {
    const d = new Date(dt);
    return d.toLocaleDateString("pt-PT");
}

function fmtTime(dt: string) {
    const d = new Date(dt);
    return d.toLocaleTimeString("pt-PT", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
}
</script>

<template>
    <Head title="Logs" />
    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-2">
                <h2 class="text-2xl font-semibold leading-tight">
                    Configurações
                </h2>
                <ConfigTabs />
            </div>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                <div class="md:col-span-2">
                    <Input
                        v-model="q"
                        placeholder="Pesquisar (utilizador, ação, IP...)"
                        @keyup.enter="search"
                    />
                </div>

                <div>
                    <Select v-model="user_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Utilizador" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">Todos</SelectItem>
                            <SelectItem
                                v-for="u in userOptions"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Select v-model="menu">
                        <SelectTrigger>
                            <SelectValue placeholder="Menu" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">Todos</SelectItem>
                            <SelectItem
                                v-for="m in menuOptions"
                                :key="m"
                                :value="m"
                            >
                                {{ m || "—" }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Input v-model="from" type="date" />
                </div>
                <div>
                    <Input v-model="to" type="date" />
                </div>

                <div class="md:col-span-6 flex justify-end">
                    <Button @click="search">Filtrar</Button>
                </div>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-100">
                        <tr>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Data
                            </th>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Hora
                            </th>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Utilizador
                            </th>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Menu
                            </th>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Acção
                            </th>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Dispositivo
                            </th>
                            <th
                                class="px-3 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                IP
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="hover:bg-slate-50 align-top"
                        >
                            <td class="px-3 py-2 text-sm text-slate-600">
                                {{ fmtDate(row.created_at) }}
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-600">
                                {{ fmtTime(row.created_at) }}
                            </td>
                            <td class="px-3 py-2 text-sm">
                                {{ row.utilizador ?? "—" }}
                            </td>
                            <td class="px-3 py-2 text-sm">
                                {{ row.menu ?? "—" }}
                            </td>
                            <td class="px-3 py-2 text-sm">
                                {{ row.acao ?? "—" }}
                            </td>
                            <td
                                class="px-3 py-2 text-xs text-slate-600 max-w-[300px]"
                            >
                                <div class="line-clamp-2">
                                    {{ row.user_agent ?? "—" }}
                                </div>
                            </td>
                            <td class="px-3 py-2 text-sm">
                                {{ row.ip ?? "—" }}
                            </td>
                        </tr>

                        <tr v-if="rows.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-sm text-slate-600">
                Total: {{ props.items.total }} registos
            </div>
        </div>
    </AuthenticatedLayout>
</template>
