<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
require '../includes/validation_helper.php';

$student_id = $_SESSION['user_id'];

// Get student's section
$student_section = Validator::getStudentSection($conn, $student_id);

if (!$student_section) {
    die("Error: You are not assigned to any class/section. Please contact admin.");
}

// Fetch published events with results for student's section
$stmt_events = $conn->prepare("SELECT e.*, s.section_name,
                               er.marks, er.remarks, er.evaluated_at
                               FROM events e
                               LEFT JOIN sections s ON e.section_id = s.id
                               LEFT JOIN event_results er ON e.id = er.event_id AND er.student_id = ?
                               WHERE e.section_id = ? AND e.published = TRUE
                               ORDER BY e.event_date DESC");
$stmt_events->bind_param("ii", $student_id, $student_section);
$stmt_events->execute();
$events = $stmt_events->get_result();

// Get selected event for rankings
$selected_event_id = intval($_GET['event_id'] ?? 0);
$selected_event = null;
$rankings = [];

if ($selected_event_id > 0) {
    // Verify event belongs to student's section and is published
    $stmt_event = $conn->prepare("SELECT e.*, s.section_name FROM events e
                                  LEFT JOIN sections s ON e.section_id = s.id
                                  WHERE e.id = ? AND e.section_id = ? AND e.published = TRUE");
    $stmt_event->bind_param("ii", $selected_event_id, $student_section);
    $stmt_event->execute();
    $selected_event = $stmt_event->get_result()->fetch_assoc();
    $stmt_event->close();

    if ($selected_event) {
        // Fetch rankings for this event (section-wise only)
        $stmt_rankings = $conn->prepare("SELECT u.id, u.username, c.class_name, er.marks, er.remarks,
                                         RANK() OVER (ORDER BY er.marks DESC) as rank_position
                                         FROM event_results er
                                         JOIN users u ON er.student_id = u.id
                                         JOIN classes c ON u.class_id = c.id
                                         WHERE er.event_id = ? AND c.section_id = ? AND er.marks IS NOT NULL
                                         ORDER BY er.marks DESC, u.username ASC");
        $stmt_rankings->bind_param("ii", $selected_event_id, $student_section);
        $stmt_rankings->execute();
        $rankings = $stmt_rankings->get_result();
        $stmt_rankings->close();
    }
}

// Get section info
$stmt_section = $conn->prepare("SELECT * FROM sections WHERE id = ?");
$stmt_section->bind_param("i", $student_section);
$stmt_section->execute();
$section_info = $stmt_section->get_result()->fetch_assoc();
$stmt_section->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Results | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .section-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .section-little-flower { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #2d3436; }
        .section-dominic-savio { background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; }
        .section-alphonsa { background: linear-gradient(135deg, #a29bfe, #6c5ce7); color: white; }
        .section-st-thomas { background: linear-gradient(135deg, #fd79a8, #e84393); color: white; }
        
        .result-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .marks-display {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }
        
        .rank-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
        }
        
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: white; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #B8860B); color: white; }
        .rank-other { background: #e0e0e0; color: #333; }
        
        .section-banner {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Event Results</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <div class="section-banner">
            <h3 style="margin: 0 0 10px 0;">
                <i class="fa-solid fa-trophy"></i> <?php echo htmlspecialchars($section_info['section_name']); ?> Section Results
            </h3>
            <p style="margin: 0; opacity: 0.9;">
                <?php echo htmlspecialchars($section_info['class_range']); ?> | 
                Rankings shown for your section only
            </p>
        </div>

        <?php if (!$selected_event): ?>
            <!-- My Results List -->
            <div class="panel">
                <div class="panel-header">
                    <h3>My Event Results</h3>
                </div>

                <?php if ($events->num_rows > 0): ?>
                    <?php while($event = $events->fetch_assoc()): 
                        $section_class = 'section-' . strtolower(str_replace(' ', '-', $event['section_name']));
                    ?>
                        <div class="result-card">
                            <h4 style="margin: 0 0 10px 0;">
                                <?php echo htmlspecialchars($event['title']); ?>
                                <span class="section-badge <?php echo $section_class; ?>">
                                    <?php echo htmlspecialchars($event['section_name']); ?>
                                </span>
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #666;">
                                <i class="fa-solid fa-calendar"></i> <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                            </p>

                            <?php if ($event['marks'] !== null): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-size: 36px; font-weight: bold; color: #667eea;">
                                            <?php echo $event['marks']; ?><span style="font-size: 18px; color: #999;">/100</span>
                                        </div>
                                        <?php if ($event['remarks']): ?>
                                            <p style="margin: 10px 0 0 0; color: #555; font-style: italic;">
                                                "<?php echo htmlspecialchars($event['remarks']); ?>"
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="?event_id=<?php echo $event['id']; ?>" class="btn-primary">
                                            <i class="fa-solid fa-ranking-star"></i> View Rankings
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p style="color: #999; font-style: italic;">
                                    <i class="fa-solid fa-clock"></i> Results not yet published
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 60px; color: #999;">
                        <i class="fa-solid fa-chart-simple" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                        No published results yet. Participate in events to see your results here!
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Event Rankings -->
            <div class="panel">
                <div class="panel-header">
                    <h3>
                        <?php echo htmlspecialchars($selected_event['title']); ?> - Rankings
                        <a href="event_results_new.php" class="btn-secondary" style="float: right; padding: 8px 15px; font-size: 13px;">
                            <i class="fa-solid fa-arrow-left"></i> Back to My Results
                        </a>
                    </h3>
                </div>

                <?php if ($rankings->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Marks</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($rank = $rankings->fetch_assoc()): 
                                $rank_class = $rank['rank_position'] <= 3 ? 'rank-' . $rank['rank_position'] : 'rank-other';
                                $is_current_student = $rank['id'] == $student_id;
                            ?>
                                <tr <?php echo $is_current_student ? 'style="background: #f0f7ff;"' : ''; ?>>
                                    <td>
                                        <span class="rank-badge <?php echo $rank_class; ?>">
                                            <?php if ($rank['rank_position'] == 1): ?>
                                                <i class="fa-solid fa-trophy"></i>
                                            <?php elseif ($rank['rank_position'] == 2): ?>
                                                <i class="fa-solid fa-medal"></i>
                                            <?php elseif ($rank['rank_position'] == 3): ?>
                                                <i class="fa-solid fa-award"></i>
                                            <?php endif; ?>
                                            #<?php echo $rank['rank_position']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rank['username']); ?></strong>
                                        <?php if ($is_current_student): ?>
                                            <span style="color: #667eea; font-size: 12px; margin-left: 5px;">(You)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($rank['class_name']); ?></td>
                                    <td>
                                        <span style="font-size: 18px; font-weight: 600; color: #00b894;">
                                            <?php echo $rank['marks']; ?>/100
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($rank['remarks'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #999;">
                        No rankings available yet.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
