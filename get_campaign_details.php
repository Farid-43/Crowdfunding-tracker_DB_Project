<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/db_functions.php';

$campaign_id = $_GET['campaign_id'] ?? 0;

// Get campaign details
$campaign_query = "SELECT c.*, u.username as creator_username, u.full_name as creator_name,
                         cat.category_name,
                         (SELECT COUNT(*) FROM Donations WHERE campaign_id = c.campaign_id) as donation_count,
                         (SELECT COUNT(*) FROM Comments WHERE campaign_id = c.campaign_id) as comment_count
                  FROM Campaigns c
                  LEFT JOIN Users u ON c.creator_id = u.user_id
                  LEFT JOIN Campaign_Category cc ON c.campaign_id = cc.campaign_id
                  LEFT JOIN Categories cat ON cc.category_id = cat.category_id
                  WHERE c.campaign_id = :campaign_id";
$campaign = executeAndLogQuery($pdo, $campaign_query, [':campaign_id' => $campaign_id], 'get_campaign_details.php', 'SELECT')->fetch();

if (!$campaign) {
    echo '<div class="text-red-600 p-4">Campaign not found.</div>';
    exit;
}

// Get comments for this campaign
$comments_query = "SELECT c.comment_id, c.content, c.comment_date, c.parent_comment_id,
                         u.username, u.full_name,
                         (SELECT COUNT(*) FROM Comments WHERE parent_comment_id = c.comment_id) as reply_count
                  FROM Comments c
                  INNER JOIN Users u ON c.user_id = u.user_id
                  WHERE c.campaign_id = :campaign_id
                  ORDER BY c.parent_comment_id ASC, c.comment_date ASC";
$comments = executeAndLogQuery($pdo, $comments_query, [':campaign_id' => $campaign_id], 'get_campaign_details.php', 'SELECT')->fetchAll();

// Calculate progress
$progress = ($campaign['goal_amount'] > 0) ? ($campaign['current_amount'] / $campaign['goal_amount']) * 100 : 0;
?>

<!-- Campaign Details -->
<div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-6 mb-6">
    <h2 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($campaign['campaign_title']); ?></h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
        <div>
            <p class="text-sm text-gray-600 mb-1"><i class="fas fa-user mr-2"></i>Creator</p>
            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($campaign['creator_name']); ?> 
                <span class="text-sm text-gray-500">(@<?php echo htmlspecialchars($campaign['creator_username']); ?>)</span>
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600 mb-1"><i class="fas fa-tag mr-2"></i>Category</p>
            <p class="text-lg">
                <?php if ($campaign['category_name']): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <?php echo htmlspecialchars($campaign['category_name']); ?>
                    </span>
                <?php else: ?>
                    <span class="text-gray-400">Uncategorized</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="mb-4">
        <p class="text-sm text-gray-600 mb-2"><i class="fas fa-align-left mr-2"></i>Description</p>
        <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <p class="text-sm text-gray-600 mb-1"><i class="fas fa-bullseye mr-2"></i>Goal</p>
            <p class="text-2xl font-bold text-blue-600">$<?php echo number_format($campaign['goal_amount'], 2); ?></p>
        </div>
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <p class="text-sm text-gray-600 mb-1"><i class="fas fa-dollar-sign mr-2"></i>Raised</p>
            <p class="text-2xl font-bold text-green-600">$<?php echo number_format($campaign['current_amount'], 2); ?></p>
        </div>
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <p class="text-sm text-gray-600 mb-1"><i class="fas fa-chart-line mr-2"></i>Progress</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo number_format($progress, 1); ?>%</p>
        </div>
    </div>

    <div class="bg-white rounded-lg p-2 mb-4">
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-4 rounded-full transition-all duration-500" 
                 style="width: <?php echo min($progress, 100); ?>%"></div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div>
            <p class="text-2xl font-bold text-gray-900"><?php echo $campaign['donation_count']; ?></p>
            <p class="text-sm text-gray-600">Donations</p>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900"><?php echo $campaign['comment_count']; ?></p>
            <p class="text-sm text-gray-600">Comments</p>
        </div>
        <div>
            <p class="text-sm font-semibold px-3 py-2 rounded-full <?php 
                echo match($campaign['status']) {
                    'active' => 'bg-green-100 text-green-800',
                    'completed' => 'bg-blue-100 text-blue-800',
                    'draft' => 'bg-gray-100 text-gray-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800'
                };
            ?>"><?php echo ucfirst($campaign['status']); ?></p>
            <p class="text-xs text-gray-600 mt-1">Status</p>
        </div>
        <div>
            <?php if ($campaign['featured']): ?>
                <p class="text-yellow-500 text-2xl"><i class="fas fa-star"></i></p>
                <p class="text-sm text-gray-600">Featured</p>
            <?php else: ?>
                <p class="text-gray-300 text-2xl"><i class="far fa-star"></i></p>
                <p class="text-sm text-gray-600">Not Featured</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Comments Section -->
