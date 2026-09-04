<template>
  <div class="min-w-0">
    <label v-if="label" class="block text-sm font-semibold text-gray-800 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <p v-if="hint" class="text-xs text-gray-500 mb-2 leading-relaxed">{{ hint }}</p>
    <slot />
    <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
});
</script>
