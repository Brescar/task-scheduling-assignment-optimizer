import type { OptimizationResponse, VariantType } from '~/types/optimizer'

export default defineEventHandler(async (event): Promise<OptimizationResponse> => {
  const query = getQuery(event)
  const variant: VariantType = (query.variant as VariantType) === 'makespan' ? 'makespan' : 'cost'
  const body = await readBody(event)

  const config = useRuntimeConfig(event)
  const forceMock = query.mock === 'true'
  const useMock = forceMock || config.useMock

  if (useMock) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Bad Request',
      data: {
        error: 'Custom dataset optimization requires the live calculation engine. Mock mode only provides results for the predefined dataset.',
      },
    })
  }

  const backendBaseUrl = config.backendUrl.replace(/\/+$/, '')
  const targetUrl = `${backendBaseUrl}/api/v1/optimize?variant=${variant}`

  try {
    const response = await $fetch<OptimizationResponse>(targetUrl, {
      method: 'POST',
      body,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      timeout: 10000,
    })

    return response
  } catch (error: any) {
    console.error(`[BFF Proxy POST Error] Could not connect to backend at ${targetUrl}:`, error.message)

    throw createError({
      statusCode: error.response?.status || 503,
      statusMessage: error.response?.statusText || 'Backend Unreachable',
      data: {
        error: error.data?.error || `Failed to communicate with calculation engine at ${backendBaseUrl}.`,
        originalMessage: error.message,
      },
    })
  }
})

