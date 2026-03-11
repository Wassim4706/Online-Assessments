<?php
    session_start();
    require_once '../database.php';
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../LOGIN-REGISTER/login.php');
        exit();
    }
    $user_id = intval($_SESSION['user_id']);
    if (!isset($_POST['assessment_id']) || !isset($_POST['assessment_result_id'])) {
        die('Invalid submission.');
    }
    $assessment_id = intval($_POST['assessment_id']);
    $assessment_result_id = intval($_POST['assessment_result_id']);
    $stmt = $dbh->prepare('SELECT * FROM assessments WHERE id = ?');
    $stmt->execute([$assessment_id]);
    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$assessment) {
        die('Assessment not found');
    }
    $duration_minutes = intval($assessment['duration'] ?? 30);
    $stmt = $dbh->prepare('SELECT * FROM assessment_results WHERE id = ? AND student_id = ? AND assessment_id = ?');
    $stmt->execute([$assessment_result_id, $user_id, $assessment_id]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        die('Assessment attempt not found or does not belong to you');
    }
    $stmt = $dbh->prepare("SELECT * FROM assessment_results 
                            WHERE student_id = ? 
                            AND assessment_id = ? 
                            AND status = 'completed'
                        ");
    $stmt->execute([$user_id, $assessment_id]);
    if ($stmt->fetch()) {
        die('You have already completed this assessment. Multiple attempts are not allowed');
    }
    $started_at = new DateTime($attempt['started_at']);
    $now = new DateTime();
    $elapsed_seconds = $now->getTimestamp() - $started_at->getTimestamp();
    $allowed_seconds = $duration_minutes * 60;
    $timed_out = $elapsed_seconds > $allowed_seconds;
    $stmt = $dbh->prepare('SELECT * FROM questions WHERE assessment_id = ? ORDER BY id');
    $stmt->execute([$assessment_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dbh->beginTransaction();
    try {
        $total_marks = 0.0;
        $correct_count = 0;
        $insertAnswerStmt = $dbh->prepare("INSERT INTO student_answers 
                                            (student_id, question_id, selected_option_id, marks_obtained, answered_at)
                                            VALUES (?, ?, ?, ?, ?)
                                        ");
        $optionStmt = $dbh->prepare('SELECT * FROM options WHERE id = ? LIMIT 1');
        foreach ($questions as $q) {
            $qid = $q['id'];
            $field = 'q' . $qid;
            $selected_option_id = isset($_POST[$field]) && is_numeric($_POST[$field]) ? intval($_POST[$field]) : null;
            $marks_obtained = 0.0;
            if ($selected_option_id) {
                $optionStmt->execute([$selected_option_id]);
                $opt = $optionStmt->fetch(PDO::FETCH_ASSOC);
                if ($opt && intval($opt['question_id']) === intval($qid)) {
                    if (!empty($opt['is_correct'])) {
                        $marks_obtained = floatval($q['marks']);
                        $correct_count++;
                        $total_marks += $marks_obtained;
                    }
                } else {
                    $selected_option_id = null;
                }
            }
            $answered_at = (new DateTime())->format('Y-m-d H:i:s');
            $insertAnswerStmt->execute([
                $user_id,
                $qid,
                $selected_option_id,
                $marks_obtained,
                $answered_at
            ]);
        }
        $updateStmt = $dbh->prepare('UPDATE assessment_results SET total_marks_obtained = ?, status = ?, completed_at = ? WHERE id = ?');
        $completed_at = (new DateTime())->format('Y-m-d H:i:s');
        $updateStmt->execute([$total_marks, 'completed', $completed_at, $assessment_result_id]);
        $dbh->commit();
    } catch (Exception $e) {
        $dbh->rollBack();
        die('Error saving answers: ' . $e->getMessage());
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Results</title>
    <link rel="stylesheet" href="../CSS/start_test.css?v=<?= time(); ?>">
</head>
<body>
    <div class="test-container">
        <h2>Results for "<?= $assessment['name']; ?>"</h2>
        <p><b>Correct answers:</b> <?= intval($correct_count) ?> of <?= count($questions) ?></p>
        <p><b>Total marks obtained:</b> <?= number_format($total_marks, 2) ?> out of <?= number_format($assessment['total_marks'] ?? 5, 2) ?></p>
        <?php if ($timed_out): ?>
            <p style="color: darkorange;"><strong>Note:</strong> Your time expired; submission was recorded at <?= htmlspecialchars($completed_at) ?>.</p>
        <?php endif; ?>
        <p><a href="student_page.php">Back to Your Page</a></p>
    </div>
</body>
</html>