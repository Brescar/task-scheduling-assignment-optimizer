<script setup lang="ts">
const props = defineProps<{
  error: any
}>()

const { refresh } = useOptimization()
const router = useRouter()
const route = useRoute()

const enableMockMode = async () => {
  await router.push({
    query: {
      ...route.query,
      mock: 'true',
    },
  })
  window.location.reload()
}
</script>

<template>
  <div class="bg-rose-950/20 border border-rose-500/30 rounded-xl p-8 text-center max-w-xl mx-auto shadow-lg shadow-rose-500/5">
    <div class="h-12 w-12 rounded-full bg-rose-500/20 text-rose-400 mx-auto flex items-center justify-center mb-4 border border-rose-500/30">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>

    <h3 class="text-lg font-bold text-white tracking-tight">Calculation Engine Unreachable</h3>
    <p class="text-sm text-slate-300 mt-2">
      The Nuxt BFF server route could not communicate with the Symfony PHP backend.
    </p>

    <div class="mt-4 p-3 rounded bg-slate-900/80 border border-slate-800 text-left text-xs font-mono text-rose-300 overflow-x-auto">
      {{ props.error?.data?.error || props.error?.message || 'Connection refused to backend' }}
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-sm"
        @click="refresh()"
      >
        Retry Connection
      </button>

      <button
        type="button"
        class="inline-flex items-center px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700 transition-colors"
        @click="enableMockMode"
      >
        Switch to Mock Mode
      </button>
    </div>
  </div>
</template>

