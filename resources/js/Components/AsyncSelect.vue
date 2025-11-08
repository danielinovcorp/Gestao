<!-- Components/AsyncSelect.vue -->
<script setup lang="ts">
import { ref, watch, computed, onMounted } from "vue";
import { Input } from "@/Components/ui/input";
import { debounce } from "lodash-es";

const props = defineProps<{
    fetchUrl: string;
    labelField?: string;
    valueField?: string;
    placeholder?: string;
    modelValue?: any;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: any): void;
    (e: "select", item: any): void;
}>();

const q = ref("");
const items = ref<any[]>([]);
const loading = ref(false);
const open = ref(false);
const selectedItem = ref<any>(null);

const labelField = computed(() => props.labelField ?? "nome");
const valueField = computed(() => props.valueField ?? "id");

// Inicialização correta do valor
onMounted(() => {
    if (props.modelValue) {
        fetchInitialValue();
    }
});

// Busca o item quando recebe um valor externo
async function fetchInitialValue() {
    if (!props.modelValue) return;
    
    try {
        loading.value = true;
        const url = `${props.fetchUrl}${encodeURIComponent('')}`;
        const res = await fetch(url, { credentials: "include" });
        const data = await res.json();
        
        // ✅ CORREÇÃO: Busca por 'value' ou 'id'
        const found = data.find((item: any) => 
            (item.value == props.modelValue) || (item.id == props.modelValue)
        );
        if (found) {
            selectedItem.value = found;
            q.value = found[labelField.value] || found.referencia || found.nif || "";
        }
    } catch (err) {
        console.error("Erro ao buscar valor inicial:", err);
    } finally {
        loading.value = false;
    }
}

// Debounce para busca
const search = debounce(async () => {
    if (!q.value.trim()) {
        items.value = [];
        open.value = false;
        return;
    }

    loading.value = true;
    try {
        const url = `${props.fetchUrl}${encodeURIComponent(q.value.trim())}`;
        const res = await fetch(url, { credentials: "include" });
        const data = await res.json();
        items.value = Array.isArray(data) ? data : [];
        open.value = true;
    } catch (err) {
        console.error("Erro no AsyncSelect:", err);
        items.value = [];
    } finally {
        loading.value = false;
    }
}, 300);

// Busca ao digitar
watch(q, (val) => {
    if (val.length === 0) {
        items.value = [];
        open.value = false;
        if (!selectedItem.value) {
            emit("update:modelValue", null);
        }
    } else if (val.length >= 2) {
        search();
    }
});

// ✅ CORREÇÃO COMPLETA: Seleção corrigida
function choose(item: any) {
    console.log('AsyncSelect - Selecionando item:', item);
    
    selectedItem.value = item;
    
    // ✅ CORREÇÃO CRÍTICA: Usa 'value' se existir, senão usa 'id'
    const value = item.value !== undefined ? item.value : item.id;
    console.log('AsyncSelect - Valor a enviar:', value, 'Tipo:', typeof value);
    
    if (value === undefined || value === null) {
        console.error('AsyncSelect - ERRO: Valor é undefined ou null');
        return;
    }
    
    // ✅ CORREÇÃO: Envia o valor diretamente, sem conversão
    emit("update:modelValue", value);
    emit("select", item);
    
    q.value = item[labelField.value] || item.referencia || item.nif || "";
    open.value = false;
    items.value = [];
}

// ✅ CORREÇÃO: Watch simplificado
watch(
    () => props.modelValue,
    (newValue) => {
        console.log('AsyncSelect - modelValue changed:', { 
            newValue, 
            tipo: typeof newValue 
        });
        
        if (newValue === null || newValue === undefined || newValue === '') {
            q.value = "";
            selectedItem.value = null;
            items.value = [];
            open.value = false;
        } else if (newValue && !selectedItem.value) {
            fetchInitialValue();
        }
    },
    { immediate: true }
);

// Fecha dropdown ao clicar fora
function handleClickOutside(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.closest('.async-select-container')) {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

import { onUnmounted } from 'vue';
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="async-select-container relative">
        <Input
            v-model="q"
            :placeholder="placeholder ?? 'Pesquisar...'"
            @focus="open = true"
            @click="open = true"
            class="w-full"
        />

        <!-- DEBUG: Mostra o valor atual -->
        <div v-if="props.modelValue !== null && props.modelValue !== undefined" class="text-xs text-green-600 mt-1">
            ✅ Selecionado (Valor: {{ props.modelValue }}, Tipo: {{ typeof props.modelValue }})
        </div>
        <div v-else class="text-xs text-red-500 mt-1">
            ❌ Nenhum valor selecionado (atual: {{ props.modelValue }})
        </div>

        <div
            v-if="open && (loading || items.length > 0)"
            class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <div v-if="loading" class="p-3 text-center text-sm text-slate-500">
                A carregar...
            </div>

            <button
                v-for="it in items"
                :key="it.id || it.value"
                type="button"
                class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0"
                @click="choose(it)"
            >
                <div class="font-medium text-sm">{{ it.nome || it.label }}</div>
                <div v-if="it.referencia" class="text-xs text-slate-500">Ref: {{ it.referencia }}</div>
                <div v-if="it.nif" class="text-xs text-slate-500">NIF: {{ it.nif }}</div>
                <div v-if="it.numero" class="text-xs text-slate-500">#{{ it.numero }}</div>
                <div class="text-xs text-blue-500">
                    ID: {{ it.id }}, Value: {{ it.value }}
                </div>
            </button>

            <div v-if="!loading && items.length === 0 && q.length >= 2" class="p-3 text-center text-sm text-slate-500">
                Nenhum resultado
            </div>
        </div>
    </div>
</template>