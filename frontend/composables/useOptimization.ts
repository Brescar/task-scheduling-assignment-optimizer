import { computed, ref, watch } from 'vue'
import type { Assignment, ComparisonMetrics, OptimizationResponse, VariantType } from '~/types/optimizer'

export interface CustomDatasetPayload {
  employees: Array<{ employeeId: number; skillLevel: number; hourlyRate: number }>
  tasks: Array<{ taskId: number; skillLevel: number; estimation: number }>
}

export const useOptimization = () => {
  const route = useRoute()
  const router = useRouter()
  const config = useRuntimeConfig()

  // Track mock mode from runtimeConfig or URL query parameter
  const isMockActive = computed(() => Boolean(config.public.useMock || route.query.mock === 'true'))

  // Selected variant, initialized from URL query if present, otherwise default to 'cost'
  const initialVariant = (route.query.variant as VariantType) === 'makespan' ? 'makespan' : 'cost'
  const selectedVariant = ref<VariantType>(initialVariant)

  // Custom in-memory dataset payload (if user edited/uploaded custom datasets)
  const customPayload = useState<CustomDatasetPayload | null>('custom-dataset-payload', () => null)
  const isCustomDataActive = computed(() => customPayload.value !== null)

  // Modal open state for editing/uploading datasets
  const isDatasetModalOpen = useState<boolean>('dataset-modal-open', () => false)

  // Employee filter for assignments table
  const selectedEmployeeId = ref<number | null>(null)

  // Unified fetch helper that automatically switches between GET (default files) and POST (custom payload)
  const fetchOptimization = async (variant: VariantType): Promise<OptimizationResponse> => {
    const isMock = isMockActive.value
    const params: Record<string, string> = { variant }
    if (isMock) {
      params.mock = 'true'
    }

    if (customPayload.value) {
      return $fetch<OptimizationResponse>('/api/optimize', {
        method: 'POST',
        params,
        body: customPayload.value,
      })
    }

    return $fetch<OptimizationResponse>('/api/optimize', {
      method: 'GET',
      params,
    })
  }

  // Primary data fetch for the active variant via our Nuxt BFF server route
  const {
    data: activeResult,
    pending: isLoading,
    error,
    refresh,
  } = useAsyncData<OptimizationResponse>(
    'optimization-active-dataset',
    () => fetchOptimization(selectedVariant.value),
    {
      watch: [selectedVariant, customPayload, () => route.query.mock],
    }
  )

  // Secondary fetch for the alternate variant to power the side-by-side comparison
  const alternateVariant = computed<VariantType>(() =>
    selectedVariant.value === 'cost' ? 'makespan' : 'cost'
  )

  const { data: alternateResult } = useAsyncData<OptimizationResponse>(
    'optimization-alternate-dataset',
    () => fetchOptimization(alternateVariant.value),
    {
      watch: [alternateVariant, customPayload, () => route.query.mock],
    }
  )

  // Synchronize variant change to URL query parameter
  const switchVariant = async (newVariant: VariantType) => {
    if (selectedVariant.value === newVariant) return
    selectedVariant.value = newVariant
    selectedEmployeeId.value = null // reset filter on variant change

    await router.push({
      query: {
        ...route.query,
        variant: newVariant,
      },
    })
  }

  // Watch URL query changes (e.g. browser back/forward buttons)
  watch(
    () => route.query.variant,
    (newQueryVariant) => {
      const parsed = (newQueryVariant as VariantType) === 'makespan' ? 'makespan' : 'cost'
      if (parsed !== selectedVariant.value) {
        selectedVariant.value = parsed
        selectedEmployeeId.value = null
      }
    }
  )

  // Feasibility status check
  const isFeasible = computed<boolean>(() => {
    return activeResult.value?.status === 'feasible'
  })

  // List of unique employee IDs from assignments for the filter dropdown
  const availableEmployees = computed<number[]>(() => {
    if (!activeResult.value || activeResult.value.status !== 'feasible') return []
    const ids = activeResult.value.assignments.map((a: Assignment) => a.employeeId)
    return Array.from(new Set(ids)).sort((a, b) => a - b)
  })

  // Filtered assignments based on selectedEmployeeId
  const filteredAssignments = computed<Assignment[]>(() => {
    if (!activeResult.value || activeResult.value.status !== 'feasible') return []
    if (selectedEmployeeId.value === null) {
      return activeResult.value.assignments
    }
    return activeResult.value.assignments.filter(
      (a: Assignment) => a.employeeId === selectedEmployeeId.value
    )
  })

  // Side-by-side comparison metrics between Cost and Makespan
  const comparisonMetrics = computed<ComparisonMetrics | null>(() => {
    if (
      !activeResult.value ||
      activeResult.value.status !== 'feasible' ||
      !alternateResult.value ||
      alternateResult.value.status !== 'feasible'
    ) {
      return null
    }

    const costData = selectedVariant.value === 'cost' ? activeResult.value : alternateResult.value
    const makespanData = selectedVariant.value === 'makespan' ? activeResult.value : alternateResult.value

    return {
      costDifference: makespanData.totalPrice - costData.totalPrice,
      makespanDifference: costData.makespan - makespanData.makespan,
      costWinner: 'cost',
      makespanWinner: 'makespan',
    }
  })

  // Custom dataset handlers
  const openDatasetModal = () => {
    isDatasetModalOpen.value = true
  }

  const closeDatasetModal = () => {
    isDatasetModalOpen.value = false
  }

  const applyCustomDataset = async (payload: CustomDatasetPayload) => {
    customPayload.value = payload
    selectedEmployeeId.value = null
    await refresh()
    closeDatasetModal()
  }

  const resetToDefaultDataset = async () => {
    customPayload.value = null
    selectedEmployeeId.value = null
    await refresh()
  }

  return {
    selectedVariant,
    selectedEmployeeId,
    activeResult,
    alternateResult,
    isLoading,
    error,
    isFeasible,
    availableEmployees,
    filteredAssignments,
    comparisonMetrics,
    switchVariant,
    refresh,
    isMockActive,
    isCustomDataActive,
    isDatasetModalOpen,
    openDatasetModal,
    closeDatasetModal,
    applyCustomDataset,
    resetToDefaultDataset,
  }
}
