<template>
  <div class="overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-800">
      <thead class="text-xs text-gray-800 uppercase bg-gray-100">
        <tr>
          <th v-for="header in headers" :key="header.key" scope="col" class="px-6 py-3">
            {{ header.label }}
          </th>
          <th v-if="actions" scope="col" class="px-6 py-3">
            <span class="sr-only">Ações</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in (items || [])" :key="index" class="bg-white border-b hover:bg-gray-50">
          <td v-for="header in headers" :key="header.key" class="px-6 py-4">
            <span>
              <slot :name="header.key" :item="item">
                {{ item[header.key] }}
              </slot>
            </span>
          </td>
          <td v-if="actions" class="px-6 py-4 text-right">
            <span>
              <slot name="actions" :item="item"></slot>
            </span>
          </td>
        </tr>
        <tr v-if="!items || items.length === 0">
          <td :colspan="headers.length + (actions ? 1 : 0)" class="px-6 py-8 text-center text-gray-600">
            <slot name="empty">Nenhum registro encontrado.</slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  headers: {
    type: Array,
    required: true,
  },
  items: {
    type: Array,
    required: true,
    default: () => []
  },
  actions: {
    type: Boolean,
    default: false,
  },
});
</script>
