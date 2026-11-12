<script setup lang="ts">
import { ref } from 'vue'

const {
  isDatasetModalOpen,
  closeDatasetModal,
  applyCustomDataset,
  resetToDefaultDataset,
  isCustomDataActive,
  isLoading,
} = useOptimization()

const defaultEmployees = `[
  { "employeeId": 1, "skillLevel": 1, "hourlyRate": 100 },
  { "employeeId": 2, "skillLevel": 2, "hourlyRate": 400 },
  { "employeeId": 3, "skillLevel": 2, "hourlyRate": 350 },
  { "employeeId": 4, "skillLevel": 3, "hourlyRate": 300 },
  { "employeeId": 5, "skillLevel": 1, "hourlyRate": 200 },
  { "employeeId": 6, "skillLevel": 1, "hourlyRate": 150 },
  { "employeeId": 7, "skillLevel": 3, "hourlyRate": 700 },
  { "employeeId": 8, "skillLevel": 1, "hourlyRate": 80 },
  { "employeeId": 9, "skillLevel": 2, "hourlyRate": 500 },
  { "employeeId": 10, "skillLevel": 1, "hourlyRate": 120 }
]`

const defaultTasks = `[
  { "taskId": 1, "skillLevel": 1, "estimation": 2 },
  { "taskId": 2, "skillLevel": 3, "estimation": 1 },
  { "taskId": 3, "skillLevel": 3, "estimation": 1 },
  { "taskId": 4, "skillLevel": 1, "estimation": 1 },
  { "taskId": 5, "skillLevel": 3, "estimation": 3 },
  { "taskId": 6, "skillLevel": 2, "estimation": 5 },
  { "taskId": 7, "skillLevel": 1, "estimation": 3 },
  { "taskId": 8, "skillLevel": 2, "estimation": 2 },
  { "taskId": 9, "skillLevel": 2, "estimation": 4 },
  { "taskId": 10, "skillLevel": 2, "estimation": 2 }
]`

const employeesText = ref(defaultEmployees)
const tasksText = ref(defaultTasks)
const validationError = ref<string | null>(null)

const loadOfficialPreset = () => {
  employeesText.value = defaultEmployees
  tasksText.value = defaultTasks
  validationError.value = null
}

const loadInfeasiblePreset = () => {
  employeesText.value = `[
  { "employeeId": 1, "skillLevel": 1, "hourlyRate": 100 },
  { "employeeId": 2, "skillLevel": 2, "hourlyRate": 200 }
]`
  tasksText.value = `[
  { "taskId": 101, "skillLevel": 1, "estimation": 2 },
  { "taskId": 102, "skillLevel": 4, "estimation": 5 },
  { "taskId": 103, "skillLevel": 5, "estimation": 3 }
]`
  validationError.value = null
}

const handleFileUpload = (event: Event, target: 'employees' | 'tasks') => {
  const fileInput = event.target as HTMLInputElement
  const file = fileInput.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    const text = e.target?.result as string
    if (target === 'employees') {
      employeesText.value = text
    } else {
      tasksText.value = text
    }
  }
  reader.readAsText(file)
}

const submitCustomDataset = async () => {
  validationError.value = null

  let parsedEmployees: any[]
  let parsedTasks: any[]

  try {
    parsedEmployees = JSON.parse(employeesText.value)
  } catch (err: any) {
    validationError.value = `Employees JSON parse error: ${err.message}`
    return
  }

  try {
    parsedTasks = JSON.parse(tasksText.value)
  } catch (err: any) {
    validationError.value = `Tasks JSON parse error: ${err.message}`
    return
  }

  if (!Array.isArray(parsedEmployees) || parsedEmployees.length === 0) {
    validationError.value = 'Employees must be a non-empty array of objects.'
    return
  }

  if (!Array.isArray(parsedTasks) || parsedTasks.length === 0) {
    validationError.value = 'Tasks must be a non-empty array of objects.'
    return
  }

  await applyCustomDataset({
    employees: parsedEmployees,
    tasks: parsedTasks,
  })
}

const handleReset = async () => {
  await resetToDefaultDataset()
  loadOfficialPreset()
  closeDatasetModal()
}
</script>

<template>
  <div
    v-if="isDatasetModalOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
    @click.self="closeDatasetModal"
  >
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-4xl w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
          <h2 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
            Dataset Editor & Custom Runner
            <span
              v-if="isCustomDataActive"
              class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 font-mono"
            >
              Custom Active
            </span>
          </h2>
          <p class="text-xs text-slate-400 mt-1">
            Edit JSON payloads directly or upload files to re-run optimization via the backend API.
          </p>
        </div>

        <button
          type="button"
          class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors"
          @click="closeDatasetModal"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Quick Preset Actions -->
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="text-slate-400 font-medium">Quick Presets:</span>
        <button
          type="button"
          class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors"
          @click="loadOfficialPreset"
        >
          Official 10 Emps / 30 Tasks Sample
        </button>
        <button
          type="button"
          class="px-2.5 py-1 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 transition-colors"
          @click="loadInfeasiblePreset"
        >
          Infeasible Edge Case Preset
        </button>
      </div>

      <!-- Textareas Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Employees Column -->
        <div class="space-y-2">
          <div class="flex items-center justify-between text-xs">
            <label class="font-medium text-slate-300">Employees JSON</label>
            <label class="cursor-pointer text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-1">
              <span>Upload .json</span>
              <input
                type="file"
                accept=".json"
                class="hidden"
                @change="handleFileUpload($event, 'employees')"
              >
            </label>
          </div>
          <textarea
            v-model="employeesText"
            rows="12"
            class="w-full bg-slate-950 font-mono text-xs text-slate-200 border border-slate-800 rounded-lg p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors selection:bg-blue-600"
            placeholder='[{"employeeId": 1, "skillLevel": 1, "hourlyRate": 100}]'
          />
        </div>

        <!-- Tasks Column -->
        <div class="space-y-2">
          <div class="flex items-center justify-between text-xs">
            <label class="font-medium text-slate-300">Tasks JSON</label>
            <label class="cursor-pointer text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-1">
              <span>Upload .json</span>
              <input
                type="file"
                accept=".json"
                class="hidden"
                @change="handleFileUpload($event, 'tasks')"
              >
            </label>
          </div>
          <textarea
            v-model="tasksText"
            rows="12"
            class="w-full bg-slate-950 font-mono text-xs text-slate-200 border border-slate-800 rounded-lg p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors selection:bg-blue-600"
            placeholder='[{"taskId": 1, "skillLevel": 1, "estimation": 2}]'
          />
        </div>
      </div>

      <!-- Error Notice -->
      <div
        v-if="validationError"
        class="p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-mono"
      >
        {{ validationError }}
      </div>

      <!-- Modal Footer -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-800">
        <div>
          <button
            v-if="isCustomDataActive"
            type="button"
            class="text-xs text-rose-400 hover:text-rose-300 underline underline-offset-4"
            @click="handleReset"
          >
            Reset to Default Dataset Files
          </button>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700 transition-colors"
            @click="closeDatasetModal"
          >
            Cancel
          </button>
          <button
            type="button"
            :disabled="isLoading"
            class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-1.5"
            @click="submitCustomDataset"
          >
            <span v-if="isLoading">Calculating...</span>
            <span v-else>Run Optimization (POST)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

