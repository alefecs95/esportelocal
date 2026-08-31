<template>
  <component :is="currentComponent" />
</template>

<script setup>
import { ref, onMounted, shallowRef } from 'vue';
import LandingPage from './LandingPage.vue';
import PortalHome from '@/modules/portal/pages/PortalHome.vue';
import { isTenantDomain } from '@/utils/tenantHelpers';

const currentComponent = shallowRef(null);

onMounted(() => {
    // Check if we are on a tenant subdomain or the main domain
    if (isTenantDomain()) {
        currentComponent.value = PortalHome;
    } else {
        currentComponent.value = LandingPage;
    }
});
</script>
