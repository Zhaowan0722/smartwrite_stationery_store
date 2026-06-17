<?php

session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Sent Messages";
$current_page = 'admin/my-messages.php';
$show_sidebar = true;
$admin_id = $_SESSION['user_id'];

$sql = "SELECT m.*, u.username as receiver_name 
        FROM messages m 
        JOIN users u ON m.receiver_id = u.id 
        WHERE m.sender_id = $admin_id 
        ORDER BY m.created_at DESC";
$result = mysqli_query($conn, $sql);

require_once '../includes/header.php';
?>

<div class="content">
    <h1><i class="fas fa-paper-plane" style="color: #ff6b6b;"></i> Sent Messages</h1>
    
    <div style="margin-bottom: 20px;">
        <a href="send-message.php" class="btn" style="background: #28a745; color: white; text-decoration: none;">
            <i class="fas fa-plus"></i> Send New Message
        </a>
    </div>
    
    <div style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8f9fa; border-bottom: 2px solid #eee;">
                    <tr>
                        <th style="padding: 15px; text-align: left; color: #555;">To</th>
                        <th style="padding: 15px; text-align: left; color: #555;">Subject</th>
                        <th style="padding: 15px; text-align: left; color: #555;">Date</th>
                        <th style="padding: 15px; text-align: left; color: #555;">Status</th>
                        <th style="padding: 15px; text-align: left; color: #555;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($msg = mysqli_fetch_assoc($result)): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 15px;">
                            <i class="fas fa-user-circle" style="color: #6c757d;"></i> 
                            <?php echo htmlspecialchars($msg['receiver_name']); ?>
                        </td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($msg['subject']); ?></td>
                        <td style="padding: 15px; color: #666; font-size: 0.9em;">
                            <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php if($msg['is_read']): ?>
                                <span style="color: #28a745; font-weight: bold;">
                                    <i class="fas fa-check-circle"></i> Read
                                </span>
                            <?php else: ?>
                                <span style="color: #ff6b6b;">
                                    <i class="fas fa-clock"></i> Unread
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <a href="view-sent-message.php?id=<?php echo $msg['id']; ?>" class="btn" style="padding: 5px 15px; font-size: 0.9em; background: #007bff; color: white; border-radius: 4px; text-decoration: none;">
                                View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 20px; color: #666;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
                <h3>No messages sent yet</h3>
                <p>Messages you send to users will appear here.</p>
                <a href="send-message.php" class="btn" style="margin-top: 20px; background: #ff6b6b; color: white;">
                    <i class="fas fa-paper-plane"></i> Send Your First Message
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>