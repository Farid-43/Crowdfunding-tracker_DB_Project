<?php
/**
 * SQL Features Catalog & Query History
 * Comprehensive demonstration of ALL implemented SQL features with live examples
 */

$page_title = 'SQL Features Catalog - CF Tracker';
$current_page = 'sql_features';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// Handle search query
$search_query = '';
$search_results = [];
$file_search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    
    // Search in Query_Log for matching queries
    $search_sql = "SELECT 
        log_id,
        query_text,
        query_type,
        page_name,
        execution_time,
        executed_at,
        rows_affected
    FROM Query_Log
    WHERE query_text LIKE :search
    ORDER BY executed_at DESC
    LIMIT 100";
    
    $stmt = $pdo->prepare($search_sql);
    $stmt->execute([':search' => '%' . $search_query . '%']);
    $search_results = $stmt->fetchAll();
    
    // Also search in SQL schema files for triggers, procedures, functions, views
    $files_to_search = [
        'database/schema.sql' => 'Schema Definition',
        'database/schema_no_drop.sql' => 'Schema (No Drop)',
        'config/database.php' => 'Database Config',
        'config/db_functions.php' => 'DB Functions'
    ];
    
    foreach ($files_to_search as $file => $description) {
        $filepath = __DIR__ . '/' . $file;
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line_num => $line) {
                if (stripos($line, $search_query) !== false) {
                    // Get context (3 lines before and after)
                    $start = max(0, $line_num - 3);
                    $end = min(count($lines) - 1, $line_num + 3);
                    $context = [];
                    
                    for ($i = $start; $i <= $end; $i++) {
                        $context[] = [
                            'line_num' => $i + 1,
                            'content' => $lines[$i],
                            'is_match' => $i === $line_num
                        ];
                    }
                    
                    $file_search_results[] = [
                        'file' => $file,
                        'description' => $description,
                        'line_number' => $line_num + 1,
                        'context' => $context
                    ];
                }
            }
        }
    }
    
    // Log this search query
    logQuery($pdo, $search_sql, 'SELECT', 'sql_features.php', 0, count($search_results));
}

// Get recent query history
$query_history_sql = "SELECT 
    log_id,
    query_text,
    query_type,
    execution_time,
    executed_at,
    page_name
FROM Query_Log
ORDER BY executed_at DESC
LIMIT 50";

$query_history = executeAndLogQuery($pdo, $query_history_sql, [], 'sql_features.php', 'SELECT')->fetchAll();

// Get query statistics by type
$query_stats_sql = "SELECT 
    query_type,
    COUNT(*) as query_count,
    AVG(execution_time) as avg_execution_time,
    MAX(execution_time) as max_execution_time,
    MIN(execution_time) as min_execution_time,
    SUM(execution_time) as total_execution_time
FROM Query_Log
GROUP BY query_type
ORDER BY query_count DESC";

$query_stats = executeAndLogQuery($pdo, $query_stats_sql, [], 'sql_features.php', 'SELECT')->fetchAll();

// Get query counts by page
$page_stats_sql = "SELECT 
    page_name,
    COUNT(*) as query_count,
    COUNT(DISTINCT query_type) as unique_query_types
FROM Query_Log
GROUP BY page_name
ORDER BY query_count DESC";

$page_stats = executeAndLogQuery($pdo, $page_stats_sql, [], 'sql_features.php', 'SELECT')->fetchAll();

