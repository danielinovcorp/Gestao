<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
  <caption :class="$props.class">
    <slot />
  </caption>
</template>