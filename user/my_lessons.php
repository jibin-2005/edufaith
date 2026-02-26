<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt_class = $conn->prepare("SELECT class_id FROM users WHERE id = ?");
$stmt_class->bind_param("i", $student_id);
$stmt_class->execute();
$class_data = $stmt_class->get_result()->fetch_assoc();
$class_id = $class_data['class_id'] ?? 0;
$stmt_class->close();

// Check if assignment_submissions table exists
$table_check = $conn->query("SHOW TABLES LIKE 'assignment_submissions'");
$table_exists = $table_check && $table_check->num_rows > 0;

// Fetch assignments for student's class with submission status
if ($table_exists) {
    $stmt = $conn->prepare("
        SELECT a.*, 
               asub.id as submission_id, 
               asub.status as submission_status,
               asub.submitted_at,
               asub.submission_file
        FROM assignments a
        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
        WHERE a.class_id = ?
        ORDER BY a.due_date ASC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT a.*, 
               NULL as submission_id, 
               NULL as submission_status,
               NULL as submitted_at,
               NULL as submission_file
        FROM assignments a
        WHERE a.class_id = ?
        ORDER BY a.due_date ASC
    ");
}

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

if ($table_exists) {
    $stmt->bind_param("ii", $student_id, $class_id);
} else {
    $stmt->bind_param("i", $class_id);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Lessons | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lesson-card { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
        }
        .lesson-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            margin-bottom: 12px; 
        }
        .lesson-title { 
            font-size: 18px; 
            font-weight: 600; 
            color: #333; 
            margin: 0; 
        }
        .lesson-due { 
            color: #e74c3c; 
            font-weight: bold; 
            font-size: 13px; 
        }
        .lesson-description { 
            color: #666; 
            font-size: 14px; 
            line-height: 1.5; 
            margin: 10px 0; 
        }
        .badge { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        .badge-submitted { 
            background: #d1e7dd; 
            color: #0f5132; 
        }
        .badge-pending { 
            background: #e7f3ff; 
            color: #0066cc; 
        }
        .badge-late { 
            background: #f8d7da; 
            color: #721c24; 
        }
        .submission-section { 
            background: #f9f9f9; 
            padding: 15px; 
            border-radius: 6px; 
            margin-top: 15px; 
        }
        .file-input-wrapper { 
            position: relative; 
            overflow: hidden; 
        }
        .file-input-wrapper input[type=file] { 
            position: absolute; 
            left: -9999px; 
        }
        .file-input-label { 
            background: var(--primary); 
            color: white; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            display: inline-block; 
            font-weight: 600; 
            font-size: 13px; 
        }
        .file-input-label:hover { 
            background: #2980b9; 
        }
        .btn-submit { 
            background: #2ecc71; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: 600; 
            margin-left: 10px;
        }
        .btn-submit:hover { 
            background: #27ae60; 
        }
        .submission-status { 
            padding: 12px; 
            background: #d1e7dd; 
            color: #0f5132; 
            border-radius: 6px; 
            font-size: 13px; 
            margin-top: 10px; 
        }
        .no-lessons { 
            text-align: center; 
            padding: 40px; 
            color: #999; 
        }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Lessons</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'success'): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fa-solid fa-check"></i> Assignment submitted successfully!
                </div>
            <?php elseif ($_GET['msg'] === 'error'): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fa-solid fa-exclamation"></i> <?php echo htmlspecialchars($_GET['error'] ?? 'Upload failed'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($lesson = $result->fetch_assoc()): ?>
                <div class="lesson-card">
                    <div class="lesson-header">
                        <div>
                            <h3 class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                        </div>
                        <div class="lesson-due">
                            <i class="fa-solid fa-calendar"></i>
                            <?php echo date("M j, Y", strtotime($lesson['due_date'])); ?>
                            <?php if (strtotime($lesson['due_date']) < time()): ?>
                                <span class="badge badge-late">Overdue</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="lesson-description">
                        <?php echo nl2br(htmlspecialchars($lesson['description'])); ?>
                    </div>

                    <?php if (isset($lesson['submission_required']) && $lesson['submission_required'] == 1): ?>
                        <div class="submission-section">
                            <strong><i class="fa-solid fa-file-pdf"></i> Work Submission Required</strong>
                            
                            <?php if (isset($lesson['submission_status']) && $lesson['submission_status'] === 'submitted'): ?>
                                <div class="submission-status">
                                    <i class="fa-solid fa-check-circle"></i> Submitted on <?php echo isset($lesson['submitted_at']) ? date("M j, Y g:i A", strtotime($lesson['submitted_at'])) : 'Unknown date'; ?>
                                </div>
                                <div style="margin-top: 15px;">
                                    <?php if (isset($lesson['submission_file']) && !empty($lesson['submission_file'])): ?>
                                        <a href="../uploads/assignments/submissions/<?php echo htmlspecialchars($lesson['submission_file']); ?>" style="color: #0066cc; text-decoration: none; font-size: 13px;">
                                            <i class="fa-solid fa-download"></i> View submitted file
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size: 13px; color: #666; margin: 15px 0 10px 0;">Replace with new submission:</p>
                            <?php else: ?>
                                <p style="font-size: 13px; color: #666; margin: 10px 0;">Upload your work as a PDF file:</p>
                            <?php endif; ?>

                            <form method="POST" action="../includes/submit_assignment.php" enctype="multipart/form-data" style="display: flex; align-items: center; margin-top: 10px;">
                                <input type="hidden" name="assignment_id" value="<?php echo $lesson['id']; ?>">
                                <div class="file-input-wrapper">
                                    <input type="file" name="assignment_file" id="file_<?php echo $lesson['id']; ?>" accept=".pdf" required>
                                    <label for="file_<?php echo $lesson['id']; ?>" class="file-input-label">
                                        <i class="fa-solid fa-upload"></i> Choose PDF
                                    </label>
                                </div>
                                <button type="submit" class="btn-submit">
                                    <i class="fa-solid fa-paper-plane"></i> Submit
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-lessons">
                <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                <p>No lessons assigned at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

