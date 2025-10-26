<?php
/**
 * Campaigns Management Page
 * Demonstrates: INSERT, UPDATE, DELETE, GROUP BY with HAVING, Complex JOINs, Prepared Statements
 */

$page_title = 'Campaign Management - CF Tracker';
$current_page = 'campaigns';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// Handle form submissions
$message = '';
$message_type = '';

// CREATE Campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        $campaign_id = createCampaign($pdo, [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'goal_amount' => $_POST['goal_amount'],
            'creator_id' => $_POST['creator_id'],
            'category_id' => $_POST['category_id'] ?: null,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status'] ?? 'draft',
            'image_url' => $_POST['image_url'] ?? null
        ]);
        $message = "Campaign created successfully! ID: $campaign_id";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error creating campaign: " . $e->getMessage();
        $message_type = 'error';
    }
}

// UPDATE Campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        updateCampaign($pdo, $_POST['campaign_id'], [
            'campaign_title' => $_POST['title'],
            'description' => $_POST['description'],
            'goal_amount' => $_POST['goal_amount'],
            'category_id' => $_POST['category_id'] ?: null,
            'status' => $_POST['status'],
            'featured' => isset($_POST['featured']) ? 1 : 0
        ]);
        $message = "Campaign updated successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error updating campaign: " . $e->getMessage();
        $message_type = 'error';
    }
}

// DELETE Campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        deleteCampaign($pdo, $_POST['campaign_id']);
        $message = "Campaign deleted successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error deleting campaign: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? null;
$category_filter = $_GET['category'] ?? null;
$search_term = $_GET['search'] ?? '';

// Get campaigns with filters
if (!empty($search_term)) {
    $campaigns = searchCampaigns($pdo, $search_term);
} else {
    $campaigns = getAllCampaigns($pdo, $status_filter, $category_filter);
}

// Get categories for filter and forms
$categories = getAllCategories($pdo);

// Get users for creator dropdown
$users = getAllUsers($pdo);

// Get campaign statistics with GROUP BY and HAVING
$stats_query = "SELECT 
                    c.status,
                    COUNT(*) as campaign_count,
                    SUM(c.goal_amount) as total_goal,
                    SUM(c.current_amount) as total_raised,
                    AVG(c.current_amount / c.goal_amount * 100) as avg_progress
                FROM Campaigns c
                GROUP BY c.status
                HAVING campaign_count > 0
                ORDER BY campaign_count DESC";
$campaign_stats = executeAndLogQuery($pdo, $stats_query, [], 'campaigns.php', 'SELECT')->fetchAll();

// Get top performing categories with GROUP BY HAVING
$category_stats_query = "SELECT 
                            cat.category_id,
                            cat.category_name,
                            cat.icon,
                            COUNT(DISTINCT c.campaign_id) as campaign_count,
                            COALESCE(SUM(c.current_amount), 0) as total_raised,
                            COALESCE(AVG(c.current_amount / c.goal_amount * 100), 0) as avg_success_rate
                         FROM Categories cat
                         LEFT JOIN Campaigns c ON cat.category_id = c.category_id
                         GROUP BY cat.category_id, cat.category_name, cat.icon
                         HAVING campaign_count > 0
                         ORDER BY total_raised DESC
                         LIMIT 5";
$top_categories = executeAndLogQuery($pdo, $category_stats_query, [], 'campaigns.php', 'SELECT')->fetchAll();

// Get most favorited campaigns - Many-to-Many demonstration
$favorited_campaigns_query = "SELECT 
                                c.campaign_id,
                                c.campaign_title,
                                c.goal_amount,
                                c.current_amount,
                                u.username as creator_name,
                                COUNT(cf.user_id) as favorite_count,
                                (SELECT COUNT(*) FROM Donations WHERE campaign_id = c.campaign_id) as donation_count
                              FROM Campaigns c
                              LEFT JOIN Campaign_Favorites cf ON c.campaign_id = cf.campaign_id
                              LEFT JOIN Users u ON c.creator_id = u.user_id
                              GROUP BY c.campaign_id, c.campaign_title, c.goal_amount, c.current_amount, u.username
                              HAVING favorite_count > 0
                              ORDER BY favorite_count DESC
                              LIMIT 5";
$favorited_campaigns = executeAndLogQuery($pdo, $favorited_campaigns_query, [], 'campaigns.php', 'SELECT')->fetchAll();

