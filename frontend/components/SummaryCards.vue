<script setup lang="ts">
import type { FeasibleOptimizationResult } from '~/types/optimizer'

const props = defineProps<{
  data: FeasibleOptimizationResult
}>()

const { comparisonMetrics, selectedVariant } = useOptimization()

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(props.data.totalPrice)
})
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Card 1: Total Price -->
    <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-sm relative overflow-hidden">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Price</span>
        <div class="h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-2xl font-bold text-white tracking-tight">{{ formattedPrice }}</div>
        <!-- Side-by-side comparison indicator -->
        <p v-if="comparisonMetrics" class="mt-1 text-xs flex items-center gap-1">
          <span
            v-if="selectedVariant === 'cost'"
            class="text-emerald-400 font-medium"
          >
            Saves ${{ comparisonMetrics.costDifference.toLocaleString() }} vs Makespan
          </span>
          <span
            v-else
            class="text-amber-400 font-medium"
          >
            +${{ comparisonMetrics.costDifference.toLocaleString() }} (+{{ Math.round((comparisonMetrics.costDifference / (props.data.totalPrice - comparisonMetrics.costDifference)) * 100) }}%) vs Min Cost
          </span>
        </p>
      </div>
    </div>

    <!-- Card 2: Makespan -->
    <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-sm relative overflow-hidden">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Makespan (Completion Time)</span>
        <div class="h-8 w-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-2xl font-bold text-white tracking-tight">{{ props.data.makespan.toFixed(1) }} <span class="text-sm font-normal text-slate-400">hours</span></div>
        <!-- Side-by-side comparison indicator -->
        <p v-if="comparisonMetrics" class="mt-1 text-xs flex items-center gap-1">
          <span
            v-if="selectedVariant === 'makespan'"
            class="text-emerald-400 font-medium"
          >
            -{{ comparisonMetrics.makespanDifference.toFixed(1) }}h ({{ Math.round((comparisonMetrics.makespanDifference / (props.data.makespan + comparisonMetrics.makespanDifference)) * 100) }}% faster) vs Min Cost
          </span>
          <span
            v-else
            class="text-amber-400 font-medium"
          >
            +{{ comparisonMetrics.makespanDifference.toFixed(1) }}h longer vs Makespan
          </span>
        </p>
      </div>
    </div>

    <!-- Card 3: Number of Tasks -->
    <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-sm relative overflow-hidden">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Assigned Tasks</span>
        <div class="h-8 w-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-2xl font-bold text-white tracking-tight">{{ props.data.taskCount }} <span class="text-sm font-normal text-slate-400">tasks</span></div>
        <p class="mt-1 text-xs text-slate-400">100% of workload allocated</p>
      </div>
    </div>

    <!-- Card 4: Active Employees -->
    <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-sm relative overflow-hidden">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Active Employees</span>
        <div class="h-8 w-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-2xl font-bold text-white tracking-tight">
          {{ props.data.activeEmployeeCount }} <span class="text-sm font-normal text-slate-400">/ {{ props.data.totalEmployeeCount }}</span>
        </div>
        <p class="mt-1 text-xs text-slate-400">
          {{ props.data.activeEmployeeCount === props.data.totalEmployeeCount ? 'Optimal fleet utilization' : `${props.data.totalEmployeeCount - props.data.activeEmployeeCount} employees idle` }}
        </p>
      </div>
    </div>
  </div>
</template>

