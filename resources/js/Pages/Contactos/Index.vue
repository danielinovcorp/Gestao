<!-- resources/js/Pages/Contactos/Index.vue -->
<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useContactos } from "@/composables/useContactos";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

// Dialog + Form
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Textarea } from "@/components/ui/textarea";

// Dados
const { rows, list, create, update, remove, loading } = useContactos();
const q = ref("");

// Dialog
const open = ref(false);
const editingId = ref<number | null>(null);

// Combos
const entidadesOpts = ref<{ id: number; nome: string }[]>([]);
const funcoesOpts = ref<{ id: number; nome: string }[]>([]);

type ContactoForm = {
    entidade_id: number | null;
    nome: string;
    apelido: string;
    funcao_id: number | null;
    telefone: string;
    telemovel: string;
    email: string;
    cargo: string;
    consentimento_rgpd: "sim" | "nao";
    estado: "ativo" | "inativo";
    observacoes: string;
};

const form = ref<ContactoForm>({
    entidade_id: null,
    nome: "",
    apelido: "",
    funcao_id: null,
    telefone: "",
    telemovel: "",
    email: "",
    cargo: "",
    consentimento_rgpd: "nao",
    estado: "ativo",
    observacoes: "",
});

// Carregar combos
async function loadCombos() {
    try {
        const res = await fetch("/api/entidades?per_page=100", {
            credentials: "include",
        });
        const json = await res.json();
        const arr = Array.isArray(json?.data) ? json.data : json;
        entidadesOpts.value = (arr ?? []).map((e: any) => ({
            id: e.id,
            nome: e.nome,
        }));
    } catch {
        entidadesOpts.value = [];
    }

    try {
        const res = await fetch("/api/funcoes-contacto", {
            credentials: "include",
        });
        const arr = await res.json();
        funcoesOpts.value = Array.isArray(arr) ? arr : [];
    } catch {
        funcoesOpts.value = [];
    }
}

function startNew() {
    editingId.value = null;
    form.value = {
        entidade_id: null,
        nome: "",
        apelido: "",
        funcao_id: null,
        telefone: "",
        telemovel: "",
        email: "",
        cargo: "",
        consentimento_rgpd: "nao",
        estado: "ativo",
        observacoes: "",
    };
    open.value = true;
}

function startEdit(c: any) {
    editingId.value = c.id;
    form.value = {
        entidade_id: c.entidade_id ?? null,
        nome: c.nome ?? "",
        apelido: c.apelido ?? "",
        funcao_id: c.funcao_id ?? null,
        telefone: c.telefone ?? "",
        telemovel: c.telemovel ?? "",
        email: c.email ?? "",
        cargo: c.cargo ?? "",
        consentimento_rgpd: c.consentimento_rgpd ?? "nao",
        estado: c.estado ?? "ativo",
        observacoes: c.observacoes ?? "",
    };
    open.value = true;
}

async function save() {
    try {
        const payload = { ...form.value };
        if (editingId.value) await update(editingId.value, payload);
        else await create(payload);

        open.value = false;
        await list({ q: q.value });
    } catch (e: any) {
        console.error("Erro ao salvar contacto:", e);
    }
}

onMounted(async () => {
    await list();
    await loadCombos();
});
</script>

<template>
    <Head title="Contactos" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-semibold leading-tight">Contactos</h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Filtros + Novo -->
            <div class="flex items-center gap-2">
                <Input
                    v-model="q"
                    placeholder="Pesquisar contacto..."
                    class="w-80"
                    @input="list({ q: q.value })"
                />
                <Button :disabled="loading" @click="list({ q: q.value })"
                    >Filtrar</Button
                >
                <div class="flex-1"></div>
                <Button @click="startNew">Novo Contacto</Button>
            </div>

            <!-- TABELA (IGUAL À PROPOSTAS E PAÍSES) -->
            <div class="overflow-hidden rounded-xl border bg-white">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                #
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Nome
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Apelido
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Função
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Entidade
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Telefone
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Telemóvel
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-semibold text-slate-700"
                            >
                                Email
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
                            v-for="c in rows"
                            :key="c.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ c.id }}
                            </td>
                            <td class="px-4 py-2 text-sm">{{ c.nome }}</td>
                            <td class="px-4 py-2 text-sm">
                                {{ c.apelido || "—" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ c.funcao || "—" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ c.entidade || "—" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ c.telefone || "—" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ c.telemovel || "—" }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ c.email || "—" }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-end">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="startEdit(c)"
                                        >Editar</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        :disabled="loading"
                                        @click="
                                            remove(c.id).then(() =>
                                                list({ q: q.value }),
                                            )
                                        "
                                    >
                                        Remover
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!rows || rows.length === 0">
                            <td
                                colspan="9"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                Nenhum contacto encontrado
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sem paginação (assumindo que o composable já filtra) -->
            <!-- Se quiser, posso adicionar depois -->

            <!-- Dialog (mantido igual) -->
            <Dialog v-model:open="open">
                <DialogContent class="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle
                            >{{
                                editingId ? "Editar" : "Novo"
                            }}
                            Contacto</DialogTitle
                        >
                        <DialogDescription
                            >Preencha os dados do contacto</DialogDescription
                        >
                    </DialogHeader>

                    <form @submit.prevent="save" class="grid gap-3">
                        <!-- Entidade -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Entidade</label>
                            <Select v-model="form.entidade_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione a entidade"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="e in entidadesOpts"
                                        :key="e.id"
                                        :value="e.id"
                                    >
                                        {{ e.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Nome</label>
                                <Input v-model="form.nome" autofocus />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium"
                                    >Apelido</label
                                >
                                <Input v-model="form.apelido" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Função</label>
                            <Select v-model="form.funcao_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione a função"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="f in funcoesOpts"
                                        :key="f.id"
                                        :value="f.id"
                                    >
                                        {{ f.nome }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium"
                                    >Telefone</label
                                >
                                <Input v-model="form.telefone" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium"
                                    >Telemóvel</label
                                >
                                <Input v-model="form.telemovel" />
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Email</label>
                                <Input
                                    v-model="form.email"
                                    placeholder="ex: nome@empresa.pt"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium"
                                    >Estado</label
                                >
                                <Select v-model="form.estado">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecione" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ativo"
                                            >Ativo</SelectItem
                                        >
                                        <SelectItem value="inativo"
                                            >Inativo</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium"
                                >Consentimento RGPD</label
                            >
                            <RadioGroup
                                v-model="form.consentimento_rgpd"
                                class="flex gap-4"
                            >
                                <div class="flex items-center gap-2">
                                    <RadioGroupItem id="rgpd-sim" value="sim" />
                                    <label
                                        for="rgpd-sim"
                                        class="text-sm font-normal cursor-pointer"
                                        >Sim</label
                                    >
                                </div>
                                <div class="flex items-center gap-2">
                                    <RadioGroupItem id="rgpd-nao" value="nao" />
                                    <label
                                        for="rgpd-nao"
                                        class="text-sm font-normal cursor-pointer"
                                        >Não</label
                                    >
                                </div>
                            </RadioGroup>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium"
                                >Observações</label
                            >
                            <Textarea v-model="form.observacoes" rows="3" />
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button
                                type="button"
                                variant="secondary"
                                @click="open = false"
                                >Cancelar</Button
                            >
                            <Button :disabled="loading" type="submit"
                                >Guardar</Button
                            >
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
