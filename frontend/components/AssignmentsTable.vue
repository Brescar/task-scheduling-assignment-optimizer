<script setup lang="ts">
import type { Assignment, FeasibleOptimizationResult } from '~/types/optimizer'

const props = defineProps<{
  data: FeasibleOptimizationResult
}>()

const {
  filteredAssignments,
  selectedEmployeeId,
  availableEmployees,
} = useOptimization()

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(val)
}
</script>

<template>
  <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl overflow-hidden shadow-sm">
    <!-- Header with Filter Dropdown -->
    <div class="p-5 border-b border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-semibold text-white tracking-tight">Task Assignments</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Showing {{ filteredAssignments.length }} of {{ props.data.assignments.length }} tasks
        </p>
      </div>

      <!-- Employee Filter Dropdown -->
      <div class="flex items-center space-x-2">
        <label for="employee-filter" class="text-xs font-medium text-slate-300 whitespace-nowrap">
          Filter by Employee:
        </label>
        <select
          id="employee-filter"
          v-model="selectedEmployeeId"
          class="bg-slate-900 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-1.5 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors"
        >
          <option :value="null">All Employees ({{ props.data.assignments.length }} tasks)</option>
          <option
            v-for="empId in availableEmployees"
            :key="empId"
            :value="empId"
          >
            Employee #{{ empId }}
          </option>
        </select>

        <button
          v-if="selectedEmployeeId !== null"
          type="button"
          class="text-xs text-slate-400 hover:text-white px-2 py-1 rounded bg-slate-700/50 hover:bg-slate-700 transition-colors"
          @click="selectedEmployeeId = null"
        >
          Clear
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-900/60 text-slate-400 uppercase font-mono tracking-wider text-[11px] border-b border-slate-800">
          <tr>
            <th class="px-5 py-3">Task ID</th>
            <th class="px-5 py-3">Req. Skill</th>
            <th class="px-5 py-3">Hours</th>
            <th class="px-5 py-3">Employee</th>
            <th class="px-5 py-3">Emp. Skill</th>
            <th class="px-5 py-3 text-right">Hourly Rate</th>
            <th class="px-5 py-3 text-right">Task Cost</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60 font-sans">
          <tr
            v-for="assignment in filteredAssignments"
            :key="assignment.taskId"
            class="hover:bg-slate-750/30 transition-colors"
          >
            <!-- Task ID -->
            <td class="px-5 py-3 font-semibold text-slate-200">
              #{{ assignment.taskId }}
            </td>

            <!-- Required Skill -->
            <td class="px-5 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                Level {{ assignment.requiredSkill }}
              </span>
            </td>

            <!-- Hours -->
            <td class="px-5 py-3 font-mono text-slate-300">
              {{ assignment.hours.toFixed(1) }} h
            </td>

            <!-- Employee ID -->
            <td class="px-5 py-3">
              <span class="inline-flex items-center gap-1.5 font-medium text-slate-100">
                <span class="h-2 w-2 rounded-full bg-blue-400" />
                Employee #{{ assignment.employeeId }}
              </span>
            </td>

            <!-- Employee Skill -->
            <td class="px-5 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium"
                :class="[
                  assignment.employeeSkill > assignment.requiredSkill
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                    : 'bg-slate-800 text-slate-300 border border-slate-700',
                ]"
              >
                Level {{ assignment.employeeSkill }}
                <span v-if="assignment.employeeSkill > assignment.requiredSkill" class="ml-1 text-[10px]">
                  (+{{ assignment.employeeSkill - assignment.requiredSkill }})
                </span>
              </span>
            </td>

            <!-- Hourly Rate -->
            <td class="px-5 py-3 text-right font-mono text-slate-300">
              ${{ assignment.hourlyRate.toFixed(2) }}/h
            </td>

            <!-- Cost -->
            <td class="px-5 py-3 text-right font-mono font-semibold text-emerald-400">
              {{ formatCurrency(assignment.cost) }}
            </td>
          </tr>

          <!-- Empty Filter State -->
          <tr v-if="filteredAssignments.length === 0">
            <td colspan="7" class="px-5 py-8 text-center text-slate-400">
              No tasks assigned to Employee #{{ selectedEmployeeId }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

