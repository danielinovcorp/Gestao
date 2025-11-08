<!-- resources/js/Components/ui/table/Table.vue -->
<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
  <div :class="$props.class">
    <table class="w-full caption-bottom text-sm">
      <slot />
    </table>
  </div>
</template>