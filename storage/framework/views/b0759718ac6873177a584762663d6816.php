<?php $__env->startSection('title'); ?>
Site Collection Reports
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $selectedSites = array_values(array_map('strval', (array) request()->input('site', [])));
    $selectedSbu = $selectedSbu ?? null;
    $canFilterBySbu = $canFilterBySbu ?? false;
    $isSbuLocked = $isSbuLocked ?? false;
    $transactionCount = $transactions->count();
    $siteCount = $transactions->pluck('siteid')->filter()->unique()->count();
    $userCount = $transactions->pluck('user_id')->filter()->unique()->count();
    $usdTransactions = (float) $transactions->sum('insurance_transactions');
    $zwgTransactions = (float) $transactions->sum('zwg_insurance_transactions');
    $usdDeposits = (float) $transactions->sum('usd_total_deposited');
    $zwgDeposits = (float) $transactions->sum('zwg_total_deposited');
    $latestSubmission = $transactions->sortByDesc('created_at')->first();
    $topSites = $transactions
        ->groupBy(fn ($row) => $row->site_name ?: 'Unknown Site')
        ->map(function ($rows, $siteName) {
            return [
                'site_name' => $siteName,
                'usd' => (float) $rows->sum('insurance_transactions'),
                'zwg' => (float) $rows->sum('zwg_insurance_transactions'),
            ];
        })
        ->sortByDesc('usd')
        ->take(8)
        ->values();

    $summaryCards = [
        [
            'label' => 'Matched Sheets',
            'value' => number_format($transactionCount),
            'note' => 'Collection records returned by the active filters.',
            'icon' => 'bi bi-journal-text',
        ],
        [
            'label' => 'Sites Covered',
            'value' => number_format($siteCount),
            'note' => 'Distinct sites represented in this report slice.',
            'icon' => 'bi bi-building',
        ],
        [
            'label' => 'Users Covered',
            'value' => number_format($userCount),
            'note' => 'Distinct users with submissions in the selected period.',
            'icon' => 'bi bi-people',
        ],
        [
            'label' => 'USD Transactions',
            'value' => '$' . number_format($usdTransactions, 2),
            'note' => 'Total USD insurance transaction value across the selected sites.',
            'icon' => 'bi bi-cash-stack',
        ],
        [
            'label' => 'ZWG Transactions',
            'value' => 'ZWG ' . number_format($zwgTransactions, 2),
            'note' => 'Total ZWG insurance transaction value across the selected sites.',
            'icon' => 'bi bi-wallet2',
        ],
        [
            'label' => 'Deposits Captured',
            'value' => '$' . number_format($usdDeposits, 2) . ' / ZWG ' . number_format($zwgDeposits, 2),
            'note' => 'Combined USD and ZWG deposit totals in the current report.',
            'icon' => 'bi bi-bank',
        ],
    ];
?>

