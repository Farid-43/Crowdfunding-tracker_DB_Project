<?php
/**
 * User Management Page
 * Demonstrates: CHECK constraints, UNIQUE constraints, Custom Functions, Triggers, Role-based Updates
 */

$page_title = 'Users - CF Tracker';
$current_page = 'users';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

// Handle user operations
$message = '';
$message_type = '';

// CREATE USER (demonstrates INSERT with constraints)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    try {
        $result = createUser(
            $pdo,
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['full_name'],
            $_POST['user_role'],
            $_POST['account_balance'] ?? 0
        );
        
        if ($result) {
            $message = "User created successfully! Username: " . htmlspecialchars($_POST['username']);
            $message_type = 'success';
        } else {
            $message = "Failed to create user. Check UNIQUE/CHECK constraints.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $message = "UNIQUE constraint violation: Username or email already exists!";
        } elseif (strpos($e->getMessage(), 'chk_account_balance') !== false) {
            $message = "CHECK constraint violation: Account balance must be >= 0!";
        } elseif (strpos($e->getMessage(), 'chk_user_role') !== false) {
            $message = "CHECK constraint violation: Invalid user role!";
        } else {
            $message = "Error: " . $e->getMessage();
        }
        $message_type = 'error';
    }
}

// UPDATE USER (demonstrates conditional UPDATE by role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    try {
        $result = updateUser(
            $pdo,
            $_POST['user_id'],
            $_POST['full_name'],
            $_POST['email'],
            $_POST['user_role'],
            $_POST['account_balance'],
            isset($_POST['is_active']) ? 1 : 0
        );
        
        if ($result) {
            $message = "User updated successfully! Changes logged by trigger.";
            $message_type = 'success';
        } else {
            $message = "Failed to update user.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $message = "UNIQUE constraint: Email already exists!";
        } elseif (strpos($e->getMessage(), 'chk_account_balance') !== false) {
            $message = "CHECK constraint: Balance must be non-negative!";
        } else {
            $message = "Error: " . $e->getMessage();
        }
        $message_type = 'error';
    }
}

