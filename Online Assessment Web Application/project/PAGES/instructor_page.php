<?php
    session_start();
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "instructor") {
        header("Location: ../LOGIN-REGISTER/login.php");
        exit();
    }
    require_once "../database.php";
    $stmt = $dbh->prepare("SELECT i.*, m.name AS module_name
                            FROM instructors i
                            JOIN modules m ON i.module_id = m.id
                            WHERE i.user_id = ?       
                        ");
    $stmt->execute([$_SESSION["user_id"]]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$instructor){
        header("Location: ../LOGIN-REGISTER/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Instructor Dashboard</title>
        <link rel="stylesheet" href="../CSS/instructor_page.css?v=<?= time() ?>">
    </head>
    <body>
        <div class="sidebar">
            <h3>Instructor</h3>
            <small><?= $_SESSION["full_name"] ?></small><br>
            <small><?= $instructor["module_name"] ?></small>
            <hr>
            <a href="#" onclick="showSection('overview')">Overview</a>
            <a href="#" onclick="showSection('assessments')">Assessments</a>
            <a href="#" onclick="showSection('questions')">Questions</a>
            <a href="#" onclick="showSection('results')">Results</a>
            <a href="#" onclick="showSection('answers')">Answers</a>
            <hr>
            <a href="../LOGIN-REGISTER/logout.php">Logout</a>
        </div>
        <div class="main">
            <div id="overview" class="section">
                <h2>Overview</h2>
                <?php
                $stmt = $dbh->prepare("SELECT COUNT(DISTINCT a.id) assessments,
                                        COUNT(DISTINCT q.id) questions
                                        FROM assessments a
                                        LEFT JOIN questions q ON a.id = q.assessment_id
                                        WHERE a.instructor_id = ?
                                    ");
                $stmt->execute([$instructor["id"]]);
                $stats = $stmt->fetch();
                ?>
                <div class="form-box">
                    <p>Total Assessments: <b><?= $stats["assessments"] ?></b></p>
                    <p>Total Questions: <b><?= $stats["questions"] ?></b></p>
                </div>
            </div>
            <div id="assessments" class="section">
                <h2>Assessments</h2>
                <button onclick="toggleForm('addAssessmentForm')">+ Create Assessment</button>
                <div id="addAssessmentForm" class="form-box" style="display:none">
                    <form method="POST" action="instructor_actions.php">
                        <input type="hidden" name="action" value="add_assessment">
                        <input type="hidden" name="instructor_id" value="<?= $instructor['id'] ?>">
                        <input type="hidden" name="module_id" value="<?= $instructor['module_id'] ?>">
                        <label>Name</label>
                        <input type="text" name="name" required>
                        <label>Total Marks</label>
                        <input type="number" name="total_marks" required>
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration" required>
                        <button type="submit">Save Assessment</button>
                    </form>
                </div>
                <?php
                    $stmt = $dbh->prepare("SELECT * FROM assessments WHERE instructor_id=?");
                    $stmt->execute([$instructor["id"]]);
                ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Marks</th>
                        <th>Duration</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($stmt as $a): ?>
                    <tr>
                        <td><?= $a["name"] ?></td>
                        <td><?= $a["total_marks"] ?></td>
                        <td><?= $a["duration"] ?></td>
                        <td>
                            <a href="instructor_actions.php?action=delete_assessment&id=<?= $a["id"] ?>"
                            onclick="return confirm('Delete this assessment?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </table>
            </div>
            <div id="questions" class="section">
                <h2>Questions</h2>
                <button onclick="toggleForm('addQuestionForm')">+ Add Question</button>
                <div id="addQuestionForm" class="form-box" style="display:none">
                    <form method="POST" action="instructor_actions.php">
                        <input type="hidden" name="action" value="add_question">
                        <label>Assessment</label>
                        <select name="assessment_id" required>
                            <?php
                                $stmt = $dbh->prepare("SELECT id,name FROM assessments WHERE instructor_id = ?");
                                $stmt->execute([$instructor["id"]]);
                                foreach ($stmt as $a):
                            ?>
                            <option value="<?= $a["id"] ?>"><?= $a["name"] ?></option>
                            <?php endforeach ?>
                        </select>
                        <label>Question</label>
                        <textarea name="text" required></textarea>
                        <label>Type</label>
                        <select name="type">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                        </select>
                        <label>Marks</label>
                        <input type="number" step="0.25" name="marks" required>
                        <button type="submit">Create Question</button>
                    </form>
                </div>
                <?php
                    $stmt = $dbh->prepare("SELECT q.*, a.name aname
                                        FROM questions q
                                        JOIN assessments a ON q.assessment_id = a.id
                                        WHERE a.instructor_id = ?
                                    ");
                    $stmt->execute([$instructor["id"]]);
                ?>
                <table>
                    <tr>
                        <th>Assessment</th>
                        <th>Question</th>
                        <th>Action</th>
                    </tr>
                <?php foreach ($stmt as $q): ?>
                    <tr>
                        <td><?= $q["aname"] ?></td>
                        <td><?= substr($q["text"], 0, 60) ?>...</td>
                        <td>
                            <a href="add_options.php?question_id=<?= $q["id"] ?>">Options</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </table>
            </div>
            <div id="results" class="section">
                <h2>Results</h2>
                <div class="form-box">
                    <p>Results page unchanged</p>
                </div>
            </div>
            <div id="answers" class="section">
                <h2>Answers</h2>
                <div class="form-box">
                    <p>Answers page unchanged</p>
                </div>
            </div>
        </div>
        <script>
            function showSection(id) {
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
                document.getElementById(id).classList.add('active');
                document.querySelector(`.sidebar a[onclick="showSection('${id}')"]`).classList.add('active');
                history.replaceState(null, null, '#' + id);
            }
            function toggleForm(id) {
                i = document.getElementById(id);
                if (i.style.display === "block") {
                    i.style.display = "none";
                } else {
                    i.style.display = "block";
                }
            }
            section = location.hash.replace("#", "") || "overview";
            showSection(section);
        </script>
    </body>
</html>