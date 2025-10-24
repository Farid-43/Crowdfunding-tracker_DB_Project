<?php
/**
 * Analytics & Reporting Page
 * Demonstrates: CTEs (WITH clause), VIEWs, Stored Procedures, ROLLUP, CUBE, Advanced Aggregations
 */

$page_title = 'Analytics - CF Tracker';
$current_page = 'analytics';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// ==========================================
// CTE DEMONSTRATION: WITH clause for complex analytics
// ==========================================
$cte_campaign_analytics = "WITH Campaign_Stats AS (
    SELECT 
        c.campaign_id,
        c.campaign_title,
        COALESCE(cat.category_name, 'Uncategorized') as category,
        c.goal_amount,
        c.current_amount,
        c.created_at,
        COUNT(d.donation_id) as donation_count,
        AVG(d.amount) as avg_donation,
        MAX(d.amount) as max_donation,
        MIN(d.amount) as min_donation
    FROM Campaigns c
    LEFT JOIN Categories cat ON c.category_id = cat.category_id
    LEFT JOIN Donations d ON c.campaign_id = d.campaign_id AND d.status = 'completed'
    GROUP BY c.campaign_id, c.campaign_title, cat.category_name
),
Donor_Engagement AS (
    SELECT 
        campaign_id,
        COUNT(DISTINCT donor_id) as unique_donors,
        COUNT(CASE WHEN is_anonymous = 1 THEN 1 END) as anonymous_count,
        COUNT(CASE WHEN is_anonymous = 0 THEN 1 END) as public_count
    FROM Donations
    WHERE status = 'completed'
    GROUP BY campaign_id
)
SELECT 
    cs.*,
    de.unique_donors,
    de.anonymous_count,
    de.public_count,
    ROUND((cs.current_amount / cs.goal_amount) * 100, 2) as completion_percentage,
    CASE 
        WHEN cs.current_amount >= cs.goal_amount THEN 'Fully Funded'
        WHEN cs.current_amount >= cs.goal_amount * 0.75 THEN 'Almost There'
        WHEN cs.current_amount >= cs.goal_amount * 0.5 THEN 'Halfway'
        WHEN cs.current_amount >= cs.goal_amount * 0.25 THEN 'Getting Started'
        ELSE 'Just Launched'
    END as funding_stage
FROM Campaign_Stats cs
LEFT JOIN Donor_Engagement de ON cs.campaign_id = de.campaign_id
ORDER BY cs.current_amount DESC
LIMIT 15";

$cte_results = executeAndLogQuery($pdo, $cte_campaign_analytics, [], 'analytics.php', 'SELECT')->fetchAll();

// ==========================================
// ROLLUP DEMONSTRATION: Multi-level grouping
// ==========================================
$rollup_query = "SELECT 
    COALESCE(cat.category_name, 'ALL CATEGORIES') as category,
    COALESCE(c.status, 'ALL STATUSES') as status,
    COUNT(*) as campaign_count,
    SUM(c.goal_amount) as total_goals,
    SUM(c.current_amount) as total_raised,
    AVG(c.current_amount) as avg_raised,
    ROUND(AVG((c.current_amount / c.goal_amount) * 100), 2) as avg_completion_pct
FROM Campaigns c
LEFT JOIN Categories cat ON c.category_id = cat.category_id
GROUP BY cat.category_name, c.status WITH ROLLUP";

$rollup_results = executeAndLogQuery($pdo, $rollup_query, [], 'analytics.php', 'SELECT')->fetchAll();

// ==========================================
// VIEW DEMONSTRATION: Using pre-defined views
// ==========================================
$view_campaign_progress = "SELECT * FROM Campaign_Progress ORDER BY completion_percentage DESC LIMIT 10";
$view_results = executeAndLogQuery($pdo, $view_campaign_progress, [], 'analytics.php', 'SELECT')->fetchAll();

