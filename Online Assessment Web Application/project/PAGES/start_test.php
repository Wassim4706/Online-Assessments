<?php
    session_start();
    require_once '../database.php';
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../LOGIN-REGISTER/login.php');
        exit();
    }
    $user_id = intval($_SESSION['user_id']);
    if (!isset($_POST['assessment_id']) && !isset($_GET['assessment_id'])) {
        die('No assessment selected');
    }

    if(isset($_POST['assessment_id'])){
        $assessment_id = intval($_POST['assessment_id']);
    }else{
        $assessment_id = intval($_GET['assessment_id']);
    }

    $stmt = $dbh->prepare("SELECT * FROM assessments WHERE id = ? LIMIT 1");
    $stmt->execute([$assessment_id]);
    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assessment) {
        die('Assessment not found');
    }

    $duration_minutes = intval($assessment['duration'] ?? 30);
    $stmt = $dbh->prepare("SELECT * FROM assessment_results 
                            WHERE student_id = ? 
                            AND assessment_id = ? 
                            AND status = 'completed'
                        ");
    $stmt->execute([$user_id, $assessment_id]);
    if ($stmt->fetch()) {
        die("<h3>You have already completed this assessment. Multiple attempts are NOT allowed</h3>");
    }
    $stmt = $dbh->prepare("SELECT * FROM assessment_results
                            WHERE student_id = ?
                            AND assessment_id = ?
                            AND status = 'answering'
                            ORDER BY id DESC
                            LIMIT 1
                        ");
    $stmt->execute([$user_id, $assessment_id]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attempt) {
        $assessment_result_id = $attempt['id'];
        $started_at = $attempt['started_at'];
    } else {
        $current_time = (new DateTime())->format('Y-m-d H:i:s');
        $stmt = $dbh->prepare("INSERT INTO assessment_results
                                (student_id, assessment_id, total_marks_obtained, status, started_at)
                                VALUES (?, ?, 0, 'answering', ?)
                            ");
        $stmt->execute([$user_id, $assessment_id, $current_time]);

        $assessment_result_id = $dbh->lastInsertId();
        $started_at = $current_time;
    }
    $started_dt = new DateTime($started_at);
    $now_dt = new DateTime();
    $elapsed = $now_dt->getTimestamp() - $started_dt->getTimestamp();
    $remaining_seconds = $duration_minutes * 60 - $elapsed;

    if ($remaining_seconds <= 0) {
        die("<h3>Your time for this assessment has expired</h3>");
    }
    $stmt = $dbh->prepare("SELECT * FROM questions 
                            WHERE assessment_id = ?
                            ORDER BY id
                        ");
    $stmt->execute([$assessment_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start Test - <?= $assessment['name']; ?></title>
    <link rel="stylesheet" href="../CSS/start_test.css?v=<?= time(); ?>">
    <style>
        #timer { font-size: 1.25rem; font-weight: bold; }
        .time-up { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="test-container">
        <h2>Test: <?= $assessment['name']; ?></h2>
        <p>Duration: <?= intval($duration_minutes) ?> minutes</p>
        <div id="timer">Loading timer...</div>
        <form id="testForm" method="POST" action="submit_test.php">
            <input type="hidden" name="assessment_id" value="<?= $assessment_id ?>">
            <input type="hidden" name="assessment_result_id" value="<?= $assessment_result_id ?>">
            <?php
                $q_num = 1;
                foreach ($questions as $q):
            ?>
                <div class="question-box">
                    <p><b><?= $q_num++ ?>. <?= $q['text']; ?></b></p>
                    <?php
                        $stmt = $dbh->prepare('SELECT * FROM options WHERE question_id = ? ORDER BY id');
                        $stmt->execute([$q['id']]);
                        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($options as $opt):
                    ?>
                        <label>
                            <input type="radio" name="q<?= $q['id'] ?>" value="<?= $opt['id'] ?>">
                            <?= $opt['text']; ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <div style="text-align: center; margin-top: 1rem;">
                <button class="btn" type="submit" id="submitBtn">Submit Test</button>
            </div>
        </form>
    </div>
    <script>
        startedAt = new Date('<?= addslashes($started_at) ?>');
        durationSeconds = <?= $duration_minutes * 60 ?>;
        assessmentResultId = '<?= $assessment_result_id ?>';
        function pad(n){ return n < 10 ? '0' + n : n; }
        function updateTimer(){
            now = new Date();
            elapsed = Math.floor((now - startedAt) / 1000);
            remaining = durationSeconds - elapsed;
            timerDiv = document.getElementById('timer');
            if (remaining <= 0){
                timerDiv.innerHTML = '<span class="time-up">Time is up. Submitting...</span>';
                disableInputs();
                setTimeout(()=> document.getElementById('testForm').submit(), 1500);
                clearInterval(timerInterval);
                return;
            }
            mm = Math.floor(remaining / 60);
            ss = remaining % 60;
            timerDiv.textContent = pad(mm) + ':' + pad(ss);
        }
        function disableInputs(){
            document.querySelectorAll('#testForm input, #testForm button').forEach(el=> el.disabled = true);
        }
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html>