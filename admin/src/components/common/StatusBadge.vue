<script setup lang="ts">
import type { OrderStatus } from '@/types'
import { ORDER_STATUS_LABELS } from '@/types'

defineProps<{
  status: OrderStatus
}>()
</script>

<template>
  <span :class="['badge', `badge-${status}`]">
    {{ ORDER_STATUS_LABELS[status] }}
  </span>
</template>
