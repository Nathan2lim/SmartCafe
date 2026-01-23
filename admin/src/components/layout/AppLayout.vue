<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import ToastContainer from '@/components/common/ToastContainer.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const sidebarOpen = ref(false)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Mobile sidebar backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <AppSidebar
      :open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <!-- Main content -->
    <div class="lg:pl-64">
      <AppHeader @toggle-sidebar="sidebarOpen = true" />

      <main class="p-4 lg:p-6">
        <router-view />
      </main>
    </div>

    <!-- Toast notifications -->
    <ToastContainer />

    <!-- Confirm dialog -->
    <ConfirmDialog />
  </div>
</template>