<div class="pagetitle">
    <h1>Site Collection Reports</h1>
    <p>Review detailed USD and ZWG activity across one or more sites, then export the report tables when you need an offline working copy.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Site Reports</h5>
                <div class="muted">Choose a reporting window, pick a network, and optionally narrow to specific sites.</div>
            </div>
            <a href="<?php echo e(route('reports.hub')); ?>" class="btn btn-secondary">
                <i class="bi bi-grid-1x2"></i> Reports Hub
            </a>
        </div>

        <div class="glass-note mb-3">
            Site selection is network-aware. Once you choose a network, the relevant sites load below as checkboxes and can be filtered together.
        </div>

        <form class="row g-3" method="post" action="<?php echo e(route('bysitesreports')); ?>">
            <?php echo csrf_field(); ?>
            <?php if($canFilterBySbu): ?>
                <div class="col-md-4">
                    <label for="sbu" class="form-label">SBU</label>
                    <select id="sbu" class="form-select" name="sbu" <?php echo e($isSbuLocked ? 'disabled' : ''); ?> onchange="handleSbuChange()">
                        <option value="">All SBUs</option>
                        <?php $__currentLoopData = $sbuOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sbu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($sbu); ?>" <?php echo e((string) $selectedSbu === (string) $sbu ? 'selected' : ''); ?>><?php echo e($sbu); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if($isSbuLocked): ?>
                        <input type="hidden" name="sbu" value="<?php echo e($selectedSbu); ?>">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="col-md-4">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" class="form-control" name="startdate" id="startdate" value="<?php echo e(request('startdate')); ?>">
            </div>
            <div class="col-md-4">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" class="form-control" name="enddate" id="enddate" value="<?php echo e(request('enddate')); ?>">
            </div>
            <div class="col-md-4">
                <label for="networkId" class="form-label">Network</label>
                <select id="networkId" class="form-select" name="network" onchange="getSites()">
                    <option value="">Choose network...</option>
                    <?php $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($network->id); ?>" <?php echo e((string) request('network') === (string) $network->id ? 'selected' : ''); ?>>
                            <?php echo e($network->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div id="siteSelect" class="row g-3">
                <div class="col-12">
                    <div class="glass-note">
                        Select a network to load the available sites for multi-site filtering.
                    </div>
                </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo e(route('bysitesreports')); ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<section class="metric-grid mb-4">
    <?php $__currentLoopData = $summaryCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="metric-card">
            <span class="metric-label"><i class="<?php echo e($card['icon']); ?>"></i> <?php echo e($card['label']); ?></span>
            <strong class="metric-value"><?php echo e($card['value']); ?></strong>
            <div class="metric-note"><?php echo e($card['note']); ?></div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<?php if($transactions->isEmpty()): ?>
    <div class="empty-state">
        No site-level transactions match the current filters. Adjust the network, date range, or selected sites and run the report again.
    </div>
<?php else: ?>
    <div class="surface-grid two-up mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Top Sites By Collection</h5>
                        <div class="muted">Highest-volume sites in the current report window.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-bar-chart-line"></i> Volume ranking</span>
                </div>
                <canvas id="siteVolumeChart" style="max-height: 360px;"></canvas>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Coverage Snapshot</h5>
                <div class="glass-note mb-3">
                    <strong>Reporting Window</strong><br>
                    <?php echo e(request('startdate') ?: 'Not specified'); ?> to <?php echo e(request('enddate') ?: 'Not specified'); ?>

                </div>
                <div class="glass-note mb-3">
                    <strong>Selected Network</strong><br>
                    <?php echo e(optional($networks->firstWhere('id', request('network')))->name ?: 'All available networks'); ?>

                </div>
                <div class="glass-note mb-3">
                    <strong>Selected Sites</strong><br>
                    <?php echo e(count($selectedSites) ? number_format(count($selectedSites)) . ' site(s) explicitly selected' : 'All matching sites in scope'); ?>

                </div>
                <div class="glass-note">
                    <strong>Latest Submission</strong><br>
                    <?php echo e($latestSubmission ? \Carbon\Carbon::parse($latestSubmission->created_at)->format('d M Y H:i') : 'No submissions found'); ?>

                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">USD Transactions</h5>
                    <div class="muted">Detailed USD collection, payment channel, and deposit activity by site submission.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportTableToExcel('usdsitesTable', 'USD_Site_Collection_Report.xlsx', 'USD Site Report')">
                    <i class="bi bi-download"></i> Export USD
                </button>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="usdsitesTable">
                    <thead>
                        <tr>
                            <th>Site Name</th>
                            <th>SBU</th>
                            <th>Username</th>
                            <th>Date</th>
                            <th>Total Transactions</th>
                            <th>Zinara Fees</th>
                            <th>Third Party Premiums</th>
                            <th>Full Cover Premiums</th>
                            <th>Other Insurances</th>
                            <th>Cash</th>
                            <th>Swipe</th>
                            <th>Transfers</th>
                            <th>MPOS</th>
                            <th>Cash Balance</th>
                            <th>Bank</th>
                            <th>USD Deposits</th>
                            <th>Debit Sales</th>
                            <th>Credit Sales</th>
                            <th>POS ID</th>
                            <th>POS Bank</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($transaction->site_name); ?></td>
                                <td><?php echo e(data_get($transaction, 'site.sbu', 'N/A')); ?></td>
                                <td><?php echo e($transaction->username); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i')); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->insurance_transactions, 2)); ?></td>
                                <td><?php echo e(number_format((float) ($transaction->zinara_fees ?? 0), 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->third_party_premiums, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->full_cover_premiums, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->other_insurances_usd, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_cash, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_swipe, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_transfers, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_mpos, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_cash_in_hand_balance, 2)); ?></td>
                                <td><?php echo e($transaction->bank); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_total_deposited, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_debit_sales, 2)); ?></td>
                                <td>$<?php echo e(number_format((float) $transaction->usd_credit_sales, 2)); ?></td>
                                <td><?php echo e(data_get($transaction, 'site.POS', 'N/A')); ?></td>
                                <td><?php echo e(data_get($transaction, 'site.bank', 'N/A')); ?></td>
                                <td><?php echo e($transaction->comments); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Totals</th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('insurance_transactions'), 2)); ?></th>
                            <th><?php echo e(number_format((float) $transactions->sum('zinara_fees'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('third_party_premiums'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('full_cover_premiums'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('other_insurances_usd'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_cash'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_swipe'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_transfers'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_mpos'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_cash_in_hand_balance'), 2)); ?></th>
                            <th></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_total_deposited'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_debit_sales'), 2)); ?></th>
                            <th>$<?php echo e(number_format((float) $transactions->sum('usd_credit_sales'), 2)); ?></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">ZWG Transactions</h5>
                    <div class="muted">Detailed ZWG collection, payment channel, and deposit activity by site submission.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportTableToExcel('zwgsitesTable', 'ZWG_Site_Collection_Report.xlsx', 'ZWG Site Report')">
                    <i class="bi bi-download"></i> Export ZWG
                </button>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="zwgsitesTable">
                    <thead>
                        <tr>
                            <th>Site Name</th>
                            <th>SBU</th>
                            <th>Username</th>
                            <th>Date</th>
                            <th>Total Transactions</th>
                            <th>Zinara Fees</th>
                            <th>Third Party Premiums</th>
                            <th>Full Cover Premiums</th>
                            <th>Other Insurances</th>
                            <th>Swipe</th>
                            <th>Transfers</th>
                            <th>Cash</th>
                            <th>MPOS</th>
                            <th>Cash In Hand</th>
                            <th>Cash Balance</th>
                            <th>Bank</th>
                            <th>ZWG Deposits</th>
                            <th>Debit Sales</th>
                            <th>Credit Sales</th>
                            <th>POS ID</th>
                            <th>POS Bank</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($transaction->site_name); ?></td>
                                <td><?php echo e(data_get($transaction, 'site.sbu', 'N/A')); ?></td>
                                <td><?php echo e($transaction->username); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i')); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_insurance_transactions, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) ($transaction->zwg_zinara_fees ?? 0), 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_third_party_premiums, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_full_cover_premiums, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->other_insurances_zwg, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_swipe, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) ($transaction->zwg_transfers ?? 0), 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) ($transaction->zwg_cash ?? 0), 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_mpos, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_cash_in_hand, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_cash_in_hand_balance, 2)); ?></td>
                                <td><?php echo e($transaction->bank); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) ($transaction->zwg_total_deposited ?? $transaction->zwg_cash_deposited), 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_debit_sales, 2)); ?></td>
                                <td class="zwg-number"><?php echo e(number_format((float) $transaction->zwg_credit_sales, 2)); ?></td>
                                <td><?php echo e(data_get($transaction, 'site.POS', 'N/A')); ?></td>
                                <td><?php echo e(data_get($transaction, 'site.bank', 'N/A')); ?></td>
                                <td><?php echo e($transaction->comments); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Totals</th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_insurance_transactions'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_zinara_fees'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_third_party_premiums'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_full_cover_premiums'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('other_insurances_zwg'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_swipe'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_transfers'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_cash'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_mpos'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_cash_in_hand'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_cash_in_hand_balance'), 2)); ?></th>
                            <th></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_total_deposited'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_debit_sales'), 2)); ?></th>
                            <th class="zwg-number"><?php echo e(number_format((float) $transactions->sum('zwg_credit_sales'), 2)); ?></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
