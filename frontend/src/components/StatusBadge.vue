<script setup>
defineProps({
  status: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <span class="badge" :data-status="status">{{ status }}</span>
</template>

<style scoped>
.badge {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.badge[data-status='success'] {
  background: rgba(52, 199, 89, 0.2);
  color: var(--success);
}

.badge[data-status='partial'] {
  background: rgba(245, 166, 35, 0.2);
  color: var(--warning);
}

.badge[data-status='failed'] {
  background: rgba(255, 69, 58, 0.2);
  color: var(--danger);
}
</style>
