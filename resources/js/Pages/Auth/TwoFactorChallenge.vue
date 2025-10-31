<script setup>
import { ref } from 'vue'
import axios from 'axios'

const code = ref('')
const recovery_code = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true; error.value = ''
  try {
    await axios.post('/two-factor-challenge', {
      code: code.value || undefined,
      recovery_code: recovery_code.value || undefined,
    })
    window.location.href = '/' // ou rota pretendida após login
  } catch (e) {
    error.value = 'Código inválido. Tenta novamente.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-md mx-auto mt-16 space-y-4">
    <h1 class="text-xl font-semibold">Autenticação de dois fatores</h1>
    <p class="text-sm text-slate-500">Introduz o código do teu Authenticator ou um código de recuperação.</p>

    <input v-model="code" class="w-full border rounded px-3 py-2" placeholder="Código 2FA" inputmode="numeric" />
    <div class="text-center text-sm text-slate-500">— ou —</div>
    <input v-model="recovery_code" class="w-full border rounded px-3 py-2" placeholder="Código de recuperação" />

    <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

    <button :disabled="loading" @click="submit" class="w-full rounded px-3 py-2 bg-indigo-600 text-white">
      Entrar
    </button>
  </div>
</template>
