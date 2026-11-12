<script setup lang="ts">
import type { VariantType } from '~/types/optimizer'

const { selectedVariant, switchVariant, isLoading } = useOptimization()

const variants: { id: VariantType; label: string; subtext: string; badge: string }[] = [
  {
    id: 'cost',
    label: 'Minimum Total Cost',
    subtext: 'Minimizes financial expenditure via greedy rate matching',
    badge: 'O(T · E) Optimal',
  },
  {
    id: 'makespan',
    label: 'Minimum Completion Time (Makespan)',
    subtext: 'Balances employee workload via Restricted LPT heuristic',
    badge: 'NP-Hard (LPT Heuristic)',
  },
]
</script>

<template>
  <div class="bg-slate-800/60 p-1.5 rounded-xl border border-slate-700/80">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
      <button
        v-for="variant in variants"
        :key="variant.id"
        type="button"
        :disabled="isLoading"
        class="relative flex flex-col p-3 rounded-lg text-left transition-all duration-200"
        :class="[
          selectedVariant === variant.id
            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20 ring-1 ring-blue-400'
            : 'text-slate-300 hover:bg-slate-700/50 hover:text-white',
        ]"
        @click="switchVariant(variant.id)"
      >
        <div class="flex items-center justify-between w-full">
          <span class="text-sm font-semibold tracking-tight">{{ variant.label }}</span>
          <span
            class="text-[10px] uppercase font-mono px-2 py-0.5 rounded-full"
            :class="[
              selectedVariant === variant.id
                ? 'bg-blue-700/80 text-blue-100 border border-blue-400/30'
                : 'bg-slate-700 text-slate-400 border border-slate-600',
            ]"
          >
            {{ variant.badge }}
          </span>
        </div>
        <p
          class="text-xs mt-1 transition-colors"
          :class="selectedVariant === variant.id ? 'text-blue-100' : 'text-slate-400'"
        >
          {{ variant.subtext }}
        </p>
      </button>
    </div>
  </div>
</template>

