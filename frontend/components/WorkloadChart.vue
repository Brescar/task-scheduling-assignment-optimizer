<script setup lang="ts">
import type { EmployeeWorkload, FeasibleOptimizationResult } from '~/types/optimizer'

const props = defineProps<{
  data: FeasibleOptimizationResult
}>()

const { selectedEmployeeId } = useOptimization()

const selectEmployee = (id: number) => {
  if (selectedEmployeeId.value === id) {
    selectedEmployeeId.value = null // toggle off
  } else {
    selectedEmployeeId.value = id
  }
}
</script>

<template>
  <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-700/60 gap-2">
      <div>
        <h3 class="text-base font-semibold text-white tracking-tight flex items-center gap-2">
          Employee Workload Distribution
          <span class="text-xs font-normal text-slate-400">
            (Normalized to Makespan: {{ props.data.makespan.toFixed(1) }}h)
          </span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Click an employee row to filter their assigned tasks in the table below
        </p>
      </div>
      <div class="flex items-center gap-3 text-xs text-slate-400">
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-sm bg-rose-500 inline-block" /> Makespan Bottleneck
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block" /> Active
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-sm bg-slate-700 inline-block" /> Idle (0h)
        </span>
      </div>
    </div>

    <!-- Workload Bars List -->
    <div class="mt-4 space-y-2.5">
      <div
        v-for="employee in props.data.employeeSummaries"
        :key="employee.employeeId"
        class="group p-2 rounded-lg transition-colors cursor-pointer border"
        :class="[
          selectedEmployeeId === employee.employeeId
            ? 'bg-blue-950/40 border-blue-500/50 ring-1 ring-blue-500/30'
            : employee.totalAssignedHours === 0
              ? 'bg-slate-900/30 border-slate-800/40 opacity-40 hover:opacity-70'
              : 'bg-slate-900/50 border-slate-800/60 hover:bg-slate-700/30',
        ]"
        @click="selectEmployee(employee.employeeId)"
      >
        <div class="flex items-center justify-between text-xs mb-1.5">
          <div class="flex items-center gap-2">
            <span class="font-medium text-slate-200">Employee #{{ employee.employeeId }}</span>
            <!-- Bottleneck Tag -->
            <span
              v-if="employee.totalAssignedHours > 0 && Math.abs(employee.totalAssignedHours - props.data.makespan) < 0.001"
              class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30"
            >
              Bottleneck ({{ props.data.makespan.toFixed(1) }}h)
            </span>
            <!-- Idle Tag -->
            <span
              v-else-if="employee.totalAssignedHours === 0"
              class="px-1.5 py-0.2 rounded text-[10px] text-slate-500 font-mono"
            >
              Idle
            </span>
            <!-- Filter active indicator -->
            <span
              v-if="selectedEmployeeId === employee.employeeId"
              class="text-[10px] text-blue-400 font-medium"
            >
              (Filtered)
            </span>
          </div>

          <div class="flex items-center gap-3">
            <span class="text-slate-400">{{ employee.taskCount }} {{ employee.taskCount === 1 ? 'task' : 'tasks' }}</span>
            <span class="font-mono font-semibold" :class="employee.totalAssignedHours > 0 ? 'text-white' : 'text-slate-500'">
              {{ employee.totalAssignedHours.toFixed(1) }} h
            </span>
            <span class="text-[11px] font-mono text-slate-400 w-12 text-right">
              {{ props.data.makespan > 0 ? ((employee.totalAssignedHours / props.data.makespan) * 100).toFixed(0) : 0 }}%
            </span>
          </div>
        </div>

        <!-- Progress Track -->
        <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden p-0.5">
          <div
            class="h-full rounded-full transition-all duration-500 ease-out"
            :class="[
              employee.totalAssignedHours > 0 && Math.abs(employee.totalAssignedHours - props.data.makespan) < 0.001
                ? 'bg-gradient-to-r from-rose-500 to-red-400 shadow-sm shadow-rose-500/50'
                : employee.totalAssignedHours > 0
                  ? 'bg-gradient-to-r from-blue-600 to-indigo-500'
                  : 'bg-transparent',
            ]"
            :style="{
              width: `${props.data.makespan > 0 ? Math.min(100, (employee.totalAssignedHours / props.data.makespan) * 100) : 0}%`,
            }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

