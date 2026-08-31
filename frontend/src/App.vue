<template>
  <router-view></router-view>
</template>

<script setup>
import { onMounted } from 'vue';
import { useTheme } from '@/composables/useTheme';

const { loadTheme } = useTheme();

onMounted(() => {
  loadTheme();
});
</script>
