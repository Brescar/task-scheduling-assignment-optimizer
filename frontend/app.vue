<script setup lang="ts">
import type { FeasibleOptimizationResult, InfeasibleOptimizationResult } from '~/types/optimizer'

const {
  activeResult,
  isLoading,
  error,
  isFeasible,
} = useOptimization()

const feasibleResult = computed(() => {
  return isFeasible.value ? (activeResult.value as FeasibleOptimizationResult) : null
})

const infeasibleResult = computed(() => {
  return !isFeasible.value && activeResult.value ? (activeResult.value as InfeasibleOptimizationResult) : null
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-900 text-slate-100 font-sans selection:bg-blue-600 selection:text-white">
    <!-- Navbar -->
    <Navbar />

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <!-- Variant Switcher -->
      <VariantSwitcher />

      <!-- Loading State (Skeleton) -->
      <div v-if="isLoading && !activeResult" class="space-y-6 animate-pulse">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="h-28 rounded-xl bg-slate-800/60 border border-slate-700/50" />
        </div>
        <div class="h-64 rounded-xl bg-slate-800/60 border border-slate-700/50" />
        <div class="h-96 rounded-xl bg-slate-800/60 border border-slate-700/50" />
      </div>

      <!-- Error State (e.g. backend unreachable) -->
      <ErrorState v-else-if="error" :error="error" />

      <!-- Infeasible State (Requirement 7) -->
      <InfeasibleAlert
        v-else-if="infeasibleResult"
        :data="infeasibleResult"
      />

      <!-- Feasible State: Dashboard Analytics -->
      <template v-else-if="feasibleResult">
        <!-- Summary Cards (Requirement 4 & Comparison) -->
        <SummaryCards :data="feasibleResult" />

        <!-- Workload Chart (Requirement 6) -->
        <WorkloadChart :data="feasibleResult" />

        <!-- Assignments Table (Requirement 5) -->
        <AssignmentsTable :data="feasibleResult" />
      </template>

      <!-- Custom Dataset Editor Modal -->
      <DatasetModal />
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/40 py-6 text-center text-xs text-slate-400">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div>
          Beijer Ref PHP Full-Stack Developer Case Study &copy; 2026
        </div>
        <div class="flex items-center gap-4 text-slate-400">
          <span>Symfony 7.4 (PHP 8.4)</span>
          <span>&bull;</span>
          <span>FrankenPHP</span>
          <span>&bull;</span>
          <span>Nuxt 3 SSR + BFF</span>
          <span>&bull;</span>
          <span>Pure Tailwind CSS</span>
        </div>
      </div>
    </footer>
  </div>
</template>