$view_user_stats = "SELECT * FROM User_Statistics WHERE total_donated > 0 OR total_funds_raised > 0 ORDER BY total_donated DESC LIMIT 10";
$user_stats = executeAndLogQuery($pdo, $view_user_stats, [], 'analytics.php', 'SELECT')->fetchAll();

// ==========================================
// STORED PROCEDURE: Platform statistics
// ==========================================
$sp_stats = getPlatformStatistics($pdo);

// ==========================================
// UNION ALL: Combine donation trends from multiple sources
// ==========================================
$union_trends = "SELECT 
    'Daily' as period_type,
    DATE(donation_date) as period_value,
    COUNT(*) as donation_count,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount
FROM Donations
WHERE status = 'completed' 
    AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(donation_date)

UNION ALL

SELECT 
    'Weekly' as period_type,
    DATE_FORMAT(donation_date, '%Y-W%u') as period_value,
    COUNT(*) as donation_count,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount
FROM Donations
WHERE status = 'completed'
    AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
GROUP BY DATE_FORMAT(donation_date, '%Y-W%u')

UNION ALL

SELECT 
    'Monthly' as period_type,
    DATE_FORMAT(donation_date, '%Y-%m') as period_value,
    COUNT(*) as donation_count,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount
FROM Donations
WHERE status = 'completed'
    AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(donation_date, '%Y-%m')

ORDER BY period_type, period_value DESC";

$union_trends_results = executeAndLogQuery($pdo, $union_trends, [], 'analytics.php', 'SELECT')->fetchAll();

// ==========================================
// Conditional Aggregation with Multiple CASE statements
// ==========================================
$conditional_agg = "SELECT 
    COALESCE(cat.category_name, 'Uncategorized') as category,
    COUNT(*) as total_campaigns,
    SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN c.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(CASE WHEN c.current_amount >= c.goal_amount THEN 1 ELSE 0 END) as funded_count,
    SUM(CASE WHEN c.current_amount < c.goal_amount * 0.25 THEN 1 ELSE 0 END) as struggling_count,
    SUM(CASE WHEN c.current_amount >= c.goal_amount THEN c.current_amount ELSE 0 END) as overfunded_amount,
    ROUND(AVG(CASE WHEN c.status = 'active' THEN (c.current_amount / c.goal_amount) * 100 END), 2) as avg_active_completion
FROM Campaigns c
LEFT JOIN Categories cat ON c.category_id = cat.category_id
GROUP BY cat.category_name
ORDER BY total_campaigns DESC";

$conditional_agg_results = executeAndLogQuery($pdo, $conditional_agg, [], 'analytics.php', 'SELECT')->fetchAll();

// ==========================================
// HAVING with Complex Conditions
// ==========================================
$having_demo = "SELECT 
    COALESCE(cat.category_name, 'Uncategorized') as category,
    COUNT(*) as campaign_count,
    SUM(c.current_amount) as total_raised,
    AVG(c.current_amount) as avg_raised,
    MAX(c.current_amount) as max_raised,
    COUNT(DISTINCT c.creator_id) as unique_creators
FROM Campaigns c
LEFT JOIN Categories cat ON c.category_id = cat.category_id
GROUP BY cat.category_name
HAVING 
    COUNT(*) >= 2 
    AND SUM(current_amount) > 10000
    AND AVG(current_amount) > 5000
ORDER BY SUM(current_amount) DESC";

$having_results = executeAndLogQuery($pdo, $having_demo, [], 'analytics.php', 'SELECT')->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="fas fa-chart-line text-purple-600 mr-2"></i>
        Advanced Analytics & Reporting
    </h2>
    <p class="text-gray-600">Comprehensive SQL demonstrations: CTEs, VIEWs, ROLLUP, UNION, Stored Procedures</p>
</div>

