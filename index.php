<?php
/**
 * Dashboard Page - Main Landing Page
 * Demonstrates: Aggregations (COUNT, SUM, AVG, MAX), INNER JOIN, Correlated Subqueries, LIMIT, Basic WHERE
 */

$page_title = 'Dashboard - CF Tracker';
$current_page = 'dashboard';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// Get platform statistics
try {
    $stats = getPlatformStatistics($pdo);
} catch (Exception $e) {
    $stats = [
        'total_active_users' => 0,
        'total_campaigns' => 0,
        'active_campaigns' => 0,
        'completed_campaigns' => 0,
        'total_funds_raised' => 0,
        'total_donations' => 0,
        'avg_donation_amount' => 0,
        'total_categories' => 0,
        'unique_donors' => 0
    ];
}

// Get recent active campaigns (using VIEW)
$recent_campaigns_query = "SELECT * FROM Active_Campaigns_Summary ORDER BY campaign_id DESC LIMIT 6";
$recent_campaigns = executeAndLogQuery($pdo, $recent_campaigns_query, [], 'index.php', 'SELECT')->fetchAll();

// Get top donors
$top_donors = getTopDonors($pdo, 5);

// Get recent donations
$recent_donations_query = "SELECT d.donation_id, d.amount, d.donation_date, 
                                  c.campaign_title, 
                                  CASE WHEN d.is_anonymous = 1 THEN 'Anonymous' ELSE u.full_name END as donor_name
                           FROM Donations d
                           INNER JOIN Campaigns c ON d.campaign_id = c.campaign_id
                           INNER JOIN Users u ON d.donor_id = u.user_id
                           WHERE d.status = 'completed'
                           ORDER BY d.donation_date DESC
                           LIMIT 10";
$recent_donations = executeAndLogQuery($pdo, $recent_donations_query, [], 'index.php', 'SELECT')->fetchAll();

// Get database tables with schema information
$tables_query = "SELECT 
    TABLE_NAME,
    TABLE_TYPE,
    TABLE_ROWS,
    CREATE_TIME,
    TABLE_COMMENT
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'CF_Tracker' 
AND TABLE_TYPE = 'BASE TABLE'
AND TABLE_NAME IN ('Users', 'Categories', 'Campaigns', 'Donations', 'Rewards', 'Comments', 
                    'Campaign_Updates', 'Campaign_Favorites', 'Campaign_Category', 'Donor_Rewards')
ORDER BY 
    CASE TABLE_NAME
        WHEN 'Users' THEN 1
        WHEN 'Categories' THEN 2
        WHEN 'Campaigns' THEN 3
        WHEN 'Donations' THEN 4
        WHEN 'Rewards' THEN 5
        WHEN 'Comments' THEN 6
        WHEN 'Campaign_Updates' THEN 7
        WHEN 'Campaign_Favorites' THEN 8
        WHEN 'Campaign_Category' THEN 9
        WHEN 'Donor_Rewards' THEN 10
    END";
$tables = executeAndLogQuery($pdo, $tables_query, [], 'index.php', 'SELECT')->fetchAll();

