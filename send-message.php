<?php

session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Send Message";
$show_sidebar = true;
$current_page = 'admin/send-message.php';

$message = '';
$error = '';
$receiver_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $msg_content = mysqli_real_escape_string($conn, $_POST['message']);
    $sender_id = $_SESSION['user_id'];

    if (empty($subject) || empty($msg_content) || $receiver_id == 0) {
        $error = "All fields are required.";
    } else {
        $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES ($sender_id, $receiver_id, '$subject', '$msg_content')";
        if (mysqli_query($conn, $sql)) {
            $message = "Message sent successfully!";

            if (!isset($_GET['user_id'])) $receiver_id = 0;
            $subject = '';
            $msg_content = '';
        } else {
            $error = "Error sending message: " . mysqli_error($conn);
        }
    }
}

$users_result = mysqli_query($conn, "SELECT id, username, email FROM users WHERE user_type = 'user' ORDER BY username");

require_once '../includes/header.php';
?>

<div class="content">
    <h1><i class="fas fa-paper-plane"></i> Send Message</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Recipient (User)</label>
                <select name="receiver_id" class="form-control" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">-- Select User --</option>
                    <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($receiver_id == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Subject</label>
                <input type="text" name="subject" class="form-control" required 
                       value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>"
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Message</label>
                <textarea name="message" class="form-control" rows="6" required 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;"><?php echo isset($msg_content) ? htmlspecialchars($msg_content) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer;">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
            <a href="manage-users.php" class="btn" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">Back</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>