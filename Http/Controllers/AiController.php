<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CollectionReportSummarizer;
use App\Ai\Agents\FaceValueTrendAnalyzer;
use App\Models\DailyCollection;
use App\Models\Supervisorfacevalues;
use App\Models\FaceValue;
use App\Models\User;
use App\Models\Network;
use App\Models\site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    /**
     * AI Analytics Dashboard
     */
    public function index()
    {
        $networks = Network::all();
        $sites = site::all();
        $clerks = User::where('role_id', 2)->get();
        
        return view('ai.index', compact('networks', 'sites', 'clerks'));
    }
    
    /**
     * Summarize a daily collection
     */
    public function summarizeDailyCollection(Request $request, $collectionId)
    {
        $collection = DailyCollection::with('site')->findOrFail($collectionId);
        
        $data = [
            'date' => $collection->created_at->format('Y-m-d'),
            'site_name' => $collection->site_name,
            'username' => $collection->username,
            'usd_total' => $collection->insurance_transactions,
            'usd_zinara' => $collection->zinara_fees,
            'usd_third_party' => $collection->third_party_premiums,
            'usd_full_cover' => $collection->full_cover_premiums,
            'usd_other' => $collection->other_insurances_usd,
            'usd_cash' => $collection->usd_cash,
            'usd_swipe' => $collection->usd_swipe,
            'usd_transfers' => $collection->usd_transfers,
            'usd_mpos' => $collection->usd_mpos,
            'usd_cash_in_hand' => $collection->usd_cash_in_hand,
            'usd_deposited' => $collection->usd_total_deposited,
            'usd_balance' => $collection->usd_cash_in_hand_balance,
            'zwg_total' => $collection->zwg_insurance_transactions,
            'zwg_zinara' => $collection->zwg_zinara_fees,
            'zwg_third_party' => $collection->zwg_third_party_premiums,
            'zwg_full_cover' => $collection->zwg_full_cover_premiums,
            'zwg_other' => $collection->other_insurances_zwg,
            'zwg_cash' => $collection->zwg_cash,
            'zwg_swipe' => $collection->zwg_swipe,
            'zwg_transfers' => $collection->zwg_transfers,
            'zwg_mpos' => $collection->zwg_mpos,
            'zwg_cash_in_hand' => $collection->zwg_cash_in_hand,
            'zwg_deposited' => $collection->zwg_total_deposited,
            'zwg_balance' => $collection->zwg_cash_in_hand_balance,
            'comments' => $collection->comments,
        ];
        
        $agent = new CollectionReportSummarizer();
        $summary = $agent->summarizeDailyCollection($data);
        
        return view('ai.summary', compact('summary', 'collection'));
    }
    
    /**
     * Summarize date range for collections
     */
    public function summarizeDateRange(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $network = $request->input('network');
            $sites = $request->input('sites');
            
            // Convert sites string to array if needed
            if ($sites && is_string($sites)) {
                $sites = explode(',', $sites);
            }
            
            // Build query
            $query = DailyCollection::with('site');
            
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            }
            
            if ($network) {
                $query->where('networkid', $network);
            }
            
            if ($sites && !empty($sites)) {
                $query->whereIn('siteid', $sites);
            }
            
            $collections = $query->get();
            
            if ($collections->isEmpty()) {
                return response()->json([
                    'summary' => '<div class="alert alert-warning">No data found for the selected filters.</div>'
                ]);
            }
            
            // Prepare summary data
            $summaryData = $this->prepareSummaryData($collections, $startDate, $endDate);
            
            // Generate formatted HTML summary
            $summary = $this->generateFormattedSummary($summaryData);
            
            return response()->json([
                'summary' => $summary,
                'data' => $summaryData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'summary' => '<div class="alert alert-danger">Error generating summary: ' . $e->getMessage() . '</div>',
                'error' => true
            ], 500);
        }
    }
    
    /**
     * Summarizer endpoint for AJAX
     */
    public function summarizer(Request $request)
    {
        return $this->summarizeDateRange($request);
    }
    
    /**
     * Face Value Trend Analysis
     */
    public function trends(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->subDays(90));
            $endDate = $request->input('end_date', Carbon::now());
            $clerkId = $request->input('clerk_id');
            $siteId = $request->input('site');
            
            // Build query for face values
            $query = FaceValue::query();
            
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            }
            
            if ($clerkId) {
                $query->where('assigned_to', $clerkId);
            }
            
            if ($siteId) {
                $query->whereHas('assignedClerk', function($q) use ($siteId) {
                    $q->where('siteid', $siteId);
                });
            }
            
            $allocations = $query->get();
            
            if ($allocations->isEmpty()) {
                return response()->json([
                    'analysis' => '<div class="alert alert-warning">No face value data found for the selected filters.</div>'
                ]);
            }
            
            // Prepare data for analysis
            $totalAllocated = $allocations->sum('allocated');
            $totalUsed = $allocations->sum('used');
            $totalSpoiled = $allocations->sum('spoiled');
            $totalBalance = $allocations->sum('closing_balance');
            $usageRate = $totalAllocated > 0 ? ($totalUsed / $totalAllocated) * 100 : 0;
            
            // Group by clerk
            $clerkData = $allocations->groupBy('assigned_to')->map(function($items, $clerkId) {
                $clerk = User::find($clerkId);
                return [
                    'name' => $clerk ? $clerk->name : 'Unknown',
                    'allocated' => $items->sum('allocated'),
                    'used' => $items->sum('used'),
                    'spoiled' => $items->sum('spoiled'),
                    'closing_balance' => $items->sum('closing_balance'),
                    'usage_rate' => $items->sum('allocated') > 0 ? ($items->sum('used') / $items->sum('allocated')) * 100 : 0
                ];
            })->sortByDesc('used');
            
            // Generate formatted analysis
            $analysis = $this->generateTrendsAnalysis($totalAllocated, $totalUsed, $totalSpoiled, $totalBalance, $usageRate, $clerkData, $startDate, $endDate);
            
            return response()->json(['analysis' => $analysis]);
            
        } catch (\Exception $e) {
            return response()->json([
                'analysis' => '<div class="alert alert-danger">Error generating trends: ' . $e->getMessage() . '</div>'
            ], 500);
        }
    }
    
    /**
     * Exhaustion Prediction
     */
    public function prediction(Request $request)
    {
        try {
            // Get active batches with closing_balance > 0
            $batches = FaceValue::where('closing_balance', '>', 0)
                ->with('assignedClerk')
                ->get();
            
            if ($batches->isEmpty()) {
                return response()->json([
                    'analysis' => '<div class="alert alert-warning">No active face value batches found.</div>'
                ]);
            }
            
            $predictions = [];
            
            foreach ($batches as $batch) {
                // Calculate average daily usage from the last 30 days
                $dailyUsage = DB::table('face_value_usages') // Adjust table name as needed
                    ->where('batch_id', $batch->id)
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->avg('used') ?? 0;
                
                // If no usage in last 30 days, use overall average
                if ($dailyUsage == 0) {
                    $dailyUsage = $batch->used / max(1, $batch->created_at->diffInDays(now()));
                }
                
                $daysRemaining = $dailyUsage > 0 ? ceil($batch->closing_balance / $dailyUsage) : 999;
                $exhaustionDate = Carbon::now()->addDays($daysRemaining);
                
                $status = 'healthy';
                if ($daysRemaining <= 7) {
                    $status = 'critical';
                } elseif ($daysRemaining <= 30) {
                    $status = 'warning';
                }
                
                $predictions[] = [
                    'batch_id' => $batch->id,
                    'range' => $batch->starting . ' - ' . $batch->ending,
                    'clerk' => $batch->assignedClerk ? $batch->assignedClerk->clerk_id : 'Unassigned',
                    'closing_balance' => $batch->closing_balance,
                    'daily_usage' => round($dailyUsage, 2),
                    'days_remaining' => $daysRemaining,
                    'exhaustion_date' => $exhaustionDate->format('Y-m-d'),
                    'status' => $status
                ];
            }
            
            // Sort by days remaining
            usort($predictions, function($a, $b) {
                return $a['days_remaining'] - $b['days_remaining'];
            });
            
            $analysis = $this->generatePredictionAnalysis($predictions);
            
            return response()->json(['analysis' => $analysis]);
            
        } catch (\Exception $e) {
            return response()->json([
                'analysis' => '<div class="alert alert-danger">Error generating predictions: ' . $e->getMessage() . '</div>'
            ], 500);
        }
    }
    
    /**
     * Prepare summary data for AI analysis
     */
    private function prepareSummaryData($collections, $startDate, $endDate)
    {
        $totalUsd = 0;
        $totalZwg = 0;
        $totalUsdCash = 0;
        $totalUsdSwipe = 0;
        $totalUsdTransfers = 0;
        $totalZwgCash = 0;
        $totalZwgSwipe = 0;
        $totalZwgTransfers = 0;
        $totalZinaraUsd = 0;
        $totalZinaraZwg = 0;
        $totalThirdPartyUsd = 0;
        $totalThirdPartyZwg = 0;
        $totalFullCoverUsd = 0;
        $totalFullCoverZwg = 0;
        $totalOtherUsd = 0;
        $totalOtherZwg = 0;
        
        // Group by site for site-level analysis
        $siteData = [];
        
        foreach ($collections as $collection) {
            $totalUsd += $collection->insurance_transactions ?? 0;
            $totalZwg += $collection->zwg_insurance_transactions ?? 0;
            $totalUsdCash += $collection->usd_cash ?? 0;
            $totalUsdSwipe += $collection->usd_swipe ?? 0;
            $totalUsdTransfers += $collection->usd_transfers ?? 0;
            $totalZwgCash += $collection->zwg_cash ?? 0;
            $totalZwgSwipe += $collection->zwg_swipe ?? 0;
            $totalZwgTransfers += $collection->zwg_transfers ?? 0;
            $totalZinaraUsd += $collection->zinara_fees ?? 0;
            $totalZinaraZwg += $collection->zwg_zinara_fees ?? 0;
            $totalThirdPartyUsd += $collection->third_party_premiums ?? 0;
            $totalThirdPartyZwg += $collection->zwg_third_party_premiums ?? 0;
            $totalFullCoverUsd += $collection->full_cover_premiums ?? 0;
            $totalFullCoverZwg += $collection->zwg_full_cover_premiums ?? 0;
            $totalOtherUsd += $collection->other_insurances_usd ?? 0;
            $totalOtherZwg += $collection->other_insurances_zwg ?? 0;
            
            // Site-level aggregation
            $siteName = $collection->site_name ?? 'Unknown';
            if (!isset($siteData[$siteName])) {
                $siteData[$siteName] = [
                    'usd_total' => 0,
                    'zwg_total' => 0,
                    'count' => 0
                ];
            }
            $siteData[$siteName]['usd_total'] += $collection->insurance_transactions ?? 0;
            $siteData[$siteName]['zwg_total'] += $collection->zwg_insurance_transactions ?? 0;
            $siteData[$siteName]['count']++;
        }
        
        // Get top 5 sites
        arsort($siteData);
        $topSites = array_slice($siteData, 0, 5, true);
        
        // Calculate percentages
        $totalCollections = $totalUsd + $totalZwg;
        $usdPercent = $totalCollections > 0 ? round(($totalUsd / $totalCollections) * 100, 1) : 0;
        $zwgPercent = $totalCollections > 0 ? round(($totalZwg / $totalCollections) * 100, 1) : 0;
        
        $usdCashPercent = $totalUsd > 0 ? round(($totalUsdCash / $totalUsd) * 100, 1) : 0;
        $usdSwipePercent = $totalUsd > 0 ? round(($totalUsdSwipe / $totalUsd) * 100, 1) : 0;
        $usdTransfersPercent = $totalUsd > 0 ? round(($totalUsdTransfers / $totalUsd) * 100, 1) : 0;
        
        $zwgCashPercent = $totalZwg > 0 ? round(($totalZwgCash / $totalZwg) * 100, 1) : 0;
        $zwgSwipePercent = $totalZwg > 0 ? round(($totalZwgSwipe / $totalZwg) * 100, 1) : 0;
        $zwgTransfersPercent = $totalZwg > 0 ? round(($totalZwgTransfers / $totalZwg) * 100, 1) : 0;
        
        return [
            'period' => [
                'start' => $startDate ? Carbon::parse($startDate)->format('M d, Y') : 'All time',
                'end' => $endDate ? Carbon::parse($endDate)->format('M d, Y') : 'Present'
            ],
            'total_transactions' => $collections->count(),
            'total_usd' => $totalUsd,
            'total_zwg' => $totalZwg,
            'usd_percentage' => $usdPercent,
            'zwg_percentage' => $zwgPercent,
            'payment_methods_usd' => [
                'cash' => ['amount' => $totalUsdCash, 'percentage' => $usdCashPercent],
                'swipe' => ['amount' => $totalUsdSwipe, 'percentage' => $usdSwipePercent],
                'transfers' => ['amount' => $totalUsdTransfers, 'percentage' => $usdTransfersPercent]
            ],
            'payment_methods_zwg' => [
                'cash' => ['amount' => $totalZwgCash, 'percentage' => $zwgCashPercent],
                'swipe' => ['amount' => $totalZwgSwipe, 'percentage' => $zwgSwipePercent],
                'transfers' => ['amount' => $totalZwgTransfers, 'percentage' => $zwgTransfersPercent]
            ],
            'insurance_breakdown_usd' => [
                'zinara' => $totalZinaraUsd,
                'third_party' => $totalThirdPartyUsd,
                'full_cover' => $totalFullCoverUsd,
                'other' => $totalOtherUsd
            ],
            'insurance_breakdown_zwg' => [
                'zinara' => $totalZinaraZwg,
                'third_party' => $totalThirdPartyZwg,
                'full_cover' => $totalFullCoverZwg,
                'other' => $totalOtherZwg
            ],
            'top_sites' => $topSites
        ];
    }
    
    /**
     * Generate formatted HTML summary
     */
    private function generateFormattedSummary($data)
    {
        $startDate = $data['period']['start'];
        $endDate = $data['period']['end'];
        $totalTransactions = $data['total_transactions'];
        $totalUsd = number_format($data['total_usd'], 2);
        $totalZwg = number_format($data['total_zwg'], 2);
        
        $html = '<div class="ai-summary">';
        
        // Executive Summary
        $html .= '<h2><i class="bi bi-graph-up"></i> Executive Summary</h2>';
        $html .= "<p>During the period <strong>{$startDate}</strong> to <strong>{$endDate}</strong>, a total of <strong>" . number_format($totalTransactions) . "</strong> collection transactions were recorded.</p>";
        
        // Financial Overview Box
        $html .= '<div class="stat-box">';
        $html .= '<h3><i class="bi bi-currency-dollar"></i> Financial Overview</h3>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6"><strong>USD Collections:</strong> $' . $totalUsd . ' (' . $data['usd_percentage'] . '%)</div>';
        $html .= '<div class="col-md-6"><strong>ZWG Collections:</strong> ZWG ' . $totalZwg . ' (' . $data['zwg_percentage'] . '%)</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Payment Method Analysis
        $html .= '<h2><i class="bi bi-credit-card"></i> Payment Method Analysis</h2>';
        $html .= '<div class="row">';
        
        // USD Payment Methods
        $html .= '<div class="col-md-6">';
        $html .= '<div class="stat-box">';
        $html .= '<h3><i class="bi bi-currency-dollar"></i> USD Payment Methods</h3>';
        $html .= '<div class="progress-bar-container mb-2"><div class="progress-bar-fill" style="width: ' . $data['payment_methods_usd']['cash']['percentage'] . '%; background: #10b981;"></div><span class="progress-bar-text">Cash: ' . $data['payment_methods_usd']['cash']['percentage'] . '%</span></div>';
        $html .= '<div class="progress-bar-container mb-2"><div class="progress-bar-fill" style="width: ' . $data['payment_methods_usd']['swipe']['percentage'] . '%; background: #3b82f6;"></div><span class="progress-bar-text">Swipe: ' . $data['payment_methods_usd']['swipe']['percentage'] . '%</span></div>';
        $html .= '<div class="progress-bar-container"><div class="progress-bar-fill" style="width: ' . $data['payment_methods_usd']['transfers']['percentage'] . '%; background: #f59e0b;"></div><span class="progress-bar-text">Transfers: ' . $data['payment_methods_usd']['transfers']['percentage'] . '%</span></div>';
        $html .= '</div></div>';
        
        // ZWG Payment Methods
        $html .= '<div class="col-md-6">';
        $html .= '<div class="stat-box">';
        $html .= '<h3><i class="bi bi-currency-exchange"></i> ZWG Payment Methods</h3>';
        $html .= '<div class="progress-bar-container mb-2"><div class="progress-bar-fill" style="width: ' . $data['payment_methods_zwg']['cash']['percentage'] . '%; background: #10b981;"></div><span class="progress-bar-text">Cash: ' . $data['payment_methods_zwg']['cash']['percentage'] . '%</span></div>';
        $html .= '<div class="progress-bar-container mb-2"><div class="progress-bar-fill" style="width: ' . $data['payment_methods_zwg']['swipe']['percentage'] . '%; background: #3b82f6;"></div><span class="progress-bar-text">Swipe: ' . $data['payment_methods_zwg']['swipe']['percentage'] . '%</span></div>';
        $html .= '<div class="progress-bar-container"><div class="progress-bar-fill" style="width: ' . $data['payment_methods_zwg']['transfers']['percentage'] . '%; background: #f59e0b;"></div><span class="progress-bar-text">Transfers: ' . $data['payment_methods_zwg']['transfers']['percentage'] . '%</span></div>';
        $html .= '</div></div>';
        $html .= '</div>';
        
        // Insurance Breakdown
        $html .= '<h2><i class="bi bi-file-text"></i> Insurance Product Performance</h2>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6"><div class="stat-box"><h3>USD Insurance</h3>';
        $html .= '<ul><li>Zinara Fees: $' . number_format($data['insurance_breakdown_usd']['zinara'], 2) . '</li>';
        $html .= '<li>Third Party: $' . number_format($data['insurance_breakdown_usd']['third_party'], 2) . '</li>';
        $html .= '<li>Full Cover: $' . number_format($data['insurance_breakdown_usd']['full_cover'], 2) . '</li>';
        $html .= '<li>Other: $' . number_format($data['insurance_breakdown_usd']['other'], 2) . '</li></ul>';
        $html .= '</div></div>';
        $html .= '<div class="col-md-6"><div class="stat-box"><h3>ZWG Insurance</h3>';
        $html .= '<ul><li>Zinara Fees: ZWG ' . number_format($data['insurance_breakdown_zwg']['zinara'], 2) . '</li>';
        $html .= '<li>Third Party: ZWG ' . number_format($data['insurance_breakdown_zwg']['third_party'], 2) . '</li>';
        $html .= '<li>Full Cover: ZWG ' . number_format($data['insurance_breakdown_zwg']['full_cover'], 2) . '</li>';
        $html .= '<li>Other: ZWG ' . number_format($data['insurance_breakdown_zwg']['other'], 2) . '</li></ul>';
        $html .= '</div></div>';
        $html .= '</div>';
        
        // Top Sites
        if (!empty($data['top_sites'])) {
            $html .= '<h2><i class="bi bi-trophy"></i> Top Performing Sites</h2>';
            $html .= '<div class="success-box"><ul>';
            foreach ($data['top_sites'] as $siteName => $siteInfo) {
                $html .= "<li><strong>{$siteName}</strong>: USD $" . number_format($siteInfo['usd_total'], 2) . " | ZWG " . number_format($siteInfo['zwg_total'], 2) . " (" . $siteInfo['count'] . " transactions)</li>";
            }
            $html .= '</ul></div>';
        }
        
        // Insights and Recommendations
        $html .= '<h2><i class="bi bi-lightbulb"></i> Key Insights & Recommendations</h2>';
        $html .= '<div class="warning-box"><ul>';
        
        if ($data['payment_methods_usd']['swipe']['percentage'] < 30) {
            $html .= "<li>⚠️ Consider promoting swipe payments to reduce cash handling costs and improve security.</li>";
        }
        if ($data['insurance_breakdown_usd']['full_cover'] < $data['insurance_breakdown_usd']['third_party']) {
            $html .= "<li>📈 Full Cover premiums are underperforming compared to Third Party. Consider targeted marketing campaigns for Full Cover products.</li>";
        }
        if ($data['total_usd'] > $data['total_zwg']) {
            $html .= "<li>💵 USD collections dominate the portfolio. Monitor exchange rate impacts on ZWG collections.</li>";
        }
        if ($data['total_transactions'] > 0 && !empty($data['top_sites'])) {
            $html .= "<li>🏆 Review best practices from top-performing sites and share across the network.</li>";
        }
        
        $html .= "<li>📊 Continue monitoring collection patterns to identify seasonal trends and optimize resource allocation.</li>";
        $html .= "<li>🤖 Schedule regular AI analysis to track performance over time and identify emerging patterns.</li>";
        $html .= '</ul></div>';
        
        // Footer
        $html .= '<div class="success-box mt-3">';
        $html .= '<small><i class="bi bi-robot"></i> This report was generated using AI. For any discrepancies, please verify against source data.</small>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate trends analysis HTML
     */
    private function generateTrendsAnalysis($totalAllocated, $totalUsed, $totalSpoiled, $totalBalance, $usageRate, $clerkData, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->format('M d, Y');
        $end = Carbon::parse($endDate)->format('M d, Y');
        
        $html = '<div class="ai-summary">';
        
        $html .= '<h2><i class="bi bi-graph-up"></i> Face Value Trend Analysis</h2>';
        $html .= "<p>Analysis period: <strong>{$start}</strong> to <strong>{$end}</strong></p>";
        
        // Overall Statistics
        $html .= '<div class="stat-box">';
        $html .= '<h3><i class="bi bi-pie-chart"></i> Overall Statistics</h3>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-3"><strong>Total Allocated:</strong><br>' . number_format($totalAllocated) . '</div>';
        $html .= '<div class="col-md-3"><strong>Total Used:</strong><br>' . number_format($totalUsed) . '</div>';
        $html .= '<div class="col-md-3"><strong>Total Spoiled:</strong><br>' . number_format($totalSpoiled) . '</div>';
        $html .= '<div class="col-md-3"><strong>Current Balance:</strong><br>' . number_format($totalBalance) . '</div>';
        $html .= '</div>';
        $html .= '<div class="progress-bar-container mt-3"><div class="progress-bar-fill" style="width: ' . min($usageRate, 100) . '%; background: ' . ($usageRate > 80 ? '#ef4444' : ($usageRate > 50 ? '#f59e0b' : '#10b981')) . ';"></div><span class="progress-bar-text">Usage Rate: ' . round($usageRate, 1) . '%</span></div>';
        $html .= '</div>';
        
        // Clerk Performance
        if ($clerkData->count() > 0) {
            $html .= '<h2><i class="bi bi-people"></i> Clerk Performance</h2>';
            $html .= '<div class="table-responsive"><table class="table table-sm">';
            $html .= '<thead><tr><th>Clerk</th><th>Allocated</th><th>Used</th><th>Spoiled</th><th>Usage Rate</th><th>Balance</th></tr></thead><tbody>';
            
            foreach ($clerkData as $clerk) {
                $rateClass = $clerk['usage_rate'] > 80 ? 'text-danger' : ($clerk['usage_rate'] > 50 ? 'text-warning' : 'text-success');
                $html .= "<tr>";
                $html .= "<td>{$clerk['name']}</td>";
                $html .= "<td>" . number_format($clerk['allocated']) . "</td>";
                $html .= "<td>" . number_format($clerk['used']) . "</td>";
                $html .= "<td>" . number_format($clerk['spoiled']) . "</td>";
                $html .= "<td class='{$rateClass} fw-bold'>" . round($clerk['usage_rate'], 1) . "%</td>";
                $html .= "<td>" . number_format($clerk['closing_balance']) . "</td>";
                $html .= "</tr>";
            }
            
            $html .= '</tbody></table></div>';
        }
        
        // Insights
        $html .= '<h2><i class="bi bi-lightbulb"></i> Insights</h2>';
        $html .= '<div class="warning-box"><ul>';
        
        if ($usageRate > 80) {
            $html .= "<li>⚠️ Overall usage rate is high (" . round($usageRate, 1) . "%). Consider reordering face values soon.</li>";
        } elseif ($usageRate < 30) {
            $html .= "<li>📊 Usage rate is low (" . round($usageRate, 1) . "%). Review allocation strategy.</li>";
        }
        
        if ($totalSpoiled > $totalUsed * 0.05) {
            $html .= "<li>⚠️ Spoilage rate is high. Train clerks on proper handling procedures.</li>";
        }
        
        $lowUsageClerks = $clerkData->filter(function($c) { return $c['usage_rate'] < 30; });
        if ($lowUsageClerks->count() > 0) {
            $html .= "<li>📌 " . $lowUsageClerks->count() . " clerk(s) have low usage rates. Investigate allocation efficiency.</li>";
        }
        
        $html .= '</ul></div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate prediction analysis HTML
     */
    private function generatePredictionAnalysis($predictions)
    {
        $criticalCount = collect($predictions)->where('status', 'critical')->count();
        $warningCount = collect($predictions)->where('status', 'warning')->count();
        $healthyCount = collect($predictions)->where('status', 'healthy')->count();
        
        $html = '<div class="ai-summary">';
        
        $html .= '<h2><i class="bi bi-calendar-check"></i> Batch Exhaustion Predictions</h2>';
        
        // Summary Stats
        $html .= '<div class="row mb-4">';
        $html .= '<div class="col-md-4"><div class="stat-box text-center"><h3>' . $criticalCount . '</h3><small>Critical (≤7 days)</small></div></div>';
        $html .= '<div class="col-md-4"><div class="stat-box text-center"><h3>' . $warningCount . '</h3><small>Warning (≤30 days)</small></div></div>';
        $html .= '<div class="col-md-4"><div class="stat-box text-center"><h3>' . $healthyCount . '</h3><small>Healthy (>30 days)</small></div></div>';
        $html .= '</div>';
        
        // Predictions Table
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-modern">';
        $html .= '<thead><tr><th>Batch ID</th><th>Range</th><th>Clerk</th><th>Balance</th><th>Daily Usage</th><th>Days Left</th><th>Est. Exhaustion</th><th>Status</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($predictions as $pred) {
            $statusBadge = $pred['status'] == 'critical' ? '<span class="badge" style="background: #ef4444;">🔴 Critical</span>' : 
                          ($pred['status'] == 'warning' ? '<span class="badge" style="background: #f59e0b;">🟡 Warning</span>' : 
                          '<span class="badge" style="background: #10b981;">🟢 Healthy</span>');
            
            $rowClass = $pred['status'] == 'critical' ? 'style="background: rgba(239, 68, 68, 0.05);"' : 
                       ($pred['status'] == 'warning' ? 'style="background: rgba(245, 158, 11, 0.05);"' : '');
            
            $html .= "<tr {$rowClass}>";
            $html .= "<td><strong>{$pred['batch_id']}</strong></td>";
            $html .= "<td>{$pred['range']}</td>";
            $html .= "<td>{$pred['clerk']}</td>";
            $html .= "<td>" . number_format($pred['closing_balance']) . "</td>";
            $html .= "<td>" . number_format($pred['daily_usage'], 2) . "</td>";
            $html .= "<td><strong>{$pred['days_remaining']}</strong> days</td>";
            $html .= "<td>{$pred['exhaustion_date']}</td>";
            $html .= "<td>{$statusBadge}</td>";
            $html .= "</tr>";
        }
        
        $html .= '</tbody></table></div>';
        
        // Urgent Recommendations
        if ($criticalCount > 0) {
            $html .= '<div class="warning-box mt-3">';
            $html .= '<h3><i class="bi bi-alarm"></i> Urgent Action Required</h3>';
            $html .= '<p>' . $criticalCount . ' batch(es) will be depleted within 7 days. Please reorder immediately to avoid disruption.</p>';
            $html .= '</div>';
        }
        
        if ($warningCount > 0) {
            $html .= '<div class="stat-box mt-3">';
            $html .= '<h3><i class="bi bi-bell"></i> Planning Recommendation</h3>';
            $html .= '<p>' . $warningCount . ' batch(es) will be depleted within 30 days. Plan reorder within the next two weeks.</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Analyze face value allocation trends
     */
    public function analyzeAllocationTrends()
    {
        $allocations = FaceValue::where('allocated', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        $clerks = User::where('role_id', 2)
            ->with(['allocations'])
            ->get()
            ->map(function ($clerk) {
                $allocations = $clerk->allocations ?? collect();
                return [
                    'name' => $clerk->name,
                    'allocated' => $allocations->sum('allocated'),
                    'used' => $allocations->sum('used'),
                    'spoiled' => $allocations->sum('spoiled'),
                ];
            });
        
        $agent = new FaceValueTrendAnalyzer();
        $analysis = $agent->analyzeAllocationTrends($allocations->toArray(), $clerks->toArray());
        
        return view('ai.analysis', compact('analysis', 'allocations', 'clerks'));
    }
    
    /**
     * Predict face value exhaustion
     */
    public function predictExhaustion()
    {
        $batches = FaceValue::where('closing_balance', '>', 0)
            ->where('closing_balance', '>', 0)
            ->get()
            ->map(function ($batch) {
                $dailyUsage = $batch->allocations()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->avg('used');
                
                return [
                    'id' => $batch->id,
                    'range' => $batch->starting . ' - ' . $batch->ending,
                    'remaining' => $batch->closing_balance,
                    'daily_usage' => round($dailyUsage ?? 0, 2),
                ];
            });
        
        $agent = new FaceValueTrendAnalyzer();
        $prediction = $agent->predictExhaustion($batches->toArray(), 30);
        
        return view('ai.prediction', compact('prediction', 'batches'));
    }
}