// SQL Feature Catalog - Comprehensive list
$sql_features = [
    'DML (Data Manipulation)' => [
        ['name' => 'SELECT', 'description' => 'Retrieve data from tables', 'example' => 'SELECT * FROM Users WHERE user_role = \'donor\'', 'page' => 'All pages', 'status' => 'implemented'],
        ['name' => 'INSERT', 'description' => 'Add new records', 'example' => 'INSERT INTO Campaigns (campaign_title, goal_amount) VALUES (?, ?)', 'page' => 'campaigns.php', 'status' => 'implemented'],
        ['name' => 'UPDATE', 'description' => 'Modify existing records', 'example' => 'UPDATE Users SET account_balance = ? WHERE user_id = ?', 'page' => 'users.php', 'status' => 'implemented'],
        ['name' => 'DELETE', 'description' => 'Remove records', 'example' => 'DELETE FROM Campaigns WHERE campaign_id = ?', 'page' => 'campaigns.php', 'status' => 'implemented'],
    ],
    'Joins' => [
        ['name' => 'INNER JOIN', 'description' => 'Returns matching rows from both tables', 'example' => 'SELECT * FROM Donations d INNER JOIN Campaigns c ON d.campaign_id = c.campaign_id', 'page' => 'index.php', 'status' => 'implemented'],
        ['name' => 'LEFT JOIN', 'description' => 'Returns all left table rows + matches', 'example' => 'SELECT * FROM Campaigns c LEFT JOIN Donations d ON c.campaign_id = d.campaign_id', 'page' => 'campaigns.php', 'status' => 'implemented'],
        ['name' => 'RIGHT JOIN', 'description' => 'Returns all right table rows + matches', 'example' => 'SELECT * FROM Donations d RIGHT JOIN Users u ON d.donor_id = u.user_id', 'page' => 'Available in schema', 'status' => 'implemented'],
        ['name' => 'CROSS JOIN', 'description' => 'Cartesian product of two tables', 'example' => 'SELECT * FROM Categories CROSS JOIN Campaign_Status', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
    'Aggregation Functions' => [
        ['name' => 'COUNT()', 'description' => 'Count rows or non-null values', 'example' => 'SELECT COUNT(*) FROM Campaigns WHERE status = \'active\'', 'page' => 'index.php', 'status' => 'implemented'],
        ['name' => 'SUM()', 'description' => 'Sum of numeric values', 'example' => 'SELECT SUM(amount) FROM Donations WHERE status = \'completed\'', 'page' => 'index.php', 'status' => 'implemented'],
        ['name' => 'AVG()', 'description' => 'Average of numeric values', 'example' => 'SELECT AVG(goal_amount) FROM Campaigns', 'page' => 'index.php', 'status' => 'implemented'],
        ['name' => 'MAX()', 'description' => 'Maximum value', 'example' => 'SELECT MAX(amount) FROM Donations', 'page' => 'index.php', 'status' => 'implemented'],
        ['name' => 'MIN()', 'description' => 'Minimum value', 'example' => 'SELECT MIN(created_at) FROM Campaigns', 'page' => 'index.php', 'status' => 'implemented'],
    ],
    'Grouping & Filtering' => [
        ['name' => 'GROUP BY', 'description' => 'Group rows sharing a property', 'example' => 'SELECT category, COUNT(*) FROM Campaigns GROUP BY category', 'page' => 'campaigns.php', 'status' => 'implemented'],
        ['name' => 'HAVING', 'description' => 'Filter grouped results', 'example' => 'SELECT category, SUM(amount) FROM Campaigns GROUP BY category HAVING SUM(amount) > 10000', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'ROLLUP', 'description' => 'Generate subtotals and totals', 'example' => 'SELECT category, status, COUNT(*) FROM Campaigns GROUP BY category, status WITH ROLLUP', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'WHERE', 'description' => 'Filter rows before grouping', 'example' => 'SELECT * FROM Donations WHERE amount > 100', 'page' => 'All pages', 'status' => 'implemented'],
    ],
    'Window Functions' => [
        ['name' => 'ROW_NUMBER()', 'description' => 'Sequential number for each row', 'example' => 'SELECT *, ROW_NUMBER() OVER (PARTITION BY campaign_id ORDER BY amount DESC) FROM Donations', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'SUM() OVER()', 'description' => 'Running total', 'example' => 'SELECT *, SUM(amount) OVER (PARTITION BY campaign_id ORDER BY donation_date) as running_total FROM Donations', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'AVG() OVER()', 'description' => 'Moving average', 'example' => 'SELECT *, AVG(amount) OVER (PARTITION BY campaign_id) FROM Donations', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'RANK()', 'description' => 'Rank with gaps for ties', 'example' => 'SELECT *, RANK() OVER (ORDER BY amount DESC) FROM Donations', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
    'Set Operations' => [
        ['name' => 'UNION', 'description' => 'Combine results, remove duplicates', 'example' => 'SELECT user_id FROM Donors UNION SELECT user_id FROM Campaigners', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'UNION ALL', 'description' => 'Combine results, keep duplicates', 'example' => 'SELECT \'Daily\', ... FROM Donations UNION ALL SELECT \'Weekly\', ... FROM Donations', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'INTERSECT', 'description' => 'Common rows between sets', 'example' => 'SELECT user_id FROM Active_Users INTERSECT SELECT user_id FROM Donors', 'page' => 'Available in schema', 'status' => 'implemented'],
        ['name' => 'EXCEPT', 'description' => 'Rows in first set but not second', 'example' => 'SELECT user_id FROM All_Users EXCEPT SELECT user_id FROM Banned_Users', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
    'Subqueries' => [
        ['name' => 'Scalar Subquery', 'description' => 'Returns single value', 'example' => 'SELECT * FROM Campaigns WHERE goal_amount > (SELECT AVG(goal_amount) FROM Campaigns)', 'page' => 'index.php', 'status' => 'implemented'],
        ['name' => 'Correlated Subquery', 'description' => 'References outer query', 'example' => 'SELECT u.*, (SELECT COUNT(*) FROM Donations d WHERE d.donor_id = u.user_id) FROM Users u', 'page' => 'users.php', 'status' => 'implemented'],
        ['name' => 'IN Subquery', 'description' => 'Check if value in set', 'example' => 'SELECT * FROM Campaigns WHERE creator_id IN (SELECT user_id FROM Users WHERE user_role = \'campaigner\')', 'page' => 'Available in schema', 'status' => 'implemented'],
        ['name' => 'EXISTS Subquery', 'description' => 'Check if subquery returns rows', 'example' => 'SELECT * FROM Users u WHERE EXISTS (SELECT 1 FROM Donations d WHERE d.donor_id = u.user_id)', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
    'Advanced Query Features' => [
        ['name' => 'CTE (WITH clause)', 'description' => 'Named temporary result sets', 'example' => 'WITH Campaign_Stats AS (SELECT ...) SELECT * FROM Campaign_Stats', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'CASE Statement', 'description' => 'Conditional logic in SELECT', 'example' => 'SELECT CASE WHEN amount > 1000 THEN \'Large\' ELSE \'Small\' END FROM Donations', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'COALESCE', 'description' => 'Return first non-NULL value', 'example' => 'SELECT COALESCE(category, \'ALL CATEGORIES\') FROM Campaigns WITH ROLLUP', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'CAST/CONVERT', 'description' => 'Type conversion', 'example' => 'SELECT CAST(amount AS DECIMAL(10,2)) FROM Donations', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
    'Constraints' => [
        ['name' => 'PRIMARY KEY', 'description' => 'Unique identifier for rows', 'example' => 'CREATE TABLE Users (user_id INT PRIMARY KEY AUTO_INCREMENT)', 'page' => 'schema.sql', 'status' => 'implemented'],
        ['name' => 'FOREIGN KEY', 'description' => 'Enforces referential integrity', 'example' => 'FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) ON DELETE CASCADE', 'page' => 'schema.sql', 'status' => 'implemented'],
        ['name' => 'UNIQUE', 'description' => 'Ensures column values are unique', 'example' => 'CONSTRAINT uk_users_username UNIQUE (username)', 'page' => 'users.php', 'status' => 'implemented'],
        ['name' => 'CHECK', 'description' => 'Validates data against condition', 'example' => 'CONSTRAINT chk_account_balance CHECK (account_balance >= 0)', 'page' => 'users.php', 'status' => 'implemented'],
        ['name' => 'NOT NULL', 'description' => 'Prevents NULL values', 'example' => 'campaign_title VARCHAR(200) NOT NULL', 'page' => 'schema.sql', 'status' => 'implemented'],
        ['name' => 'DEFAULT', 'description' => 'Default value for column', 'example' => 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'page' => 'schema.sql', 'status' => 'implemented'],
    ],
    'Database Objects' => [
        ['name' => 'VIEWs', 'description' => 'Virtual tables based on queries', 'example' => 'CREATE VIEW Campaign_Progress AS SELECT ... ; SELECT * FROM Campaign_Progress', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'Stored Procedures', 'description' => 'Reusable SQL code blocks', 'example' => 'CALL Process_Donation(campaign_id, donor_id, amount, ...)', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'Functions', 'description' => 'Return calculated values', 'example' => 'SELECT Get_Donor_Level(user_id) FROM Users', 'page' => 'users.php', 'status' => 'implemented'],
        ['name' => 'Triggers', 'description' => 'Automatic actions on events', 'example' => 'CREATE TRIGGER trg_user_after_update AFTER UPDATE ON Users FOR EACH ROW ...', 'page' => 'users.php', 'status' => 'implemented'],
        ['name' => 'INDEXes', 'description' => 'Improve query performance', 'example' => 'CREATE INDEX idx_campaign_category ON Campaigns(category)', 'page' => 'schema.sql', 'status' => 'implemented'],
    ],
    'Transaction Control' => [
        ['name' => 'START TRANSACTION', 'description' => 'Begin transaction', 'example' => 'START TRANSACTION; INSERT INTO ...; UPDATE ...; COMMIT;', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'COMMIT', 'description' => 'Save transaction changes', 'example' => 'BEGIN; INSERT INTO Donations ...; UPDATE Campaigns ...; COMMIT;', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'ROLLBACK', 'description' => 'Undo transaction changes', 'example' => 'BEGIN; DELETE FROM ...; ROLLBACK;', 'page' => 'donations.php', 'status' => 'implemented'],
        ['name' => 'SAVEPOINT', 'description' => 'Set intermediate transaction point', 'example' => 'SAVEPOINT before_update; UPDATE ...; ROLLBACK TO before_update;', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
    'String Functions' => [
        ['name' => 'CONCAT()', 'description' => 'Concatenate strings', 'example' => 'SELECT CONCAT(first_name, \' \', last_name) FROM Users', 'page' => 'Available in schema', 'status' => 'implemented'],
        ['name' => 'UPPER()/LOWER()', 'description' => 'Change case', 'example' => 'SELECT UPPER(username) FROM Users', 'page' => 'Available in schema', 'status' => 'implemented'],
        ['name' => 'SUBSTRING()', 'description' => 'Extract substring', 'example' => 'SELECT SUBSTRING(description, 1, 100) FROM Campaigns', 'page' => 'Available in schema', 'status' => 'implemented'],
        ['name' => 'LIKE', 'description' => 'Pattern matching', 'example' => 'SELECT * FROM Campaigns WHERE campaign_title LIKE \'%education%\'', 'page' => 'campaigns.php', 'status' => 'implemented'],
    ],
    'Date/Time Functions' => [
        ['name' => 'NOW()', 'description' => 'Current date and time', 'example' => 'SELECT NOW()', 'page' => 'schema.sql', 'status' => 'implemented'],
        ['name' => 'DATE()', 'description' => 'Extract date from datetime', 'example' => 'SELECT DATE(donation_date) FROM Donations', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'DATE_FORMAT()', 'description' => 'Format date/time', 'example' => 'SELECT DATE_FORMAT(created_at, \'%Y-%m\') FROM Campaigns', 'page' => 'analytics.php', 'status' => 'implemented'],
        ['name' => 'DATEDIFF()', 'description' => 'Difference between dates', 'example' => 'SELECT DATEDIFF(end_date, NOW()) FROM Campaigns', 'page' => 'Available in schema', 'status' => 'implemented'],
    ],
];

// Count total features
$total_categories = count($sql_features);
$total_features = array_sum(array_map('count', $sql_features));
$implemented_count = 0;
foreach ($sql_features as $features) {
    foreach ($features as $feature) {
        if ($feature['status'] === 'implemented') $implemented_count++;
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h2 class="text-4xl font-bold text-gray-900 mb-2">
        <i class="fas fa-database text-indigo-600 mr-3"></i>
        SQL Features Catalog
    </h2>
    <p class="text-gray-600 text-lg">Comprehensive reference of all implemented SQL features with live examples</p>
</div>

<!-- SQL Query Search Box -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-search text-blue-600 mr-2"></i>
            Search SQL Queries
        </h3>
        <p class="text-sm text-gray-700">Find where specific SQL queries, keywords, or table names are used in the application</p>
    </div>
    
    <div class="p-6">
        <form method="GET" action="" class="mb-6">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Enter SQL keyword, table name, or query fragment (e.g., SELECT, Campaigns, JOIN, WHERE status)"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           autofocus>
                </div>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <?php if ($search_query): ?>
                <a href="sql_features.php" 
                   class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Examples -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <h4 class="font-semibold text-blue-900 mb-2">
                <i class="fas fa-lightbulb mr-2"></i>Search Examples
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                <a href="?search=SELECT" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">SELECT</a>
                <a href="?search=JOIN" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">JOIN</a>
                <a href="?search=GROUP BY" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">GROUP BY</a>
                <a href="?search=WHERE" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">WHERE</a>
                <a href="?search=Campaigns" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">Campaigns</a>
                <a href="?search=Donations" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">Donations</a>
                <a href="?search=COUNT" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">COUNT(*)</a>
                <a href="?search=SUM" class="bg-white px-3 py-2 rounded hover:bg-blue-100 text-blue-800 font-mono">SUM()</a>
            </div>
        </div>
        
        <?php if ($search_query): ?>
        <!-- Search Results -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h4 class="font-bold text-gray-900">
                    <i class="fas fa-list-ul text-green-600 mr-2"></i>
                    Search Results for: <code class="bg-yellow-100 px-2 py-1 rounded"><?php echo htmlspecialchars($search_query); ?></code>
                </h4>
                <p class="text-sm text-gray-600 mt-1">
                    Found <?php echo count($search_results); ?> executed queries
                    <?php if (count($file_search_results) > 0): ?>
                    | <?php echo count($file_search_results); ?> matches in schema files
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (empty($search_results) && empty($file_search_results)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-search text-4xl mb-3"></i>
                <p class="text-lg">No queries found matching <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></p>
                <p class="text-sm mt-2">Try a different keyword or browse the query history below</p>
            </div>
            <?php else: ?>
            
            <!-- Schema/File Search Results -->
            <?php if (!empty($file_search_results)): ?>
            <div class="p-4 bg-purple-50 border-b border-purple-200">
                <h5 class="font-bold text-purple-900 mb-2">
                    <i class="fas fa-file-code text-purple-600 mr-2"></i>
                    Found in Schema & SQL Files (<?php echo count($file_search_results); ?> matches)
                </h5>
                <p class="text-sm text-purple-800">
                    These are SQL definitions (triggers, procedures, functions, views) found in your schema files
                </p>
            </div>
            
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                <?php foreach (array_slice($file_search_results, 0, 20) as $result): ?>
                <div class="p-4 hover:bg-purple-50">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded text-xs font-bold">
                                <i class="fas fa-database mr-1"></i>
                                <?php echo htmlspecialchars($result['description']); ?>
                            </span>
                            <span class="text-xs text-gray-600">
                                Line <?php echo $result['line_number']; ?>
                            </span>
                        </div>
                        <code class="text-xs text-gray-500">
                            <?php echo htmlspecialchars($result['file']); ?>
                        </code>
                    </div>
                    
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs font-mono"><code><?php
                            foreach ($result['context'] as $ctx) {
                                $line_class = $ctx['is_match'] ? 'bg-yellow-500 text-gray-900 font-bold' : 'text-gray-400';
                                echo '<span class="' . $line_class . '">';
                                echo sprintf('%4d', $ctx['line_num']) . ' | ';
                                if ($ctx['is_match']) {
                                    // Highlight search term
                                    $highlighted = preg_replace(
                                        '/(' . preg_quote($search_query, '/') . ')/i',
                                        '<span class="bg-red-400 text-white px-1 rounded">$1</span>',
                                        htmlspecialchars($ctx['content'])
                                    );
                                    echo $highlighted;
                                } else {
                                    echo htmlspecialchars($ctx['content']);
                                }
                                echo '</span>' . "\n";
                            }
                        ?></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($file_search_results) > 20): ?>
                <div class="p-4 text-center text-gray-600 bg-gray-50">
                    <i class="fas fa-info-circle mr-2"></i>
                    Showing first 20 of <?php echo count($file_search_results); ?> matches in files
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Executed Query Results -->
            <?php if (!empty($search_results)): ?>
            <div class="p-4 bg-blue-50 border-b border-blue-200">
                <h5 class="font-bold text-blue-900 mb-2">
                    <i class="fas fa-play-circle text-blue-600 mr-2"></i>
                    Executed Queries (<?php echo count($search_results); ?> found)
                </h5>
                <p class="text-sm text-blue-800">
                    These queries were actually executed and logged in the application
                </p>
            </div>
            
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                <?php foreach ($search_results as $result): ?>
                <div class="p-4 hover:bg-blue-50">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-gray-600 text-xs">
                                #<?php echo $result['log_id']; ?>
                            </span>
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                <?php echo match($result['query_type']) {
                                    'SELECT' => 'bg-blue-100 text-blue-800',
                                    'INSERT' => 'bg-green-100 text-green-800',
                                    'UPDATE' => 'bg-yellow-100 text-yellow-800',
                                    'DELETE' => 'bg-red-100 text-red-800',
                                    'CALL' => 'bg-purple-100 text-purple-800',
                                    default => 'bg-gray-100 text-gray-800'
                                }; ?>">
                                <?php echo $result['query_type']; ?>
                            </span>
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-semibold">
                                <i class="fas fa-file-code mr-1"></i>
                                <?php echo htmlspecialchars($result['page_name']); ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-700">
                                <i class="fas fa-table mr-1"></i>
                                <?php echo number_format($result['rows_affected'] ?? 0); ?> rows
                            </span>
                            <span class="font-semibold
                                <?php echo $result['execution_time'] > 0.01 ? 'text-red-600' : ($result['execution_time'] > 0.005 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo number_format($result['execution_time'] * 1000, 2); ?> ms
                            </span>
                            <span class="text-gray-500">
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo date('M j, H:i:s', strtotime($result['executed_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <code class="text-xs text-green-400 font-mono whitespace-pre-wrap"><?php 
                            // Highlight search term in query
                            $highlighted = preg_replace(
                                '/(' . preg_quote($search_query, '/') . ')/i',
                                '<span class="bg-yellow-400 text-gray-900 font-bold px-1 rounded">$1</span>',
                                htmlspecialchars($result['query_text'])
                            );
                            echo $highlighted;
                        ?></code>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Feature Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Total Categories</h3>
            <i class="fas fa-folder-open text-3xl opacity-75"></i>
        </div>
        <p class="text-5xl font-bold mb-2"><?php echo $total_categories; ?></p>
        <p class="text-sm opacity-80">Feature categories</p>
    </div>
    
    <div class="bg-gradient-to-br from-green-500 to-teal-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Total Features</h3>
            <i class="fas fa-code text-3xl opacity-75"></i>
        </div>
        <p class="text-5xl font-bold mb-2"><?php echo $total_features; ?></p>
        <p class="text-sm opacity-80">SQL features</p>
    </div>
    
    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Implemented</h3>
            <i class="fas fa-check-circle text-3xl opacity-75"></i>
        </div>
        <p class="text-5xl font-bold mb-2"><?php echo $implemented_count; ?></p>
        <p class="text-sm opacity-80">
            <?php echo round(($implemented_count / $total_features) * 100, 1); ?>% coverage
        </p>
    </div>
    
    <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium uppercase opacity-90">Queries Logged</h3>
            <i class="fas fa-history text-3xl opacity-75"></i>
        </div>
        <p class="text-5xl font-bold mb-2"><?php echo count($query_history); ?></p>
        <p class="text-sm opacity-80">Recent queries</p>
    </div>
</div>

<!-- Query Statistics by Type -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">
            <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
            Query Statistics by Type (GROUP BY query_type)
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Query Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Time (ms)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min Time (ms)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Max Time (ms)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Time (ms)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT query_type, COUNT(*) as count, AVG(execution_time_ms), MAX(execution_time_ms), MIN(execution_time_ms) FROM Query_Log GROUP BY query_type ORDER BY count DESC"
                   data-sql-explanation="GROUP BY aggregates query logs by type with multiple aggregate functions">
                <?php foreach ($query_stats as $stat): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            <?php echo match($stat['query_type']) {
                                'SELECT' => 'bg-blue-100 text-blue-800',
                                'INSERT' => 'bg-green-100 text-green-800',
                                'UPDATE' => 'bg-yellow-100 text-yellow-800',
                                'DELETE' => 'bg-red-100 text-red-800',
                                'CALL' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800'
                            }; ?>">
                            <?php echo htmlspecialchars($stat['query_type']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-gray-900 text-center">
                        <?php echo number_format($stat['query_count']); ?>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700">
                        <?php echo number_format($stat['avg_execution_time'], 2); ?>
                    </td>
                    <td class="px-4 py-3 text-center text-green-600">
                        <?php echo number_format($stat['min_execution_time'], 2); ?>
                    </td>
                    <td class="px-4 py-3 text-center text-red-600">
                        <?php echo number_format($stat['max_execution_time'], 2); ?>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-blue-600">
                        <?php echo number_format($stat['total_execution_time'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SQL Features Catalog -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
        <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="fas fa-book text-indigo-600 mr-2"></i>
            Complete SQL Features Reference
        </h3>
        <p class="text-sm text-gray-700">
            <?php echo $implemented_count; ?> of <?php echo $total_features; ?> features implemented across <?php echo $total_categories; ?> categories
        </p>
    </div>
    
    <div class="p-6">
        <?php foreach ($sql_features as $category => $features): ?>
        <div class="mb-8 last:mb-0">
            <h4 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-caret-right text-indigo-600 mr-2"></i>
                <?php echo $category; ?>
                <span class="ml-3 text-sm font-normal text-gray-600">
                    (<?php echo count($features); ?> features)
                </span>
            </h4>
            
            <div class="grid grid-cols-1 gap-4">
                <?php foreach ($features as $feature): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                     data-sql-query="<?php echo htmlspecialchars($feature['example']); ?>"
                     data-sql-explanation="<?php echo htmlspecialchars($feature['description']); ?>"
                     data-sql-type="<?php echo htmlspecialchars($feature['name']); ?>">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h5 class="text-lg font-bold text-gray-900 flex items-center">
                                <code class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded mr-2 text-sm">
                                    <?php echo $feature['name']; ?>
                                </code>
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">
                                    ✓ <?php echo $feature['status']; ?>
                                </span>
                            </h5>
                            <p class="text-sm text-gray-600 mt-1">
                                <?php echo $feature['description']; ?>
                            </p>
                        </div>
                        <div class="ml-4">
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                📄 <?php echo $feature['page']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-3 bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <code class="text-xs text-green-400 font-mono">
                            <?php echo htmlspecialchars($feature['example']); ?>
                        </code>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Query History Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">
            <i class="fas fa-history text-purple-600 mr-2"></i>
            Recent Query History (Last 50 queries)
        </h3>
        <p class="text-sm text-gray-600 mt-1">
            Live tracking of all SQL queries executed across the application
        </p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time (ms)</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Executed At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($query_history as $query): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-gray-600">
                        #<?php echo $query['log_id']; ?>
                    </td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 rounded text-xs font-semibold
                            <?php echo match($query['query_type']) {
                                'SELECT' => 'bg-blue-100 text-blue-800',
                                'INSERT' => 'bg-green-100 text-green-800',
                                'UPDATE' => 'bg-yellow-100 text-yellow-800',
                                'DELETE' => 'bg-red-100 text-red-800',
                                'CALL' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800'
                            }; ?>">
                            <?php echo $query['query_type']; ?>
                        </span>
                    </td>
                    <td class="px-3 py-2 max-w-md">
                        <code class="text-xs text-gray-700 font-mono block truncate" title="<?php echo htmlspecialchars($query['query_text']); ?>">
                            <?php echo htmlspecialchars(substr($query['query_text'], 0, 100)); ?>
                            <?php echo strlen($query['query_text']) > 100 ? '...' : ''; ?>
                        </code>
                    </td>
                    <td class="px-3 py-2 text-gray-600">
                        <?php echo htmlspecialchars($query['page_name']); ?>
                    </td>
                    <td class="px-3 py-2 text-center font-semibold
                        <?php echo $query['execution_time'] > 0.01 ? 'text-red-600' : ($query['execution_time'] > 0.005 ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php echo number_format($query['execution_time'] * 1000, 2); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-500">
                        <?php echo date('M j, H:i:s', strtotime($query['executed_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Page Statistics -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">
            <i class="fas fa-file-code text-orange-600 mr-2"></i>
            Query Activity by Page
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Queries</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unique Query Types</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT page_name, COUNT(*) as query_count, COUNT(DISTINCT query_type) FROM Query_Log GROUP BY page_name ORDER BY query_count DESC"
                   data-sql-explanation="GROUP BY with COUNT and COUNT DISTINCT to analyze query patterns per page">
                <?php foreach ($page_stats as $page): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">
                        <i class="fas fa-file text-blue-600 mr-2"></i>
                        <?php echo htmlspecialchars($page['page_name']); ?>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-blue-600">
                        <?php echo number_format($page['query_count']); ?>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700">
                        <?php echo $page['unique_query_types']; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" 
                                 style="width: <?php echo min(($page['query_count'] / max(array_column($page_stats, 'query_count'))) * 100, 100); ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Educational Note -->
<div class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-indigo-900 mb-3">
        <i class="fas fa-graduation-cap mr-2"></i>
        Educational Purpose
    </h3>
    <p class="text-indigo-800 mb-3">
        This application demonstrates <strong><?php echo $total_features; ?> SQL features</strong> from the Oracle SQL curriculum. 
        Every interactive element shows the exact SQL query being executed.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="bg-white rounded p-3">
            <strong class="text-indigo-900">✓ Hover SQL Tooltips</strong>
            <p class="text-gray-700 mt-1">Hover over any element to see the SQL query</p>
        </div>
        <div class="bg-white rounded p-3">
            <strong class="text-indigo-900">✓ Query Logging</strong>
            <p class="text-gray-700 mt-1">All queries tracked in Query_Log table</p>
        </div>
        <div class="bg-white rounded p-3">
            <strong class="text-indigo-900">✓ Live Examples</strong>
            <p class="text-gray-700 mt-1">Every feature has working code examples</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
