<?php
/**
 * Database Configuration File
 * Establishes PDO connection to CF_Tracker database
 * Implements query logging for educational purposes
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'CF_Tracker');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP password is empty
define('DB_CHARSET', 'utf8mb4');

// PDO Options for security and error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

// Global database connection variable
$pdo = null;

try {
    // Create PDO instance
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please ensure MySQL is running and CF_Tracker database exists.");
}

/**
 * Execute a query and log it for educational purposes
 * 
 * @param PDO $pdo Database connection
 * @param string $query SQL query to execute
 * @param array $params Parameters for prepared statement
 * @param string $page_name Name of the page executing the query
 * @param string $query_type Type of SQL operation (SELECT, INSERT, UPDATE, etc.)
 * @return PDOStatement|bool Query result
 */
function executeAndLogQuery($pdo, $query, $params = [], $page_name = '', $query_type = '') {
    $start_time = microtime(true);
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $execution_time = microtime(true) - $start_time;
        $rows_affected = $stmt->rowCount();
        
        // Auto-detect query type if not provided
        if (empty($query_type)) {
            $query_type = strtoupper(explode(' ', trim($query))[0]);
        }
        
        // Log the query to Query_Log table for educational display
        logQuery($pdo, $query, $query_type, $page_name, $execution_time, $rows_affected);
        
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | Query: " . $query);
        throw $e;
    }
}

/**
 * Log query to database for educational tracking
 * 
 * @param PDO $pdo Database connection
 * @param string $query_text The SQL query
 * @param string $query_type Type of query
 * @param string $page_name Page executing the query
 * @param float $execution_time Query execution time
 * @param int $rows_affected Number of rows affected
 */
function logQuery($pdo, $query_text, $query_type, $page_name, $execution_time, $rows_affected) {
    try {
        // Don't log queries to Query_Log table to avoid infinite recursion
        if (stripos($query_text, 'Query_Log') !== false) {
            return;
        }
        
        $log_query = "INSERT INTO Query_Log (query_text, query_type, page_name, execution_time, rows_affected, user_session) 
                      VALUES (:query_text, :query_type, :page_name, :execution_time, :rows_affected, :user_session)";
        
        $stmt = $pdo->prepare($log_query);
        $stmt->execute([
            ':query_text' => $query_text,
            ':query_type' => $query_type,
            ':page_name' => $page_name,
            ':execution_time' => $execution_time,
            ':rows_affected' => $rows_affected,
            ':user_session' => session_id()
        ]);
    } catch (PDOException $e) {
        // Silently fail query logging to not disrupt main application
        error_log("Query logging failed: " . $e->getMessage());
    }
}

/**
 * Get recent queries for educational display
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Number of queries to retrieve
 * @return array Array of recent queries
 */
function getRecentQueries($pdo, $limit = 50) {
    $query = "SELECT log_id, query_text, query_type, page_name, execution_time, 
                     rows_affected, executed_at 
              FROM Query_Log 
              ORDER BY executed_at DESC 
              LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get queries grouped by type for analytics
 * 
 * @param PDO $pdo Database connection
 * @return array Query statistics by type
 */
function getQueryStatsByType($pdo) {
    $query = "SELECT query_type, 
                     COUNT(*) as query_count,
                     AVG(execution_time) as avg_execution_time,
                     SUM(rows_affected) as total_rows_affected
              FROM Query_Log 
              GROUP BY query_type
              ORDER BY query_count DESC";
    
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}

/**
 * Clear old query logs (for maintenance)
 * 
 * @param PDO $pdo Database connection
 * @param int $days_to_keep Number of days to keep logs
 * @return int Number of rows deleted
 */
function clearOldQueryLogs($pdo, $days_to_keep = 7) {
    $query = "DELETE FROM Query_Log 
              WHERE executed_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':days' => $days_to_keep]);
    
    return $stmt->rowCount();
}

// Start session for user tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

return $pdo;
?>
