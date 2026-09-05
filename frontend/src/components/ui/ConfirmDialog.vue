<template>
  <BaseModal :is-open="isOpen" :title="title" @close="$emit('cancel')" @confirm="$emit('confirm')">
    <p class="text-sm text-gray-600">{{ message }}</p>
  </BaseModal>
</template>

<script setup>
import BaseModal from './BaseModal.vue';

defineProps({
  isOpen: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar ação' },
  message: { type: String, default: 'Deseja continuar?' },
});

defineEmits(['confirm', 'cancel']);
</script>
