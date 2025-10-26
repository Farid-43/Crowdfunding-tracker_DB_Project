<?php
/**
 * Database Helper Functions
 * Provides reusable SQL operations for the application
 * Each function demonstrates specific SQL features
 */

require_once __DIR__ . '/database.php';

// =====================================================
// USER MANAGEMENT FUNCTIONS
// =====================================================

/**
 * Get all users with statistics
 * Demonstrates: SELECT with JOINs, Aggregation, GROUP BY
 */
function getAllUsers($pdo) {
    $query = "SELECT u.user_id, u.username, u.email, u.full_name, u.user_role, 
                     u.account_balance, u.created_at, u.is_active,
                     COUNT(DISTINCT c.campaign_id) as campaigns_count,
                     COUNT(DISTINCT d.donation_id) as donations_count,
                     COALESCE(SUM(d.amount), 0) as total_donated
              FROM Users u
              LEFT JOIN Campaigns c ON u.user_id = c.creator_id
              LEFT JOIN Donations d ON u.user_id = d.donor_id AND d.status = 'completed'
              GROUP BY u.user_id
              ORDER BY u.created_at DESC";
    
    return executeAndLogQuery($pdo, $query, [], 'users.php', 'SELECT')->fetchAll();
}

/**
 * Get user by ID with detailed statistics
 * Demonstrates: Parameterized query, Multiple LEFT JOINs, Subqueries
 */
function getUserById($pdo, $user_id) {
    $query = "SELECT u.*, 
                     (SELECT COUNT(*) FROM Campaigns WHERE creator_id = u.user_id) as total_campaigns,
                     (SELECT COUNT(*) FROM Donations WHERE donor_id = u.user_id) as total_donations,
                     (SELECT COALESCE(SUM(amount), 0) FROM Donations 
                      WHERE donor_id = u.user_id AND status = 'completed') as total_donated,
                     Get_Donor_Level(u.user_id) as donor_level
              FROM Users u
              WHERE u.user_id = :user_id";
    
    $stmt = executeAndLogQuery($pdo, $query, [':user_id' => $user_id], 'users.php', 'SELECT');
    return $stmt->fetch();
}

/**
 * Create new user
 * Demonstrates: INSERT with prepared statements, DEFAULT values
 */
function createUser($pdo, $username, $email, $password, $full_name, $role = 'donor', $account_balance = 0) {
    $query = "INSERT INTO Users (username, email, password_hash, full_name, user_role, account_balance) 
              VALUES (:username, :email, :password_hash, :full_name, :role, :account_balance)";
    
    $params = [
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':full_name' => $full_name,
        ':role' => $role,
        ':account_balance' => $account_balance
    ];
    
    executeAndLogQuery($pdo, $query, $params, 'users.php', 'INSERT');
    return $pdo->lastInsertId();
}

/**
 * Update user information
 * Demonstrates: UPDATE with WHERE, Prepared statements, Triggers (audit logging)
 */
function updateUser($pdo, $user_id, $full_name, $email, $user_role, $account_balance, $is_active) {
    $query = "UPDATE Users 
              SET full_name = :full_name,
                  email = :email,
                  user_role = :user_role,
                  account_balance = :account_balance,
                  is_active = :is_active
              WHERE user_id = :user_id";
    
    $params = [
        ':user_id' => $user_id,
        ':full_name' => $full_name,
        ':email' => $email,
        ':user_role' => $user_role,
        ':account_balance' => $account_balance,
        ':is_active' => $is_active
    ];
    
    executeAndLogQuery($pdo, $query, $params, 'users.php', 'UPDATE');
    return true;
}

/**
 * Delete user (cascade demonstration)
 * Demonstrates: DELETE with WHERE, CASCADE effects via foreign keys
 */
function deleteUser($pdo, $user_id) {
    $query = "DELETE FROM Users WHERE user_id = :user_id";
    
    executeAndLogQuery($pdo, $query, [':user_id' => $user_id], 'users.php', 'DELETE');
    return true;
}

// =====================================================
// CAMPAIGN MANAGEMENT FUNCTIONS
// =====================================================

/**
 * Get all campaigns with progress
 * Demonstrates: Using VIEW, Complex calculations
 */
