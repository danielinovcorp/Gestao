<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import { useContactos } from "@/composables/useContactos";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

// Dialog + Form (shadcn)
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
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
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Textarea } from "@/components/ui/textarea";

// DataTable (TanStack + shadcn table) — SAFE MODE (sem sorting/globalFilter)
import {
  useVueTable,
  getCoreRowModel,
  createColumnHelper,
  type ColumnDef,
} from "@tanstack/vue-table";
import {
  Table,
  TableHeader,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from "@/components/ui/table";
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
} from "@/components/ui/dropdown-menu";

// dados / ações
const { rows, list, create, update, remove, loading } = useContactos();
const q = ref("");

// dialog + form
const open = ref(false);
const editingId = ref<number | null>(null);

// combos
const entidadesOpts = ref<{ id: number; nome: string }[]>([]);
const funcoesOpts = ref<{ id: number; nome: string }[]>([]);

// modelo do formulário (campos do projeto)
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

// carregar combos
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
    funcoesOpts.value = await res.json();
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
  if (editingId.value) await update(editingId.value, form.value);
  else await create(form.value);
  open.value = false;
  await list({ q: q.value });
}

// ---------- DataTable (colunas + table instance) — SAFE MODE
type RowT = {
  id: number;
  numero?: number;
  nome: string;
  apelido?: string;
  funcao?: string;
  funcao_id?: number;
  entidade?: string;
  entidade_id?: number;
  telefone?: string;
  telemovel?: string;
  email?: string;
  estado?: "ativo" | "inativo";
  consentimento_rgpd?: "sim" | "nao";
};

const columnHelper = createColumnHelper<RowT>();
const columns: ColumnDef<RowT, any>[] = [
  columnHelper.accessor("nome", { header: "Nome", cell: ({ row }) => row.original.nome ?? "" }),
  columnHelper.accessor("apelido", { header: "Apelido", cell: ({ getValue }) => getValue() ?? "—" }),
  columnHelper.accessor("funcao", { header: "Função", cell: ({ getValue }) => getValue() ?? "—" }),
  columnHelper.accessor("entidade", { header: "Entidade", cell: ({ getValue }) => getValue() ?? "—" }),
  columnHelper.accessor("telefone", { header: "Telefone", cell: ({ getValue }) => getValue() ?? "—" }),
  columnHelper.accessor("telemovel", { header: "Telemóvel", cell: ({ getValue }) => getValue() ?? "—" }),
  columnHelper.accessor("email", { header: "Email", cell: ({ getValue }) => getValue() ?? "—" }),
  columnHelper.display({
    id: "actions",
    header: "Ações",
    cell: ({ row }) => row.original.id,
  }),
];

const data = computed<RowT[]>(() => (rows.value as any[]) ?? []);
const table = useVueTable<RowT>({
  get data() { return data.value },
  columns,
  // SAFE: apenas row model base (sem sorting/filter/pagination reativo)
  getCoreRowModel: getCoreRowModel(),
});

// ✅ helper do cabeçalho (evita casts/func headers no template)
function headerText(h: any) {
  const hd = h.column.columnDef.header;
  return typeof hd === "function" ? h.column.id : (hd ?? "");
}