// Get CREATE TABLE statements for each table
$table_schemas = [];
foreach ($tables as $table) {
    $create_query = "SHOW CREATE TABLE " . $table['TABLE_NAME'];
    $create_result = executeAndLogQuery($pdo, $create_query, [], 'index.php', 'SHOW')->fetch();
    if ($create_result) {
        $table_schemas[$table['TABLE_NAME']] = $create_result['Create Table'];
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="fas fa-chart-line text-blue-600 mr-2"></i>
        Dashboard Overview
    </h2>
    <p class="text-gray-600">Real-time platform statistics and active campaigns</p>
</div>

<!-- SQL Features Panel for Dashboard -->
<div class="sql-features-panel">
    <h3>
        📋 SQL Features Demonstrated on This Page
        <button onclick="toggleFeaturePanel('dashboard-features')">
            <i class="fas fa-chevron-down"></i>
        </button>
    </h3>
    <div id="dashboard-features">
        <div class="sql-feature-item">
            <span class="feature-type">Stored Procedure</span>
            <code>CALL Calculate_Platform_Statistics()</code>
            <span class="feature-desc">Executes stored procedure to calculate aggregate platform statistics</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">VIEW Usage</span>
            <code>SELECT * FROM Active_Campaigns_Summary ORDER BY campaign_id DESC LIMIT 6</code>
            <span class="feature-desc">Uses pre-built VIEW for complex campaign data with calculations</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">INNER JOIN</span>
            <code>FROM Donations d INNER JOIN Campaigns c ON d.campaign_id = c.campaign_id INNER JOIN Users u ON d.donor_id = u.user_id</code>
            <span class="feature-desc">Multiple INNER JOINs to connect donations with campaigns and users</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">CASE Statement</span>
            <code>CASE WHEN d.is_anonymous = 1 THEN 'Anonymous' ELSE u.full_name END</code>
            <span class="feature-desc">Conditional logic to display anonymous donors</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Aggregation Functions</span>
            <code>COUNT(*), SUM(amount), AVG(amount), MAX(amount)</code>
            <span class="feature-desc">Aggregate functions used in stored procedure for platform statistics</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">LIMIT & ORDER BY</span>
            <code>ORDER BY donation_date DESC LIMIT 10</code>
            <span class="feature-desc">Pagination and sorting for recent items display</span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users -->
    <div class="bg-white rounded-lg shadow-md p-6 card"
         data-sql-query="SELECT COUNT(*) FROM Users WHERE is_active = TRUE"
         data-sql-explanation="Counts total active users using WHERE clause for filtering"
         data-sql-type="COUNT Aggregation">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Active Users</p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['total_active_users']); ?></h3>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Campaigns -->
    <div class="bg-white rounded-lg shadow-md p-6 card"
         data-sql-query="SELECT COUNT(*) FROM Campaigns"
         data-sql-explanation="Simple COUNT to get total number of campaigns"
         data-sql-type="COUNT Aggregation">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Campaigns</p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['total_campaigns']); ?></h3>
                <p class="text-sm text-green-600 mt-1">
                    <i class="fas fa-circle-notch fa-spin mr-1"></i><?php echo $stats['active_campaigns']; ?> active
                </p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-bullhorn text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Funds Raised -->
    <div class="bg-white rounded-lg shadow-md p-6 card"
         data-sql-query="SELECT COALESCE(SUM(amount), 0) FROM Donations WHERE status = 'completed'"
         data-sql-explanation="SUM aggregation with COALESCE to handle NULL values, filtered by status"
         data-sql-type="SUM Aggregation">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Funds Raised</p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1">$<?php echo number_format($stats['total_funds_raised'], 2); ?></h3>
                <p class="text-sm text-blue-600 mt-1">
                    <?php echo number_format($stats['total_donations']); ?> donations
                </p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Average Donation -->
    <div class="bg-white rounded-lg shadow-md p-6 card"
         data-sql-query="SELECT COALESCE(AVG(amount), 0) FROM Donations WHERE status = 'completed'"
         data-sql-explanation="AVG function to calculate average donation amount across all completed donations"
         data-sql-type="AVG Aggregation">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Avg Donation</p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1">$<?php echo number_format($stats['avg_donation_amount'], 2); ?></h3>
                <p class="text-sm text-gray-500 mt-1">
                    <?php echo number_format($stats['unique_donors']); ?> unique donors
                </p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
                <i class="fas fa-hand-holding-heart text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Recent Active Campaigns -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-fire text-orange-500 mr-2"></i>
                    Active Campaigns
                </h3>
                <a href="/Crowdfunding-tracker_DB_Project/campaigns.php" 
                   class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                   data-sql-query="SELECT * FROM Campaigns WHERE status = 'active'"
                   data-sql-explanation="Retrieves all active campaigns using WHERE clause">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <?php if (empty($recent_campaigns)): ?>
                <p class="text-center text-gray-500 py-8">No active campaigns found. Create one to get started!</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_campaigns as $campaign): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition"
                             data-sql-query="SELECT c.*, u.username, cat.category_name FROM Campaigns c INNER JOIN Users u ON c.creator_id = u.user_id LEFT JOIN Categories cat ON c.category_id = cat.category_id WHERE c.campaign_id = <?php echo $campaign['campaign_id']; ?>"
                             data-sql-explanation="INNER JOIN with Users and LEFT JOIN with Categories to get campaign details">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($campaign['campaign_title']); ?></h4>
                                    <p class="text-sm text-gray-600 mb-2">
                                        by <span class="text-blue-600"><?php echo htmlspecialchars($campaign['creator']); ?></span>
                                        <?php if ($campaign['category_name']): ?>
                                            • <span class="text-gray-500"><?php echo htmlspecialchars($campaign['category_name']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <!-- Progress Bar -->
                                    <div class="mb-2">
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">$<?php echo number_format($campaign['current_amount'], 2); ?> raised</span>
                                            <span class="font-semibold text-gray-900"><?php echo $campaign['completion_percentage']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" 
                                                 style="width: <?php echo min($campaign['completion_percentage'], 100); ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="flex items-center text-sm text-gray-500 space-x-4">
                                        <span><i class="fas fa-bullseye mr-1"></i>Goal: $<?php echo number_format($campaign['goal_amount'], 2); ?></span>
                                        <span><i class="fas fa-users mr-1"></i><?php echo $campaign['backer_count']; ?> backers</span>
                                        <span class="<?php echo $campaign['days_left'] > 7 ? 'text-green-600' : 'text-red-600'; ?>">
                                            <i class="fas fa-clock mr-1"></i><?php echo $campaign['days_left']; ?> days left
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        
        <!-- Top Donors -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                Top Donors
            </h3>

            <?php if (empty($top_donors)): ?>
                <p class="text-center text-gray-500 py-4">No donors yet</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($top_donors as $index => $donor): ?>
                        <div class="flex items-center space-x-3"
                             data-sql-query="SELECT u.user_id, u.username, u.full_name, COUNT(d.donation_id) as donation_count, SUM(d.amount) as total_donated FROM Users u INNER JOIN Donations d ON u.user_id = d.donor_id WHERE d.status = 'completed' GROUP BY u.user_id ORDER BY total_donated DESC LIMIT 5"
                             data-sql-explanation="GROUP BY with aggregations (COUNT, SUM) and ORDER BY to rank top donors">
                            <div class="flex-shrink-0">
                                <?php if ($index === 0): ?>
                                    <i class="fas fa-medal text-yellow-500 text-2xl"></i>
                                <?php elseif ($index === 1): ?>
                                    <i class="fas fa-medal text-gray-400 text-2xl"></i>
                                <?php elseif ($index === 2): ?>
                                    <i class="fas fa-medal text-orange-600 text-2xl"></i>
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-bold"><?php echo $index + 1; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($donor['full_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $donor['donation_count']; ?> donations</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">$<?php echo number_format($donor['total_donated'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Donations -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-heart text-red-500 mr-2"></i>
                Recent Donations
            </h3>

            <?php if (empty($recent_donations)): ?>
                <p class="text-center text-gray-500 py-4">No donations yet</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($recent_donations, 0, 5) as $donation): ?>
                        <div class="border-l-4 border-green-500 pl-3">
                            <p class="font-semibold text-sm text-gray-900">$<?php echo number_format($donation['amount'], 2); ?></p>
                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars($donation['donor_name']); ?></p>
                            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($donation['campaign_title']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <a href="/Crowdfunding-tracker_DB_Project/donations.php" 
               class="block text-center text-blue-600 hover:text-blue-700 text-sm font-medium mt-4">
                View All Donations <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Database Tables Schema -->
<div class="mt-8 bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 flex items-center"
            data-sql-query="SELECT TABLE_NAME, TABLE_ROWS, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'CF_Tracker' AND TABLE_TYPE = 'BASE TABLE'"
            data-sql-explanation="Queries information_schema to get metadata about all tables in the database"
            data-sql-type="SELECT">
            <i class="fas fa-database text-blue-600 mr-3"></i>
            Database Tables Schema
        </h2>
        <span class="text-sm text-gray-500">
            <i class="fas fa-table mr-1"></i><?php echo count($tables); ?> Tables
        </span>
    </div>

    <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
            <div class="text-sm text-blue-800">
                <strong>Hover over any table card</strong> to see the complete SQL CREATE TABLE statement with all columns, data types, constraints, and foreign keys.
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($tables as $table): ?>
            <?php
            $table_name = $table['TABLE_NAME'];
            $is_bridge = strpos($table_name, '_') !== false && (
                strpos($table_name, 'Campaign_Favorites') !== false ||
                strpos($table_name, 'Campaign_Category') !== false ||
                strpos($table_name, 'Donor_Rewards') !== false
            );
            $is_audit = strpos($table_name, 'Audit') !== false || strpos($table_name, 'Log') !== false;
            
            if ($is_bridge) {
                $bg_color = 'bg-purple-50 border-purple-300';
                $icon_color = 'text-purple-600';
                $badge_color = 'bg-purple-100 text-purple-800';
                $badge_text = 'M:N Bridge';
            } elseif ($is_audit) {
                $bg_color = 'bg-gray-50 border-gray-300';
                $icon_color = 'text-gray-600';
                $badge_color = 'bg-gray-100 text-gray-800';
                $badge_text = 'Audit';
            } else {
                $bg_color = 'bg-blue-50 border-blue-300';
                $icon_color = 'text-blue-600';
                $badge_color = 'bg-blue-100 text-blue-800';
                $badge_text = 'Core';
            }
            ?>
            
            <div class="border-2 <?php echo $bg_color; ?> rounded-lg p-4 hover:shadow-lg transition-all cursor-pointer"
                 data-sql-query="<?php echo htmlspecialchars($table_schemas[$table_name] ?? 'CREATE TABLE ' . $table_name); ?>"
                 data-sql-explanation="Complete CREATE TABLE statement showing all columns, constraints, indexes, and foreign keys"
                 data-sql-type="CREATE TABLE">
                
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-table <?php echo $icon_color; ?> text-xl mr-2"></i>
                        <h3 class="font-bold text-gray-900 text-sm"><?php echo $table_name; ?></h3>
                    </div>
                    <span class="text-xs px-2 py-1 <?php echo $badge_color; ?> rounded-full font-medium">
                        <?php echo $badge_text; ?>
                    </span>
                </div>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">
                            <i class="fas fa-chart-bar mr-1"></i>Rows
                        </span>
                        <span class="font-semibold text-gray-900">
                            <?php echo number_format($table['TABLE_ROWS'] ?? 0); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($table['TABLE_COMMENT'])): ?>
                    <div class="text-xs text-gray-600 italic border-t border-gray-200 pt-2">
                        <i class="fas fa-comment-dots mr-1"></i>
                        <?php echo htmlspecialchars($table['TABLE_COMMENT']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-xs text-gray-500 border-t border-gray-200 pt-2">
                        <i class="fas fa-clock mr-1"></i>
                        Created: <?php echo $table['CREATE_TIME'] ? date('M j, Y', strtotime($table['CREATE_TIME'])) : 'N/A'; ?>
                    </div>
                </div>
                
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex items-center text-xs text-gray-500">
                        <i class="fas fa-mouse-pointer mr-1"></i>
                        <span class="italic">Hover to see CREATE TABLE query</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-8 text-white">
    <div class="text-center mb-6">
        <h3 class="text-2xl font-bold mb-2">Ready to explore SQL features?</h3>
        <p class="text-blue-100">Check out all the advanced SQL features demonstrated in this platform</p>
    </div>
    <div class="flex justify-center gap-4">
        <a href="/Crowdfunding-tracker_DB_Project/sql_features.php" 
           class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
            <i class="fas fa-code mr-2"></i>Explore SQL Features
        </a>
        <a href="/Crowdfunding-tracker_DB_Project/analytics.php" 
           class="bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-800 transition">
            <i class="fas fa-chart-bar mr-2"></i>View Analytics
        </a>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