<!-- SQL Features Panel -->
<div class="sql-features-panel">
    <h3>
        📊 Advanced SQL Features Demonstrated on This Page
        <button onclick="toggleFeaturePanel('analytics-features')">
            <i class="fas fa-chevron-down"></i>
        </button>
    </h3>
    <div id="analytics-features">
        <div class="sql-feature-item">
            <span class="feature-type">CTEs (WITH clause)</span>
            <code>WITH Campaign_Stats AS (SELECT ...), Donor_Engagement AS (SELECT ...) SELECT * FROM Campaign_Stats JOIN Donor_Engagement</code>
            <span class="feature-desc">Common Table Expressions for breaking complex queries into readable, modular subqueries</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">GROUP BY ... WITH ROLLUP</span>
            <code>GROUP BY category, status WITH ROLLUP</code>
            <span class="feature-desc">Generates subtotals and grand totals in a single query for hierarchical grouping</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">VIEWs</span>
            <code>SELECT * FROM Campaign_Progress</code>
            <span class="feature-desc">Pre-defined virtual tables that simplify complex queries and provide data abstraction</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">UNION ALL</span>
            <code>SELECT 'Daily', ... FROM ... UNION ALL SELECT 'Weekly', ... FROM ... UNION ALL SELECT 'Monthly', ...</code>
            <span class="feature-desc">Combines multiple SELECT results including duplicates (unlike UNION which removes them)</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Stored Procedures (CALL)</span>
            <code>CALL Calculate_Platform_Statistics(@total_campaigns, @total_users, @total_donations, ...)</code>
            <span class="feature-desc">Pre-compiled SQL routines stored in database for reusable business logic</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">HAVING with Multiple Conditions</span>
            <code>GROUP BY category HAVING COUNT(*) >= 2 AND SUM(amount) > 10000 AND AVG(amount) > 5000</code>
            <span class="feature-desc">Filters grouped results using aggregate conditions (WHERE filters rows, HAVING filters groups)</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Conditional Aggregation</span>
            <code>SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_count</code>
            <span class="feature-desc">Multiple CASE statements within aggregates to pivot data and create conditional counts/sums</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">COALESCE for NULL Handling</span>
            <code>COALESCE(category, 'ALL CATEGORIES')</code>
            <span class="feature-desc">Replaces NULL values with default text, essential for ROLLUP results</span>
        </div>
    </div>
</div>

<!-- Stored Procedure Results: Platform Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg shadow-lg p-6 text-white"
         data-sql-query="CALL Calculate_Platform_Statistics(@total_campaigns, @total_users, @total_donations, @total_amount, @avg_donation, @success_rate)"
         data-sql-explanation="Stored procedure that calculates multiple platform metrics in a single database call"
         data-sql-type="Stored Procedure">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Total Campaigns</h3>
            <i class="fas fa-bullhorn text-3xl opacity-75"></i>
        </div>
        <p class="text-4xl font-bold mb-2"><?php echo number_format($sp_stats['total_campaigns'] ?? 0); ?></p>
        <p class="text-sm opacity-80">Tracked campaigns</p>
    </div>
    
    <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-lg shadow-lg p-6 text-white"
         data-sql-query="CALL Calculate_Platform_Statistics(...)"
         data-sql-explanation="Total donation amount calculated by stored procedure"
         data-sql-type="Stored Procedure">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Total Raised</h3>
            <i class="fas fa-dollar-sign text-3xl opacity-75"></i>
        </div>
        <p class="text-4xl font-bold mb-2">$<?php echo number_format($sp_stats['total_funds_raised'] ?? 0, 0); ?></p>
        <p class="text-sm opacity-80">All-time donations</p>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-lg shadow-lg p-6 text-white"
         data-sql-query="CALL Calculate_Platform_Statistics(...)"
         data-sql-explanation="Average donation calculated by stored procedure"
         data-sql-type="Stored Procedure">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Avg Donation</h3>
            <i class="fas fa-hand-holding-usd text-3xl opacity-75"></i>
        </div>
        <p class="text-4xl font-bold mb-2">$<?php echo number_format($sp_stats['avg_donation_amount'] ?? 0, 0); ?></p>
        <p class="text-sm opacity-80">Per donation</p>
    </div>
    
    <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-lg shadow-lg p-6 text-white"
         data-sql-query="CALL Calculate_Platform_Statistics(...)"
         data-sql-explanation="Success rate percentage from stored procedure"
         data-sql-type="Stored Procedure">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Success Rate</h3>
            <i class="fas fa-percentage text-3xl opacity-75"></i>
        </div>
        <p class="text-4xl font-bold mb-2"><?php echo number_format($sp_stats['success_rate'] ?? 0, 1); ?>%</p>
        <p class="text-sm opacity-80">Campaigns funded</p>
    </div>
