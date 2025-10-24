<?php
/**
 * Donations Tracking Page
 * Demonstrates: Transactions, CASE statements, UNION, Triggers, Window Functions
 */

$page_title = 'Donations - CF Tracker';
$current_page = 'donations';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// Handle donation processing
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'donate') {
    try {
        $result = processDonation(
            $pdo,
            $_POST['campaign_id'],
            $_POST['donor_id'],
            $_POST['amount'],
            $_POST['payment_method'],
            $_POST['message'] ?? '',
            isset($_POST['is_anonymous']) ? 1 : 0
        );
        
        if ($result['success']) {
            $message = "Donation processed successfully! Donation ID: " . $result['donation_id'];
            $message_type = 'success';
        } else {
            $message = "Donation failed: " . $result['message'];
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = "Error processing donation: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get all donations with window functions for running totals
$donations_with_running_total = "SELECT 
    d.donation_id,
    d.campaign_id,
    d.amount,
    d.donation_date,
    d.payment_method,
    d.status,
    d.is_anonymous,
    d.message,
    c.campaign_title,
    CASE 
        WHEN d.is_anonymous = 1 THEN 'Anonymous Donor'
        ELSE u.full_name
    END as donor_name,
    u.username as donor_username,
    -- Window functions for running totals
    SUM(d.amount) OVER (
        PARTITION BY d.campaign_id 
        ORDER BY d.donation_date
        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
    ) as running_total,
    ROW_NUMBER() OVER (
        PARTITION BY d.campaign_id 
        ORDER BY d.amount DESC
    ) as donation_rank,
    AVG(d.amount) OVER (
        PARTITION BY d.campaign_id
    ) as campaign_avg_donation
FROM Donations d
INNER JOIN Campaigns c ON d.campaign_id = c.campaign_id
INNER JOIN Users u ON d.donor_id = u.user_id
WHERE d.status = 'completed'
ORDER BY d.donation_date DESC
LIMIT 50";

$donations = executeAndLogQuery($pdo, $donations_with_running_total, [], 'donations.php', 'SELECT')->fetchAll();

// UNION example: Get combined view of top donors and campaigners
$union_query = "SELECT 
    user_id,
    full_name,
    'Top Donor' as user_type,
    COALESCE(SUM(d.amount), 0) as total_amount,
    COUNT(d.donation_id) as transaction_count
FROM Users u
LEFT JOIN Donations d ON u.user_id = d.donor_id AND d.status = 'completed'
WHERE u.user_role = 'donor'
GROUP BY u.user_id, u.full_name
HAVING total_amount > 0

UNION

SELECT 
    u.user_id,
    u.full_name,
    'Campaign Creator' as user_type,
    COALESCE(SUM(c.current_amount), 0) as total_amount,
    COUNT(c.campaign_id) as transaction_count
FROM Users u
LEFT JOIN Campaigns c ON u.user_id = c.creator_id
WHERE u.user_role = 'campaigner'
GROUP BY u.user_id, u.full_name
HAVING total_amount > 0

ORDER BY total_amount DESC
LIMIT 10";

$union_results = executeAndLogQuery($pdo, $union_query, [], 'donations.php', 'SELECT')->fetchAll();

// Get donation statistics by payment method using CASE
$payment_stats_query = "SELECT 
    payment_method,
    COUNT(*) as donation_count,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded_count
FROM Donations
GROUP BY payment_method
ORDER BY total_amount DESC";

$payment_stats = executeAndLogQuery($pdo, $payment_stats_query, [], 'donations.php', 'SELECT')->fetchAll();

// Get active campaigns for donation form
$active_campaigns = getAllCampaigns($pdo, 'active');

// Get active donors
$active_donors_query = "SELECT user_id, username, full_name, account_balance 
                        FROM Users 
                        WHERE user_role = 'donor' AND is_active = TRUE 
                        ORDER BY full_name";
$active_donors = executeAndLogQuery($pdo, $active_donors_query, [], 'donations.php', 'SELECT')->fetchAll();

// Get recent donation audit log (trigger demonstration)
$audit_log_query = "SELECT * FROM Donation_Audit_Log ORDER BY performed_at DESC LIMIT 10";
$audit_logs = executeAndLogQuery($pdo, $audit_log_query, [], 'donations.php', 'SELECT')->fetchAll();

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
            <i class="fas fa-hand-holding-usd text-green-600 mr-2"></i>
            Donation Tracking
        </h2>
        <p class="text-gray-600">Process and monitor campaign donations with transaction safety</p>
    </div>
    <button onclick="openDonateModal()" 
            class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition"
            data-sql-query="CALL Process_Donation(:campaign_id, :donor_id, :amount, :payment_method, :message, :is_anonymous, @donation_id, @success, @message_out)"
            data-sql-explanation="Stored procedure with transaction handling (BEGIN, INSERT, UPDATE, COMMIT/ROLLBACK)"
            data-sql-type="Transaction">
        <i class="fas fa-donate mr-2"></i>Process Donation
    </button>
</div>

<!-- SQL Features Panel -->
<div class="sql-features-panel">
    <h3>
        📋 SQL Features Demonstrated on This Page
        <button onclick="toggleFeaturePanel('donations-features')">
            <i class="fas fa-chevron-down"></i>
        </button>
    </h3>
    <div id="donations-features">
        <div class="sql-feature-item">
            <span class="feature-type">Transaction Control</span>
            <code>START TRANSACTION; INSERT INTO Donations...; UPDATE Campaigns...; COMMIT;</code>
            <span class="feature-desc">ACID-compliant transaction with BEGIN/COMMIT/ROLLBACK for data integrity</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">CASE Statements</span>
            <code>CASE WHEN is_anonymous = 1 THEN 'Anonymous' ELSE full_name END</code>
            <span class="feature-desc">Conditional logic in SELECT for displaying anonymous vs named donors</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Window Functions</span>
            <code>SUM(amount) OVER (PARTITION BY campaign_id ORDER BY donation_date ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)</code>
            <span class="feature-desc">Running totals per campaign using window function with frame specification</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">UNION Operation</span>
            <code>SELECT ... FROM Donors UNION SELECT ... FROM Campaigners ORDER BY total_amount DESC</code>
            <span class="feature-desc">Combines donor and campaigner data into single result set, removing duplicates</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Trigger Execution</span>
            <code>AFTER INSERT ON Donations ... INSERT INTO Donation_Audit_Log</code>
            <span class="feature-desc">Automatic audit logging triggered after donation insertion</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">ROW_NUMBER()</span>
            <code>ROW_NUMBER() OVER (PARTITION BY campaign_id ORDER BY amount DESC)</code>
            <span class="feature-desc">Ranks donations within each campaign by amount</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Aggregate CASE</span>
            <code>SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count</code>
            <span class="feature-desc">Conditional aggregation to count by status using CASE in SUM</span>
        </div>
    </div>
</div>

<!-- Payment Method Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <?php foreach ($payment_stats as $stat): ?>
    <div class="bg-white rounded-lg shadow-md p-6 card"
         data-sql-query="SELECT payment_method, COUNT(*), SUM(amount), SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) FROM Donations GROUP BY payment_method"
         data-sql-explanation="GROUP BY with multiple aggregations and CASE for conditional counting">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-500 uppercase">
                <?php echo str_replace('_', ' ', ucwords($stat['payment_method'])); ?>
            </h3>
            <i class="fas fa-<?php echo match($stat['payment_method']) {
                'credit_card' => 'credit-card',
                'paypal' => 'paypal',
                'bank_transfer' => 'university',
                'crypto' => 'bitcoin',
                default => 'money-bill'
            }; ?> text-2xl text-blue-600"></i>
        </div>
        <p class="text-3xl font-bold text-gray-900 mb-2">
            $<?php echo number_format($stat['total_amount'], 0); ?>
        </p>
        <div class="text-sm text-gray-600 space-y-1">
            <p><span class="text-green-600">✓ <?php echo $stat['completed_count']; ?></span> completed</p>
            <?php if ($stat['pending_count'] > 0): ?>
            <p><span class="text-yellow-600">⏳ <?php echo $stat['pending_count']; ?></span> pending</p>
            <?php endif; ?>
            <?php if ($stat['failed_count'] > 0): ?>
            <p><span class="text-red-600">✗ <?php echo $stat['failed_count']; ?></span> failed</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- UNION Results: Top Contributors -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
        <i class="fas fa-star text-yellow-500 mr-2"></i>
        Top Contributors (Donors UNION Campaigners)
    </h3>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT * FROM Donors UNION SELECT * FROM Campaigners ORDER BY total_amount DESC"
                   data-sql-explanation="UNION combines two SELECT statements (donors and campaigners) into one result">
                <?php foreach ($union_results as $index => $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center">
                        <?php if ($index < 3): ?>
                            <i class="fas fa-medal text-2xl <?php echo $index === 0 ? 'text-yellow-500' : ($index === 1 ? 'text-gray-400' : 'text-orange-600'); ?>"></i>
                        <?php else: ?>
                            <span class="text-gray-600 font-bold"><?php echo $index + 1; ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $user['user_type'] === 'Top Donor' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <?php echo $user['user_type']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-green-600">
                        $<?php echo number_format($user['total_amount'], 2); ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        <?php echo $user['transaction_count']; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Donations with Running Totals -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">
            <i class="fas fa-list mr-2"></i>
            Recent Donations with Running Totals (Window Functions)
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Running Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($donations)): ?>
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No donations found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($donations as $donation): ?>
                    <tr class="hover:bg-gray-50"
                        data-sql-query="SELECT d.*, SUM(amount) OVER (PARTITION BY campaign_id ORDER BY donation_date) as running_total, ROW_NUMBER() OVER (PARTITION BY campaign_id ORDER BY amount DESC) as rank FROM Donations d"
                        data-sql-explanation="Window functions calculate running totals and rankings without GROUP BY">
                        <td class="px-4 py-3 text-sm text-gray-600">
                            #<?php echo $donation['donation_id']; ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center">
                                <?php if ($donation['is_anonymous']): ?>
                                    <i class="fas fa-user-secret text-gray-400 mr-2"></i>
                                    <span class="text-gray-500 italic">Anonymous</span>
                                <?php else: ?>
                                    <i class="fas fa-user text-blue-600 mr-2"></i>
                                    <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($donation['donor_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 max-w-xs truncate">
                            <?php echo htmlspecialchars($donation['campaign_title']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-green-600">
                            $<?php echo number_format($donation['amount'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-blue-600">
                            $<?php echo number_format($donation['running_total'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <?php if ($donation['donation_rank'] <= 3): ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                                    #<?php echo $donation['donation_rank']; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-500">#<?php echo $donation['donation_rank']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            $<?php echo number_format($donation['campaign_avg_donation'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                <?php echo str_replace('_', ' ', $donation['payment_method']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?php echo date('M j, Y', strtotime($donation['donation_date'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Trigger Demonstration: Audit Log -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
        <i class="fas fa-history text-purple-600 mr-2"></i>
        Donation Audit Log (Trigger Demonstration)
    </h3>
    <p class="text-sm text-gray-600 mb-4">
        This log is automatically populated by the <code class="bg-gray-100 px-2 py-1 rounded">trg_donation_after_insert</code> trigger 
        whenever a donation is inserted.
    </p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Audit ID</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Donation ID</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="-- Trigger automatically executes: INSERT INTO Donation_Audit_Log (...) VALUES (NEW.donation_id, NEW.amount, 'INSERT', ...)"
                   data-sql-explanation="AFTER INSERT trigger automatically logs every new donation">
                <?php foreach ($audit_logs as $log): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-gray-600">#<?php echo $log['audit_id']; ?></td>
                    <td class="px-3 py-2 text-gray-900">#<?php echo $log['donation_id'] ?? 'N/A'; ?></td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                            <?php echo $log['action_type']; ?>
                        </span>
                    </td>
                    <td class="px-3 py-2 font-semibold text-green-600">
                        $<?php echo number_format($log['amount'] ?? 0, 2); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-600 max-w-xs truncate">
                        <?php echo htmlspecialchars($log['notes'] ?? ''); ?>
                    </td>
                    <td class="px-3 py-2 text-gray-500">
                        <?php echo date('M j, Y H:i', strtotime($log['performed_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Process Donation Modal -->
<div id="donateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900">Process New Donation</h3>
            <p class="text-sm text-gray-600 mt-1">Transaction will ensure data integrity (COMMIT/ROLLBACK)</p>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="donate">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Campaign *</label>
                    <select name="campaign_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">Select Campaign</option>
                        <?php foreach ($active_campaigns as $campaign): ?>
                            <option value="<?php echo $campaign['campaign_id']; ?>">
                                <?php echo htmlspecialchars($campaign['campaign_title']); ?> 
                                (Goal: $<?php echo number_format($campaign['goal_amount'], 0); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Donor *</label>
                    <select name="donor_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">Select Donor</option>
                        <?php foreach ($active_donors as $donor): ?>
                            <option value="<?php echo $donor['user_id']; ?>">
                                <?php echo htmlspecialchars($donor['full_name']); ?> 
                                (Balance: $<?php echo number_format($donor['account_balance'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="1" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2"
                           placeholder="100.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                    <select name="payment_method" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="credit_card">Credit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="crypto">Cryptocurrency</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message (Optional)</label>
                    <textarea name="message" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2"
                              placeholder="Leave a message for the campaign creator..."></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_anonymous" class="mr-2">
                        <span class="text-sm text-gray-700">Make this donation anonymous</span>
                    </label>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-blue-900 mb-2">
                    <i class="fas fa-shield-alt mr-2"></i>Transaction Safety
                </h4>
                <p class="text-sm text-blue-800">
                    This donation will be processed within a database transaction. If any step fails, 
                    all changes will be rolled back to ensure data integrity.
                </p>
            </div>
            
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeDonateModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-hand-holding-usd mr-2"></i>Process Donation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDonateModal() {
    document.getElementById('donateModal').classList.remove('hidden');
}

function closeDonateModal() {
    document.getElementById('donateModal').classList.add('hidden');
}

window.onclick = function(event) {
    if (event.target.id === 'donateModal') closeDonateModal();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
