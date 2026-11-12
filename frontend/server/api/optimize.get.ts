import mockResults from '../data/mock-results.json'
import type { OptimizationResponse, VariantType } from '~/types/optimizer'

export default defineEventHandler(async (event): Promise<OptimizationResponse> => {
  const query = getQuery(event)
  const variant: VariantType = (query.variant as VariantType) === 'makespan' ? 'makespan' : 'cost'
  const forceMock = query.mock === 'true'

  const config = useRuntimeConfig(event)
  const useMock = forceMock || config.useMock

  // 1. Mock Mode: serve from local mock-results.json
  if (useMock) {
    const data = mockResults as Record<string, OptimizationResponse>
    if (data[variant]) {
      return data[variant]
    }
  }

  // 2. Proxy to PHP Backend
  const backendBaseUrl = config.backendUrl.replace(/\/+$/, '')
  const targetUrl = `${backendBaseUrl}/api/v1/optimize?variant=${variant}`

  try {
    const response = await $fetch<OptimizationResponse>(targetUrl, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
      timeout: 10000,
    })

    return response
  } catch (error: any) {
    console.error(`[BFF Proxy Error] Could not connect to backend at ${targetUrl}:`, error.message)

    throw createError({
      statusCode: error.response?.status || 503,
      statusMessage: error.response?.statusText || 'Backend Unreachable',
      data: {
        error: `Failed to communicate with calculation engine at ${backendBaseUrl}. Ensure the Symfony backend container is running.`,
        originalMessage: error.message,
      },
    })
  }
})

