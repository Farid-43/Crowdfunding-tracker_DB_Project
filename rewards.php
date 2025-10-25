<?php
/**
 * Rewards Management Page
 * Demonstrates: Rewards system, Many-to-Many relationships, Trigger automation
 */

$page_title = 'Rewards - CF Tracker';
$current_page = 'rewards';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// Get all campaigns with their rewards
$campaigns_with_rewards = "SELECT 
    c.campaign_id,
    c.campaign_title,
    c.goal_amount,
    c.current_amount,
    c.status,
    COUNT(DISTINCT r.reward_id) as total_rewards,
    COUNT(DISTINCT CASE WHEN r.is_available = 1 THEN r.reward_id END) as available_rewards,
    MIN(r.min_amount) as lowest_reward,
    MAX(r.min_amount) as highest_reward
FROM Campaigns c
LEFT JOIN Rewards r ON c.campaign_id = r.campaign_id
WHERE c.status IN ('active', 'completed')
GROUP BY c.campaign_id
HAVING total_rewards > 0
ORDER BY c.status = 'active' DESC, c.created_at DESC";

$campaigns = executeAndLogQuery($pdo, $campaigns_with_rewards, [], 'rewards.php', 'SELECT')->fetchAll();

// Get all rewards with campaign info
$all_rewards_query = "SELECT 
    r.reward_id,
    r.campaign_id,
    c.campaign_title,
    c.status as campaign_status,
    r.title as reward_title,
    r.description,
    r.min_amount,
    r.max_backers,
    r.current_backers,
    r.is_available,
    r.estimated_delivery,
    CASE 
        WHEN r.max_backers IS NULL THEN 'Unlimited'
        ELSE CONCAT(r.current_backers, ' / ', r.max_backers)
    END as backer_status,
    CASE 
        WHEN r.max_backers IS NULL THEN 0
        ELSE ROUND((r.current_backers / r.max_backers) * 100, 1)
    END as capacity_percent
FROM Rewards r
INNER JOIN Campaigns c ON r.campaign_id = c.campaign_id
WHERE c.status IN ('active', 'completed')
ORDER BY c.campaign_id, r.min_amount ASC";

$all_rewards = executeAndLogQuery($pdo, $all_rewards_query, [], 'rewards.php', 'SELECT')->fetchAll();

// Get reward claims
$reward_claims_query = "SELECT 
    dr.donor_id,
    dr.reward_id,
    dr.donation_id,
    dr.claimed_at,
    dr.fulfillment_status,
    u.username,
    u.full_name,
    r.title as reward_title,
    c.campaign_title,
    d.amount as donation_amount,
    r.min_amount as required_amount
FROM Donor_Rewards dr
INNER JOIN Users u ON dr.donor_id = u.user_id
INNER JOIN Rewards r ON dr.reward_id = r.reward_id
INNER JOIN Campaigns c ON r.campaign_id = c.campaign_id
INNER JOIN Donations d ON dr.donation_id = d.donation_id
ORDER BY dr.claimed_at DESC
LIMIT 20";

$reward_claims = executeAndLogQuery($pdo, $reward_claims_query, [], 'rewards.php', 'SELECT')->fetchAll();

// Reward statistics
$reward_stats_query = "SELECT 
    COUNT(DISTINCT r.reward_id) as total_rewards,
    COUNT(DISTINCT CASE WHEN r.is_available = 1 THEN r.reward_id END) as available_rewards,
    COUNT(DISTINCT CASE WHEN r.is_available = 0 THEN r.reward_id END) as full_rewards,
    SUM(r.current_backers) as total_backers,
    COUNT(DISTINCT dr.donor_id) as unique_donors,
    AVG(r.min_amount) as avg_min_amount,
    MIN(r.min_amount) as lowest_min_amount,
    MAX(r.min_amount) as highest_min_amount
FROM Rewards r
LEFT JOIN Donor_Rewards dr ON r.reward_id = dr.reward_id";

