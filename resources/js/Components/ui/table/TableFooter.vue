<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
  <tfoot :class="$props.class">
    <slot />
  </tfoot>
</template>