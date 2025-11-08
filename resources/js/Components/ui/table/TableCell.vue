<script setup lang="ts">
defineProps<{
  class?: string
  colSpan?: number
}>()
</script>

<template>
  <td
    :colspan="colSpan"
    :class="$props.class"
  >
    <slot />
  </td>
</template>