const selectedSiteIds = <?php echo json_encode($selectedSites, 15, 512) ?>;

function exportTableToExcel(tableId, filename, sheetName) {
    const table = document.getElementById(tableId);
    if (!table) {
        return;
    }

    // For ZWG table, clean the data before exporting
    if (tableId === 'zwgsitesTable') {
        // Store original HTML
        const originalHtml = table.innerHTML;
        
        // Create a temporary clone to modify
        const tempTable = table.cloneNode(true);
        
        // Remove all "ZWG " text from numeric cells in the clone
        const zwgCells = tempTable.querySelectorAll('.zwg-number');
        zwgCells.forEach(cell => {
            // Just keep the number, remove any text prefix
            const originalText = cell.innerText;
            // Extract just the number (remove any non-numeric except decimal and negative)
            const numberMatch = originalText.match(/[\d,.-]+/);
            if (numberMatch) {
                cell.innerText = numberMatch[0];
            }
        });
        
        // Also handle any cells that might not have the class but contain ZWG prefix
        const allCells = tempTable.querySelectorAll('td, th');
        allCells.forEach(cell => {
            if (cell.innerText && cell.innerText.toString().trim().startsWith('ZWG')) {
                const numberMatch = cell.innerText.match(/[\d,.-]+/);
                if (numberMatch) {
                    cell.innerText = numberMatch[0];
                }
            }
        });
        
        // Export using the cleaned table
        const workbook = XLSX.utils.table_to_book(tempTable, { sheet: sheetName });
        XLSX.writeFile(workbook, filename);
    } else {
        // For USD table, export normally
        const workbook = XLSX.utils.table_to_book(table, { sheet: sheetName });
        XLSX.writeFile(workbook, filename);
    }
}

