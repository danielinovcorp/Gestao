<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from "@/Components/ui/dialog";

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/Components/ui/form";

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            phone: string | null;
            roles: Array<{ name: string }>;
            status: "active" | "inactive";
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    roles: Record<string, string>;
    filters: { search?: string; status?: string };
}>();

const search = ref(props.filters.search || "");
const statusFilter = ref(props.filters.status || "");
const open = ref(false);
const editing = ref<any | null>(null);

const form = useForm({
    name: "",
    email: "",
    phone: "",
    role: "",
    status: "active",
    password: "",
});

function aplicarFiltros() {
    router.get(
        route("access.users.index"),
        {
            search: search.value || null,
            status: statusFilter.value || null,
        },
        { preserveState: true, replace: true },
    );
}

function limparFiltros() {
    search.value = "";
    statusFilter.value = "";
    aplicarFiltros();
}

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.name = "";
    form.email = "";
    form.phone = "";
    form.role = "";
    form.status = "active";
    form.password = "";
    open.value = true;
}

function openEdit(u: any) {
    editing.value = u;
    form.clearErrors();
    form.name = u.name;
    form.email = u.email;
    form.phone = u.phone ?? "";
    form.role = u.roles?.[0]?.name ?? "";
    form.status = u.status;
    form.password = "";
    open.value = true;
}

function submit() {
    if (editing.value) {
        // Para edição - usar PUT
        form.put(route("access.users.update", editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                form.reset();
            },
            onError: (errors) => {
                console.error("Erro ao salvar:", errors);
            },
        });
    } else {
        // Para criação - usar POST
        form.post(route("access.users.store"), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                form.reset();
            },
            onError: (errors) => {
                console.error("Erro ao salvar:", errors);
            },
        });
    }
}

function remove(u: any) {
    if (confirm(`Remover ${u.name}?`)) {
        router.delete(route("access.users.destroy", u.id), {
            preserveScroll: true,
        });
    }
}

function toggle(u: any) {
    router.patch(route("access.users.toggle", u.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Gestão de Acessos - Utilizadores" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-semibold leading-tight">
                Gestão de Acessos — Utilizadores
            </h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros + Novo -->
            <div class="flex flex-col sm:flex-row items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Pesquisar por nome, email, telemóvel"
                    class="w-full sm:w-80"
                    @keyup.enter="aplicarFiltros"
                />
                <Select v-model="statusFilter">
                    <SelectTrigger class="w-full sm:w-40">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Todos</SelectItem>
                        <SelectItem value="active">Ativo</SelectItem>
                        <SelectItem value="inactive">Inativo</SelectItem>
                    </SelectContent>
                </Select>
                <Button @click="aplicarFiltros" class="w-full sm:w-auto">
                    Filtrar
                </Button>
                <Button
                    variant="secondary"
                    @click="limparFiltros"
                    class="w-full sm:w-auto"
                >
                    Limpar
                </Button>
                <div class="flex-1"></div>
                <Button @click="openCreate">Novo Utilizador</Button>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Nome
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Email
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Telemóvel
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Grupo de Permissões
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Estado
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-slate-700"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr
                            v-for="u in users.data"
                            :key="u.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ u.name }}
                            </td>
                            <td class="px-4 py-2 text-sm">{{ u.email }}</td>
                            <td class="px-4 py-2 text-sm">
                                {{ u.phone || "-" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ u.roles[0]?.name || "-" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800':
                                            u.status === 'active',
                                        'bg-yellow-100 text-yellow-800':
                                            u.status === 'inactive',
                                    }"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{
                                        u.status === "active"
                                            ? "Ativo"
                                            : "Inativo"
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="openEdit(u)"
                                    >
                                        Editar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="toggle(u)"
                                    >
                                        {{
                                            u.status === "active"
                                                ? "Inativar"
                                                : "Ativar"
                                        }}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="remove(u)"
                                    >
                                        Remover
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhum utilizador encontrado
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div v-if="users.links.length > 3" class="flex justify-center">
                <div class="flex gap-1">
                    <template v-for="link in users.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1 rounded border"
                            :class="{
                                'bg-blue-500 text-white border-blue-500':
                                    link.active,
                                'bg-white text-slate-700 border-slate-300 hover:bg-slate-50':
                                    !link.active,
                            }"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1 text-slate-400"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Dialog -->
        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? "Editar Utilizador" : "Novo Utilizador"
                    }}</DialogTitle>
                    <DialogDescription>
                        Preencha os campos abaixo para
                        {{ editing ? "editar" : "criar" }} o utilizador.
                    </DialogDescription>
                </DialogHeader>

                <!-- FORM HTML + SHADCN -->
                <form @submit.prevent="submit" class="space-y-4">
                    <FormField name="name">
                        <FormItem>
                            <FormLabel>Nome *</FormLabel>
                            <FormControl>
                                <Input v-model="form.name" required />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField name="email">
                        <FormItem>
                            <FormLabel>Email *</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="form.email"
                                    type="email"
                                    required
                                />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField name="phone">
                        <FormItem>
                            <FormLabel>Telemóvel</FormLabel>
                            <FormControl>
                                <Input v-model="form.phone" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <div class="grid grid-cols-2 gap-3">
                        <FormField name="role">
                            <FormItem>
                                <FormLabel>Grupo de Permissões *</FormLabel>
                                <FormControl>
                                    <Select v-model="form.role">
                                        <SelectTrigger
                                            ><SelectValue
                                                placeholder="Selecione..."
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="(name, id) in roles"
                                                :key="id"
                                                :value="name"
                                            >
                                                {{ name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField name="status">
                            <FormItem>
                                <FormLabel>Estado *</FormLabel>
                                <FormControl>
                                    <Select v-model="form.status">
                                        <SelectTrigger
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active"
                                                >Ativo</SelectItem
                                            >
                                            <SelectItem value="inactive"
                                                >Inativo</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <FormField v-if="!editing" name="password">
                        <FormItem>
                            <FormLabel>Password *</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="form.password"
                                    type="password"
                                    required
                                />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="open = false"
                            >Cancelar</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editing ? "Guardar" : "Criar" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
