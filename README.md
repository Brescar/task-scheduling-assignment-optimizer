# Beijer Ref - Task Scheduling & Employee Assignment Engine

Full-stack optimization platform for task scheduling and employee assignment, built with **Symfony 7.4 (PHP 8.4)** on **FrankenPHP** and **Nuxt 3 (TypeScript + Vue 3 + Tailwind CSS)**.

---

## Architecture Overview

```
                      [ Host Browser ]
                             |
             http://localhost:3000 (Nuxt App)
                             |
                             v
  +------------------------------------------------------+
  | Docker Network: beijer-network                       |
  |                                                      |
  |  [ frontend ] (Nuxt 3 SSR / Node 22 Alpine)          |
  |       |                                              |
  |       | Internal BFF request: http://backend:80      |
  |       v                                              |
  |  [ backend ] (Symfony 7.4 / PHP 8.4 on FrankenPHP)   |
  |       |                                              |
  |       +---> [ Domain Optimization Engine ]           |
  |                                                      |
  +------------------------------------------------------+
                             |
              http://localhost:8000 (Direct REST API)
```

### Architectural Points:
1. **Stateless Calculation Engine**: Decoupled from databases; accepts raw JSON payloads or file inputs and outputs deterministic assignments in milliseconds.
2. **Backend-for-Frontend (BFF) Pattern**: The browser **never** communicates directly with the PHP backend. All data fetching is aggregated through Nuxt 3 server routes (`/server/api/optimize.get.ts`), preventing CORS issues and network exposure.
3. **FrankenPHP Application Server**: High-performance Caddy-based Go web server embedding the PHP 8.4 runtime directly without separate Nginx/PHP-FPM containers.
4. **Clean Architecture / DDD**: The core domain layer has zero framework dependencies and 100% test coverage.
5. **Pure Tailwind CSS**: Dashboard visualizer built entirely with Tailwind utilities—no heavy UI component libraries (Vuetify, PrimeVue) and no external charting libraries.

---

## Algorithmic Problem Formulation

### 1. Variant A: Minimum Total Cost
- **Objective**: $\min \sum_{t \in T} \text{hourlyRate}(e(t)) \times \text{estimation}(t)$
- **Algorithm**: Decoupled Greedy Rate Matching.
  - Since there are no individual capacity caps or deadlines, each task is assigned to the eligible worker ($e.\text{skillLevel} \ge t.\text{skillLevel}$) with the lowest `hourlyRate`.
  - **Deterministic Tie-Breaking**:
    1. Lowest current assigned workload (distributes work evenly among equal-cost workers).
    2. Lowest `employeeId` (ensures 100% reproducible output).
- **Complexity**: $\mathcal{O}(|T| \cdot |E|)$ time, $\mathcal{O}(|T| + |E|)$ space.

### 2. Variant B: Minimum Makespan ($C_{\max}$)
- **Objective**: $\min \max_{e \in E} \sum_{t \in T_e} \text{estimation}(t)$
- **Mathematical Nature**: Restricted assignment multiprocessor scheduling ($P | \text{eligibility} | C_{\max}$), **NP-hard in the strong sense**.
- **Algorithm**: **Restricted LPT (Longest Processing Time) Heuristic**.
  - Sorts tasks descending by duration (`estimation` DESC, tie-break: `skillLevel` DESC, `taskId` ASC).
  - Greedily places each task onto the eligible worker currently carrying the lowest accumulated workload.
  - **Deterministic Tie-Breaking**:
    1. Lowest `hourlyRate` (cost-effective secondary objective).
    2. Lowest `employeeId`.
- **Complexity**: $\mathcal{O}(|T| \log |T| + |T| \cdot |E|)$ time, $\mathcal{O}(|T| + |E|)$ space. Solves 200 tasks in $< 5\text{ms}$.

---

## Quick Start (Docker Multi-Container)

### 1. Start All Services
```bash
# Navigate into the project root directory
cd beijer-ref

# Copy environment configuration
cp .env.example .env

# Launch frontend and backend containers
docker compose up -d
```

### 2. Access the Applications
- **Nuxt Analytical Dashboard**: [http://localhost:3000](http://localhost:3000)
  - Features: Variant switcher, summary metrics, interactive workload bars, filtered task assignments, and an in-browser **Custom Dataset Editor / JSON Runner** (allowing on-the-fly payload editing and file uploads).
- **Symfony REST API Health**: [http://localhost:8000/api/v1/health](http://localhost:8000/api/v1/health)
- **Symfony Optimization Endpoint**: [http://localhost:8000/api/v1/optimize?variant=cost](http://localhost:8000/api/v1/optimize?variant=cost)

---

## CLI Runner Usage

Run the optimization engine from the command line:

```bash
# Minimum Total Cost (Human-readable table)
docker compose run --rm backend php bin/console app:optimize --variant=cost

# Minimum Makespan (Human-readable table)
docker compose run --rm backend php bin/console app:optimize --variant=makespan

# Output raw JSON payload (suitable for CLI piping)
docker compose run --rm backend php bin/console app:optimize --variant=cost --format=json

# Run against custom file paths
docker compose run --rm backend php bin/console app:optimize data/benchmark_30_employees.json data/benchmark_200_tasks.json --variant=makespan
```

---

## Running Automated Tests

### Backend Unit & Integration Tests (PHPUnit 11)
```bash
docker compose run --rm backend ./vendor/bin/phpunit
```
> Runs 25 automated tests and 244 assertions covering feasibility errors, skewed task durations, extreme rates, determinism, and 200 tasks / 30 employees performance benchmarks.

### Frontend Unit Tests (Vitest)
```bash
docker compose run --rm frontend npm test
```
> Runs Vitest tests verifying financial calculations, makespan aggregation, relative bar load formulas, and employee filters.

---

## Mock Mode

To run the frontend completely offline without the backend container:
1. In `.env`, set:
   ```env
   NUXT_USE_MOCK=true
   ```
2. Or simply add `?mock=true` to the URL:
   `http://localhost:3000/?mock=true`

The dashboard will instantly load pre-computed official datasets from `server/data/mock-results.json`.

---

## Official Dataset Results Comparison

| Metric | Minimum Total Cost | Minimum Makespan | Difference / Impact |
| :--- | :--- | :--- | :--- |
| **Total Price** | **\$17,460.00** | \$26,700.00 | Min Cost saves \$9,240.00 |
| **Makespan** | 51.00 hours | **13.00 hours** | Min Makespan is **74.5% faster** |
| **Active Employees** | 2 / 10 | **10 / 10** | All 10 workers utilized |
| **Workload Distribution** | Emp #4: 51h, Emp #8: 27h | Balanced across all 10 (5h – 13h) | Eliminates bottleneck starvation |

