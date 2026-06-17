<?php
require_once 'includes/config.php';

$page_title = "Submit Feedback";
$show_sidebar = false;

$is_logged_in = isset($_SESSION['user_id']); // 🔥 FIX

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    $rating = (int)($_POST['rating'] ?? 5);

    if ($rating < 1 || $rating > 5) $rating = 5;

    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        $sql = "INSERT INTO contacts (name, email, subject, message, rating, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'new', NOW())";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $subject, $message_text, $rating);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Thank you for your feedback!";

            if (!$is_logged_in) {
                $_POST = [];
            }

        } else {
            $error = "Error submitting feedback.";
        }
    }
}

include 'includes/header.php';
?>

<div class="content">
    <h1><i class="fas fa-comment-dots" style="color:#3498db;"></i> Submit Feedback</h1>
    
    <?php if ($message): ?>
        <div style="background:#eaf4fc; color:#2c3e50; padding:15px; border-radius:8px; margin-bottom:20px; border-left:4px solid #3498db;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background:#fdecea; color:#c0392b; padding:15px; border-radius:8px; margin-bottom:20px; border-left:4px solid #e74c3c;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div style="background:white; border-radius:12px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:30px;">
        
        <div style="text-align:center; margin-bottom:30px;">
            <h2 style="color:#2c3e50; margin-bottom:10px;">We Value Your Opinion</h2>
            <p style="color:#666; max-width:550px; margin:0 auto; line-height:1.6;">
                Help us improve our service by sharing your experience.
            </p>
        </div>
        
        <form method="POST" style="max-width:600px; margin:0 auto;">
            
            <?php if ($is_logged_in): ?>
                <div style="background:#ebf5fb; border-left:4px solid #3498db; padding:12px; border-radius:6px; margin-bottom:20px;">
                    <i class="fas fa-user"></i> Logged in as 
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </div>
            <?php endif; ?>
            
            <!-- NAME -->
            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600; color:#444;">
                    Name <span style="color:#e74c3c;">*</span>
                </label>
                <input type="text" name="name"
                       style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"
                       value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['username']) : ($_POST['name'] ?? ''); ?>"
                       required <?php echo $is_logged_in ? 'readonly' : ''; ?>>
            </div>
            
            <!-- EMAIL -->
            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600; color:#444;">
                    Email <span style="color:#e74c3c;">*</span>
                </label>
                <input type="email" name="email"
                       style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"
                       value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['email'] ?? '') : ($_POST['email'] ?? ''); ?>"
                       required <?php echo $is_logged_in ? 'readonly' : ''; ?>>
            </div>

            
<div style="margin-bottom:18px;">
    <label style="display:block; margin-bottom:6px; font-weight:600; color:#444;">
        Subject <span style="color:#e74c3c;">*</span>
    </label>
    <input type="text" name="subject"
           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"
           value="<?php echo $_POST['subject'] ?? ''; ?>"
           placeholder="e.g., Suggestion, Service feedback..." required>
</div>


<!-- RATING -->
<div style="margin-bottom:18px;">
    <label style="display:block; margin-bottom:6px; font-weight:600; color:#444;">
        Rating
    </label>

    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">

        <div style="display:flex; gap:6px;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                       style="display:none;"
                       <?php echo ($_POST['rating'] ?? 5) == $i ? 'checked' : ''; ?>>

                <label for="star<?php echo $i; ?>"
                       style="cursor:pointer; font-size:1.6rem; color:#ccc;"
                       onmouseover="highlightStars(<?php echo $i; ?>)"
                       onmouseout="resetStars()"
                       onclick="setRating(<?php echo $i; ?>)">
                    <i class="fas fa-star"></i>
                </label>
            <?php endfor; ?>
        </div>

        <div style="font-size:0.85rem; color:#666;">
            <span id="rating-text">Excellent</span>
        </div>
    </div>
</div>


<!-- MESSAGE -->
<div style="margin-bottom:22px;">
    <label style="display:block; margin-bottom:6px; font-weight:600; color:#444;">
        Feedback <span style="color:#e74c3c;">*</span>
    </label>

    <textarea name="message"
              style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; min-height:140px;"
              placeholder="Share your experience..."
              required oninput="updateCharCount(this)"><?php echo $_POST['message'] ?? ''; ?></textarea>

    <div style="text-align:right; font-size:0.8rem; color:#777; margin-top:5px;">
        <span id="char-count">0</span> / 1000
    </div>
</div>


<!-- BUTTON -->
<button type="submit"
        style="background:#3498db; color:white; border:none; padding:12px; border-radius:6px; font-size:1rem; font-weight:600; width:100%; cursor:pointer;">
    <i class="fas fa-paper-plane"></i> Submit Feedback
</button>

</form>
</div>


<!-- TIPS -->
<div style="background:#f5f7fa; border-radius:10px; padding:20px;">
    <h3 style="color:#2c3e50; margin-bottom:10px;">
        <i class="fas fa-lightbulb" style="color:#3498db;"></i> Tips
    </h3>

    <ul style="color:#666; margin-left:18px; line-height:1.5;">
        <li>Be specific about your experience</li>
        <li>Mention items or services clearly</li>
        <li>Suggest improvements if possible</li>
        <li>Tell us about delivery or ordering</li>
    </ul>

    <div style="margin-top:15px; border-top:1px solid #ddd; padding-top:10px;">
        <p style="font-size:0.85rem; color:#777;">
            <i class="fas fa-shield-alt"></i> Your feedback is confidential.
        </p>
        <p style="font-size:0.85rem; color:#777;">
            <i class="fas fa-clock"></i> Response within 24–48 hours.
        </p>
    </div>
</div>

</div>

<script>

let currentRating = <?php echo $_POST['rating'] ?? 5; ?>;

const ratingTexts = {
    1: 'Poor',
    2: 'Fair',
    3: 'Good',
    4: 'Very Good',
    5: 'Excellent'
};

function getStars(){
    return document.querySelectorAll('label i.fa-star');
}

function highlightStars(star) {
    const stars = getStars();
    stars.forEach((starIcon, index) => {
        starIcon.style.color = index < star ? '#3498db' : '#ddd';
    });
}

function resetStars() {
    const stars = getStars();
    stars.forEach((starIcon, index) => {
        starIcon.style.color = index < currentRating ? '#3498db' : '#ddd';
    });
}

function setRating(rating) {
    currentRating = rating;
    const text = document.getElementById('rating-text');
    if (text) text.textContent = ratingTexts[rating];
    resetStars();
}

document.addEventListener('DOMContentLoaded', function() {
    resetStars();
    const text = document.getElementById('rating-text');
    if (text) text.textContent = ratingTexts[currentRating];
});

function updateCharCount(textarea) {
    const charCount = textarea.value.length;
    const counter = document.getElementById('char-count');

    if (!counter) return;

    counter.textContent = charCount;

    if (charCount > 1000) {
        counter.style.color = '#e74c3c';
        textarea.style.borderColor = '#e74c3c';
    } else if (charCount > 800) {
        counter.style.color = '#f39c12';
        textarea.style.borderColor = '#f39c12';
    } else {
        counter.style.color = '#666';
        textarea.style.borderColor = '#ddd';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('textarea[name="message"]');
    if (textarea) {
        updateCharCount(textarea);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    if (form) {
        form.addEventListener('submit', function(e) {
            const textarea = this.querySelector('textarea[name="message"]');

            if (textarea && textarea.value.length > 1000) {
                e.preventDefault();
                alert('Please limit your feedback to 1000 characters.');
                textarea.focus();
            }
        });
    }
});

</script>

<?php include 'includes/footer.php'; ?>