// Get usernames who favorited each campaign
$campaign_favoriters = [];
foreach ($favorited_campaigns as $fav) {
    $favoriters_query = "SELECT u.username, u.full_name 
                         FROM Campaign_Favorites cf
                         INNER JOIN Users u ON cf.user_id = u.user_id
                         WHERE cf.campaign_id = :campaign_id
                         ORDER BY cf.favorited_at DESC";
    $favoriters = executeAndLogQuery($pdo, $favoriters_query, [':campaign_id' => $fav['campaign_id']], 'campaigns.php', 'SELECT')->fetchAll();
    $campaign_favoriters[$fav['campaign_id']] = $favoriters;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Success/Error Messages -->
<?php if ($message): ?>
<div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
    <div class="flex items-center">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
            <i class="fas fa-bullhorn text-purple-600 mr-2"></i>
            Campaign Management
        </h2>
        <p class="text-gray-600">Create, update, and manage crowdfunding campaigns</p>
    </div>
    <button onclick="openCreateModal()" 
            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
            data-sql-query="INSERT INTO Campaigns (campaign_title, description, goal_amount, creator_id, category_id, start_date, end_date, status) VALUES (:title, :description, :goal_amount, :creator_id, :category_id, :start_date, :end_date, :status)"
            data-sql-explanation="Inserts a new campaign with all required fields and foreign key relationships"
            data-sql-type="INSERT">
        <i class="fas fa-plus mr-2"></i>Create Campaign
    </button>
</div>


<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <?php foreach ($campaign_stats as $stat): ?>
    <div class="bg-white rounded-lg shadow-md p-6 card"
         data-sql-query="SELECT status, COUNT(*) as count, SUM(goal_amount), AVG(progress) FROM Campaigns GROUP BY status HAVING count > 0"
         data-sql-explanation="GROUP BY aggregates campaigns by status, HAVING filters results">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium"><?php echo ucfirst($stat['status']); ?></p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stat['campaign_count']; ?></h3>
                <p class="text-sm text-gray-600 mt-1">
                    <?php echo number_format($stat['avg_progress'], 1); ?>% avg progress
                </p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Most Favorited Campaigns (Campaign_Favorites M:N Relationship) -->
<?php if (!empty($favorited_campaigns)): ?>
<div class="bg-white rounded-lg shadow-md p-6 mb-8" style="overflow: visible;">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-900 flex items-center"
            data-sql-query="SELECT c.campaign_title, COUNT(cf.user_id) as favorite_count FROM Campaigns c LEFT JOIN Campaign_Favorites cf ON c.campaign_id = cf.campaign_id GROUP BY c.campaign_id ORDER BY favorite_count DESC"
            data-sql-explanation="Many-to-Many: Campaign_Favorites bridge table demonstrates M:N relationship between Users and Campaigns"
            data-sql-type="SELECT">
            <i class="fas fa-heart text-red-500 mr-3"></i>
            Most Favorited Campaigns
        </h2>
        <span class="text-sm text-gray-500">
            <i class="fas fa-database mr-1"></i>Campaign_Favorites (M:N Bridge Table)
        </span>
    </div>
    
    <div class="overflow-x-auto" style="overflow: visible;">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        data-sql-query="SELECT campaign_title FROM Campaigns"
                        data-sql-explanation="Campaign title from Campaigns table">
                        Campaign
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        data-sql-query="SELECT username FROM Users u INNER JOIN Campaigns c ON u.user_id = c.creator_id"
                        data-sql-explanation="Creator name via INNER JOIN with Users table">
                        Creator
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        data-sql-query="SELECT COUNT(cf.user_id) as favorite_count FROM Campaign_Favorites cf GROUP BY cf.campaign_id"
                        data-sql-explanation="COUNT aggregate with GROUP BY to count favorites per campaign">
                        <i class="fas fa-heart text-red-500 mr-1"></i>Favorites
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        data-sql-query="SELECT COUNT(*) FROM Donations WHERE campaign_id = :campaign_id"
                        data-sql-explanation="Subquery to count donations per campaign">
                        Donations
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        data-sql-query="SELECT goal_amount, current_amount FROM Campaigns"
                        data-sql-explanation="Numeric fields for funding calculation">
                        Progress
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($favorited_campaigns as $fav): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">
                            <?php echo htmlspecialchars($fav['campaign_title']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">
                            <?php echo htmlspecialchars($fav['creator_name']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" style="overflow: visible;">
                        <?php 
                        $favoriters = $campaign_favoriters[$fav['campaign_id']] ?? [];
                        $favoriters_list = array_map(function($f) {
                            return htmlspecialchars($f['full_name'] ? $f['full_name'] . ' (' . $f['username'] . ')' : $f['username']);
                        }, $favoriters);
                        $tooltip_content = !empty($favoriters_list) ? implode('<br>', $favoriters_list) : 'No users yet';
                        ?>
                        <div class="relative inline-block favorites-tooltip-container" style="overflow: visible;">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
                                  style="cursor: help;">
                                <i class="fas fa-heart mr-1"></i>
                                <?php echo $fav['favorite_count']; ?> users
                            </span>
                            <div class="favorites-tooltip hidden absolute bg-gray-900 text-white text-sm rounded-lg shadow-lg p-3" 
                                 style="bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 8px; min-width: 250px; max-width: 400px; white-space: normal; z-index: 9999;">
                                <div class="font-semibold mb-2 text-yellow-300">
                                    <i class="fas fa-users mr-1"></i> Favorited by:
                                </div>
                                <div class="text-left">
                                    <?php echo $tooltip_content; ?>
                                </div>
                                <div class="absolute" style="top: 100%; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-top: 6px solid #1f2937;"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-donate mr-1"></i>
                            <?php echo $fav['donation_count']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $progress = $fav['goal_amount'] > 0 ? ($fav['current_amount'] / $fav['goal_amount']) * 100 : 0;
                        $progress_class = $progress >= 100 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';
                        ?>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $progress_class; ?>">
                                <?php echo number_format($progress, 1); ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-purple-600 mt-1 mr-3"></i>
            <div class="text-sm text-purple-800">
                <strong>Many-to-Many Relationship:</strong> The <code class="bg-purple-200 px-2 py-1 rounded">Campaign_Favorites</code> table is a bridge table 
                demonstrating M:N relationships. It connects Users to Campaigns with a composite primary key 
                <code class="bg-purple-200 px-2 py-1 rounded">(user_id, campaign_id)</code>. Each user can favorite many campaigns, 
                and each campaign can be favorited by many users.
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                    data-sql-query="SELECT * FROM Campaigns WHERE status = :status"
                    data-sql-explanation="Filters campaigns by status using WHERE clause">
                <option value="">All Statuses</option>
                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select name="category" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                    data-sql-query="SELECT * FROM Campaigns WHERE category_id = :category_id"
                    data-sql-explanation="Filters by category using foreign key relationship">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Search campaigns..."
                   class="w-full border border-gray-300 rounded-lg px-4 py-2"
                   data-sql-query="SELECT * FROM Campaigns WHERE MATCH(campaign_title, description) AGAINST(:search)"
                   data-sql-explanation="Full-text search using MATCH AGAINST for efficient searching">
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </div>
    </form>
</div>

<!-- Top Categories -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
        <i class="fas fa-trophy text-yellow-500 mr-2"></i>
        Top Performing Categories
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <?php foreach ($top_categories as $cat): ?>
        <div class="border border-gray-200 rounded-lg p-4 text-center hover:border-blue-300 transition"
             data-sql-query="SELECT category_id, COUNT(*) as count, SUM(current_amount) as total FROM Campaigns GROUP BY category_id HAVING count > 0 ORDER BY total DESC"
             data-sql-explanation="GROUP BY category with HAVING to filter, ORDER BY to rank by total raised">
            <i class="fas <?php echo htmlspecialchars($cat['icon']); ?> text-3xl text-blue-600 mb-2"></i>
            <h4 class="font-semibold text-gray-900 text-sm mb-1"><?php echo htmlspecialchars($cat['category_name']); ?></h4>
            <p class="text-xs text-gray-600"><?php echo $cat['campaign_count']; ?> campaigns</p>
            <p class="text-sm font-bold text-green-600">$<?php echo number_format($cat['total_raised'], 0); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Campaigns Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">
            <i class="fas fa-list mr-2"></i>
            All Campaigns (<?php echo count($campaigns); ?>)
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Goal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Raised</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No campaigns found. Create one to get started!</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($campaign['campaign_title']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: <?php echo $campaign['campaign_id']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo htmlspecialchars($campaign['creator_username'] ?? 'Unknown'); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php if (!empty($campaign['category_name'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($campaign['category_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            $<?php echo number_format($campaign['goal_amount'], 2); ?>
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600">
                            $<?php echo number_format($campaign['current_amount'], 2); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                            $progress = ($campaign['goal_amount'] > 0) 
                                ? ($campaign['current_amount'] / $campaign['goal_amount']) * 100 
                                : 0;
                            ?>
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min($progress, 100); ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo number_format($progress, 1); ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                echo match($campaign['status']) {
                                    'active' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?php echo ucfirst($campaign['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <button onclick="viewCampaign(<?php echo $campaign['campaign_id']; ?>)"
                                    class="text-green-600 hover:text-green-900 mr-3"
                                    title="View campaign details and comments">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($campaign)); ?>)"
                                    class="text-blue-600 hover:text-blue-900 mr-3"
                                    data-sql-query="UPDATE Campaigns SET campaign_title = :title, status = :status WHERE campaign_id = :id"
                                    data-sql-explanation="Updates campaign fields using prepared statement with WHERE clause">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete(<?php echo $campaign['campaign_id']; ?>, '<?php echo htmlspecialchars($campaign['campaign_title']); ?>')"
                                    class="text-red-600 hover:text-red-900"
                                    data-sql-query="DELETE FROM Campaigns WHERE campaign_id = :id"
                                    data-sql-explanation="Deletes campaign and CASCADE deletes all related donations, comments, updates">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Campaign Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900">Create New Campaign</h3>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="create">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Title *</label>
                    <input type="text" name="title" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" required rows="4"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Goal Amount ($) *</label>
                    <input type="number" name="goal_amount" step="0.01" min="1" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Creator *</label>
                    <select name="creator_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['username']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
                    <input type="date" name="start_date" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date *</label>
                    <input type="date" name="end_date" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                    <input type="text" name="image_url"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2"
                           placeholder="https://example.com/image.jpg">
                </div>
            </div>
            
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeCreateModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Create Campaign
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Campaign Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900">Edit Campaign</h3>
        </div>
        <form method="POST" class="p-6" id="editForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="campaign_id" id="edit_campaign_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Title *</label>
                    <input type="text" name="title" id="edit_title" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" id="edit_description" required rows="4"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Goal Amount ($) *</label>
                    <input type="number" name="goal_amount" id="edit_goal_amount" step="0.01" min="1" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" id="edit_category_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" id="edit_status" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="featured" id="edit_featured" class="mr-2">
                        <span class="text-sm font-medium text-gray-700">Featured Campaign</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeEditModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Campaign
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-red-100 rounded-full p-3 mr-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Confirm Deletion</h3>
                    <p class="text-sm text-gray-600">This action cannot be undone</p>
                </div>
            </div>
            <p class="text-gray-700 mb-6">
                Are you sure you want to delete campaign <strong id="delete_campaign_name"></strong>?
                This will also delete all related donations, comments, and updates.
            </p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="campaign_id" id="delete_campaign_id">
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeDeleteModal()"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        <i class="fas fa-trash mr-2"></i>Delete Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Campaign Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white mb-10">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-eye text-green-600 mr-2"></i>
                Campaign Details
            </h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div id="viewModalContent">
            <!-- Content will be loaded dynamically -->
            <div class="flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            </div>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(campaign) {
    document.getElementById('edit_campaign_id').value = campaign.campaign_id;
    document.getElementById('edit_title').value = campaign.campaign_title;
    document.getElementById('edit_description').value = campaign.description;
    document.getElementById('edit_goal_amount').value = campaign.goal_amount;
    document.getElementById('edit_category_id').value = campaign.category_id || '';
    document.getElementById('edit_status').value = campaign.status;
    document.getElementById('edit_featured').checked = campaign.featured == 1;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(campaignId, campaignName) {
    document.getElementById('delete_campaign_id').value = campaignId;
    document.getElementById('delete_campaign_name').textContent = campaignName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function viewCampaign(campaignId) {
    document.getElementById('viewModal').classList.remove('hidden');
    
    // Fetch campaign details with comments via AJAX
    fetch(`get_campaign_details.php?campaign_id=${campaignId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('viewModalContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('viewModalContent').innerHTML = 
                '<div class="text-red-600 p-4">Error loading campaign details.</div>';
        });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.id === 'createModal') closeCreateModal();
    if (event.target.id === 'editModal') closeEditModal();
    if (event.target.id === 'deleteModal') closeDeleteModal();
    if (event.target.id === 'viewModal') closeViewModal();
}

// Favorites tooltip functionality
document.addEventListener('DOMContentLoaded', function() {
    const tooltipContainers = document.querySelectorAll('.favorites-tooltip-container');
    
    tooltipContainers.forEach(container => {
        const trigger = container.querySelector('span');
        const tooltip = container.querySelector('.favorites-tooltip');
        
        if (trigger && tooltip) {
            trigger.addEventListener('mouseenter', function() {
                tooltip.classList.remove('hidden');
            });
            
            trigger.addEventListener('mouseleave', function() {
                setTimeout(() => {
                    if (!tooltip.matches(':hover')) {
                        tooltip.classList.add('hidden');
                    }
                }, 100);
            });
            
            tooltip.addEventListener('mouseleave', function() {
                tooltip.classList.add('hidden');
            });
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
