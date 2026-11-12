<script setup lang="ts">
import type { InfeasibleOptimizationResult } from '~/types/optimizer'

defineProps<{
  data: InfeasibleOptimizationResult
}>()

const { refresh } = useOptimization()
</script>

<template>
  <div class="bg-amber-950/20 border border-amber-500/30 rounded-xl p-6 sm:p-8 text-center max-w-2xl mx-auto shadow-lg shadow-amber-500/5">
    <div class="h-12 w-12 rounded-full bg-amber-500/20 text-amber-400 mx-auto flex items-center justify-center mb-4 border border-amber-500/30">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>

    <h3 class="text-lg font-bold text-white tracking-tight">
      Optimization Infeasible ({{ data.variant.toUpperCase() }})
    </h3>

    <p class="text-sm text-slate-300 mt-2 max-w-lg mx-auto">
      The problem instance cannot be scheduled because the required skill level for one or more tasks exceeds that of every available employee in the pool.
    </p>

    <!-- Unassignable Task IDs Badges -->
    <div class="mt-5 p-4 rounded-lg bg-slate-900/60 border border-slate-800 text-left">
      <div class="text-xs uppercase font-mono tracking-wider text-amber-400 font-semibold mb-2">
        Unassignable Task ID(s):
      </div>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="id in data.unassignableTaskIds"
          :key="id"
          class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-amber-500/10 text-amber-300 border border-amber-500/30"
        >
          Task #{{ id }}
        </span>
      </div>
    </div>

    <div class="mt-6 flex items-center justify-center gap-3">
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-700 border border-slate-700 transition-colors"
        @click="refresh()"
      >
        Re-check Feasibility
      </button>
    </div>
  </div>
</template>

