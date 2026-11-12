export type VariantType = 'cost' | 'makespan'
export type OptimizationStatus = 'feasible' | 'infeasible'

export interface Assignment {
  taskId: number
  requiredSkill: number
  hours: number
  employeeId: number
  employeeSkill: number
  hourlyRate: number
  cost: number
}

export interface EmployeeWorkload {
  employeeId: number
  totalAssignedHours: number
  taskCount: number
}

export interface FeasibleOptimizationResult {
  status: 'feasible'
  variant: VariantType
  totalPrice: number
  makespan: number
  taskCount: number
  activeEmployeeCount: number
  totalEmployeeCount: number
  assignments: Assignment[]
  employeeSummaries: EmployeeWorkload[]
}

export interface InfeasibleOptimizationResult {
  status: 'infeasible'
  variant: VariantType
  unassignableTaskIds: number[]
  message?: string
}

export type OptimizationResponse = FeasibleOptimizationResult | InfeasibleOptimizationResult

export interface ComparisonMetrics {
  costDifference: number
  makespanDifference: number
  costWinner: VariantType
  makespanWinner: VariantType
}

