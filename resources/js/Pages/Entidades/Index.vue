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

// ✅ shadcn Form bits
import {
  Form,
  FormField,
  FormItem,
  FormLabel,
  FormControl,
  FormMessage,
  FormDescription,
} from "@/components/ui/form";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
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
  pais_id?: number | null;
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
  pais_id: undefined,
  telefone: "",
  telemovel: "",
  website: "",
  email: "",
  consentimento_rgpd: "nao",
  observacoes: "",
  estado: "ativo",
});

const editingId = ref<number | null>(null);
const title = computed(() => (currentTipo.value === "fornecedor" ? "Fornecedores" : "Clientes"));

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
  if (!val) { nifExiste.value = false; return; }
  try {
    const { data } = await axios.get("/api/entidades/check-nif", { params: { nif: val } });
    nifExiste.value = !!data?.exists;
  } catch {
    nifExiste.value = false;
  }
}

// VIES (auto-preenchimento básico)
async function consultarVies() {
  const val = (form.value.nif || "").trim();
  if (!val) return;
  try {
    const { data } = await axios.get("/api/vies", { params: { nif: val } });
    if (data?.valid) {
      form.value.nome = form.value.nome || (data?.name ?? "");
      // Se quiseres: mapear data.countryCode -> pais_id (precisa tabela/códigos)
      // Se quiseres: parse de address -> morada/localidade/codigo_postal
    }
  } catch {
    // silencioso por enquanto
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
    pais_id: item.pais_id ?? undefined,
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

async function save() {
  const payload = {
    ...tipoToBools(form.value.tipo),
    nome: form.value.nome,
    nif: form.value.nif || undefined,
    morada: form.value.morada || null,
    codigo_postal: form.value.codigo_postal || null,
    localidade: form.value.localidade || null,
    pais_id: form.value.pais_id ?? null,
    telefone: form.value.telefone || null,
    telemovel: form.value.telemovel || null,
    website: form.value.website || null,
    email: form.value.email || null,
    consentimento_rgpd: form.value.consentimento_rgpd ?? "nao",
    observacoes: form.value.observacoes || null,
    estado: form.value.estado ?? "ativo",
  };

  if (editingId.value) {
    await update(editingId.value, payload);
  } else {
    await create(payload);
  }
  open.value = false;
  editingId.value = null;
  form.value = {
    tipo: currentTipo.value,
    nome: "",
    nif: "",
    morada: "",
    codigo_postal: "",
    localidade: "",
    pais_id: undefined,
    telefone: "",
    telemovel: "",
    website: "",
    email: "",
    consentimento_rgpd: "nao",
    observacoes: "",
    estado: "ativo",
  };
  await aplicarFiltro();
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

watch(() => form.value.codigo_postal, (v) => {
  if (!v) return;
  // nada além do computed
});

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
          :class="currentTipo === 'cliente' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-50'"
          @click="trocaAba('cliente')"
        >
          Clientes
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm border-l"
          :class="currentTipo === 'fornecedor' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-50'"
          @click="trocaAba('fornecedor')"
        >
          Fornecedores
        </button>
      </div>

      <!-- Filtros e Ações -->
      <div class="flex flex-wrap items-center gap-2">
        <Input v-model="q" placeholder="Pesquisar por nome ou NIF..." class="max-w-sm" />
        <Button :disabled="loading" @click="aplicarFiltro()">Filtrar</Button>

        <Dialog v-model:open="open">
          <DialogTrigger as-child>
            <Button>Novo</Button>
          </DialogTrigger>

          <DialogContent class="max-w-2xl">
            <DialogHeader>
              <DialogTitle>{{ editingId ? "Editar" : "Nova" }} Entidade</DialogTitle>
            </DialogHeader>

            <!-- ✅ FORM shadcn-vue -->
            <Form class="grid gap-3" @submit.prevent="save">
              <!-- Tipo -->
              <FormField name="tipo">
                <FormItem>
                  <FormLabel>Tipo</FormLabel>
                  <FormControl>
                    <RadioGroup v-model="form.tipo" class="flex gap-4">
                      <div class="flex items-center gap-2">
                        <RadioGroupItem id="tipo-cliente" value="cliente" />
                        <FormLabel for="tipo-cliente" class="m-0">Cliente</FormLabel>
                      </div>
                      <div class="flex items-center gap-2">
                        <RadioGroupItem id="tipo-fornecedor" value="fornecedor" />
                        <FormLabel for="tipo-fornecedor" class="m-0">Fornecedor</FormLabel>
                      </div>
                      <div class="flex items-center gap-2">
                        <RadioGroupItem id="tipo-ambos" value="ambos" />
                        <FormLabel for="tipo-ambos" class="m-0">Ambos</FormLabel>
                      </div>
                    </RadioGroup>
                  </FormControl>
                </FormItem>
              </FormField>

              <!-- NIF + VIES -->
              <FormField name="nif">
                <FormItem>
                  <FormLabel>NIF</FormLabel>
                  <FormControl>
                    <div class="flex gap-2">
                      <Input v-model="form.nif" placeholder="PT123456789" @blur="checkNif" />
                      <Button type="button" variant="secondary" @click="consultarVies">VIES</Button>
                    </div>
                  </FormControl>
                  <FormMessage />
                  <p v-if="nifExiste" class="text-sm text-red-600 mt-1">Este NIF já existe.</p>
                </FormItem>
              </FormField>

              <!-- Nome -->
              <FormField name="nome">
                <FormItem>
                  <FormLabel>Nome</FormLabel>
                  <FormControl>
                    <Input v-model="form.nome" />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </FormField>

              <!-- Endereço -->
              <div class="grid sm:grid-cols-2 gap-3">
                <FormField name="morada">
                  <FormItem>
                    <FormLabel>Morada</FormLabel>
                    <FormControl>
                      <Input v-model="form.morada" />
                    </FormControl>
                  </FormItem>
                </FormField>

                <FormField name="codigo_postal">
                  <FormItem>
                    <FormLabel>Código Postal</FormLabel>
                    <FormControl>
                      <Input v-model="form.codigo_postal" placeholder="1234-567" />
                    </FormControl>
                    <FormDescription>Formato: XXXX-XXX</FormDescription>
                    <p v-if="!cepValido" class="text-sm text-red-600">Código Postal inválido.</p>
                  </FormItem>
                </FormField>

                <FormField name="localidade">
                  <FormItem>
                    <FormLabel>Localidade</FormLabel>
                    <FormControl>
                      <Input v-model="form.localidade" />
                    </FormControl>
                  </FormItem>
                </FormField>

                <FormField name="pais_id">
                  <FormItem>
                    <FormLabel>País</FormLabel>
                    <FormControl>
                      <Select v-model="form.pais_id">
                        <SelectTrigger>
                          <SelectValue placeholder="Selecione o país" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem
                            v-for="p in paises"
                            :key="p.id"
                            :value="p.id"
                          >
                            {{ p.nome }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </FormControl>
                  </FormItem>
                </FormField>
              </div>

              <!-- Contacts -->
              <div class="grid sm:grid-cols-2 gap-3">
                <FormField name="telefone">
                  <FormItem>
                    <FormLabel>Telefone</FormLabel>
                    <FormControl>
                      <Input v-model="form.telefone" />
                    </FormControl>
                  </FormItem>
                </FormField>

                <FormField name="telemovel">
                  <FormItem>
                    <FormLabel>Telemóvel</FormLabel>
                    <FormControl>
                      <Input v-model="form.telemovel" />
                    </FormControl>
                  </FormItem>
                </FormField>

                <FormField name="website">
                  <FormItem>
                    <FormLabel>Website</FormLabel>
                    <FormControl>
                      <Input v-model="form.website" placeholder="https://..." />
                    </FormControl>
                  </FormItem>
                </FormField>

                <FormField name="email">
                  <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                      <Input v-model="form.email" placeholder="contato@empresa.pt" />
                    </FormControl>
                  </FormItem>
                </FormField>
              </div>

              <!-- RGPD / Estado -->
              <div class="grid sm:grid-cols-2 gap-3">
                <FormField name="consentimento_rgpd">
                  <FormItem>
                    <FormLabel>Consentimento RGPD</FormLabel>
                    <FormControl>
                      <RadioGroup v-model="form.consentimento_rgpd" class="flex gap-4">
                        <div class="flex items-center gap-2">
                          <RadioGroupItem id="rgpd-sim" value="sim" />
                          <FormLabel for="rgpd-sim" class="m-0">Sim</FormLabel>
                        </div>
                        <div class="flex items-center gap-2">
                          <RadioGroupItem id="rgpd-nao" value="nao" />
                          <FormLabel for="rgpd-nao" class="m-0">Não</FormLabel>
                        </div>
                      </RadioGroup>
                    </FormControl>
                  </FormItem>
                </FormField>

                <FormField name="estado">
                  <FormItem>
                    <FormLabel>Estado</FormLabel>
                    <FormControl>
                      <Select v-model="form.estado">
                        <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="ativo">Ativo</SelectItem>
                          <SelectItem value="inativo">Inativo</SelectItem>
                        </SelectContent>
                      </Select>
                    </FormControl>
                  </FormItem>
                </FormField>
              </div>

              <FormField name="observacoes">
                <FormItem>
                  <FormLabel>Observações</FormLabel>
                  <FormControl>
                    <Textarea v-model="form.observacoes" rows="3" />
                  </FormControl>
                </FormItem>
              </FormField>

              <div class="flex justify-end gap-2 pt-2">
                <Button type="button" variant="secondary" @click="open = false">Cancelar</Button>
                <Button :disabled="loading" type="submit">Guardar</Button>
              </div>
            </Form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Tabela (por enquanto simples; depois trocamos por DataTable shadcn) -->
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
            <tr v-for="item in rows" :key="item.id" class="border-t">
              <td class="p-2">{{ item.numero ?? item.id }}</td>
              <td class="p-2">{{ item.nif ?? '' }}</td>
              <td class="p-2">{{ item.nome }}</td>
              <td class="p-2">{{ item.telefone ?? '' }}</td>
              <td class="p-2">{{ item.telemovel ?? '' }}</td>
              <td class="p-2">
                <a v-if="item.website" :href="item.website" target="_blank" class="underline hover:no-underline">
                  {{ item.website }}
                </a>
              </td>
              <td class="p-2">{{ item.email ?? '' }}</td>
              <td class="p-2">
                <div class="flex gap-2">
                  <Button variant="secondary" @click="startEdit(item)">Editar</Button>
                  <Button
                    variant="destructive"
                    :disabled="loading"
                    @click="remove(item.id).then(() => aplicarFiltro())"
                  >
                    Remover
                  </Button>
                </div>
              </td>
            </tr>

            <tr v-if="!rows || rows.length === 0">
              <td colspan="8" class="p-4 text-center text-slate-500">
                Sem resultados
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
