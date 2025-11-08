<script setup lang="ts">
defineProps<{
  class?: string
  colSpan?: number
}>()
</script>

<template>
  <th
    :colspan="colSpan"
    :class="$props.class"
  >
    <slot />
  </th>
</template>