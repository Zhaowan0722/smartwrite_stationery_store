<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/config.php';

$page_title = "Manage Contact Messages";
$show_sidebar = true;
$current_page = 'admin/manage-contacts.php';

if (isset($_POST['update_status'])) {
    $contact_id = $_POST['contact_id'];
    $status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    $sql = "UPDATE contacts SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $status, $admin_notes, $contact_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header("Location: manage-contacts.php?updated=1");
    exit();
}

if (isset($_GET['delete'])) {
    $contact_id = $_GET['delete'];
    
    $sql = "DELETE FROM contacts WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $contact_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header("Location: manage-contacts.php?deleted=1");
    exit();
}

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$status_counts_query = "SELECT status, COUNT(*) as count FROM contacts GROUP BY status";
$status_counts_result = mysqli_query($conn, $status_counts_query);
$status_counts = ['new' => 0, 'read' => 0, 'replied' => 0, 'closed' => 0];
while ($row = mysqli_fetch_assoc($status_counts_result)) {
    $status_counts[$row['status']] = $row['count'];
}
$total_contacts = array_sum($status_counts);

require_once '../includes/header.php';
?>

<style>
    .manage-contacts {
        padding: 20px;
        width: 100%;
        box-sizing: border-box;
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

    .page-title h1 {
        color: #ff6b6b;
        margin: 0;
        font-size: 2rem;
        border-bottom: 3px solid #ff6b6b;
        padding-bottom: 10px;
    }

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-weight: 600;
        color: #555;
        font-size: 0.9rem;
    }

    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
    }

    .btn-filter {
        padding: 8px 20px;
        background: #ff6b6b;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }

    .btn-filter:hover {
        background: #ff5252;
    }

    .btn-reset {
        padding: 8px 20px;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background 0.3s;
    }

    .btn-reset:hover {
        background: #5a6268;
    }

    .status-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .status-stat {
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        transition: transform 0.3s;
    }

    .status-stat:hover {
        transform: translateY(-3px);
    }

    .status-stat.all {
        background: #f8f9fa;
        border: 2px solid #6c757d;
    }

    .status-stat.new {
        background: #fff3cd;
        border: 2px solid #ffc107;
    }

    .status-stat.read {
        background: #cce5ff;
        border: 2px solid #007bff;
    }

    .status-stat.replied {
        background: #d4edda;
        border: 2px solid #28a745;
    }

    .status-stat.closed {
        background: #e2e3e5;
        border: 2px solid #6c757d;
    }

    .stat-count {
        font-size: 2rem;
        font-weight: 700;
        display: block;
    }

    .stat-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .contacts-table-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
        overflow: hidden;
    }

    .contacts-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .contacts-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #eee;
        font-size: 0.9rem;
    }

    .contacts-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        font-size: 0.9rem;
        vertical-align: top;
    }

    .contacts-table tr:hover {
        background: #f9f9f9;
    }

    .contact-message {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .contact-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-new {
        background: #fff3cd;
        color: #856404;
    }

    .status-read {
        background: #cce5ff;
        color: #004085;
    }

    .status-replied {
        background: #d4edda;
        color: #155724;
    }

    .status-closed {
        background: #e2e3e5;
        color: #383d41;
    }

    .btn-action {
        padding: 6px 12px;
        background: #f8f9fa;
        color: #333;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        margin: 2px;
    }

    .btn-action:hover {
        background: #ff6b6b;
        color: white;
    }

    .btn-message {
        background: #17a2b8;
        color: white;
    }

    .btn-message:hover {
        background: #138496;
        color: white;
    }

    .btn-view {
        background: #007bff;
        color: white;
    }

    .btn-view:hover {
        background: #0056b3;
        color: white;
    }

    .btn-delete {
        background: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background: #c82333;
        color: white;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eee;
    }

    .modal-header h2 {
        color: #333;
        margin: 0;
    }

    .close-modal {
        color: #999;
        font-size: 1.5rem;
        cursor: pointer;
        transition: color 0.3s;
    }

    .close-modal:hover {
        color: #ff6b6b;
    }

    .message-details {
        margin-bottom: 20px;
    }

    .detail-group {
        margin-bottom: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .detail-group label {
        display: block;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .detail-group p {
        margin: 0;
        color: #333;
        line-height: 1.5;
    }

    .update-form {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #eee;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
    }

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .btn-update {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }

    .btn-update:hover {
        background: #218838;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    @media (max-width: 768px) {
        .manage-contacts {
            padding: 15px;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .contacts-table {
            min-width: 1200px;
        }

        .status-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .status-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="manage-contacts">
    <div class="page-header">
        <div class="page-title">
            <h1>Contact Messages</h1>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">
            Message status updated successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            Message deleted successfully!
        </div>
    <?php endif; ?>

    <div class="status-stats">
        <a href="?status=all" class="status-stat all" style="text-decoration: none; color: inherit;">
            <span class="stat-count"><?php echo $total_contacts; ?></span>
            <span class="stat-label">All Messages</span>
        </a>
        <a href="?status=new" class="status-stat new" style="text-decoration: none; color: inherit;">
            <span class="stat-count"><?php echo $status_counts['new']; ?></span>
            <span class="stat-label">New</span>
        </a>
        <a href="?status=read" class="status-stat read" style="text-decoration: none; color: inherit;">
            <span class="stat-count"><?php echo $status_counts['read']; ?></span>
            <span class="stat-label">Read</span>
        </a>
        <a href="?status=replied" class="status-stat replied" style="text-decoration: none; color: inherit;">
            <span class="stat-count"><?php echo $status_counts['replied']; ?></span>
            <span class="stat-label">Replied</span>
        </a>
        <a href="?status=closed" class="status-stat closed" style="text-decoration: none; color: inherit;">
            <span class="stat-count"><?php echo $status_counts['closed']; ?></span>
            <span class="stat-label">Closed</span>
        </a>
    </div>

    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Filter by Status</label>
                <select name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                    <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search messages..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            
            <a href="manage-contacts.php" class="btn-reset">
                <i class="fas fa-redo"></i> Reset
            </a>
        </form>
    </div>

    <div class="contacts-table-container">
        <div class="table-responsive">
            <table class="contacts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($contact = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?php echo str_pad($contact['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                <td><?php echo htmlspecialchars($contact['phone'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                                <td class="contact-message" title="<?php echo htmlspecialchars($contact['message']); ?>">
                                    <?php echo htmlspecialchars(substr($contact['message'], 0, 50)); ?>...
                                </td>
                                <td>
                                    <span class="contact-status status-<?php echo $contact['status']; ?>">
                                        <?php echo ucfirst($contact['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($contact['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- View Button -->
                                        <a href="#" class="btn-action btn-view" 
                                           data-id="<?php echo $contact['id']; ?>"
                                           data-name="<?php echo htmlspecialchars($contact['name']); ?>"
                                           data-email="<?php echo htmlspecialchars($contact['email']); ?>"
                                           data-phone="<?php echo htmlspecialchars($contact['phone']); ?>"
                                           data-subject="<?php echo htmlspecialchars($contact['subject']); ?>"
                                           data-message="<?php echo htmlspecialchars($contact['message']); ?>"
                                           data-status="<?php echo $contact['status']; ?>"
                                           data-notes="<?php echo htmlspecialchars($contact['admin_notes'] ?? ''); ?>"
                                           data-date="<?php echo date('M d, Y H:i', strtotime($contact['created_at'])); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- Message Button -->
                                        <a href="send-message.php?email=<?php echo urlencode($contact['email']); ?>&name=<?php echo urlencode($contact['name']); ?>" 
                                           class="btn-action btn-message"
                                           title="Send Message">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <a href="?delete=<?php echo $contact['id']; ?>" 
                                           class="btn-action btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this message?');"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px; display: block; color: #ccc;"></i>
                                No contact messages found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View/Edit Modal -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Contact Message Details</h2>
            <span class="close-modal">&times;</span>
        </div>
        
        <form method="POST" id="updateForm">
            <input type="hidden" name="contact_id" id="modalContactId">
            
            <div class="message-details">
                <div class="detail-group">
                    <label>From:</label>
                    <p id="modalName"></p>
                </div>
                
                <div class="form-row">
                    <div class="detail-group">
                        <label>Email:</label>
                        <p id="modalEmail"></p>
                    </div>
                    
                    <div class="detail-group">
                        <label>Phone:</label>
                        <p id="modalPhone"></p>
                    </div>
                </div>
                
                <div class="detail-group">
                    <label>Subject:</label>
                    <p id="modalSubject"></p>
                </div>
                
                <div class="detail-group">
                    <label>Message:</label>
                    <p id="modalMessage" style="white-space: pre-wrap;"></p>
                </div>
                
                <div class="form-row">
                    <div class="detail-group">
                        <label>Submitted:</label>
                        <p id="modalDate"></p>
                    </div>
                    
                    <div class="detail-group">
                        <label>Current Status:</label>
                        <p id="modalCurrentStatus"></p>
                    </div>
                </div>
            </div>
            
            <div class="update-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modalStatus">Update Status:</label>
                        <select name="status" id="modalStatus" required>
                            <option value="new">New</option>
                            <option value="read">Read</option>
                            <option value="replied">Replied</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="modalNotes">Admin Notes:</label>
                    <textarea name="admin_notes" id="modalNotes" placeholder="Add internal notes about this message..."></textarea>
                </div>
                
                <button type="submit" name="update_status" class="btn-update">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>

    const modal = document.getElementById('contactModal');
    const closeBtn = document.querySelector('.close-modal');
    const viewBtns = document.querySelectorAll('.btn-view');
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const contactId = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const email = btn.getAttribute('data-email');
            const phone = btn.getAttribute('data-phone');
            const subject = btn.getAttribute('data-subject');
            const message = btn.getAttribute('data-message');
            const status = btn.getAttribute('data-status');
            const notes = btn.getAttribute('data-notes');
            const date = btn.getAttribute('data-date');

            document.getElementById('modalContactId').value = contactId;
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalPhone').textContent = phone || 'N/A';
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('modalDate').textContent = date;
            document.getElementById('modalCurrentStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);

            document.getElementById('modalStatus').value = status;
            document.getElementById('modalNotes').value = notes;

            modal.style.display = 'block';
        });
    });
    
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>