async function getSites() {
    const networkId = document.getElementById('networkId').value;
    const sbu = document.getElementById('sbu') ? document.getElementById('sbu').value : '';
    const siteSelect = document.getElementById('siteSelect');

    if (!networkId) {
        siteSelect.innerHTML = `
            <div class="col-12">
                <div class="glass-note">
                    Select a network to load the available sites for multi-site filtering.
                </div>
            </div>
        `;
        return;
    }

    try {
        const response = await fetch(`<?php echo e(url('/getsites')); ?>/${networkId}${sbu ? `?sbu=${encodeURIComponent(sbu)}` : ''}`);
        if (!response.ok) {
            throw new Error('Failed to load sites');
        }

        const data = await response.json();

        if (!Array.isArray(data) || !data.length) {
            siteSelect.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        No sites are attached to the selected network yet.
                    </div>
                </div>
            `;
            return;
        }

        const siteMarkup = data.map((site) => {
            const siteName = site.site_name || site.name || `Site ${site.id}`;
            const checked = selectedSiteIds.includes(String(site.id)) ? 'checked' : '';

            return `
                <div class="col-sm-6 col-lg-4">
                    <div class="form-check p-3 border rounded-4 h-100">
                        <input class="form-check-input" type="checkbox" name="site[]" value="${site.id}" id="site${site.id}" ${checked}>
                        <label class="form-check-label ms-2" for="site${site.id}">
                            ${siteName}
                        </label>
                    </div>
                </div>
            `;
        }).join('');

        siteSelect.innerHTML = `
            <div class="col-12">
                <label class="form-label">Sites</label>
                <div class="row g-3">${siteMarkup}</div>
            </div>
        `;
    } catch (error) {
        siteSelect.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    Unable to load sites for the selected network right now.
                </div>
            </div>
        `;
    }
}

function handleSbuChange() {
    const networkSelect = document.getElementById('networkId');
    const siteSelect = document.getElementById('siteSelect');

    if (networkSelect) {
        networkSelect.value = '';
    }

    if (siteSelect) {
        siteSelect.innerHTML = `
            <div class="col-12">
                <div class="glass-note">
                    Select a network to load the available sites for multi-site filtering.
                </div>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#siteVolumeChart')) {
        new Chart(document.querySelector('#siteVolumeChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($topSites->pluck('site_name')->toArray(), 15, 512) ?>,
                datasets: [
                    {
                        label: 'USD Transactions',
                        data: <?php echo json_encode($topSites->pluck('usd')->toArray(), 15, 512) ?>,
                        backgroundColor: 'rgba(15, 107, 110, 0.82)',
                        borderRadius: 12
                    },
                    {
                        label: 'ZWG Transactions',
                        data: <?php echo json_encode($topSites->pluck('zwg')->toArray(), 15, 512) ?>,
                        backgroundColor: 'rgba(217, 119, 69, 0.82)',
                        borderRadius: 12
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#173138',
                            usePointStyle: true,
                            boxWidth: 12
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(17, 36, 47, 0.08)'
                        },
                        ticks: {
                            color: '#5f7274'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#5f7274'
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('networkId').value) {
        getSites();
    }
});
</script>

<style>
/* Optional: Add a subtle indicator that ZWG numbers are clean for export */
.zwg-number {
    font-family: monospace;
}
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/Reports/bysite.blade.php ENDPATH**/ ?>