<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

$query = "SELECT user_id, username, full_name, account_balance 
          FROM Users 
          WHERE is_active = TRUE 
          ORDER BY full_name";

$stmt = $pdo->prepare($query);
$stmt->execute();
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($donors);
?>