<div class="bg-white rounded-lg p-6 border-t-4 border-blue-500">
    <h3 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-comments text-blue-600 mr-2"></i>
        Comments (<?php echo count($comments); ?>)
    </h3>

    <?php if (empty($comments)): ?>
        <div class="text-center py-8 bg-gray-50 rounded-lg">
            <i class="fas fa-comment-slash text-gray-300 text-5xl mb-3"></i>
            <p class="text-gray-500">No comments yet for this campaign.</p>
        </div>
    <?php else: ?>
        <!-- Group comments by parent -->
        <?php 
        $parent_comments = array_filter($comments, fn($c) => $c['parent_comment_id'] === null);
        $reply_comments = array_filter($comments, fn($c) => $c['parent_comment_id'] !== null);
        $replies_by_parent = [];
        foreach ($reply_comments as $reply) {
            $replies_by_parent[$reply['parent_comment_id']][] = $reply;
        }
        ?>

        <div class="space-y-4">
            <?php foreach ($parent_comments as $comment): ?>
                <!-- Parent Comment -->
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                    <div class="flex items-start mb-2">
                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($comment['full_name']); ?></p>
                                    <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($comment['username']); ?> • 
                                        <?php echo date('M j, Y g:i A', strtotime($comment['comment_date'])); ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-comment mr-1"></i> Parent
                                </span>
                            </div>
                            <p class="mt-2 text-gray-700"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            
                            <?php if ($comment['reply_count'] > 0): ?>
                                <p class="text-xs text-purple-600 mt-2">
                                    <i class="fas fa-reply mr-1"></i><?php echo $comment['reply_count']; ?> 
                                    <?php echo $comment['reply_count'] == 1 ? 'reply' : 'replies'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Replies to this comment -->
                    <?php if (isset($replies_by_parent[$comment['comment_id']])): ?>
                        <div class="ml-12 mt-3 space-y-3">
                            <?php foreach ($replies_by_parent[$comment['comment_id']] as $reply): ?>
                                <div class="bg-white rounded-lg p-3 border-l-4 border-green-500">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-8 w-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                            <i class="fas fa-user text-green-600 text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($reply['full_name']); ?></p>
                                                    <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($reply['username']); ?> • 
                                                        <?php echo date('M j, Y g:i A', strtotime($reply['comment_date'])); ?>
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-reply mr-1"></i> Reply
                                                </span>
                                            </div>
                                            <p class="mt-2 text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- SQL Feature Info -->
        <div class="mt-6 bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
            <p class="text-sm text-purple-900">
                <i class="fas fa-info-circle mr-2"></i><strong>SQL Feature:</strong> 
                This demonstrates a <strong>self-referencing foreign key</strong> where 
                <code class="bg-purple-200 px-2 py-1 rounded">parent_comment_id</code> references 
                <code class="bg-purple-200 px-2 py-1 rounded">comment_id</code> in the same table, 
                creating a hierarchical parent-child relationship for threaded comments.
            </p>
        </div>
    <?php endif; ?>
</div>
