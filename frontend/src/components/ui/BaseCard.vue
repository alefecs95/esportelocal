<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div v-if="title" class="mb-4">
      <h3 class="text-lg leading-6 font-medium text-gray-900">{{ title }}</h3>
      <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
    </div>
    <slot></slot>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
});
</script>
