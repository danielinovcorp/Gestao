<script setup>
import { Head } from '@inertiajs/vue3';
const title = "";
</script>

<template>
  <Head :title="title" />
  <div class="p-6">
    <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
    <div class="mt-4 rounded-xl border bg-white p-4 shadow-sm min-h-32">
      <!-- TODO: implementar -->
    </div>
  </div>
</template>
