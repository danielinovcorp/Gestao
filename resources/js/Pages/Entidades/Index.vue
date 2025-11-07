<script setup lang="ts">
import { onMounted, ref, computed, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { useEntidades } from "@/composables/useEntidades";
import axios from "axios";

// shadcn-vue
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";

// REMOVIDO: Componentes Form do shadcn que causam conflito
import { Textarea } from "@/components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";

/**
 * Props vindas do Inertia (web controller)
 * - tipo: 'cliente' | 'fornecedor' (define a aba e filtro inicial)
 */
const props = defineProps<{ tipo?: "cliente" | "fornecedor" }>();

const { rows, list, create, update, remove, loading } = useEntidades();

const currentTipo = ref<"cliente" | "fornecedor">(props.tipo ?? "cliente");
const q = ref("");
const open = ref(false);

type Form = {
    // UI
    tipo: "cliente" | "fornecedor" | "ambos";
    // Campos do projeto
    nome: string;
    nif: string;
    morada?: string | null;
    codigo_postal?: string | null;
    localidade?: string | null;
    pais_id?: string | null;
    telefone?: string | null;
    telemovel?: string | null;
    website?: string | null;
    email?: string | null;
    consentimento_rgpd?: "sim" | "nao";
    observacoes?: string | null;
    estado?: "ativo" | "inativo";
};
const form = ref<Form>({
    tipo: "cliente",
    nome: "",
    nif: "",
    morada: "",
    codigo_postal: "",
    localidade: "",
    pais_id: "",
    telefone: "",
    telemovel: "",
    website: "",
    email: "",
    consentimento_rgpd: "nao",
    observacoes: "",
    estado: "ativo",
});

const editingId = ref<number | null>(null);
const title = computed(() =>
    currentTipo.value === "fornecedor" ? "Fornecedores" : "Clientes",
);

// Países p/ Select
const paises = ref<{ id: number; nome: string; codigo?: string }[]>([]);
async function loadPaises() {
    try {
        const { data } = await axios.get("/api/paises"); // cria/garante esse endpoint simples
        paises.value = Array.isArray(data) ? data : [];
    } catch {
        paises.value = [];
    }
}

// Verificação de NIF existente
const nifExiste = ref(false);
async function checkNif() {
    const val = (form.value.nif || "").trim();
    if (!val) {
        nifExiste.value = false;
        return;
    }
    try {
        const { data } = await axios.get("/api/entidades/check-nif", {
            params: { nif: val },
        });
        nifExiste.value = !!data?.exists;
    } catch {
        nifExiste.value = false;
    }
}

// ===============================
// VIES - ESTADOS (adicionar no topo com os outros estados)
// ===============================
const validatingVat = ref(false);
const vatValidation = ref({
    valid: false,
    name: null as string | null,
    address: null as string | null,
    error: null as string | null,
});

// =====
// VIES
// =====
async function consultarVies() {
    const nif = (form.value.nif || "").trim();

    if (!nif) {
        alert("Por favor, insira um NIF para validar");
        return;
    }

    if (!form.value.pais_id) {
        alert("Por favor, selecione um país primeiro");
        return;
    }

    // Obter código do país a partir do pais_id
    const paisSelecionado = paises.value.find(
        (p) => String(p.id) === String(form.value.pais_id),
    );
    if (!paisSelecionado) {
        alert("País selecionado inválido");
        return;
    }

    // Usar código do país (assumindo que o campo é 'codigo' ou 'iso')
    const countryCode = paisSelecionado.codigo || paisSelecionado.iso;
    if (!countryCode) {
        alert("País selecionado não tem código definido");
        return;
    }

    console.log("🔍 Validando VIES:", {
        nif,
        countryCode,
        pais: paisSelecionado.nome,
    });

    // Limpar validação anterior
    vatValidation.value = {
        valid: false,
        name: null,
        address: null,
        error: null,
    };
    validatingVat.value = true;

    try {
        console.log("🔄 Iniciando validação VIES...");

        // Para Portugal, validar formato primeiro
        if (countryCode === "PT") {
            try {
                console.log("🇵🇹 Validando formato NIF português...");
                const nifResponse = await axios.post("/api/vies/validate-nif", {
                    nif: nif,
                });

                console.log("📋 Resposta validação NIF:", nifResponse.data);

                if (!nifResponse.data.valid) {
                    vatValidation.value.error = "NIF português inválido";
                    alert("NIF português inválido");
                    return;
                }
            } catch (error) {
                console.error("❌ Erro na validação local do NIF:", error);
                // Continua mesmo com erro na validação local
            }
        }

        // Validar no VIES - CORREÇÃO: usar a rota correta
        console.log("🌍 Validando no VIES...");
        const response = await axios.post("/api/vies/validate-vat", {
            country_code: countryCode,
            vat_number: nif,
        });

        const result = response.data;
        console.log("📋 Resposta VIES:", result);

        if (result.valid) {
            vatValidation.value = {
                valid: true,
                name: result.name,
                address: result.address,
                error: null,
            };

            // Preencher automaticamente o nome se estiver vazio
            if (!form.value.nome && result.name) {
                form.value.nome = result.name;
            }

            // Preencher morada se disponível
            if (result.address && !form.value.morada) {
                form.value.morada = result.address;
            }

            alert("✅ NIF validado com sucesso no VIES!");
        } else {
            vatValidation.value = {
                valid: false,
                name: null,
                address: null,
                error: result.error || "NIF inválido no VIES",
            };

            alert("❌ NIF inválido no VIES: " + (result.error || ""));
        }
    } catch (error: any) {
        console.error("💥 Erro VIES:", error);
        const errorMsg =
            error.response?.data?.message ||
            error.message ||
            "Erro desconhecido";

        vatValidation.value = {
            valid: false,
            name: null,
            address: null,
            error: "Erro na validação VIES: " + errorMsg,
        };

        alert("❌ Erro na validação VIES: " + errorMsg);
    } finally {
        validatingVat.value = false;
    }
}

// badge texto (se precisa em algum lugar)
function badgeTexto(item: any) {
    const c = item?.is_cliente;
    const f = item?.is_fornecedor;
    if (c && f) return "Cliente & Fornecedor";
    if (c) return "Cliente";
    if (f) return "Fornecedor";
    return "—";
}

// filtro
async function aplicarFiltro() {
    await list({ search: q.value || undefined, tipo: currentTipo.value });
}

function startEdit(item: any) {
    editingId.value = item.id;

    let t: Form["tipo"] = "cliente";
    if (item.is_cliente && item.is_fornecedor) t = "ambos";
    else if (item.is_fornecedor) t = "fornecedor";
    else t = "cliente";

    form.value = {
        tipo: t,
        nome: item.nome ?? "",
        nif: "", // nunca vem em claro (by design)
        morada: item.morada ?? "",
        codigo_postal: item.codigo_postal ?? "",
        localidade: item.localidade ?? "",
        pais_id: item.pais_id != null ? String(item.pais_id) : "",
        telefone: item.telefone ?? "",
        telemovel: item.telemovel ?? "",
        website: item.website ?? "",
        email: item.email ?? "",
        consentimento_rgpd: item.consentimento_rgpd ?? "nao",
        observacoes: item.observacoes ?? "",
        estado: item.estado ?? "ativo",
    };
    open.value = true;
}

// helper: mapeia form.tipo -> booleans esperados pela API
function tipoToBools(tipo: Form["tipo"]) {
    return {
        is_cliente: tipo === "cliente" || tipo === "ambos",
        is_fornecedor: tipo === "fornecedor" || tipo === "ambos",
    };
}

const saving = ref(false);

async function save() {
    try {
        saving.value = true;

        // Validação do website antes de enviar
        let website = form.value.website || null;
        if (
            website &&
            !website.startsWith("http://") &&
            !website.startsWith("https://")
        ) {
            website = "https://" + website;
        }

        const payload = {
            ...tipoToBools(form.value.tipo),
            nome: form.value.nome,
            nif: form.value.nif || undefined,
            morada: form.value.morada || null,
            codigo_postal: form.value.codigo_postal || null,
            localidade: form.value.localidade || null,
            pais_id:
                form.value.pais_id != null && form.value.pais_id !== ""
                    ? Number(form.value.pais_id)
                    : null,
            telefone: form.value.telefone || null,
            telemovel: form.value.telemovel || null,
            website: website, // Usa a versão corrigida
            email: form.value.email || null,
            consentimento_rgpd: form.value.consentimento_rgpd ?? "nao",
            observacoes: form.value.observacoes || null,
            estado: form.value.estado ?? "ativo",
        };

        console.log("Enviando payload:", payload); // Para debug

        if (editingId.value) {
            await update(editingId.value, payload);
        } else {
            await create(payload);
        }

        open.value = false;
        editingId.value = null;

        // reset rápido
        form.value = {
            tipo: currentTipo.value,
            nome: "",
            nif: "",
            morada: "",
            codigo_postal: "",
            localidade: "",
            pais_id: "",
            telefone: "",
            telemovel: "",
            website: "",
            email: "",
            consentimento_rgpd: "nao",
            observacoes: "",
            estado: "ativo",
        };

        await aplicarFiltro();
    } catch (err: any) {
        console.error("Falha ao guardar entidade:", err?.response?.data ?? err);

        // Mensagem mais específica para erro de URL
        if (err?.response?.data?.errors?.website) {
            alert(
                "URL do website inválida. Certifique-se de que começa com http:// ou https://",
            );
        } else {
            alert(
                err?.response?.data?.message ||
                    "Não foi possível guardar. Verifique os campos obrigatórios.",
            );
        }
    } finally {
        saving.value = false;
    }
}

async function trocaAba(tipo: "cliente" | "fornecedor") {
    if (currentTipo.value === tipo) return;
    currentTipo.value = tipo;
    form.value.tipo = tipo; // novo default
    await aplicarFiltro();
}

// validação leve de CEP na UI (o back já valida via FormRequest)
const cepValido = computed(() => {
    const v = (form.value.codigo_postal || "").trim();
    if (!v) return true;
    return /^\d{4}-\d{3}$/.test(v);
});

watch(
    () => form.value.codigo_postal,
    (v) => {
        if (!v) return;
        // nada além do computed
    },
);

onMounted(async () => {
    await loadPaises();
    aplicarFiltro();
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <div class="p-6 space-y-4">
            <!-- Tabs Clientes | Fornecedores -->
            <div class="inline-flex overflow-hidden rounded-xl border bg-white">
                <button
                    type="button"
                    class="px-4 py-2 text-sm"
                    :class="
                        currentTipo === 'cliente'
                            ? 'bg-indigo-600 text-white'
                            : 'hover:bg-slate-50'
                    "
                    @click="trocaAba('cliente')"
                >
                    Clientes
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm border-l"
                    :class="
                        currentTipo === 'fornecedor'
                            ? 'bg-indigo-600 text-white'
                            : 'hover:bg-slate-50'
                    "
                    @click="trocaAba('fornecedor')"
                >
                    Fornecedores
                </button>
            </div>

            <!-- Filtros e Ações -->
            <div class="flex flex-wrap items-center gap-2">
                <Input
                    v-model="q"
                    placeholder="Pesquisar por nome ou NIF..."
                    class="max-w-sm"
                />
                <Button :disabled="loading" @click="aplicarFiltro()"
                    >Filtrar</Button
                >

                <Dialog v-model:open="open">
                    <DialogTrigger as-child>
                        <Button>Novo</Button>
                    </DialogTrigger>

                    <DialogContent
                        class="sm:max-w-xl p-0 flex flex-col"
                        description="Formulário para adicionar ou editar entidades"
                    >
                        <DialogHeader class="px-6 pt-6 pb-3">
                            <DialogTitle
                                >{{
                                    editingId ? "Editar" : "Nova"
                                }}
                                Entidade</DialogTitle
                            >
                        </DialogHeader>

                        <!-- corpo scrollável -->
                        <div class="px-6 pb-4 max-h-[70vh] overflow-y-auto">
                            <!-- ✅ FORM SIMPLES - substituído Form do shadcn -->
                            <form
                                id="__entidades_form__"
                                class="grid gap-3"
                                @submit.prevent="save"
                            >
                                <!-- Tipo -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium"
                                        >Tipo</label
                                    >
                                    <RadioGroup
                                        v-model="form.tipo"
                                        class="flex gap-4"
                                    >
                                        <div class="flex items-center gap-2">
                                            <RadioGroupItem
                                                id="tipo-cliente"
                                                value="cliente"
                                            />
                                            <label
                                                for="tipo-cliente"
                                                class="text-sm font-normal cursor-pointer"
                                                >Cliente</label
                                            >
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <RadioGroupItem
                                                id="tipo-fornecedor"
                                                value="fornecedor"
                                            />
                                            <label
                                                for="tipo-fornecedor"
                                                class="text-sm font-normal cursor-pointer"
                                                >Fornecedor</label
                                            >
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <RadioGroupItem
                                                id="tipo-ambos"
                                                value="ambos"
                                            />
                                            <label
                                                for="tipo-ambos"
                                                class="text-sm font-normal cursor-pointer"
                                                >Ambos</label
                                            >
                                        </div>
                                    </RadioGroup>
                                </div>

                                <!-- NIF + VIES -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium"
                                        >NIF</label
                                    >
                                    <div class="flex gap-2">
                                        <Input
                                            v-model="form.nif"
                                            placeholder="PT123456789"
                                            @blur="checkNif"
                                            :class="{
                                                'border-yellow-500':
                                                    validatingVat,
                                                'border-green-500':
                                                    vatValidation.valid,
                                                'border-red-500':
                                                    vatValidation.error,
                                            }"
                                        />
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            @click="consultarVies"
                                            :disabled="
                                                validatingVat ||
                                                !form.nif ||
                                                !form.pais_id
                                            "
                                        >
                                            <span v-if="validatingVat">⏳</span>
                                            <span v-else>VIES</span>
                                        </Button>
                                    </div>

                                    <!-- Feedback da validação VIES -->
                                    <div
                                        v-if="validatingVat"
                                        class="mt-1 text-sm text-yellow-600 flex items-center"
                                    >
                                        <span class="animate-pulse">🔍</span>
                                        <span class="ml-1"
                                            >A validar no VIES...</span
                                        >
                                    </div>
                                    <div
                                        v-else-if="vatValidation.valid"
                                        class="mt-1 text-sm text-green-600 flex items-center"
                                    >
                                        <span>✅</span>
                                        <span class="ml-1">NIF válido</span>
                                        <span
                                            v-if="vatValidation.name"
                                            class="ml-2 font-semibold"
                                            >{{ vatValidation.name }}</span
                                        >
                                    </div>
                                    <div
                                        v-else-if="vatValidation.error"
                                        class="mt-1 text-sm text-red-600 flex items-center"
                                    >
                                        <span>❌</span>
                                        <span class="ml-1">{{
                                            vatValidation.error
                                        }}</span>
                                    </div>
                                    <p
                                        v-if="nifExiste"
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        Este NIF já existe.
                                    </p>
                                </div>

                                <!-- Nome -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium"
                                        >Nome</label
                                    >
                                    <Input v-model="form.nome" />
                                </div>

                                <!-- Endereço -->
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >Morada</label
                                        >
                                        <Input v-model="form.morada" />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >Código Postal</label
                                        >
                                        <Input
                                            v-model="form.codigo_postal"
                                            placeholder="1234-567"
                                        />
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            Formato: XXXX-XXX
                                        </p>
                                        <p
                                            v-if="!cepValido"
                                            class="text-sm text-red-600"
                                        >
                                            Código Postal inválido.
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >Localidade</label
                                        >
                                        <Input v-model="form.localidade" />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >País</label
                                        >
                                        <Select v-model="form.pais_id">
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Selecione o país"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="p in paises"
                                                    :key="p.id"
                                                    :value="String(p.id)"
                                                >
                                                    {{ p.nome }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <!-- Contacts -->
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

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >Website</label
                                        >
                                        <Input
                                            v-model="form.website"
                                            placeholder="https://..."
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >Email</label
                                        >
                                        <Input
                                            v-model="form.email"
                                            placeholder="contato@empresa.pt"
                                        />
                                    </div>
                                </div>

                                <!-- RGPD / Estado -->
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium"
                                            >Consentimento RGPD</label
                                        >
                                        <RadioGroup
                                            v-model="form.consentimento_rgpd"
                                            class="flex gap-4"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <RadioGroupItem
                                                    id="rgpd-sim"
                                                    value="sim"
                                                />
                                                <label
                                                    for="rgpd-sim"
                                                    class="text-sm font-normal cursor-pointer"
                                                    >Sim</label
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <RadioGroupItem
                                                    id="rgpd-nao"
                                                    value="nao"
                                                />
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
                                            >Estado</label
                                        >
                                        <Select v-model="form.estado">
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Selecione"
                                                />
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

                                <!-- Observações -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium"
                                        >Observações</label
                                    >
                                    <Textarea
                                        v-model="form.observacoes"
                                        rows="3"
                                    />
                                </div>
                            </form>
                        </div>

                        <!-- footer fixo -->
                        <div
                            class="px-6 py-3 border-t bg-white flex justify-end gap-2"
                        >
                            <Button
                                type="button"
                                variant="secondary"
                                @click="open = false"
                                >Cancelar</Button
                            >
                            <Button
                                :disabled="loading || saving"
                                type="submit"
                                form="__entidades_form__"
                                >Guardar</Button
                            >
                        </div>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Tabela -->
            <div class="rounded-xl border bg-white overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-2">#</th>
                            <th class="text-left p-2">NIF</th>
                            <th class="text-left p-2">Nome</th>
                            <th class="text-left p-2">Telefone</th>
                            <th class="text-left p-2">Telemóvel</th>
                            <th class="text-left p-2">Website</th>
                            <th class="text-left p-2">Email</th>
                            <th class="text-left p-2">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in rows"
                            :key="item.id"
                            class="border-t"
                        >
                            <td class="p-2">{{ item.numero ?? item.id }}</td>
                            <td class="p-2">{{ item.nif ?? "" }}</td>
                            <td class="p-2">{{ item.nome }}</td>
                            <td class="p-2">{{ item.telefone ?? "" }}</td>
                            <td class="p-2">{{ item.telemovel ?? "" }}</td>
                            <td class="p-2">
                                <a
                                    v-if="item.website"
                                    :href="item.website"
                                    target="_blank"
                                    class="underline hover:no-underline"
                                >
                                    {{ item.website }}
                                </a>
                            </td>
                            <td class="p-2">{{ item.email ?? "" }}</td>
                            <td class="p-2">
                                <div class="flex gap-2">
                                    <Button
                                        variant="secondary"
                                        @click="startEdit(item)"
                                        >Editar</Button
                                    >
                                    <Button
                                        variant="destructive"
                                        :disabled="loading"
                                        @click="
                                            remove(item.id).then(() =>
                                                aplicarFiltro(),
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
                                colspan="8"
                                class="p-4 text-center text-slate-500"
                            >
                                Sem resultados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