</div>

<!-- CTE Demonstration: Campaign Analytics with Multiple CTEs -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-project-diagram text-indigo-600 mr-2"></i>
            CTE Demonstration: Campaign Performance (WITH clause)
        </h3>
        <div class="text-sm text-gray-700 bg-white rounded p-3 font-mono text-xs">
            <strong>SQL:</strong> WITH Campaign_Stats AS (...), Donor_Engagement AS (...) SELECT * FROM Campaign_Stats JOIN Donor_Engagement
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Goal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raised</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">%</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donors</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donations</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="WITH Campaign_Stats AS (SELECT c.*, COUNT(d.*), AVG(d.amount) FROM Campaigns c LEFT JOIN Donations d GROUP BY c.campaign_id), Donor_Engagement AS (SELECT campaign_id, COUNT(DISTINCT donor_id) FROM Donations GROUP BY campaign_id) SELECT * FROM Campaign_Stats JOIN Donor_Engagement"
                   data-sql-explanation="CTE creates two temporary result sets (Campaign_Stats and Donor_Engagement) then joins them">
                <?php foreach ($cte_results as $row): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 max-w-xs truncate font-medium text-gray-900">
                        <?php echo htmlspecialchars($row['campaign_title']); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                            <?php echo htmlspecialchars($row['category']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        $<?php echo number_format($row['goal_amount'], 0); ?>
                    </td>
                    <td class="px-4 py-3 font-bold text-green-600">
                        $<?php echo number_format($row['current_amount'], 0); ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-green-600 h-2 rounded-full" 
                                     style="width: <?php echo min($row['completion_percentage'], 100); ?>%"></div>
                            </div>
                            <span class="text-xs font-semibold"><?php echo $row['completion_percentage']; ?>%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-purple-600">
                        <?php echo $row['unique_donors'] ?? 0; ?>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">
                        <?php echo $row['donation_count'] ?? 0; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        $<?php echo number_format($row['avg_donation'] ?? 0, 0); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-medium
                            <?php echo match($row['funding_stage']) {
                                'Fully Funded' => 'bg-green-100 text-green-800',
                                'Almost There' => 'bg-blue-100 text-blue-800',
                                'Halfway' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800'
                            }; ?>">
                            <?php echo $row['funding_stage']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ROLLUP Demonstration: Hierarchical Grouping -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-teal-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-layer-group text-green-600 mr-2"></i>
            ROLLUP Demonstration: Multi-Level Grouping (Subtotals & Grand Totals)
        </h3>
        <div class="text-sm text-gray-700 bg-white rounded p-3 font-mono text-xs">
            <strong>SQL:</strong> SELECT category, status, COUNT(*), SUM(amount) FROM Campaigns GROUP BY category, status WITH ROLLUP
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Goals</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Raised</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Raised</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT category, status, COUNT(*), SUM(goal_amount), SUM(current_amount), AVG(current_amount) FROM Campaigns GROUP BY category, status WITH ROLLUP"
                   data-sql-explanation="ROLLUP generates subtotals for each category and a grand total row (NULL values indicate aggregate rows)">
                <?php foreach ($rollup_results as $row): ?>
                <tr class="hover:bg-gray-50 <?php echo $row['category'] === 'ALL CATEGORIES' ? 'bg-yellow-50 font-bold border-t-2 border-yellow-400' : ($row['status'] === 'ALL STATUSES' ? 'bg-blue-50 font-semibold' : ''); ?>">
                    <td class="px-4 py-3 <?php echo $row['category'] === 'ALL CATEGORIES' ? 'font-bold text-gray-900' : 'text-gray-700'; ?>">
                        <?php echo $row['category'] === 'ALL CATEGORIES' ? '🌐 ' . $row['category'] : htmlspecialchars($row['category']); ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($row['status'] === 'ALL STATUSES'): ?>
                            <span class="px-2 py-1 bg-blue-100 text-blue-900 rounded text-xs font-bold">
                                <?php echo $row['status']; ?>
                            </span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-900">
                        <?php echo $row['campaign_count']; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        $<?php echo number_format($row['total_goals'], 0); ?>
                    </td>
                    <td class="px-4 py-3 font-bold text-green-600">
                        $<?php echo number_format($row['total_raised'], 0); ?>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        $<?php echo number_format($row['avg_raised'], 0); ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-semibold text-blue-600"><?php echo number_format($row['avg_completion_pct'], 1); ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- VIEW Demonstration -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- VIEW: Campaign_Progress -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-pink-50 to-red-50">
            <h3 class="text-lg font-bold text-gray-900 mb-1">
                <i class="fas fa-eye text-pink-600 mr-2"></i>
                VIEW: Campaign_Progress
            </h3>
            <code class="text-xs text-gray-700 bg-white px-2 py-1 rounded">SELECT * FROM Campaign_Progress</code>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-600 mb-4">Pre-defined VIEW that calculates campaign progress metrics automatically</p>
            <div class="space-y-3"
                 data-sql-query="CREATE VIEW Campaign_Progress AS SELECT campaign_id, campaign_title, (current_amount / goal_amount) * 100 as completion_percentage FROM Campaigns"
                 data-sql-explanation="VIEW is a stored query that acts like a virtual table - no data duplication">
                <?php foreach (array_slice($view_results, 0, 5) as $cp): ?>
                <div class="border border-gray-200 rounded p-3 hover:bg-gray-50">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($cp['campaign_title']); ?></span>
                        <span class="text-sm font-bold text-green-600"><?php echo number_format($cp['completion_percentage'], 1); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" 
                             style="width: <?php echo min($cp['completion_percentage'], 100); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- VIEW: User_Statistics -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-cyan-50 to-blue-50">
            <h3 class="text-lg font-bold text-gray-900 mb-1">
                <i class="fas fa-eye text-cyan-600 mr-2"></i>
                VIEW: User_Statistics
            </h3>
            <code class="text-xs text-gray-700 bg-white px-2 py-1 rounded">SELECT * FROM User_Statistics</code>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-600 mb-4">Aggregated user donation and campaign statistics from VIEW</p>
            <div class="overflow-x-auto"
                 data-sql-query="CREATE VIEW User_Statistics AS SELECT user_id, full_name, SUM(donations) as total_donated, COUNT(campaigns) as campaigns_created FROM Users LEFT JOIN..."
                 data-sql-explanation="Complex JOIN and aggregation logic hidden behind simple VIEW query">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">User</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">Donated</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">Raised</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach (array_slice($user_stats, 0, 5) as $us): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 text-gray-900 truncate"><?php echo htmlspecialchars($us['full_name']); ?></td>
                            <td class="px-2 py-2 font-semibold text-green-600">$<?php echo number_format($us['total_donated'], 0); ?></td>
                            <td class="px-2 py-2 text-blue-600">$<?php echo number_format($us['total_funds_raised'], 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- UNION ALL: Donation Trends -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-yellow-50 to-orange-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-compress-arrows-alt text-yellow-600 mr-2"></i>
            UNION ALL Demonstration: Multi-Period Donation Trends
        </h3>
        <div class="text-sm text-gray-700 bg-white rounded p-3 font-mono text-xs">
            <strong>SQL:</strong> SELECT 'Daily', ... FROM Donations GROUP BY DATE(...) UNION ALL SELECT 'Weekly', ... UNION ALL SELECT 'Monthly', ...
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period Value</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donations</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT 'Daily' as type, DATE(donation_date), COUNT(*), SUM(amount) FROM Donations GROUP BY DATE(donation_date) UNION ALL SELECT 'Weekly', DATE_FORMAT(...), ... UNION ALL SELECT 'Monthly', ..."
                   data-sql-explanation="UNION ALL combines three separate queries (daily, weekly, monthly trends) keeping all rows including duplicates">
                <?php foreach (array_slice($union_trends_results, 0, 20) as $trend): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            <?php echo match($trend['period_type']) {
                                'Daily' => 'bg-green-100 text-green-800',
                                'Weekly' => 'bg-blue-100 text-blue-800',
                                'Monthly' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800'
                            }; ?>">
                            <?php echo $trend['period_type']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-700">
                        <?php echo htmlspecialchars($trend['period_value']); ?>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-900">
                        <?php echo number_format($trend['donation_count']); ?>
                    </td>
                    <td class="px-4 py-3 font-bold text-green-600">
                        $<?php echo number_format($trend['total_amount'], 0); ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        $<?php echo number_format($trend['avg_amount'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Conditional Aggregation with Multiple CASE -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-code-branch text-purple-600 mr-2"></i>
            Conditional Aggregation: Multiple CASE Statements
        </h3>
        <div class="text-sm text-gray-700 bg-white rounded p-3 font-mono text-xs">
            <strong>SQL:</strong> SELECT category, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END), SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END), ...
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cancelled</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Funded</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Struggling</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overfunded $</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT category, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_count, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed_count, ... FROM Campaigns GROUP BY category"
                   data-sql-explanation="Multiple CASE statements in SELECT allow pivoting data - counts different statuses in separate columns">
                <?php foreach ($conditional_agg_results as $cat): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-gray-900">
                        <?php echo $cat['total_campaigns']; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded font-semibold">
                            <?php echo $cat['active_count']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded font-semibold">
                            <?php echo $cat['completed_count']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded font-semibold">
                            <?php echo $cat['cancelled_count']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded font-semibold">
                            <?php echo $cat['funded_count']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-orange-600 font-semibold">
                        <?php echo $cat['struggling_count']; ?>
                    </td>
                    <td class="px-4 py-3 font-bold text-green-600">
                        $<?php echo number_format($cat['overfunded_amount'], 0); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- HAVING Clause Demonstration -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-red-50 to-pink-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-filter text-red-600 mr-2"></i>
            HAVING Clause: Filter Grouped Results
        </h3>
        <div class="text-sm text-gray-700 bg-white rounded p-3 font-mono text-xs">
            <strong>SQL:</strong> SELECT category, COUNT(*), SUM(amount) FROM Campaigns GROUP BY category HAVING COUNT(*) >= 2 AND SUM(amount) > 10000
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaigns</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Raised</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Raised</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Max Raised</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creators</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT category, COUNT(*), SUM(current_amount), AVG(current_amount) FROM Campaigns GROUP BY category HAVING COUNT(*) >= 2 AND SUM(current_amount) > 10000 AND AVG(current_amount) > 5000"
                   data-sql-explanation="HAVING filters groups after GROUP BY (WHERE filters rows before grouping)">
                <?php if (empty($having_results)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-info-circle text-3xl mb-2"></i>
                        <p>No categories meet all HAVING conditions (≥2 campaigns, >$10k total, >$5k avg)</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($having_results as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <?php echo htmlspecialchars($row['category']); ?>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-blue-600">
                            <?php echo $row['campaign_count']; ?>
                        </td>
                        <td class="px-4 py-3 font-bold text-green-600">
                            $<?php echo number_format($row['total_raised'], 0); ?>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            $<?php echo number_format($row['avg_raised'], 0); ?>
                        </td>
                        <td class="px-4 py-3 text-purple-600 font-semibold">
                            $<?php echo number_format($row['max_raised'], 0); ?>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">
                            <?php echo $row['unique_creators']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