function getAllCampaigns($pdo, $status = null, $category_id = null, $limit = null, $offset = 0) {
    $query = "SELECT * FROM Campaign_Progress WHERE 1=1";
    $params = [];
    
    if ($status) {
        $query .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    if ($category_id) {
        $query .= " AND category_id = :category_id";
        $params[':category_id'] = $category_id;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    }
    
    $stmt = $pdo->prepare($query);
    
    // Bind integer parameters separately
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    logQuery($pdo, $query, 'SELECT', 'campaigns.php', 0, $stmt->rowCount());
    
    return $stmt->fetchAll();
}

/**
 * Get campaign by ID with full details
 * Demonstrates: Multiple JOINs, Subqueries, Aggregation
 */
function getCampaignById($pdo, $campaign_id) {
    $query = "SELECT c.*, 
                     u.username as creator_username, 
                     u.full_name as creator_name,
                     cat.category_name,
                     cat.icon as category_icon,
                     COUNT(DISTINCT d.donation_id) as donation_count,
                     COUNT(DISTINCT d.donor_id) as unique_donors,
                     COALESCE(AVG(d.amount), 0) as avg_donation,
                     (SELECT COUNT(*) FROM Comments WHERE campaign_id = c.campaign_id) as comment_count,
                     (SELECT COUNT(*) FROM Campaign_Favorites WHERE campaign_id = c.campaign_id) as favorite_count,
                     Calculate_Campaign_Progress(c.campaign_id) as progress_percentage,
                     Get_Days_Until_End(c.campaign_id) as days_remaining,
                     Is_Campaign_Fully_Funded(c.campaign_id) as is_funded
              FROM Campaigns c
              INNER JOIN Users u ON c.creator_id = u.user_id
              LEFT JOIN Categories cat ON c.category_id = cat.category_id
              LEFT JOIN Donations d ON c.campaign_id = d.campaign_id AND d.status = 'completed'
              WHERE c.campaign_id = :campaign_id
              GROUP BY c.campaign_id";
    
    $stmt = executeAndLogQuery($pdo, $query, [':campaign_id' => $campaign_id], 'campaigns.php', 'SELECT');
    return $stmt->fetch();
}

/**
 * Create new campaign
 * Demonstrates: INSERT with multiple columns, Foreign keys, Triggers
 */
function createCampaign($pdo, $data) {
    $query = "INSERT INTO Campaigns (campaign_title, description, goal_amount, creator_id, 
                                     category_id, start_date, end_date, status, image_url) 
              VALUES (:title, :description, :goal_amount, :creator_id, :category_id, 
                      :start_date, :end_date, :status, :image_url)";
    
    $params = [
        ':title' => $data['title'],
        ':description' => $data['description'],
        ':goal_amount' => $data['goal_amount'],
        ':creator_id' => $data['creator_id'],
        ':category_id' => $data['category_id'] ?? null,
        ':start_date' => $data['start_date'],
        ':end_date' => $data['end_date'],
        ':status' => $data['status'] ?? 'draft',
        ':image_url' => $data['image_url'] ?? null
    ];
    
    executeAndLogQuery($pdo, $query, $params, 'campaigns.php', 'INSERT');
    return $pdo->lastInsertId();
}

/**
 * Update campaign
 * Demonstrates: UPDATE with multiple SET clauses, WHERE condition
 */
function updateCampaign($pdo, $campaign_id, $data) {
    $allowed_fields = ['campaign_title', 'description', 'goal_amount', 'category_id', 
                       'start_date', 'end_date', 'status', 'featured', 'image_url'];
    $set_clauses = [];
    $params = [':campaign_id' => $campaign_id];
    
    foreach ($data as $field => $value) {
        if (in_array($field, $allowed_fields)) {
            $set_clauses[] = "$field = :$field";
            $params[":$field"] = $value;
        }
    }
    
    if (empty($set_clauses)) {
        return false;
    }
    
    $query = "UPDATE Campaigns SET " . implode(', ', $set_clauses) . " WHERE campaign_id = :campaign_id";
    
    executeAndLogQuery($pdo, $query, $params, 'campaigns.php', 'UPDATE');
    return true;
}

/**
 * Delete campaign
 * Demonstrates: DELETE with CASCADE effects
 */
function deleteCampaign($pdo, $campaign_id) {
    $query = "DELETE FROM Campaigns WHERE campaign_id = :campaign_id";
    
    executeAndLogQuery($pdo, $query, [':campaign_id' => $campaign_id], 'campaigns.php', 'DELETE');
    return true;
}

// =====================================================
// DONATION FUNCTIONS
// =====================================================

