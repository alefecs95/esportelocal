<template>
  <div class="bg-white border border-dashed border-gray-200 rounded-xl px-6 py-12 text-center">
    <p class="text-lg font-semibold text-gray-800">{{ title }}</p>
    <p v-if="description" class="mt-2 text-sm text-gray-500 max-w-lg mx-auto">{{ description }}</p>
    <div v-if="$slots.action" class="mt-5">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
