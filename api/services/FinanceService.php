<?php

class FinanceService {
    
    /**
     * Determines the applicable hourly rate by cascading through User -> Project -> Company rules.
     */
    public function determineHourlyRate(string $companyId, string $userId, ?string $projectId): float {
        // 1. Check if user has a specific rate
        $userRule = Database::fetch("
            SELECT hourly_rate FROM invoice_rules 
            WHERE company_id = ? AND entity_type = 'user' AND entity_id = ?
        ", [$companyId, $userId]);
        
        if ($userRule) {
            return (float)$userRule['hourly_rate'];
        }
        
        // 2. Check if project has a specific rate
        if ($projectId) {
            $projectRule = Database::fetch("
                SELECT hourly_rate FROM invoice_rules 
                WHERE company_id = ? AND entity_type = 'project' AND entity_id = ?
            ", [$companyId, $projectId]);
            
            if ($projectRule) {
                return (float)$projectRule['hourly_rate'];
            }
        }
        
        // 3. Fallback to company default rate
        $companyRule = Database::fetch("
            SELECT hourly_rate FROM invoice_rules 
            WHERE company_id = ? AND entity_type = 'company' AND entity_id = ?
        ", [$companyId, $companyId]);
        
        if ($companyRule) {
            return (float)$companyRule['hourly_rate'];
        }
        
        // Default if no rules exist
        return 0.0;
    }
    
    /**
     * Logs time and automatically calculates the billable rate if the entry is billable.
     */
    public function logTime(array $data, string $companyId, string $userId): array {
        $projectId = $data['project_id'] ?? null;
        $isBillable = isset($data['is_billable']) ? (int)$data['is_billable'] : 1;
        
        $hourlyRate = null;
        if ($isBillable) {
            $hourlyRate = $this->determineHourlyRate($companyId, $userId, $projectId);
        }
        
        $sql = "
            INSERT INTO time_entries (
                company_id, user_id, project_id, task_id, duration_minutes, 
                is_billable, hourly_rate_applied, currency, notes, date_logged
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        Database::execute($sql, [
            $companyId,
            $userId,
            $projectId,
            $data['task_id'] ?? null,
            $data['duration_minutes'],
            $isBillable,
            $hourlyRate,
            $data['currency'] ?? 'CHF',
            $data['notes'] ?? null,
            $data['date_logged'] ?? date('Y-m-d')
        ]);
        
        return [
            'success' => true,
            'hourly_rate_applied' => $hourlyRate,
            'billable_amount' => $hourlyRate ? ($data['duration_minutes'] / 60) * $hourlyRate : 0
        ];
    }
    
    /**
     * Generates a revenue report for a specific period.
     */
    public function getRevenueReport(string $companyId, string $startDate, string $endDate): array {
        $sql = "
            SELECT 
                project_id,
                SUM(duration_minutes) as total_minutes,
                SUM(
                    CASE WHEN is_billable = 1 THEN (duration_minutes / 60.0) * hourly_rate_applied ELSE 0 END
                ) as total_revenue
            FROM time_entries
            WHERE company_id = ? AND date_logged BETWEEN ? AND ?
            GROUP BY project_id
        ";
        
        $report = Database::fetchAll($sql, [$companyId, $startDate, $endDate]);
        
        $totalRevenue = 0;
        foreach ($report as $row) {
            $totalRevenue += (float)$row['total_revenue'];
        }
        
        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'projects' => $report,
            'total_revenue' => $totalRevenue
        ];
    }
}
