<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class CollectionReportSummarizer implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return <<<INSTRUCTIONS
You are a financial analyst specializing in insurance collection reports for GRUMA.

Your task is to analyze daily collection reports and provide concise, actionable summaries.

When summarizing collection data, focus on:
1. **Total Collections**: USD and ZWG totals
2. **Payment Methods**: Breakdown of cash, swipe, transfers, and M-POS
3. **Insurance Breakdown**: Zinara fees, Third Party, Full Cover, Other Insurances
4. **Deposit Status**: Deposits made vs cash in hand
5. **Anomalies**: Any unusual patterns or discrepancies
6. **Key Insights**: Trends and recommendations

Format your response in a professional but concise manner. Use bullet points for clarity.
End with a recommendation for the supervisor.
INSTRUCTIONS;
    }

    /**
     * Summarize a single day's collection
     */
    public function summarizeDailyCollection(array $collectionData): string
    {
        $prompt = $this->formatDailyPrompt($collectionData);
        return (string) $this->prompt($prompt);
    }

    /**
     * Summarize a date range of collections
     */
    public function summarizeDateRange(array $collections, string $startDate, string $endDate): string
    {
        $summaryData = $this->aggregateCollectionData($collections);
        $prompt = $this->formatRangePrompt($summaryData, $startDate, $endDate);
        return (string) $this->prompt($prompt);
    }

    /**
     * Format daily collection data for AI prompt
     */
    private function formatDailyPrompt(array $data): string
    {
        return <<<PROMPT
Please analyze and summarize the following daily collection report:

**Date:** {$data['date']}
**Site:** {$data['site_name']}
**User:** {$data['username']}

**USD Collections:**
- Total Insurance Transactions: \${$data['usd_total']}
- Breakdown: Zinara: \${$data['usd_zinara']}, Third Party: \${$data['usd_third_party']}, Full Cover: \${$data['usd_full_cover']}, Other: \${$data['usd_other']}

**USD Payment Methods:**
- Cash: \${$data['usd_cash']}
- Swipe: \${$data['usd_swipe']}
- Transfers: \${$data['usd_transfers']}
- M-POS: \${$data['usd_mpos']}

**USD Cash Position:**
- Cash In Hand: \${$data['usd_cash_in_hand']}
- Cash Deposited: \${$data['usd_deposited']}
- Cash Balance: \${$data['usd_balance']}

**ZWG Collections:**
- Total Insurance Transactions: ZWG {$data['zwg_total']}
- Breakdown: Zinara: ZWG {$data['zwg_zinara']}, Third Party: ZWG {$data['zwg_third_party']}, Full Cover: ZWG {$data['zwg_full_cover']}, Other: ZWG {$data['zwg_other']}

**ZWG Payment Methods:**
- Cash: ZWG {$data['zwg_cash']}
- Swipe: ZWG {$data['zwg_swipe']}
- Transfers: ZWG {$data['zwg_transfers']}
- M-POS: ZWG {$data['zwg_mpos']}

**ZWG Cash Position:**
- Cash In Hand: ZWG {$data['zwg_cash_in_hand']}
- Cash Deposited: ZWG {$data['zwg_deposited']}
- Cash Balance: ZWG {$data['zwg_balance']}

**Comments:** {$data['comments'] ?? 'None'}

Provide a concise summary highlighting key points and any concerns.
PROMPT;
    }

    /**
     * Format date range data for AI prompt
     */
    private function formatRangePrompt(array $data, string $startDate, string $endDate): string
    {
        return <<<PROMPT
Please analyze and summarize collection data for the period **{$startDate} to {$endDate}**:

**Summary Statistics:**
- Number of Transactions: {$data['transaction_count']}
- Total USD Collections: \${$data['usd_total']}
- Total ZWG Collections: ZWG {$data['zwg_total']}

**USD Breakdown:**
- Cash: \${$data['usd_cash_total']} ({$data['usd_cash_percent']}%)
- Swipe: \${$data['usd_swipe_total']} ({$data['usd_swipe_percent']}%)
- Transfers: \${$data['usd_transfers_total']} ({$data['usd_transfers_percent']}%)
- M-POS: \${$data['usd_mpos_total']} ({$data['usd_mpos_percent']}%)

**ZWG Breakdown:**
- Cash: ZWG {$data['zwg_cash_total']} ({$data['zwg_cash_percent']}%)
- Swipe: ZWG {$data['zwg_swipe_total']} ({$data['zwg_swipe_percent']}%)
- Transfers: ZWG {$data['zwg_transfers_total']} ({$data['zwg_transfers_percent']}%)
- M-POS: ZWG {$data['zwg_mpos_total']} ({$data['zwg_mpos_percent']}%)

**Insurance Breakdown (USD):**
- Zinara Fees: \${$data['usd_zinara_total']} ({$data['usd_zinara_percent']}%)
- Third Party Premiums: \${$data['usd_third_party_total']} ({$data['usd_third_party_percent']}%)
- Full Cover Premiums: \${$data['usd_full_cover_total']} ({$data['usd_full_cover_percent']}%)
- Other Insurances: \${$data['usd_other_total']} ({$data['usd_other_percent']}%)

**Cash Position:**
- Total USD Deposited: \${$data['usd_deposited_total']}
- Total USD Cash in Hand: \${$data['usd_cash_in_hand_total']}
- Total ZWG Deposited: ZWG {$data['zwg_deposited_total']}
- Total ZWG Cash in Hand: ZWG {$data['zwg_cash_in_hand_total']}

Please provide:
1. Executive Summary
2. Key Trends
3. Payment Method Analysis
4. Insurance Product Performance
5. Recommendations
PROMPT;
    }

    /**
     * Aggregate collection data for date range
     */
    private function aggregateCollectionData($collections): array
    {
        $totals = [
            'transaction_count' => $collections->count(),
            'usd_total' => 0,
            'zwg_total' => 0,
            'usd_cash_total' => 0,
            'usd_swipe_total' => 0,
            'usd_transfers_total' => 0,
            'usd_mpos_total' => 0,
            'zwg_cash_total' => 0,
            'zwg_swipe_total' => 0,
            'zwg_transfers_total' => 0,
            'zwg_mpos_total' => 0,
            'usd_zinara_total' => 0,
            'usd_third_party_total' => 0,
            'usd_full_cover_total' => 0,
            'usd_other_total' => 0,
            'zwg_zinara_total' => 0,
            'zwg_third_party_total' => 0,
            'zwg_full_cover_total' => 0,
            'zwg_other_total' => 0,
            'usd_deposited_total' => 0,
            'usd_cash_in_hand_total' => 0,
            'zwg_deposited_total' => 0,
            'zwg_cash_in_hand_total' => 0,
        ];

        foreach ($collections as $collection) {
            $totals['usd_total'] += $collection->insurance_transactions;
            $totals['zwg_total'] += $collection->zwg_insurance_transactions;
            $totals['usd_cash_total'] += $collection->usd_cash;
            $totals['usd_swipe_total'] += $collection->usd_swipe;
            $totals['usd_transfers_total'] += $collection->usd_transfers;
            $totals['usd_mpos_total'] += $collection->usd_mpos;
            $totals['zwg_cash_total'] += $collection->zwg_cash;
            $totals['zwg_swipe_total'] += $collection->zwg_swipe;
            $totals['zwg_transfers_total'] += $collection->zwg_transfers;
            $totals['zwg_mpos_total'] += $collection->zwg_mpos;
            $totals['usd_zinara_total'] += $collection->zinara_fees;
            $totals['usd_third_party_total'] += $collection->third_party_premiums;
            $totals['usd_full_cover_total'] += $collection->full_cover_premiums;
            $totals['usd_other_total'] += $collection->other_insurances_usd;
            $totals['zwg_zinara_total'] += $collection->zwg_zinara_fees;
            $totals['zwg_third_party_total'] += $collection->zwg_third_party_premiums;
            $totals['zwg_full_cover_total'] += $collection->zwg_full_cover_premiums;
            $totals['zwg_other_total'] += $collection->other_insurances_zwg;
            $totals['usd_deposited_total'] += $collection->usd_total_deposited;
            $totals['usd_cash_in_hand_total'] += $collection->usd_cash_in_hand;
            $totals['zwg_deposited_total'] += $collection->zwg_total_deposited;
            $totals['zwg_cash_in_hand_total'] += $collection->zwg_cash_in_hand;
        }

        // Calculate percentages
        $usdGrandTotal = $totals['usd_total'] ?: 1;
        $zwgGrandTotal = $totals['zwg_total'] ?: 1;
        
        $totals['usd_cash_percent'] = round(($totals['usd_cash_total'] / $usdGrandTotal) * 100, 1);
        $totals['usd_swipe_percent'] = round(($totals['usd_swipe_total'] / $usdGrandTotal) * 100, 1);
        $totals['usd_transfers_percent'] = round(($totals['usd_transfers_total'] / $usdGrandTotal) * 100, 1);
        $totals['usd_mpos_percent'] = round(($totals['usd_mpos_total'] / $usdGrandTotal) * 100, 1);
        
        $totals['zwg_cash_percent'] = round(($totals['zwg_cash_total'] / $zwgGrandTotal) * 100, 1);
        $totals['zwg_swipe_percent'] = round(($totals['zwg_swipe_total'] / $zwgGrandTotal) * 100, 1);
        $totals['zwg_transfers_percent'] = round(($totals['zwg_transfers_total'] / $zwgGrandTotal) * 100, 1);
        $totals['zwg_mpos_percent'] = round(($totals['zwg_mpos_total'] / $zwgGrandTotal) * 100, 1);
        
        $totals['usd_zinara_percent'] = round(($totals['usd_zinara_total'] / $usdGrandTotal) * 100, 1);
        $totals['usd_third_party_percent'] = round(($totals['usd_third_party_total'] / $usdGrandTotal) * 100, 1);
        $totals['usd_full_cover_percent'] = round(($totals['usd_full_cover_total'] / $usdGrandTotal) * 100, 1);
        $totals['usd_other_percent'] = round(($totals['usd_other_total'] / $usdGrandTotal) * 100, 1);
        
        return $totals;
    }
}