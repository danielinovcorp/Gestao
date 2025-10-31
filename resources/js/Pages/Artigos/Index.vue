<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import { useArtigos } from '@/composables/useArtigos'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { rows, list, create, update, remove, loading } = useArtigos()
const q = ref('')
const open = ref(false)
const form = ref<any>({ sku: '', descricao: '', preco: 0, iva: 23, unidade: 'un', ativo: true })
const editingId = ref<number | null>(null)

function startNew() { editingId.value = null; form.value = { sku:'', descricao:'', preco:0, iva:23, unidade:'un', ativo:true }; open.value = true }
function startEdit(a: any) { editingId.value = a.id; form.value = { sku:a.sku, descricao:a.descricao, preco:a.preco, iva:a.iva, unidade:a.unidade, ativo:a.ativo }; open.value = true }
async function save() {
  if (editingId.value) await update(editingId.value, form.value)
  else await create(form.value)
  open.value = false
  await list({ q: q.value })
}

onMounted(() => list())
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Artigos" />
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Artigos</h2>
    </template>

    <div class="p-6 space-y-4">
      <div class="flex items-center gap-2">
        <Input v-model="q" placeholder="Pesquisar por SKU/Descrição..." class="max-w-sm" />
        <Button :disabled="loading" @click="list({ q: q.value })">Filtrar</Button>
        <Button @click="startNew">Novo</Button>
      </div>

      <div class="rounded-xl border bg-white">
        <table class="w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="p-2 text-left">ID</th>
              <th class="p-2 text-left">SKU</th>
              <th class="p-2 text-left">Descrição</th>
              <th class="p-2 text-left">Preço</th>
              <th class="p-2 text-left">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in rows" :key="a.id" class="border-t">
              <td class="p-2">{{ a.id }}</td>
              <td class="p-2">{{ a.sku }}</td>
              <td class="p-2">{{ a.descricao }}</td>
              <td class="p-2">€ {{ Number(a.preco).toFixed(2) }}</td>
              <td class="p-2">
                <div class="flex gap-2">
                  <Button variant="secondary" @click="startEdit(a)">Editar</Button>
                  <Button variant="destructive" :disabled="loading" @click="remove(a.id).then(() => list({ q: q.value }))">Remover</Button>
                </div>
              </td>
            </tr>
            <tr v-if="!rows || rows.length === 0">
              <td colspan="5" class="p-4 text-center text-slate-500">Sem resultados</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- modal simples -->
      <div v-if="open" class="fixed inset-0 bg-black/40 grid place-items-center p-4">
        <div class="w-full max-w-lg rounded-xl bg-white p-4 space-y-3">
          <h3 class="font-semibold">{{ editingId ? 'Editar' : 'Novo' }} artigo</h3>
          <div class="grid gap-2">
            <label class="text-sm">SKU</label>
            <Input v-model="form.sku" />
            <label class="text-sm">Descrição</label>
            <Input v-model="form.descricao" />
            <label class="text-sm">Preço</label>
            <Input v-model="form.preco" type="number" step="0.01" />
            <label class="text-sm">IVA</label>
            <Input v-model="form.iva" type="number" />
            <label class="text-sm">Unidade</label>
            <Input v-model="form.unidade" />
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="secondary" @click="open = false">Cancelar</Button>
            <Button @click="save">Guardar</Button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
