import { describe, expect, it } from 'vitest'
import type { Assignment, EmployeeWorkload } from '~/types/optimizer'

describe('Domain Calculations & Filters (Frontend)', () => {
  const sampleAssignments: Assignment[] = [
    { taskId: 1, requiredSkill: 1, hours: 2, employeeId: 8, employeeSkill: 1, hourlyRate: 80, cost: 160 },
    { taskId: 2, requiredSkill: 3, hours: 3, employeeId: 4, employeeSkill: 3, hourlyRate: 300, cost: 900 },
    { taskId: 3, requiredSkill: 2, hours: 4, employeeId: 4, employeeSkill: 3, hourlyRate: 300, cost: 1200 },
    { taskId: 4, requiredSkill: 1, hours: 1, employeeId: 8, employeeSkill: 1, hourlyRate: 80, cost: 80 },
  ]

  it('calculates total cost accurately as sum of individual assignment costs', () => {
    const totalCost = sampleAssignments.reduce((acc, curr) => acc + curr.cost, 0)
    expect(totalCost).toBe(160 + 900 + 1200 + 80) // 2340
  })

  it('calculates makespan as the maximum workload among employees', () => {
    const workloads: Record<number, number> = {}
    for (const a of sampleAssignments) {
      workloads[a.employeeId] = (workloads[a.employeeId] || 0) + a.hours
    }

    // Employee 8: 2 + 1 = 3h
    // Employee 4: 3 + 4 = 7h
    expect(workloads[8]).toBe(3)
    expect(workloads[4]).toBe(7)

    const makespan = Math.max(...Object.values(workloads))
    expect(makespan).toBe(7)
  })

  it('correctly filters assignments by selected employee ID', () => {
    const filterEmployeeId = 4
    const filtered = sampleAssignments.filter(a => a.employeeId === filterEmployeeId)

    expect(filtered).toHaveLength(2)
    expect(filtered.every(a => a.employeeId === 4)).toBe(true)
  })

  it('correctly calculates relative load bar percentage relative to makespan', () => {
    const makespan = 51
    const employeeLoad = 27
    const percentage = makespan > 0 ? (employeeLoad / makespan) * 100 : 0

    expect(percentage).toBeCloseTo(52.94, 2)
  })
})

