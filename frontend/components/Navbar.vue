<script setup lang="ts">
import { computed } from 'vue'

const {
  isLoading,
  refresh,
  openDatasetModal,
  isCustomDataActive,
  resetToDefaultDataset,
  isMockActive,
} = useOptimization()
</script>

<template>
  <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Logo & Branding -->
      <div class="flex items-center space-x-3">
        <div class="h-9 w-9 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20">
          BR
        </div>
        <div>
          <h1 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
            Beijer Ref
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
              Task Scheduler
            </span>
          </h1>
          <p class="text-xs text-slate-400">Optimization & Assignment Engine</p>
        </div>
      </div>

      <!-- Actions & Status Badges -->
      <div class="flex items-center space-x-2 sm:space-x-3">
        <!-- Custom Dataset Button (Requirement: Upload / edit JSON in UI) -->
        <button
          type="button"
          class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors border"
          :class="isCustomDataActive ? 'bg-blue-600/20 text-blue-300 border-blue-500/40 hover:bg-blue-600/30' : 'bg-slate-800 text-slate-200 border-slate-700 hover:bg-slate-700'"
          @click="openDatasetModal"
        >
          <svg class="w-3.5 h-3.5 mr-1.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          <span class="hidden sm:inline">{{ isCustomDataActive ? 'Custom Data Active' : 'Edit / Upload JSON' }}</span>
          <span class="sm:hidden">JSON</span>
        </button>

        <button
          v-if="isCustomDataActive"
          type="button"
          class="inline-flex items-center px-2 py-1.5 text-xs text-rose-400 hover:text-rose-300 border border-rose-500/30 bg-rose-500/10 rounded-lg hover:bg-rose-500/20 transition-colors"
          title="Reset to default dataset files"
          @click="resetToDefaultDataset"
        >
          Reset
        </button>

        <!-- Mock Mode Indicator -->
        <span
          v-if="isMockActive"
          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20"
        >
          Mock Mode Active
        </span>
        <span
          v-else
          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse" />
          <span class="hidden sm:inline">Live Engine Connected</span>
          <span class="sm:hidden">Live</span>
        </span>

        <!-- Refresh Button -->
        <button
          type="button"
          :disabled="isLoading"
          class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700 transition-colors disabled:opacity-50"
          @click="refresh()"
        >
          <svg
            class="w-3.5 h-3.5 sm:mr-1.5"
            :class="{ 'animate-spin': isLoading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.033 8.033 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span class="hidden sm:inline">Refresh</span>
        </button>
      </div>
    </div>
  </header>
</template>
