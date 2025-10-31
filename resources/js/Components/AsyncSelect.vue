<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";

const props = defineProps<{
    fetchUrl: string;
    labelField?: string;
    valueField?: string;
    placeholder?: string;
    modelValue?: any;
}>();
const emit = defineEmits(["update:modelValue", "select"]);

const items = ref<any[]>([]);
const q = ref("");
const loading = ref(false);
const labelField = props.labelField ?? "nome";
const valueField = props.valueField ?? "id";

async function search() {
    loading.value = true;
    const res = await fetch(`${props.fetchUrl}${encodeURIComponent(q.value)}`);
    items.value = await res.json();
    loading.value = false;
}
onMounted(search);
watch(q, (v) => {
    if (v.length === 0 || v.length > 1) search();
});

function choose(item: any) {
    emit("update:modelValue", item[valueField]);
    emit("select", item);
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex gap-2">
            <Input v-model="q" :placeholder="placeholder ?? 'Pesquisar...'" />
            <Button type="button" @click="search" :disabled="loading">{{
                loading ? "..." : "OK"
            }}</Button>
        </div>
        <div class="max-h-40 overflow-auto border rounded-md">
            <button
                v-for="it in items"
                :key="it[valueField]"
                type="button"
                class="w-full text-left px-3 py-2 hover:bg-accent"
                @click="choose(it)"
            >
                <div class="font-medium">{{ it[labelField] }}</div>
                <div v-if="it.referencia" class="text-xs text-muted-foreground">
                    Ref: {{ it.referencia }}
                </div>
                <div v-if="it.nif" class="text-xs text-muted-foreground">
                    NIF: {{ it.nif }}
                </div>
            </button>
            <div
                v-if="!items.length && !loading"
                class="text-sm text-center p-2 text-muted-foreground"
            >
                Sem resultados
            </div>
        </div>
    </div>
</template>
