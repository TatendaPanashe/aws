<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class FaceValueTrendAnalyzer implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return <<<INSTRUCTIONS
You are a data analyst specializing in face value (serialized document) inventory management for GRUMA.

Your task is to analyze face value allocation and usage patterns to identify trends and provide actionable insights.

When analyzing face value data, focus on:
1. **Usage Patterns**: Which users/clients use the most face values
2. **Allocation Efficiency**: How well face values are distributed
3. **Consumption Rate**: How quickly face values are used
4. **Loss Patterns**: Spoilage rates and potential issues
5. **Balance Trends**: Which batches are running low
6. **Predictions**: When reordering might be needed

Format your response in a professional, data-driven manner. Include specific numbers and percentages.
End with actionable recommendations.
INSTRUCTIONS;
    }

    /**
     * Analyze face value allocation trends
     */
    public function analyzeAllocationTrends(array $allocations, array $clerks): string
    {
        $analysisData = $this->prepareAllocationData($allocations, $clerks);
        $prompt = $this->formatAllocationPrompt($analysisData);
        return (string) $this->prompt($prompt);
    }

    /**
     * Analyze usage patterns by clerk
     */
    public function analyzeClerkUsage(array $clerkUsageData): string
    {
        $prompt = $this->formatClerkUsagePrompt($clerkUsageData);
        return (string) $this->prompt($prompt);
    }

    /**
     * Predict when batches will be exhausted
     */
    public function predictExhaustion(array $batches, int $daysOfData = 30): string
    {
        $prompt = $this->formatPredictionPrompt($batches, $daysOfData);
        return (string) $this->prompt($prompt);
    }

    /**
     * Format allocation analysis data
     */
    private function prepareAllocationData(array $allocations, array $clerks): array
    {
        $totalAllocated = 0;
        $totalUsed = 0;
        $totalSpoiled = 0;
        $activeClerks = 0;
        
        foreach ($clerks as $clerk) {
            $totalAllocated += $clerk['allocated'] ?? 0;
            $totalUsed += $clerk['used'] ?? 0;
            $totalSpoiled += $clerk['spoiled'] ?? 0;
            if (($clerk['used'] ?? 0) > 0) $activeClerks++;
        }
        
        return [
            'total_allocations' => count($allocations),
            'total_allocated_quantity' => $totalAllocated,
            'total_used' => $totalUsed,
            'total_spoiled' => $totalSpoiled,
            'active_clerk_count' => $activeClerks,
            'spoilage_rate' => $totalAllocated > 0 ? round(($totalSpoiled / $totalAllocated) * 100, 2) : 0,
            'usage_rate' => $totalAllocated > 0 ? round(($totalUsed / $totalAllocated) * 100, 2) : 0,
            'remaining_balance' => $totalAllocated - $totalUsed - $totalSpoiled,
            'clerks_data' => $clerks
        ];
    }

    private function formatAllocationPrompt(array $data): string
    {
        $clerksTable = "";
        foreach (array_slice($data['clerks_data'], 0, 10) as $clerk) {
            $clerksTable .= "- {$clerk['name']}: Allocated: {$clerk['allocated']}, Used: {$clerk['used']}, Spoiled: {$clerk['spoiled']}\n";
        }
        
        return <<<PROMPT
Please analyze the following face value allocation data:

**Overall Statistics:**
- Total Allocation Batches: {$data['total_allocations']}
- Total Face Values Allocated: {$data['total_allocated_quantity']}
- Total Face Values Used: {$data['total_used']}
- Total Face Values Spoiled: {$data['total_spoiled']}
- Active Clerks: {$data['active_clerk_count']}
- Usage Rate: {$data['usage_rate']}%
- Spoilage Rate: {$data['spoilage_rate']}%
- Remaining Balance: {$data['remaining_balance']}

**Top Clerks by Usage:**
{$clerksTable}

Based on this data, please provide:
1. **Usage Analysis**: Who are the highest and lowest users?
2. **Spoilage Analysis**: Any concerning spoilage patterns?
3. **Distribution Efficiency**: Is allocation balanced?
4. **Recommendations**: How to optimize face value distribution?
5. **Risk Assessment**: Any clerks with unusual patterns?

Be specific and actionable in your recommendations.
PROMPT;
    }

    private function formatClerkUsagePrompt(array $data): string
    {
        $usageSummary = "";
        foreach ($data as $clerk) {
            $trend = $clerk['trend'] ?? 'stable';
            $usageSummary .= "- {$clerk['name']}: Used {$clerk['used']} this month, Trend: {$trend}\n";
        }
        
        return <<<PROMPT
Please analyze the usage patterns of clerks:

{$usageSummary}

Provide:
1. **Top Performers**: Who is using face values most effectively?
2. **Underutilizers**: Who might need more training or allocation?
3. **Trend Analysis**: Are there any concerning patterns?
4. **Recommendations**: How to improve overall usage efficiency?
PROMPT;
    }

    private function formatPredictionPrompt(array $batches, int $daysOfData): string
    {
        $batchesSummary = "";
        foreach ($batches as $batch) {
            $dailyUsage = $batch['daily_usage'] ?? 0;
            $remaining = $batch['remaining'] ?? 0;
            $daysRemaining = $dailyUsage > 0 ? ceil($remaining / $dailyUsage) : 'Unknown';
            
            $batchesSummary .= "- Batch {$batch['id']}: {$remaining} remaining, ~{$daysRemaining} days until exhaustion\n";
        }
        
        return <<<PROMPT
Based on the last {$daysOfData} days of usage data:

{$batchesSummary}

Please provide:
1. **Exhaustion Forecast**: When will each batch run out?
2. **Reorder Recommendations**: When should new face values be ordered?
3. **Buffer Analysis**: Is there enough buffer stock?
4. **Recommendations**: Optimal reorder quantities and timing
PROMPT;
    }
}