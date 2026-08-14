<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>

<template>
  <div class="card">
    <div class="font-semibold text-xl mb-4">
      {{ title }}
    </div>

    <p class="text-muted-color">This feature is currently under development.</p>
  </div>
</template>
