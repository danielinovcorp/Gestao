<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Breeze components
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Modal from '@/Components/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'

const enabled = ref(false)
const qrSvg = ref('')
const recovery = ref([])
const busy = ref(false)
const error = ref('')
const success = ref('')

// modal
const confirmOpen = ref(false)
const password = ref('')
const confirming = ref(false)
const confirmError = ref('')

async function csrf() {
  try { await axios.get('/sanctum/csrf-cookie') } catch {}
}

async function fetchStatus() {
  error.value = ''; success.value = ''
  try {
    const { data } = await axios.get('/user/two-factor-qr-code')
    enabled.value = true
    qrSvg.value = (typeof data === 'object' && data.svg) ? data.svg : data
  } catch {
    enabled.value = false
    qrSvg.value = ''
  }
  if (enabled.value) {
    try {
      const { data } = await axios.get('/user/two-factor-recovery-codes')
      recovery.value = Array.isArray(data) ? data : (data.codes ?? [])
    } catch { recovery.value = [] }
  } else {
    recovery.value = []
  }
}

function openConfirm() {
  confirmError.value = ''
  password.value = ''
  confirmOpen.value = true
}

async function confirmPasswordFlow() {
  confirming.value = true
  confirmError.value = ''
  try {
    await csrf()
    await axios.post('/user/confirm-password', { password: password.value })
    confirmOpen.value = false
    await enable2FA(true)
  } catch (e) {
    const status = e?.response?.status
    if (status === 422 || status === 401) confirmError.value = 'Palavra-passe incorreta.'
    else if (status === 419) confirmError.value = 'Sessão expirada. Atualiza a página e tenta de novo.'
    else confirmError.value = 'Não foi possível confirmar. Tenta novamente.'
  } finally {
    confirming.value = false
  }
}

async function enable2FA(alreadyConfirmed = false) {
  busy.value = true; error.value = ''; success.value = ''
  try {
    await csrf()
    await axios.post('/user/two-factor-authentication')
    success.value = '2FA ativado. Digitaliza o QR com o teu Authenticator.'
    await fetchStatus()
  } catch (e) {
    const status = e?.response?.status
    if (status === 423 && !alreadyConfirmed) {
      openConfirm()
    } else if (status === 419) {
      error.value = 'Sessão/CSRF expirados. Faz login novamente ou atualiza a página.'
    } else {
      error.value = 'Não foi possível ativar 2FA. Tenta novamente.'
    }
  } finally {
    busy.value = false
  }
}

async function disable2FA() {
  busy.value = true; error.value = ''; success.value = ''
  try {
    await csrf()
    await axios.delete('/user/two-factor-authentication')
    success.value = '2FA desativado.'
    await fetchStatus()
  } catch (e) {
    const status = e?.response?.status
    if (status === 423) {
      openConfirm()
    } else if (status === 419) {
      error.value = 'Sessão/CSRF expirados. Faz login novamente.'
    } else {
      error.value = 'Não foi possível desativar 2FA.'
    }
  } finally {
    busy.value = false
  }
}

async function regenerateCodes() {
  busy.value = true; error.value = ''; success.value = ''
  try {
    await csrf()
    await axios.post('/user/two-factor-recovery-codes')
    const { data } = await axios.get('/user/two-factor-recovery-codes')
    recovery.value = Array.isArray(data) ? data : (data.codes ?? [])
    success.value = 'Novos códigos de recuperação gerados.'
  } catch {
    error.value = 'Falha ao gerar códigos.'
  } finally {
    busy.value = false
  }
}

onMounted(fetchStatus)
</script>

<template>
  <section class="p-6 bg-white dark:bg-neutral-900 rounded-xl ring-1 ring-slate-200/70 space-y-4">
    <header>
      <h2 class="text-lg font-semibold">Autenticação de dois fatores (2FA)</h2>
      <p class="text-sm text-slate-500">Adiciona uma segunda camada de segurança à tua conta.</p>
    </header>

    <div class="space-y-3">
      <div v-if="success" class="text-sm text-emerald-600">{{ success }}</div>
      <div v-if="error" class="text-sm text-red-600">{{ error }}</div>

      <div class="flex items-center gap-3">
        <span class="text-sm">Estado:</span>
        <span class="text-xs rounded-full px-2 py-1"
              :class="enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
          {{ enabled ? 'Ativo' : 'Inativo' }}
        </span>
      </div>

      <div class="flex gap-2">
        <PrimaryButton :disabled="busy" v-if="!enabled" @click="enable2FA()">
          Ativar 2FA
        </PrimaryButton>
        <PrimaryButton :disabled="busy" class="bg-red-600 hover:bg-red-700" v-else @click="disable2FA()">
          Desativar 2FA
        </PrimaryButton>

        <SecondaryButton :disabled="busy" v-if="enabled" @click="regenerateCodes">
          Regenerar códigos
        </SecondaryButton>
      </div>

      <div v-if="enabled" class="grid md:grid-cols-2 gap-6">
        <div>
          <h3 class="font-medium mb-2">Código QR</h3>
          <p class="text-sm text-slate-500 mb-3">Lê com Google Authenticator, 1Password, Authy, etc.</p>
          <div class="p-3 rounded border bg-white" v-html="qrSvg" />
        </div>
        <div>
          <h3 class="font-medium mb-2">Códigos de recuperação</h3>
          <p class="text-sm text-slate-500 mb-3">Guarda estes códigos num local seguro.</p>
          <ul class="text-sm font-mono grid grid-cols-1 sm:grid-cols-2 gap-2">
            <li v-for="(c, i) in recovery" :key="i" class="px-2 py-1 rounded border">{{ c }}</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal de confirmação de palavra-passe -->
  <Modal :show="confirmOpen" @close="confirmOpen=false">
    <div class="p-6">
      <h2 class="text-lg font-semibold">Confirmar palavra-passe</h2>
      <p class="text-sm text-slate-500 mb-4">Por segurança, confirma a tua palavra-passe para continuar.</p>

      <div class="space-y-2">
        <InputLabel for="password" value="Palavra-passe" />
        <TextInput id="password" v-model="password" type="password" class="mt-1 block w-full"
                   autocomplete="current-password" @keyup.enter="confirmPasswordFlow" />
        <p v-if="confirmError" class="text-sm text-red-600">{{ confirmError }}</p>
      </div>

      <div class="mt-6 flex justify-end gap-2">
        <SecondaryButton :disabled="confirming" @click="confirmOpen=false">Cancelar</SecondaryButton>
        <PrimaryButton :disabled="confirming || !password" @click="confirmPasswordFlow">
          {{ confirming ? 'Confirmando…' : 'Confirmar' }}
        </PrimaryButton>
      </div>
    </div>
  </Modal>
</template>
