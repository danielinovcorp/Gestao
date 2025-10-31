<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
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
import { Checkbox } from "@/components/ui/checkbox";

const props = defineProps<{
    roles: { data: any[]; links: any[] };
    permissions: { id: number; name: string }[];
    permissionMatrix: Record<string, string[]>;
    filters: { search?: string; status?: string };
}>();

const search = ref(props.filters.search ?? "");
const statusFilter = ref(props.filters.status ?? "");

const open = ref(false);
const editing = ref<any | null>(null);

const form = useForm({
    name: "",
    status: "active",
    permissions: [] as string[],
});

function openCreate() {
    editing.value = null;
    form.reset({ name: "", status: "active", permissions: [] });
    open.value = true;
}
function openEdit(r: any) {
    editing.value = r;
    form.reset({
        name: r.name,
        status: r.status ?? "active",
        permissions: (r.permissions ?? []).map((p: any) => p.name),
    });
    open.value = true;
}
function toggle(r: any) {
    router.patch(route("access.roles.toggle", r.id), { preserveScroll: true });
}
function remove(r: any) {
    if (confirm(`Remover grupo ${r.name}?`)) {
        router.delete(route("access.roles.destroy", r.id), {
            preserveScroll: true,
        });
    }
}
function submit() {
    if (editing.value) {
        form.put(route("access.roles.update", editing.value.id), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    } else {
        form.post(route("access.roles.store"), {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    }
}
function doFilter() {
  const status = statusFilter.value === 'all' ? '' : statusFilter.value
  router.get(
    route('access.roles.index'),
    { search: search.value, status },
    { preserveState: true, replace: true },
  )
}
function togglePermission(p: string) {
    const idx = form.permissions.indexOf(p);
    if (idx === -1) form.permissions.push(p);
    else form.permissions.splice(idx, 1);
}
function hasPermission(p: string) {
    return form.permissions.includes(p);
}
</script>

<template>
    <Head title="Gestão de Acessos - Permissões" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Gestão de Acessos — Permissões/Grupos
            </h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros -->
            <div
                class="grid gap-2 sm:grid-cols-4 bg-white p-4 rounded-xl shadow"
            >
                <Input
                    v-model="search"
                    placeholder="Pesquisar por nome do grupo"
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
                    <Button @click="openCreate">Novo Grupo</Button>
                </div>
            </div>

            <!-- Tabela -->
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left p-3">Nome do Grupo</th>
                            <th class="text-left p-3">
                                Utilizadores Relacionados
                            </th>
                            <th class="text-left p-3">Estado</th>
                            <th class="text-right p-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="r in roles.data"
                            :key="r.id"
                            class="border-b last:border-0"
                        >
                            <td class="p-3">{{ r.name }}</td>
                            <td class="p-3">{{ r.users_count ?? 0 }}</td>
                            <td class="p-3">
                                <Badge
                                    :variant="
                                        (r.status ?? 'active') === 'active'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        (r.status ?? "active") === "active"
                                            ? "Ativo"
                                            : "Inativo"
                                    }}
                                </Badge>
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="openEdit(r)"
                                        >Editar</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="toggle(r)"
                                    >
                                        {{
                                            (r.status ?? "active") === "active"
                                                ? "Inativar"
                                                : "Ativar"
                                        }}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="remove(r)"
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
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? "Editar Grupo" : "Novo Grupo"
                    }}</DialogTitle>
                </DialogHeader>

                <Form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-3">
                        <FormField name="name">
                            <FormItem>
                                <FormLabel>Nome do Grupo</FormLabel>
                                <FormControl
                                    ><Input v-model="form.name" required
                                /></FormControl>
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

                    <!-- Matrix de permissões por menu -->
                    <div class="rounded-lg border p-4">
                        <h3 class="font-medium mb-3">Permissões por Menu</h3>
                        <div class="grid sm:grid-cols-2 gap-6">
                            <div
                                v-for="(perms, label) in props.permissionMatrix"
                                :key="label"
                                class="space-y-3"
                            >
                                <div class="font-semibold">{{ label }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    <label
                                        v-for="p in perms"
                                        :key="p"
                                        class="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            :checked="hasPermission(p)"
                                            @update:checked="
                                                () => togglePermission(p)
                                            "
                                        />
                                        <span class="truncate">{{
                                            p
                                                .split(".")
                                                .slice(-1)[0]
                                                .toUpperCase()
                                        }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

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
