<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import {
    Form,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
} from "@/components/ui/form";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";

const props = defineProps<{
    users: { data: any[]; links: any[]; meta?: any };
    roles: { id: number; name: string }[];
    filters: { search?: string; status?: string };
}>();

const search = ref(props.filters.search ?? "");
const statusFilter = ref(props.filters.status ?? "");

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

function openCreate() {
    editing.value = null;
    form.reset();
    form.status = "active";
    open.value = true;
}
function openEdit(u: any) {
    editing.value = u;
    form.reset({
        name: u.name,
        email: u.email,
        phone: u.phone ?? "",
        role: u.roles?.[0]?.name ?? "",
        status: u.status,
        password: "",
    });
    open.value = true;
}
function submit() {
    if (editing.value) {
        form.put(route("access.users.update", editing.value.id), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    } else {
        form.post(route("access.users.store"), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
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
function doFilter() {
    const status = statusFilter.value === "all" ? "" : statusFilter.value;
    router.get(
        route("access.users.index"),
        { search: search.value, status },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Gestão de Acessos - Utilizadores" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Gestão de Acessos — Utilizadores
            </h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros -->
            <div
                class="grid gap-2 sm:grid-cols-4 bg-white p-4 rounded-xl shadow"
            >
                <Input
                    v-model="search"
                    placeholder="Pesquisar por nome, email, telemóvel"
                    class="sm:col-span-2"
                />
                <Select v-model="statusFilter">
                    <SelectTrigger
                        ><SelectValue placeholder="Estado"
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="active">Ativo</SelectItem>
                        <SelectItem value="inactive">Inativo</SelectItem>
                    </SelectContent>
                </Select>
                <div class="flex gap-2 justify-end">
                    <Button
                        variant="outline"
                        @click="
                            () => {
                                search = '';
                                statusFilter = 'all';
                                doFilter();
                            }
                        "
                        >Limpar</Button
                    >
                    <Button @click="doFilter">Filtrar</Button>
                    <Button @click="openCreate">Novo</Button>
                </div>
            </div>

            <!-- Tabela -->
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left p-3">Nome</th>
                            <th class="text-left p-3">Email</th>
                            <th class="text-left p-3">Telemóvel</th>
                            <th class="text-left p-3">Grupo de Permissões</th>
                            <th class="text-left p-3">Estado</th>
                            <th class="text-right p-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="u in props.users.data"
                            :key="u.id"
                            class="border-b last:border-0"
                        >
                            <td class="p-3">{{ u.name }}</td>
                            <td class="p-3">{{ u.email }}</td>
                            <td class="p-3">{{ u.phone ?? "-" }}</td>
                            <td class="p-3">{{ u.roles?.[0]?.name ?? "-" }}</td>
                            <td class="p-3">
                                <Badge
                                    :variant="
                                        u.status === 'active'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    >{{
                                        u.status === "active"
                                            ? "Ativo"
                                            : "Inativo"
                                    }}</Badge
                                >
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="openEdit(u)"
                                        >Editar</Button
                                    >
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
                                        >Remover</Button
                                    >
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dialog Create/Edit -->
        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? "Editar Utilizador" : "Novo Utilizador"
                    }}</DialogTitle>
                </DialogHeader>

                <Form @submit.prevent="submit" class="space-y-4">
                    <FormField name="name">
                        <FormItem>
                            <FormLabel>Nome</FormLabel>
                            <FormControl
                                ><Input v-model="form.name" required
                            /></FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField name="email">
                        <FormItem>
                            <FormLabel>Email</FormLabel>
                            <FormControl
                                ><Input
                                    v-model="form.email"
                                    type="email"
                                    required
                            /></FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField name="phone">
                        <FormItem>
                            <FormLabel>Telemóvel</FormLabel>
                            <FormControl
                                ><Input v-model="form.phone"
                            /></FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <div class="grid grid-cols-2 gap-3">
                        <FormField name="role">
                            <FormItem>
                                <FormLabel>Grupo de Permissões</FormLabel>
                                <FormControl>
                                    <Select v-model="form.role">
                                        <SelectTrigger
                                            ><SelectValue
                                                placeholder="Selecione..."
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="r in props.roles"
                                                :key="r.id"
                                                :value="String(r.name)"
                                            >
                                                {{ r.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField name="status">
                            <FormItem>
                                <FormLabel>Estado</FormLabel>
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
                            <FormLabel>Password</FormLabel>
                            <FormControl
                                ><Input
                                    v-model="form.password"
                                    type="password"
                                    required
                            /></FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="open = false"
                            >Cancelar</Button
                        >
                        <Button type="submit" :disabled="form.processing">{{
                            editing ? "Guardar" : "Criar"
                        }}</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
