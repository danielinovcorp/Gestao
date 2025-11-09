<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, nextTick, watch } from "vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Checkbox } from "@/Components/ui/checkbox";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";

const props = defineProps<{
    roles: {
        data: Array<{
            id: number;
            name: string;
            status: "active" | "inactive";
            users_count: number;
            permissions: Array<{ name: string }>;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    permissionMatrix: Record<string, string[]>;
    filters: { search?: string; status?: string };
}>();

const search = ref(props.filters.search || "");
const statusFilter = ref(props.filters.status || "");
const open = ref(false);
const editing = ref<any | null>(null);

// Dados do formulário
const formData = ref({
    name: "",
    status: "active",
    permissions: [] as string[],
});

// Form do Inertia para submit
const form = useForm({
    name: "",
    status: "active",
    permissions: [] as string[],
});

// Sincronizar automaticamente formData com form
watch(
    () => formData.value.name,
    (newVal) => {
        form.name = newVal;
    },
);

watch(
    () => formData.value.status,
    (newVal) => {
        form.status = newVal;
    },
);

watch(
    () => formData.value.permissions,
    (newVal) => {
        form.permissions = [...newVal];
    },
    { deep: true },
);

function aplicarFiltros() {
    router.get(
        route("access.roles.index"),
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
    form.reset();
    formData.value = {
        name: "",
        status: "active",
        permissions: [],
    };
    open.value = true;
}

function openEdit(r: any) {
    editing.value = r;
    form.clearErrors();
    formData.value = {
        name: r.name,
        status: r.status ?? "active",
        permissions: [...(r.permissions?.map((p: any) => p.name) ?? [])],
    };
    open.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route("access.roles.update", editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                form.reset();
                formData.value = {
                    name: "",
                    status: "active",
                    permissions: [],
                };
            },
            onError: (errors) => {
                console.error("Erro ao editar:", errors);
            },
        });
    } else {
        form.post(route("access.roles.store"), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                form.reset();
                formData.value = {
                    name: "",
                    status: "active",
                    permissions: [],
                };
            },
            onError: (errors) => {
                console.error("Erro ao criar:", errors);
            },
        });
    }
}

function remove(r: any) {
    if (confirm(`Remover grupo ${r.name}?`)) {
        router.delete(route("access.roles.destroy", r.id), {
            preserveScroll: true,
        });
    }
}

function toggle(r: any) {
    router.patch(route("access.roles.toggle", r.id), { preserveScroll: true });
}

function togglePermission(p: string, checked: boolean) {
    if (checked) {
        if (!formData.value.permissions.includes(p)) {
            formData.value.permissions.push(p);
        }
    } else {
        const idx = formData.value.permissions.indexOf(p);
        if (idx > -1) {
            formData.value.permissions.splice(idx, 1);
        }
    }
}
</script>

<template>
    <Head title="Gestão de Acessos - Permissões" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-semibold leading-tight">
                Gestão de Acessos — Permissões/Grupos
            </h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros + Novo -->
            <div class="flex flex-col sm:flex-row items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Pesquisar por nome do grupo"
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
                <Button @click="openCreate">Novo Grupo</Button>
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Nome do Grupo
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Utilizadores Relacionados
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
                            v-for="r in roles.data"
                            :key="r.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ r.name }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ r.users_count ?? 0 }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800':
                                            r.status === 'active',
                                        'bg-yellow-100 text-yellow-800':
                                            r.status === 'inactive',
                                    }"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{
                                        r.status === "active"
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
                                        @click="openEdit(r)"
                                    >
                                        Editar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="toggle(r)"
                                    >
                                        {{
                                            r.status === "active"
                                                ? "Inativar"
                                                : "Ativar"
                                        }}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="remove(r)"
                                    >
                                        Remover
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!roles.data.length">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhum grupo encontrado
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div v-if="roles.links.length > 3" class="flex justify-center">
                <div class="flex gap-1">
                    <template v-for="link in roles.links" :key="link.label">
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
            <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-lg">
                        {{ editing ? "Editar Grupo" : "Novo Grupo" }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium"
                                >Nome do Grupo *</label
                            >
                            <Input
                                v-model="formData.name"
                                required
                                :disabled="form.processing"
                                placeholder="Digite o nome do grupo"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-red-600 text-xs mt-1"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Estado *</label>
                            <Select
                                v-model="formData.status"
                                :disabled="form.processing"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecionar estado"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active"
                                        >Ativo</SelectItem
                                    >
                                    <SelectItem value="inactive"
                                        >Inativo</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.status"
                                class="text-red-600 text-xs mt-1"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>
                    </div>

                    <!-- Seção de Permissões -->
                    <div class="border rounded-lg">
                        <div class="bg-slate-50 px-4 py-3 border-b">
                            <h3 class="font-medium text-sm">
                                Permissões por Menu *
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Permissões selecionadas:
                                {{ formData.permissions.length }}
                                <span
                                    v-if="form.errors.permissions"
                                    class="text-red-600 font-medium"
                                >
                                    - {{ form.errors.permissions }}
                                </span>
                            </p>
                        </div>
                        <div class="p-4 max-h-96 overflow-y-auto">
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                            >
                                <div
                                    v-for="(perms, label) in permissionMatrix"
                                    :key="label"
                                    class="space-y-3"
                                >
                                    <div
                                        class="font-semibold text-sm text-slate-700"
                                    >
                                        {{ label }}
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            v-for="p in perms"
                                            :key="p"
                                            class="flex items-center gap-2 py-1"
                                        >
                                            <Checkbox
                                                :model-value="
                                                    formData.permissions.includes(
                                                        p,
                                                    )
                                                "
                                                @update:model-value="
                                                    (checked) =>
                                                        togglePermission(
                                                            p,
                                                            checked,
                                                        )
                                                "
                                                :disabled="form.processing"
                                            />
                                            <span class="text-sm truncate">
                                                {{
                                                    p
                                                        .split(".")
                                                        .pop()
                                                        ?.toUpperCase()
                                                }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensagens de erro gerais -->
                    <div
                        v-if="Object.keys(form.errors).length > 0"
                        class="bg-red-50 border border-red-200 rounded p-3"
                    >
                        <p class="text-red-700 text-sm font-medium">
                            Erros no formulário:
                        </p>
                        <ul
                            class="text-red-600 text-sm mt-1 list-disc list-inside"
                        >
                            <li
                                v-for="error in Object.values(form.errors)"
                                :key="error"
                            >
                                {{ error }}
                            </li>
                        </ul>
                    </div>

                    <DialogFooter class="gap-2 pt-4 border-t">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="open = false"
                            :disabled="form.processing"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing || !formData.name.trim()"
                            class="min-w-20"
                        >
                            <span v-if="form.processing">A guardar...</span>
                            <span v-else>{{
                                editing ? "Guardar" : "Criar"
                            }}</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
