<script setup lang="ts">
import { computed } from "vue";
const isTipos = computed(() =>
  route().current('config.calendario.tipos.*')
);
</script>


<template>
    <div class="inline-flex rounded-xl bg-slate-100 p-1">
        <a
            :href="route('config.calendario.tipos.index')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition"
            :class="
                isTipos
                    ? 'bg-indigo-600 text-white shadow'
                    : 'text-slate-700 hover:bg-white'
            "
        >
            Tipos
        </a>
        <a
            :href="route('config.calendario.acoes.index')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition"
            :class="
                !isTipos
                    ? 'bg-indigo-600 text-white shadow'
                    : 'text-slate-700 hover:bg-white'
            "
        >
            Ações
        </a>
    </div>
</template>