/**
 * Process donation using stored procedure
 * Demonstrates: CALL stored procedure, Transaction handling
 */
function processDonation($pdo, $campaign_id, $donor_id, $amount, $payment_method, $message = '', $is_anonymous = false, $reward_id = null) {
    $query = "CALL Process_Donation(:campaign_id, :donor_id, :amount, :payment_method, 
                                     :message, :is_anonymous, @donation_id, @success, @message_out)";
    
    $params = [
        ':campaign_id' => $campaign_id,
        ':donor_id' => $donor_id,
        ':amount' => $amount,
        ':payment_method' => $payment_method,
        ':message' => $message,
        ':is_anonymous' => $is_anonymous ? 1 : 0
    ];
    
    executeAndLogQuery($pdo, $query, $params, 'donations.php', 'CALL');
    
    // Get output parameters
    $result = $pdo->query("SELECT @donation_id as donation_id, @success as success, @message_out as message")->fetch();
    
    // If donation successful and reward selected, assign reward
    if ($result['success'] && $reward_id) {
        try {
            $reward_query = "INSERT INTO Donor_Rewards (donor_id, reward_id, donation_id, fulfillment_status, claimed_at)
                            VALUES (:donor_id, :reward_id, :donation_id, 'pending', NOW())";
            $reward_params = [
                ':donor_id' => $donor_id,
                ':reward_id' => $reward_id,
                ':donation_id' => $result['donation_id']
            ];
            executeAndLogQuery($pdo, $reward_query, $reward_params, 'donations.php', 'INSERT');
        } catch (Exception $e) {
            // Log error but don't fail the donation
            error_log("Error assigning reward: " . $e->getMessage());
        }
    }
    
    return $result;
}

/**
 * Get all donations with details
 * Demonstrates: Multiple INNER JOINs, ORDER BY, LIMIT
 */
function getAllDonations($pdo, $limit = 100, $offset = 0) {
    $query = "SELECT d.*, 
                     c.campaign_title,
                     u.username as donor_username,
                     u.full_name as donor_name,
                     CASE 
                         WHEN d.is_anonymous = 1 THEN 'Anonymous'
                         ELSE u.full_name
                     END as display_name
              FROM Donations d
              INNER JOIN Campaigns c ON d.campaign_id = c.campaign_id
              INNER JOIN Users u ON d.donor_id = u.user_id
              ORDER BY d.donation_date DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    logQuery($pdo, $query, 'SELECT', 'donations.php', 0, $stmt->rowCount());
    
    return $stmt->fetchAll();
}

/**
 * Get donations by campaign
 * Demonstrates: WHERE with parameter, LEFT JOIN
 */
function getDonationsByCampaign($pdo, $campaign_id) {
    $query = "SELECT d.*, 
                     u.username, 
                     u.full_name,
                     CASE 
                         WHEN d.is_anonymous = 1 THEN 'Anonymous Donor'
                         ELSE u.full_name
                     END as donor_display_name
              FROM Donations d
              LEFT JOIN Users u ON d.donor_id = u.user_id
              WHERE d.campaign_id = :campaign_id AND d.status = 'completed'
              ORDER BY d.donation_date DESC";
    
    return executeAndLogQuery($pdo, $query, [':campaign_id' => $campaign_id], 'donations.php', 'SELECT')->fetchAll();
}

// =====================================================
// ANALYTICS FUNCTIONS
// =====================================================

/**
 * Get platform statistics using stored procedure
 * Demonstrates: CALL stored procedure, Aggregations
 */
function getPlatformStatistics($pdo) {
    $query = "CALL Calculate_Platform_Statistics()";
    
    $stmt = executeAndLogQuery($pdo, $query, [], 'analytics.php', 'CALL');
    return $stmt->fetch();
}

/**
 * Get top donors
 * Demonstrates: Using VIEW, ORDER BY, LIMIT
 */
function getTopDonors($pdo, $limit = 10) {
    $query = "SELECT * FROM Top_Donors LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    logQuery($pdo, $query, 'SELECT', 'analytics.php', 0, $stmt->rowCount());
    
    return $stmt->fetchAll();
}

/**
 * Get campaign analytics with CTE
 * Demonstrates: CTE (WITH clause), Window functions, Complex aggregation
 */
