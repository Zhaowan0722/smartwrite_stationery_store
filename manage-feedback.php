<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/config.php';

$page_title = "Manage Feedback";
$show_sidebar = true;
$current_page = 'admin/manage-feedback.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $id = (int)$_POST['feedback_id'];
        $status = $_POST['status'];
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        $sql = "UPDATE contacts SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $status, $admin_notes, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Feedback status updated!";
        } else {
            $error = "Error updating feedback: " . mysqli_error($conn);
        }
    }

    if (isset($_POST['delete_feedback'])) {
        $id = (int)$_POST['feedback_id'];
        
        $sql = "DELETE FROM contacts WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Feedback deleted successfully!";
        } else {
            $error = "Error deleting feedback: " . mysqli_error($conn);
        }
    }
}

$sql = "SELECT * FROM contacts ORDER BY created_at DESC";
$feedback_result = mysqli_query($conn, $sql);

$stats_sql = "SELECT 
                COUNT(*) as total_feedback,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_feedback,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_feedback,
                SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_feedback,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_feedback
              FROM contacts";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

require_once '../includes/header.php';
?>

<style>
    .admin-feedback-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #eee;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .page-header h1 {
        color: #333;
        margin: 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        line-height: 1;
    }
    
    .total-feedback { color: #007bff; }
    .new-feedback { color: #ffc107; }
    .read-feedback { color: #17a2b8; }
    .replied-feedback { color: #28a745; }
    .closed-feedback { color: #6c757d; }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .feedback-table-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
        overflow-x: auto;
    }
    
    .feedback-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }
    
    .feedback-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #eee;
    }
    
    .feedback-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        vertical-align: top;
    }
    
    .feedback-table tr:hover {
        background: #f9f9f9;
    }
    
    .feedback-message {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-new { background: #fff3cd; color: #856404; }
    .status-read { background: #cce5ff; color: #004085; }
    .status-replied { background: #d4edda; color: #155724; }
    .status-closed { background: #e2e3e5; color: #383d41; }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-view, .btn-reply, .btn-delete {
        padding: 6px 12px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .btn-view {
        background: #e3f2fd;
        color: #1565c0;
    }
    
    .btn-reply {
        background: #fff3cd;
        color: #856404;
    }
    
    .btn-delete {
        background: #f8d7da;
        color: #721c24;
    }
    
    .btn-view:hover, .btn-reply:hover, .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
    }
    
    .modal-content {
        background: white;
        width: 90%;
        max-width: 800px;
        margin: 50px auto;
        padding: 30px;
        border-radius: 10px;
        position: relative;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .close-modal {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }
    
    .feedback-details {
        margin-top: 20px;
    }
    
    .feedback-detail-item {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .feedback-detail-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }
    
    .feedback-detail-value {
        color: #333;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .feedback-table-container {
            padding: 15px;
        }
        
        .feedback-table {
            min-width: 1200px;
        }
    }
    
    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="admin-feedback-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-comments"></i> Manage Feedback</h1>
            <p style="color: #666; margin: 5px 0 0;">View and respond to customer feedback</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number total-feedback"><?php echo $stats['total_feedback']; ?></div>
            <div class="stat-label">Total Feedback</div>
        </div>
        <div class="stat-card">
            <div class="stat-number new-feedback"><?php echo $stats['new_feedback']; ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="stat-card">
            <div class="stat-number read-feedback"><?php echo $stats['read_feedback']; ?></div>
            <div class="stat-label">Read</div>
        </div>
        <div class="stat-card">
            <div class="stat-number replied-feedback"><?php echo $stats['replied_feedback']; ?></div>
            <div class="stat-label">Replied</div>
        </div>
        <div class="stat-card">
            <div class="stat-number closed-feedback"><?php echo $stats['closed_feedback']; ?></div>
            <div class="stat-label">Closed</div>
        </div>
    </div>

    <!-- Feedback Table -->
    <div class="feedback-table-container">
        <?php if (mysqli_num_rows($feedback_result) > 0): ?>
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($feedback = mysqli_fetch_assoc($feedback_result)): 
                        $date = date('M d, Y', strtotime($feedback['created_at']));
                        $time = date('h:i A', strtotime($feedback['created_at']));
                    ?>
                    <tr>
                        <td>#<?php echo $feedback['id']; ?></td>
                        <td><?php echo htmlspecialchars($feedback['name']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['email']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['subject']); ?></td>
                        <td class="feedback-message" title="<?php echo htmlspecialchars($feedback['message']); ?>">
                            <?php echo htmlspecialchars(substr($feedback['message'], 0, 100)); ?>...
                        </td>
                        <td>
                            <?php echo $date; ?><br>
                            <small style="color: #666;"><?php echo $time; ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $feedback['status']; ?>">
                                <?php echo ucfirst($feedback['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-view" onclick="viewFeedback(<?php echo $feedback['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this feedback?');" style="display: inline;">
                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                    <input type="hidden" name="delete_feedback" value="1">
                                    <button type="submit" class="btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments fa-3x" style="color: #ddd; margin-bottom: 20px;"></i>
                <h3>No Feedback Yet</h3>
                <p>Customer feedback will appear here when submitted.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Feedback Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeViewModal()">&times;</span>
        <h2><i class="fas fa-comment-dots"></i> Feedback Details</h2>
        
        <div id="feedbackDetails" class="feedback-details">
            <!-- Content will be loaded by JavaScript -->
        </div>
        
        <div id="adminActions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <h3><i class="fas fa-cog"></i> Update Status</h3>
            <form method="POST" id="updateStatusForm">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="feedback_id" id="modal_feedback_id">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #555;">Status</label>
                    <select name="status" id="modal_status" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 200px;">
                        <option value="new">New</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #555;">Admin Notes</label>
                    <textarea name="admin_notes" id="modal_admin_notes" rows="4" 
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                              placeholder="Add notes or response..."></textarea>
                </div>
                
                <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>
</div>

<script>

function viewFeedback(id) {

    fetch(`../ajax/get-feedback.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const feedback = data.feedback;
                const details = document.getElementById('feedbackDetails');
                
                details.innerHTML = `
                    <div class="feedback-detail-item">
                        <div class="feedback-detail-label">From</div>
                        <div class="feedback-detail-value">
                            <strong>${feedback.name}</strong> (${feedback.email})
                            ${feedback.phone ? `<br>Phone: ${feedback.phone}` : ''}
                        </div>
                    </div>
                    
                    <div class="feedback-detail-item">
                        <div class="feedback-detail-label">Subject</div>
                        <div class="feedback-detail-value"><strong>${feedback.subject}</strong></div>
                    </div>
                    
                    <div class="feedback-detail-item">
                        <div class="feedback-detail-label">Date & Time</div>
                        <div class="feedback-detail-value">
                            ${new Date(feedback.created_at).toLocaleDateString('en-US', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            })} at ${new Date(feedback.created_at).toLocaleTimeString('en-US', {
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                        </div>
                    </div>
                    
                    <div class="feedback-detail-item">
                        <div class="feedback-detail-label">Status</div>
                        <div class="feedback-detail-value">
                            <span class="status-badge status-${feedback.status}" style="display: inline-block;">
                                ${feedback.status.charAt(0).toUpperCase() + feedback.status.slice(1)}
                            </span>
                            ${feedback.updated_at !== feedback.created_at ? 
                                `<br><small>Last updated: ${new Date(feedback.updated_at).toLocaleDateString()}</small>` : ''}
                        </div>
                    </div>
                    
                    <div class="feedback-detail-item">
                        <div class="feedback-detail-label">Message</div>
                        <div class="feedback-detail-value" style="background: #f8f9fa; padding: 15px; border-radius: 5px; white-space: pre-wrap;">
                            ${feedback.message}
                        </div>
                    </div>
                    
                    ${feedback.admin_notes ? `
                    <div class="feedback-detail-item">
                        <div class="feedback-detail-label">Admin Notes</div>
                        <div class="feedback-detail-value" style="background: #e3f2fd; padding: 15px; border-radius: 5px; white-space: pre-wrap;">
                            ${feedback.admin_notes}
                        </div>
                    </div>
                    ` : ''}
                `;

                document.getElementById('modal_feedback_id').value = feedback.id;
                document.getElementById('modal_status').value = feedback.status;
                document.getElementById('modal_admin_notes').value = feedback.admin_notes || '';

                document.getElementById('viewModal').style.display = 'block';
            } else {
                alert('Error loading feedback details.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading feedback details.');
        });
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (event.target == modal) {
        closeViewModal();
    }
}
</script>

<?php 

$ajax_file = '../ajax/get-feedback.php';
if (!file_exists(dirname($ajax_file))) {
    mkdir(dirname($ajax_file), 0777, true);
}

$ajax_content = '<?php
require_once "../includes/config.php";

header("Content-Type: application/json");

if (!isset($_GET["id"])) {
    echo json_encode(["success" => false, "error" => "No ID provided"]);
    exit();
}

$id = (int)$_GET["id"];
$sql = "SELECT * FROM contacts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$feedback = mysqli_fetch_assoc($result);

if ($feedback) {
    echo json_encode(["success" => true, "feedback" => $feedback]);
} else {
    echo json_encode(["success" => false, "error" => "Feedback not found"]);
}
?>';

if (!file_exists($ajax_file)) {
    file_put_contents($ajax_file, $ajax_content);
}

require_once '../includes/footer.php';
?>