onMounted(async () => {
  // sequencial para não “segurar” a renderização
  await list();
  await loadCombos();
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Contactos" />
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Contactos</h2>
    </template>

    <div class="p-6 space-y-4">
      <!-- toolbar -->
      <div class="flex flex-wrap items-center gap-2">
        <Input v-model="q" placeholder="Pesquisar..." class="max-w-sm" />
        <Button :disabled="loading" @click="list({ q: q.value })">Filtrar</Button>
        <Button @click="startNew">Novo</Button>
      </div>

      <!-- DataTable (modo seguro) -->
      <div class="rounded-xl border bg-white">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead v-for="h in table.getFlatHeaders()" :key="h.id">
                <div class="inline-flex items-center gap-1 select-none">
                  {{ headerText(h) }}
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="row in table.getRowModel().rows" :key="row.id" class="border-t">
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <template v-if="cell.column.id === 'actions'">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="outline" size="sm">Ações</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem @click="startEdit(row.original)">Editar</DropdownMenuItem>
                      <DropdownMenuItem
                        class="text-red-600"
                        @click="remove(row.original.id).then(() => list({ q: q.value }))"
                      >
                        Remover
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </template>
                <template v-else>
                  {{ cell.getValue() ?? "—" }}
                </template>
              </TableCell>
            </TableRow>

            <TableRow v-if="table.getRowModel().rows.length === 0">
              <TableCell :colspan="table.getAllColumns().length" class="p-4 text-center text-slate-500">
                Sem resultados
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Dialog com formulário -->
      <Dialog v-model:open="open">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{{ editingId ? "Editar" : "Novo" }} contacto</DialogTitle>
          </DialogHeader>

          <Form class="grid gap-3" @submit.prevent="save">
            <!-- Entidade -->
            <FormField name="entidade_id">
              <FormItem>
                <FormLabel>Entidade</FormLabel>
                <FormControl>
                  <Select v-model="form.entidade_id">
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione a entidade" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="e in entidadesOpts" :key="e.id" :value="e.id">
                        {{ e.nome }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <div class="grid sm:grid-cols-2 gap-3">
              <FormField name="nome">
                <FormItem>
                  <FormLabel>Nome</FormLabel>
                  <FormControl><Input v-model="form.nome" autofocus /></FormControl>
                </FormItem>
              </FormField>

              <FormField name="apelido">
                <FormItem>
                  <FormLabel>Apelido</FormLabel>
                  <FormControl><Input v-model="form.apelido" /></FormControl>
                </FormItem>
              </FormField>
            </div>

            <FormField name="funcao_id">
              <FormItem>
                <FormLabel>Função</FormLabel>
                <FormControl>
                  <Select v-model="form.funcao_id">
                    <SelectTrigger><SelectValue placeholder="Selecione a função" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="f in funcoesOpts" :key="f.id" :value="f.id">
                        {{ f.nome }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </FormControl>
              </FormItem>
            </FormField>

            <div class="grid sm:grid-cols-2 gap-3">
              <FormField name="telefone">
                <FormItem>
                  <FormLabel>Telefone</FormLabel>
                  <FormControl><Input v-model="form.telefone" /></FormControl>
                </FormItem>
              </FormField>
              <FormField name="telemovel">
                <FormItem>
                  <FormLabel>Telemóvel</FormLabel>
                  <FormControl><Input v-model="form.telemovel" /></FormControl>
                </FormItem>
              </FormField>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
              <FormField name="email">
                <FormItem>
                  <FormLabel>Email</FormLabel>
                  <FormControl><Input v-model="form.email" placeholder="ex: nome@empresa.pt" /></FormControl>
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

            <FormField name="consentimento_rgpd">
              <FormItem>
                <FormLabel>Consentimento RGPD</FormLabel>
                <FormControl>
                  <RadioGroup v-model="form.consentimento_rgpd" class="flex gap-4">
                    <div class="flex items-center gap-2">
                      <RadioGroupItem id="rgpd-sim-ct" value="sim" />
                      <FormLabel for="rgpd-sim-ct" class="m-0">Sim</FormLabel>
                    </div>
                    <div class="flex items-center gap-2">
                      <RadioGroupItem id="rgpd-nao-ct" value="nao" />
                      <FormLabel for="rgpd-nao-ct" class="m-0">Não</FormLabel>
                    </div>
                  </RadioGroup>
                </FormControl>
              </FormItem>
            </FormField>

            <FormField name="observacoes">
              <FormItem>
                <FormLabel>Observações</FormLabel>
                <FormControl><Textarea v-model="form.observacoes" rows="3" /></FormControl>
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
  </AuthenticatedLayout>
</template>
