<?php

session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: my-messages.php');
    exit();
}

$msg_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$sql = "SELECT m.*, u.username as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.id = $msg_id AND m.receiver_id = $user_id";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Message not found or access denied.");
}

$msg = mysqli_fetch_assoc($result);

if ($msg['is_read'] == 0) {
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE id = $msg_id");
}

$page_title = htmlspecialchars($msg['subject']);
$show_sidebar = true;

require_once 'includes/header.php';
?>

<div class="content">

    <!-- BACK -->
    <div style="margin-bottom:20px;">
        <a href="my-messages.php" style="color:#666; text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Back to Inbox
        </a>
    </div>

    <!-- MESSAGE BOX -->
    <div style="background:white; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">

        <!-- SUBJECT -->
        <h1 style="margin-top:0; color:#2c3e50; font-size:1.6rem;">
            <?php echo htmlspecialchars($msg['subject']); ?>
        </h1>

        <!-- HEADER -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">

            <!-- LEFT -->
            <div style="display:flex; align-items:center; gap:10px;">

                <div style="width:40px; height:40px; background:#3498db; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                </div>

                <div>
                    <div style="font-weight:bold; color:#333;">
                        <?php echo htmlspecialchars($msg['sender_name']); ?>

                        <?php if($msg['sender_name'] == 'admin'): ?>
                            <span style="background:#3498db; color:white; font-size:0.7em; padding:2px 6px; border-radius:3px; margin-left:5px;">
                                STAFF
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:0.85em; color:#999;">
                        To: You
                    </div>
                </div>
            </div>

            <!-- DATE -->
            <div style="color:#777; font-size:0.85em;">
                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
            </div>

        </div>

        <!-- MESSAGE CONTENT -->
        <div style="line-height:1.6; color:#444; min-height:150px; white-space:pre-wrap;">
            <?php echo htmlspecialchars($msg['message']); ?>
        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>