// DELETE USER (demonstrates CASCADE delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    try {
        $result = deleteUser($pdo, $_POST['user_id']);
        
        if ($result) {
            $message = "User deleted (CASCADE will remove related donations/campaigns). Trigger logged this action.";
            $message_type = 'success';
        } else {
            $message = "Failed to delete user.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Delete error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get all users with custom function demonstration
$users_query = "SELECT 
    u.*,
    -- Custom function: Get donor level
    Get_Donor_Level(u.user_id) as donor_level,
    -- Aggregated statistics
    (SELECT COUNT(*) FROM Campaigns WHERE creator_id = u.user_id) as campaigns_created,
    (SELECT COUNT(*) FROM Donations WHERE donor_id = u.user_id AND status = 'completed') as donations_made,
    (SELECT COALESCE(SUM(amount), 0) FROM Donations WHERE donor_id = u.user_id AND status = 'completed') as total_donated,
    -- CASE for status badge
    CASE 
        WHEN u.is_active = TRUE THEN 'Active'
        ELSE 'Inactive'
    END as account_status,
    -- CASE for account tier
    CASE 
        WHEN u.account_balance >= 10000 THEN 'Premium'
        WHEN u.account_balance >= 5000 THEN 'Gold'
        WHEN u.account_balance >= 1000 THEN 'Silver'
        ELSE 'Basic'
    END as account_tier
FROM Users u
ORDER BY u.created_at DESC";

$users = executeAndLogQuery($pdo, $users_query, [], 'users.php', 'SELECT')->fetchAll();

// Get user audit history (trigger demonstration)
$audit_query = "SELECT 
    ual.*,
    u.username,
    u.full_name
FROM User_Audit_Log ual
LEFT JOIN Users u ON ual.user_id = u.user_id
ORDER BY ual.changed_at DESC
LIMIT 20";

$audit_history = executeAndLogQuery($pdo, $audit_query, [], 'users.php', 'SELECT')->fetchAll();

// Statistics by role (GROUP BY with HAVING)
$role_stats_query = "SELECT 
    user_role,
    COUNT(*) as user_count,
    SUM(account_balance) as total_balance,
    AVG(account_balance) as avg_balance,
    MAX(account_balance) as max_balance,
    SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_count
FROM Users
GROUP BY user_role
HAVING COUNT(*) > 0
ORDER BY user_count DESC";

$role_stats = executeAndLogQuery($pdo, $role_stats_query, [], 'users.php', 'SELECT')->fetchAll();

// Constraint demonstration query
$constraint_demo = "SHOW CREATE TABLE Users";
$constraint_info = executeAndLogQuery($pdo, $constraint_demo, [], 'users.php', 'SHOW')->fetch();

include __DIR__ . '/includes/header.php';
?>

<!-- Success/Error Messages -->
<?php if ($message): ?>
<div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
    <div class="flex items-center">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
        <span><?php echo $message; ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
            <i class="fas fa-users text-blue-600 mr-2"></i>
            User Management
        </h2>
        <p class="text-gray-600">Demonstrating constraints, custom functions, triggers, and role-based operations</p>
    </div>
    <button onclick="openCreateUserModal()" 
            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
            data-sql-query="INSERT INTO Users (username, email, password_hash, full_name, user_role, account_balance) VALUES (?, ?, ?, ?, ?, ?)"
            data-sql-explanation="INSERT with UNIQUE constraints on username/email and CHECK constraint on account_balance >= 0"
            data-sql-type="INSERT">
        <i class="fas fa-user-plus mr-2"></i>Create New User
    </button>
</div>

<!-- SQL Features Panel -->
<div class="sql-features-panel">
    <h3>
        🔐 SQL Features Demonstrated on This Page
        <button onclick="toggleFeaturePanel('users-features')">
            <i class="fas fa-chevron-down"></i>
        </button>
    </h3>
    <div id="users-features">
        <div class="sql-feature-item">
            <span class="feature-type">UNIQUE Constraints</span>
            <code>CONSTRAINT uk_users_username UNIQUE (username), CONSTRAINT uk_users_email UNIQUE (email)</code>
            <span class="feature-desc">Prevents duplicate usernames and emails - violations throw errors on INSERT/UPDATE</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">CHECK Constraints</span>
            <code>CONSTRAINT chk_account_balance CHECK (account_balance >= 0), CONSTRAINT chk_user_role CHECK (user_role IN ('donor', 'campaigner', 'admin'))</code>
            <span class="feature-desc">Validates data before insertion - balance must be non-negative, role must be valid</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Custom Functions</span>
            <code>SELECT Get_Donor_Level(user_id) FROM Users</code>
            <span class="feature-desc">User-defined function that calculates donor tier based on total donations</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Audit Triggers (AFTER UPDATE)</span>
            <code>CREATE TRIGGER trg_user_after_update AFTER UPDATE ON Users FOR EACH ROW INSERT INTO User_Audit_History...</code>
            <span class="feature-desc">Automatically logs every user modification with old/new values</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Conditional Updates by Role</span>
            <code>UPDATE Users SET account_balance = ? WHERE user_id = ? AND user_role = 'donor'</code>
            <span class="feature-desc">Role-based UPDATE restrictions ensure only appropriate users can be modified</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">CASCADE DELETE</span>
            <code>ON DELETE CASCADE</code>
            <span class="feature-desc">Deleting user automatically removes related donations and campaigns via FK constraints</span>
        </div>
        <div class="sql-feature-item">
            <span class="feature-type">Correlated Subqueries</span>
            <code>(SELECT COUNT(*) FROM Donations WHERE donor_id = u.user_id) as donations_made</code>
            <span class="feature-desc">Subquery executes for each user row to fetch related donation count</span>
        </div>
    </div>
</div>

<!-- Role Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <?php foreach ($role_stats as $stat): ?>
    <div class="bg-white rounded-lg shadow-md p-6"
         data-sql-query="SELECT user_role, COUNT(*), SUM(account_balance), SUM(CASE WHEN is_active=TRUE THEN 1 ELSE 0 END) FROM Users GROUP BY user_role HAVING COUNT(*) > 0"
         data-sql-explanation="GROUP BY with conditional aggregation and HAVING clause to filter roles">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 uppercase">
                <?php echo htmlspecialchars($stat['user_role']); ?>
            </h3>
            <i class="fas fa-<?php echo match($stat['user_role']) {
                'donor' => 'hand-holding-usd',
                'campaigner' => 'bullhorn',
                'admin' => 'user-shield',
                default => 'user'
            }; ?> text-3xl text-blue-600"></i>
        </div>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Total Users:</span>
                <span class="font-bold text-gray-900"><?php echo $stat['user_count']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Active:</span>
                <span class="font-semibold text-green-600"><?php echo $stat['active_count']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Total Balance:</span>
                <span class="font-bold text-blue-600">$<?php echo number_format($stat['total_balance'], 0); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Avg Balance:</span>
                <span class="text-gray-700">$<?php echo number_format($stat['avg_balance'], 0); ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Users Table with Custom Function -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">
            <i class="fas fa-table mr-2"></i>
            All Users (with Custom Function: Get_Donor_Level)
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donor Level</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="SELECT u.*, Get_Donor_Level(u.user_id) as donor_level, CASE WHEN account_balance >= 10000 THEN 'Premium' WHEN account_balance >= 5000 THEN 'Gold' ELSE 'Basic' END as tier FROM Users u"
                   data-sql-explanation="Custom function Get_Donor_Level() calculates donor tier based on total donations">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                        #<?php echo $user['user_id']; ?>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-medium
                            <?php echo match($user['user_role']) {
                                'donor' => 'bg-green-100 text-green-800',
                                'campaigner' => 'bg-blue-100 text-blue-800',
                                'admin' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800'
                            }; ?>">
                            <?php echo htmlspecialchars($user['user_role']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-bold"
                              data-sql-query="SELECT Get_Donor_Level(<?php echo $user['user_id']; ?>)"
                              data-sql-explanation="Custom function: Bronze (<$100), Silver ($100-$500), Gold ($500-$1000), Platinum (>$1000)"
                              data-sql-type="Function">
                            <?php echo htmlspecialchars($user['donor_level'] ?? 'N/A'); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-semibold text-blue-600">
                        $<?php echo number_format($user['account_balance'], 2); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs
                            <?php echo match($user['account_tier']) {
                                'Premium' => 'bg-purple-100 text-purple-800 font-bold',
                                'Gold' => 'bg-yellow-100 text-yellow-800 font-semibold',
                                'Silver' => 'bg-gray-100 text-gray-800',
                                default => 'bg-gray-50 text-gray-600'
                            }; ?>">
                            <?php echo $user['account_tier']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-medium
                            <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $user['account_status']; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button onclick='openEditUserModal(<?php echo json_encode($user); ?>)'
                                    class="text-blue-600 hover:text-blue-800"
                                    title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openDeleteUserModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')"
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete User">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Audit History (Trigger Demonstration) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
        <h3 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-history text-purple-600 mr-2"></i>
            User Audit History (Trigger: trg_user_after_update)
        </h3>
        <p class="text-sm text-gray-700">
            This log is automatically populated by <code class="bg-white px-2 py-1 rounded">AFTER UPDATE</code> trigger. 
            Every user modification is tracked with old/new values (role and email changes).
        </p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Audit ID</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Old Email / Role</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">New Email / Role</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Changed By</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200"
                   data-sql-query="-- Trigger executes: AFTER UPDATE ON Users FOR EACH ROW INSERT INTO User_Audit_Log (user_id, action_type, old_email, new_email, old_role, new_role, changed_at) VALUES (NEW.user_id, 'UPDATE', OLD.email, NEW.email, OLD.user_role, NEW.user_role, NOW())"
                   data-sql-explanation="AFTER UPDATE trigger automatically logs changes - captures OLD and NEW values for audit trail">
                <?php if (empty($audit_history)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-info-circle text-3xl mb-2"></i>
                        <p>No audit logs yet. Update a user to see trigger in action!</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($audit_history as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-600 font-mono text-xs">
                            #<?php echo $log['audit_id']; ?>
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900">
                            <?php echo htmlspecialchars($log['username'] ?? 'User #' . $log['user_id']); ?>
                        </td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                <?php echo htmlspecialchars($log['action_type']); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-red-600 text-xs">
                            <?php if ($log['old_email']): ?>
                                <div>Email: <?php echo htmlspecialchars($log['old_email']); ?></div>
                            <?php endif; ?>
                            <?php if ($log['old_role']): ?>
                                <div>Role: <?php echo htmlspecialchars($log['old_role']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-green-600 font-semibold text-xs">
                            <?php if ($log['new_email']): ?>
                                <div>Email: <?php echo htmlspecialchars($log['new_email']); ?></div>
                            <?php endif; ?>
                            <?php if ($log['new_role']): ?>
                                <div>Role: <?php echo htmlspecialchars($log['new_role']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-gray-600 text-xs">
                            <?php echo htmlspecialchars($log['changed_by'] ?? 'System'); ?>
                        </td>
                        <td class="px-3 py-2 text-gray-500 text-xs">
                            <?php echo date('M j, Y H:i', strtotime($log['changed_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900">Create New User</h3>
            <p class="text-sm text-gray-600 mt-1">Tests UNIQUE and CHECK constraints</p>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="create_user">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Username * <span class="text-xs text-gray-500">(UNIQUE)</span>
                    </label>
                    <input type="text" name="username" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2"
                           placeholder="john_doe">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email * <span class="text-xs text-gray-500">(UNIQUE)</span>
                    </label>
                    <input type="email" name="email" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2"
                           placeholder="john@example.com">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                    <input type="password" name="password" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="full_name" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2"
                           placeholder="John Doe">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        User Role * <span class="text-xs text-gray-500">(CHECK)</span>
                    </label>
                    <select name="user_role" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="donor">Donor</option>
                        <option value="campaigner">Campaigner</option>
                        <option value="admin">Admin</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">CHECK: Must be 'donor', 'campaigner', or 'admin'</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Account Balance <span class="text-xs text-gray-500">(CHECK >= 0)</span>
                    </label>
                    <input type="number" name="account_balance" step="0.01" min="0" value="0"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <p class="text-xs text-gray-500 mt-1">CHECK: Must be >= 0 (try negative to test!)</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-yellow-900 mb-2">
                    <i class="fas fa-shield-alt mr-2"></i>Constraint Testing
                </h4>
                <ul class="text-sm text-yellow-800 space-y-1">
                    <li>✓ Try duplicate username → UNIQUE constraint violation</li>
                    <li>✓ Try negative balance → CHECK constraint violation</li>
                    <li>✓ Try invalid role → CHECK constraint violation</li>
                </ul>
            </div>
            
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeCreateUserModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-user-plus mr-2"></i>Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900">Edit User</h3>
            <p class="text-sm text-gray-600 mt-1">Update triggers audit logging</p>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="full_name" id="edit_full_name" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email * (UNIQUE)</label>
                    <input type="email" name="email" id="edit_email" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User Role *</label>
                    <select name="user_role" id="edit_user_role" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="donor">Donor</option>
                        <option value="campaigner">Campaigner</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Balance (CHECK >= 0)</label>
                    <input type="number" name="account_balance" id="edit_account_balance" step="0.01" min="0"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="mr-2">
                        <span class="text-sm text-gray-700">Account Active</span>
                    </label>
                </div>
            </div>
            
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-purple-900 mb-2">
                    <i class="fas fa-bolt mr-2"></i>Trigger Action
                </h4>
                <p class="text-sm text-purple-800">
                    Updating this user will fire the <code class="bg-white px-2 py-1 rounded">trg_user_after_update</code> trigger,
                    automatically logging changes to User_Audit_Log table.
                </p>
            </div>
            
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeEditUserModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-red-600">Delete User</h3>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" id="delete_user_id">
            
            <div class="mb-6">
                <p class="text-gray-700 mb-4">
                    Are you sure you want to delete user <strong id="delete_username"></strong>?
                </p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>CASCADE DELETE Warning
                    </h4>
                    <p class="text-sm text-red-800">
                        This will automatically delete:
                    </p>
                    <ul class="text-sm text-red-800 list-disc list-inside mt-2">
                        <li>All campaigns created by this user</li>
                        <li>All donations made by this user</li>
                        <li>All related audit logs</li>
                    </ul>
                    <p class="text-sm text-red-800 mt-2 font-semibold">
                        This action cannot be undone!
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeDeleteUserModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>Delete User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('hidden');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.add('hidden');
}

function openEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_user_role').value = user.user_role;
    document.getElementById('edit_account_balance').value = user.account_balance;
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

function openDeleteUserModal(userId, username) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.add('hidden');
}

window.onclick = function(event) {
    if (event.target.id === 'createUserModal') closeCreateUserModal();
    if (event.target.id === 'editUserModal') closeEditUserModal();
    if (event.target.id === 'deleteUserModal') closeDeleteUserModal();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