function getCampaignAnalyticsWithCTE($pdo) {
    $query = "WITH DonationStats AS (
                  SELECT campaign_id, 
                         COUNT(*) as donation_count,
                         SUM(amount) as total_raised,
                         AVG(amount) as avg_donation
                  FROM Donations 
                  WHERE status = 'completed'
                  GROUP BY campaign_id
              ),
              CampaignRanking AS (
                  SELECT c.campaign_id,
                         c.campaign_title,
                         c.goal_amount,
                         COALESCE(ds.total_raised, 0) as current_amount,
                         COALESCE(ds.donation_count, 0) as donations,
                         ROUND((COALESCE(ds.total_raised, 0) / c.goal_amount) * 100, 2) as progress_pct
                  FROM Campaigns c
                  LEFT JOIN DonationStats ds ON c.campaign_id = ds.campaign_id
                  WHERE c.status = 'active'
              )
              SELECT * FROM CampaignRanking
              ORDER BY progress_pct DESC";
    
    return executeAndLogQuery($pdo, $query, [], 'analytics.php', 'SELECT')->fetchAll();
}

/**
 * Get donation trends with ROLLUP
 * Demonstrates: GROUP BY with ROLLUP, Date functions
 */
function getDonationTrendsWithRollup($pdo) {
    $query = "SELECT DATE(donation_date) as donation_day,
                     payment_method,
                     COUNT(*) as transaction_count,
                     SUM(amount) as total_amount
              FROM Donations
              WHERE status = 'completed' 
                AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY DATE(donation_date), payment_method WITH ROLLUP
              ORDER BY donation_day DESC, payment_method";
    
    return executeAndLogQuery($pdo, $query, [], 'analytics.php', 'SELECT')->fetchAll();
}

// =====================================================
// CATEGORY FUNCTIONS
// =====================================================

/**
 * Get all categories with campaign counts
 * Demonstrates: LEFT JOIN, COUNT aggregation, GROUP BY
 */
function getAllCategories($pdo) {
    $query = "SELECT c.*, 
                     COUNT(DISTINCT camp.campaign_id) as campaign_count,
                     COALESCE(SUM(camp.current_amount), 0) as total_raised
              FROM Categories c
              LEFT JOIN Campaigns camp ON c.category_id = camp.category_id
              GROUP BY c.category_id
              ORDER BY c.category_name";
    
    return executeAndLogQuery($pdo, $query, [], 'categories.php', 'SELECT')->fetchAll();
}

/**
 * Create category
 * Demonstrates: Simple INSERT
 */
function createCategory($pdo, $name, $description, $icon = 'fa-folder') {
    $query = "INSERT INTO Categories (category_name, description, icon) 
              VALUES (:name, :description, :icon)";
    
    executeAndLogQuery($pdo, $query, [
        ':name' => $name,
        ':description' => $description,
        ':icon' => $icon
    ], 'categories.php', 'INSERT');
    
    return $pdo->lastInsertId();
}

// =====================================================
// SEARCH AND FILTER FUNCTIONS
// =====================================================

/**
 * Search campaigns
 * Demonstrates: LIKE operator, OR conditions, FULLTEXT search
 */
function searchCampaigns($pdo, $search_term) {
    $query = "SELECT c.*, 
                     u.username as creator_username,
                     cat.category_name,
                     MATCH(c.campaign_title, c.description) AGAINST(:search_term) as relevance
              FROM Campaigns c
              INNER JOIN Users u ON c.creator_id = u.user_id
              LEFT JOIN Categories cat ON c.category_id = cat.category_id
              WHERE MATCH(c.campaign_title, c.description) AGAINST(:search_term)
                 OR c.campaign_title LIKE :like_term
              ORDER BY relevance DESC, c.created_at DESC";
    
    $params = [
        ':search_term' => $search_term,
        ':like_term' => '%' . $search_term . '%'
    ];
    
    return executeAndLogQuery($pdo, $query, $params, 'search.php', 'SELECT')->fetchAll();
}

/**
 * Get user audit history
 * Demonstrates: Simple SELECT from audit table, ORDER BY
 */
function getUserAuditHistory($pdo, $user_id = null, $limit = 50) {
    if ($user_id) {
        $query = "SELECT * FROM User_Audit_Log WHERE user_id = :user_id ORDER BY changed_at DESC LIMIT :limit";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    } else {
        $query = "SELECT * FROM User_Audit_Log ORDER BY changed_at DESC LIMIT :limit";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    logQuery($pdo, $query, 'SELECT', 'audit.php', 0, $stmt->rowCount());
    
    return $stmt->fetchAll();
}

return $pdo;
?>