$stats = executeAndLogQuery($pdo, $reward_stats_query, [], 'rewards.php', 'SELECT')->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-gift text-pink-600 mr-4"></i>
                Rewards Management
            </h1>
            <p class="text-gray-600 mt-2">Campaign reward tiers and backer management</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-pink-500 sql-hover cursor-pointer"
             data-sql-query="SELECT COUNT(DISTINCT r.reward_id) as total_rewards FROM Rewards r LEFT JOIN Donor_Rewards dr ON r.reward_id = dr.reward_id"
             data-sql-type="SELECT"
             data-sql-explanation="Counts total number of rewards across all campaigns using COUNT DISTINCT">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm uppercase">Total Rewards</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_rewards']; ?></p>
                </div>
                <i class="fas fa-gift text-pink-500 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500 sql-hover cursor-pointer"
             data-sql-query="SELECT COUNT(DISTINCT CASE WHEN r.is_available = 1 THEN r.reward_id END) as available_rewards FROM Rewards r"
             data-sql-type="SELECT"
             data-sql-explanation="Uses conditional COUNT with CASE to count only available rewards">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm uppercase">Available</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['available_rewards']; ?></p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500 sql-hover cursor-pointer"
             data-sql-query="SELECT SUM(r.current_backers) as total_backers FROM Rewards r LEFT JOIN Donor_Rewards dr ON r.reward_id = dr.reward_id"
             data-sql-type="SELECT"
             data-sql-explanation="Aggregates total backers across all rewards using SUM function">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm uppercase">Total Backers</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_backers']; ?></p>
                </div>
                <i class="fas fa-users text-blue-500 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500 sql-hover cursor-pointer"
             data-sql-query="SELECT AVG(r.min_amount) as avg_min_amount FROM Rewards r"
             data-sql-type="SELECT"
             data-sql-explanation="Calculates average minimum donation amount using AVG aggregate function">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm uppercase">Avg Min Amount</p>
                    <p class="text-3xl font-bold text-gray-800">$<?php echo number_format($stats['avg_min_amount'], 0); ?></p>
                </div>
                <i class="fas fa-dollar-sign text-yellow-500 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Campaigns with Rewards -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center sql-hover cursor-pointer"
            data-sql-query="SELECT c.campaign_id, c.campaign_title, COUNT(DISTINCT r.reward_id) as total_rewards, MIN(r.min_amount) as lowest_reward, MAX(r.min_amount) as highest_reward FROM Campaigns c LEFT JOIN Rewards r ON c.campaign_id = r.campaign_id WHERE c.status IN ('active', 'completed') GROUP BY c.campaign_id HAVING total_rewards > 0"
            data-sql-type="SELECT"
            data-sql-explanation="Complex query with LEFT JOIN, WHERE, GROUP BY, HAVING clause, and aggregate functions (COUNT, MIN, MAX)">
            <i class="fas fa-trophy text-yellow-500 mr-3"></i>
            Campaigns with Rewards
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($campaigns as $campaign): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($campaign['campaign_title']); ?></h3>
                    <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $campaign['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                        <?php echo strtoupper($campaign['status']); ?>
                    </span>
                </div>
                
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span><i class="fas fa-gift text-pink-500 mr-2"></i>Total Rewards:</span>
                        <strong><?php echo $campaign['total_rewards']; ?></strong>
                    </div>
                    <div class="flex justify-between">
                        <span><i class="fas fa-check-circle text-green-500 mr-2"></i>Available:</span>
                        <strong><?php echo $campaign['available_rewards']; ?></strong>
                    </div>
                    <div class="flex justify-between">
                        <span><i class="fas fa-arrow-down text-blue-500 mr-2"></i>From:</span>
                        <strong>$<?php echo number_format($campaign['lowest_reward'], 2); ?></strong>
                    </div>
                    <div class="flex justify-between">
                        <span><i class="fas fa-arrow-up text-purple-500 mr-2"></i>Up to:</span>
                        <strong>$<?php echo number_format($campaign['highest_reward'], 2); ?></strong>
                    </div>
                </div>
                
                <button onclick="showCampaignRewards(<?php echo $campaign['campaign_id']; ?>)" 
                        class="mt-4 w-full bg-pink-600 text-white py-2 rounded hover:bg-pink-700 transition-colors sql-hover"
                        data-sql-query="SELECT * FROM Rewards WHERE campaign_id = <?php echo $campaign['campaign_id']; ?> ORDER BY min_amount ASC"
                        data-sql-type="SELECT"
                        data-sql-explanation="Retrieves all rewards for a specific campaign ordered by minimum donation amount">
                    <i class="fas fa-eye mr-2"></i>View Rewards
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- All Rewards Table -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center sql-hover cursor-pointer"
            data-sql-query="SELECT r.*, c.campaign_title, c.status, CASE WHEN r.max_backers IS NULL THEN 'Unlimited' ELSE CONCAT(r.current_backers, ' / ', r.max_backers) END as backer_status FROM Rewards r INNER JOIN Campaigns c ON r.campaign_id = c.campaign_id WHERE c.status IN ('active', 'completed') ORDER BY c.campaign_id, r.min_amount ASC"
            data-sql-type="SELECT"
            data-sql-explanation="INNER JOIN between Rewards and Campaigns with CASE statement for conditional display and ORDER BY multiple columns">
            <i class="fas fa-list text-blue-600 mr-3"></i>
            All Rewards
        </h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT campaign_title FROM Campaigns WHERE campaign_id IN (SELECT DISTINCT campaign_id FROM Rewards)"
                            data-sql-type="SELECT"
                            data-sql-explanation="Subquery to get campaigns that have rewards">Campaign</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT title, description FROM Rewards ORDER BY min_amount"
                            data-sql-type="SELECT"
                            data-sql-explanation="Simple SELECT of reward title and description">Reward</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT min_amount FROM Rewards ORDER BY min_amount ASC"
                            data-sql-type="SELECT"
                            data-sql-explanation="ORDER BY min_amount to show rewards from cheapest to most expensive">Min Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT current_backers, max_backers FROM Rewards"
                            data-sql-type="SELECT"
                            data-sql-explanation="Shows current and maximum backers for capacity tracking">Backers</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT ROUND((current_backers / max_backers) * 100, 1) as capacity_percent FROM Rewards WHERE max_backers IS NOT NULL"
                            data-sql-type="SELECT"
                            data-sql-explanation="Calculates percentage capacity using division and ROUND function">Capacity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT estimated_delivery FROM Rewards WHERE estimated_delivery IS NOT NULL"
                            data-sql-type="SELECT"
                            data-sql-explanation="Filters NULL values to show only rewards with delivery dates">Delivery</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT is_available, CASE WHEN is_available = 1 THEN 'Available' ELSE 'Full' END as status FROM Rewards"
                            data-sql-type="SELECT"
                            data-sql-explanation="CASE statement to convert boolean to readable status text">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($all_rewards as $reward): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($reward['campaign_title']); ?></td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reward['reward_title']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($reward['description'], 0, 60)) . '...'; ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-pink-600">$<?php echo number_format($reward['min_amount'], 2); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo $reward['backer_status']; ?></td>
                        <td class="px-4 py-3">
                            <?php if ($reward['max_backers']): ?>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $reward['capacity_percent']; ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo $reward['capacity_percent']; ?>%</span>
                            <?php else: ?>
                            <span class="text-xs text-gray-500">Unlimited</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <?php echo $reward['estimated_delivery'] ? date('M Y', strtotime($reward['estimated_delivery'])) : 'TBD'; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($reward['is_available']): ?>
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                <i class="fas fa-check-circle"></i> Available
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">
                                <i class="fas fa-times-circle"></i> Full
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Reward Claims -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center sql-hover cursor-pointer"
            data-sql-query="SELECT dr.*, u.username, r.title as reward_title, c.campaign_title, d.amount FROM Donor_Rewards dr INNER JOIN Users u ON dr.donor_id = u.user_id INNER JOIN Rewards r ON dr.reward_id = r.reward_id INNER JOIN Campaigns c ON r.campaign_id = c.campaign_id INNER JOIN Donations d ON dr.donation_id = d.donation_id ORDER BY dr.claimed_at DESC LIMIT 20"
            data-sql-type="SELECT"
            data-sql-explanation="Complex 4-table INNER JOIN demonstrating many-to-many relationship tracking with ORDER BY and LIMIT">
            <i class="fas fa-hand-holding-heart text-purple-600 mr-3"></i>
            Recent Reward Claims
        </h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT u.username, u.full_name FROM Users u INNER JOIN Donor_Rewards dr ON u.user_id = dr.donor_id"
                            data-sql-type="SELECT"
                            data-sql-explanation="JOIN to get donor information from Users table">Donor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT r.title FROM Rewards r INNER JOIN Donor_Rewards dr ON r.reward_id = dr.reward_id"
                            data-sql-type="SELECT"
                            data-sql-explanation="JOIN to get reward details">Reward</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT c.campaign_title FROM Campaigns c INNER JOIN Rewards r ON c.campaign_id = r.campaign_id INNER JOIN Donor_Rewards dr ON r.reward_id = dr.reward_id"
                            data-sql-type="SELECT"
                            data-sql-explanation="Multi-table JOIN to link reward claims back to campaigns">Campaign</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT d.amount FROM Donations d INNER JOIN Donor_Rewards dr ON d.donation_id = dr.donation_id WHERE d.amount >= (SELECT min_amount FROM Rewards WHERE reward_id = dr.reward_id)"
                            data-sql-type="SELECT"
                            data-sql-explanation="Correlated subquery to validate donation meets reward minimum">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT claimed_at FROM Donor_Rewards ORDER BY claimed_at DESC"
                            data-sql-type="SELECT"
                            data-sql-explanation="Timestamp ordering to show most recent claims first">Claimed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sql-hover cursor-pointer"
                            data-sql-query="SELECT fulfillment_status FROM Donor_Rewards WHERE fulfillment_status IN ('pending', 'processing', 'shipped', 'delivered')"
                            data-sql-type="SELECT"
                            data-sql-explanation="ENUM field demonstrating status tracking with IN clause">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reward_claims as $claim): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($claim['username']); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($claim['reward_title']); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($claim['campaign_title']); ?></td>
                        <td class="px-4 py-3 text-sm font-bold text-green-600">$<?php echo number_format($claim['donation_amount'], 2); ?></td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('M d, Y', strtotime($claim['claimed_at'])); ?></td>
                        <td class="px-4 py-3">
                            <?php
                            $status_colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'processing' => 'bg-blue-100 text-blue-800',
                                'shipped' => 'bg-purple-100 text-purple-800',
                                'delivered' => 'bg-green-100 text-green-800'
                            ];
                            $color = $status_colors[$claim['fulfillment_status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $color; ?>">
                                <?php echo ucfirst($claim['fulfillment_status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SQL Features Panel -->
    <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
        <h3 class="text-xl font-bold text-blue-900 mb-4 flex items-center">
            <i class="fas fa-database mr-3"></i>
            SQL Features Demonstrated on This Page
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <h4 class="font-bold text-blue-800 mb-2">Advanced JOINs</h4>
                <ul class="list-disc list-inside text-blue-700 space-y-1">
                    <li>Multiple LEFT JOINs (Rewards, Categories)</li>
                    <li>INNER JOINs for required data</li>
                    <li>4-table joins (Donor_Rewards)</li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold text-blue-800 mb-2">Aggregations</h4>
                <ul class="list-disc list-inside text-blue-700 space-y-1">
                    <li>COUNT DISTINCT for unique values</li>
                    <li>Conditional COUNT with CASE</li>
                    <li>MIN/MAX for price ranges</li>
                    <li>AVG for average calculations</li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold text-blue-800 mb-2">CASE Statements</h4>
                <ul class="list-disc list-inside text-blue-700 space-y-1">
                    <li>Conditional display logic</li>
                    <li>CONCAT with CASE for status</li>
                    <li>Percentage calculations</li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold text-blue-800 mb-2">Many-to-Many</h4>
                <ul class="list-disc list-inside text-blue-700 space-y-1">
                    <li>Donor_Rewards bridge table</li>
                    <li>Composite primary keys</li>
                    <li>Trigger automation (backer counts)</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<script src="/Crowdfunding-tracker_DB_Project/assets/js/sql-tooltip.js"></script>

<script>
function showCampaignRewards(campaignId) {
    alert('Campaign ID: ' + campaignId + '\n\nIn a full implementation, this would show a modal with detailed rewards for this campaign.');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
