<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
  <tbody :class="$props.class">
    <slot />
  </tbody>
</template>