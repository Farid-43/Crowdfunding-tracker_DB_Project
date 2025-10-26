<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

$campaign_id = $_GET['campaign_id'] ?? 0;

$query = "SELECT r.reward_id, r.title, r.description, r.min_amount, r.max_backers,
                 r.current_backers, r.estimated_delivery
          FROM Rewards r
          WHERE r.campaign_id = :campaign_id
          ORDER BY r.min_amount ASC";

$stmt = $pdo->prepare($query);
$stmt->execute([':campaign_id' => $campaign_id]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rewards);
?>
