<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
  <tr :class="$props.class">
    <slot />
  </tr>
</template>