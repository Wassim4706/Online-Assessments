<?php 
    session_start(); 
    require_once "../database.php"; 
    if (!isset($_SESSION["user_id"])) { 
        header("Location: ../LOGIN-REGISTER/login.php"); 
        exit(); 
    } 
    $user_id = $_SESSION["user_id"];
    $stmt = $dbh->prepare("SELECT full_name, current_year FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $full_name = $user["full_name"]; 
    $current_year = strtolower(trim($user["current_year"])); 
    $semesters = [1, 2];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Choose Module</title>
    <link rel="stylesheet" href="../CSS/student_page.css?v=<?php time(); ?>">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo $full_name; ?></h2>
        <form method="POST" action="student_page.php">
            <select name="semester" required>
                <option value="" disabled selected>Select Semester</option>
                <?php foreach ($semesters as $s) { ?>
                    <option value="<?php echo $s; ?>">Semester <?php echo $s; ?></option>
                <?php } ?>
            </select>
            <button type="submit">Load Modules</button>
        </form>
        <?php
        if (isset($_POST["semester"])) {
            $semester = intval($_POST["semester"]);
            $stmt = $dbh->prepare("SELECT * FROM modules 
                                WHERE year = ? AND semester = ?
                            ");
            $stmt->execute([$current_year, $semester]);
            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($modules) === 0) {
                echo "<p>No modules for your year/semester</p>";
            } else {
                echo '<form method="POST" action="start_test.php">';
                echo '<select name="assessment_id" id="sel" required>';
                echo '<option disabled selected>Select Assessment</option>';

                foreach ($modules as $m) {
                    $stmta = $dbh->prepare("SELECT id, name FROM assessments WHERE module_id = ?");
                    $stmta->execute([$m['id']]);
                    $assessments = $stmta->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($assessments as $a) {
                        echo '<option value="' . $a['id'] . '">'
                            . $m['name'] . ' - ' . $a['name']
                            . '</option>';
                    }
                }
                echo '</select>';
                echo '<button type="submit">Start Test</button>';
                echo '</form>';
            }
        }
        ?>
        <hr>
        <?php
        $stmt = $dbh->prepare("SELECT ar.total_marks_obtained,
                                ar.status,
                                ar.completed_at,
                                a.name AS assessment_name,
                                a.total_marks,
                                m.name AS module_name
                                FROM assessment_results ar
                                JOIN assessments a ON ar.assessment_id = a.id
                                JOIN modules m ON a.module_id = m.id
                                WHERE ar.student_id = ?
                                ORDER BY ar.completed_at DESC
                            ");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Your Completed Assessments</h3>";

        if (count($results) === 0) {
            echo "<p>You have not completed any assessments yet</p>";
        } else {
            echo "<table class='results-table'>";
            echo "<tr>
                    <th>Module</th>
                    <th>Assessment</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Completed At</th>
                </tr>";

            foreach ($results as $res) {

                $score = $res['total_marks_obtained'] . 
                        " / " . $res['total_marks'];

                echo "<tr>";
                echo "<td>" . $res['module_name'] . "</td>";
                echo "<td>" . $res['assessment_name'] . "</td>";
                echo "<td>" . $score . "</td>";
                echo "<td>" . $res['status'] . "</td>";
                echo "<td>" . $res['completed_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        ?>

        <br>
        <a href="../LOGIN-REGISTER/logout.php">Logout</a>
    </div>
</body>
</html>