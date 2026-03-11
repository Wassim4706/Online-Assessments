<?php
    session_start();
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "instructor") {
        header("Location: ../LOGIN-REGISTER/login.php");
        exit();
    }

    require_once "../database.php";
    $stmt = $dbh->prepare("SELECT id FROM instructors WHERE user_id = ?");
    $stmt->execute([$_SESSION["user_id"]]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$instructor) {
        die("Instructor not found");
    }
    $action = $_REQUEST["action"] ?? "";
    try {
        switch ($action) {            
            case "add_assessment":
                $name = $_POST["name"];
                $total_marks = $_POST["total_marks"];
                $duration = $_POST["duration"];
                $module_id = $_POST["module_id"];
                $instructor_id = $_POST["instructor_id"];
                $sql = "INSERT INTO assessments (module_id, instructor_id, name, total_marks, duration) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$module_id, $instructor_id, $name, $total_marks, $duration]);
                header("Location: instructor_page.php?success=assessment_added");
                break;
            case "delete_assessment":
                $id = $_GET["id"];                
                $stmt = $dbh->prepare("SELECT id FROM assessments WHERE id = ? AND instructor_id = ?");
                $stmt->execute([$id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }
                $stmt = $dbh->prepare("DELETE FROM assessments WHERE id = ?");
                $stmt->execute([$id]);
                
                header("Location: instructor_page.php?success=assessment_deleted");
                break;
            case "add_question":
                $assessment_id = $_POST["assessment_id"];
                $text = $_POST["text"];
                $type = $_POST["type"];
                $marks = $_POST["marks"];                
                $stmt = $dbh->prepare("SELECT id FROM assessments WHERE id = ? AND instructor_id = ?");
                $stmt->execute([$assessment_id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }
                $sql = "INSERT INTO questions (assessment_id, text, type, marks) VALUES (?, ?, ?, ?)";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$assessment_id, $text, $type, $marks]);
                $question_id = $dbh->lastInsertId();
                
                header("Location: add_options.php?question_id=" . $question_id);
                break;
            case "edit_question":
                $id = $_GET["id"];
                $text = $_GET["text"];
                $type = $_GET["type"];
                $marks = $_GET["marks"];                
                $stmt = $dbh->prepare("SELECT q.id FROM questions q 
                                    JOIN assessments a ON q.assessment_id = a.id 
                                    WHERE q.id = ? AND a.instructor_id = ?");
                $stmt->execute([$id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }
                $sql = "UPDATE questions SET text = ?, type = ?, marks = ? WHERE id = ?";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$text, $type, $marks, $id]);
                header("Location: instructor_page.php?success=question_updated");
                break;
            case "delete_question":
                $id = $_GET["id"];
                $stmt = $dbh->prepare("SELECT q.id FROM questions q 
                                    JOIN assessments a ON q.assessment_id = a.id 
                                    WHERE q.id = ? AND a.instructor_id = ?");
                $stmt->execute([$id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }
                $stmt = $dbh->prepare("DELETE FROM questions WHERE id = ?");
                $stmt->execute([$id]);
                
                header("Location: instructor_page.php?success=question_deleted");
                break;
            case "add_option":
                $question_id = $_POST["question_id"];
                $text = $_POST["text"];
                $is_correct = isset($_POST["is_correct"]) ? 1 : 0;                
                $stmt = $dbh->prepare("SELECT q.id FROM questions q 
                                    JOIN assessments a ON q.assessment_id = a.id 
                                    WHERE q.id = ? AND a.instructor_id = ?");
                $stmt->execute([$question_id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }                
                if ($is_correct) {
                    $stmt = $dbh->prepare("UPDATE options SET is_correct = 0 WHERE question_id = ?");
                    $stmt->execute([$question_id]);
                }
                $sql = "INSERT INTO options (question_id, text, is_correct) VALUES (?, ?, ?)";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$question_id, $text, $is_correct]);
                
                $redirect = $_POST["redirect"] ?? "instructor_page.php";
                header("Location: " . $redirect . "?success=option_added");
                break;
            case "edit_option":
                $id = $_GET["id"];
                $text = $_GET["text"];
                $is_correct = $_GET["is_correct"];                
                $stmt = $dbh->prepare("SELECT question_id FROM options WHERE id = ?");
                $stmt->execute([$id]);
                $option = $stmt->fetch(PDO::FETCH_ASSOC);                
                $stmt = $dbh->prepare("SELECT q.id FROM questions q 
                                    JOIN assessments a ON q.assessment_id = a.id 
                                    JOIN options o ON o.question_id = q.id
                                    WHERE o.id = ? AND a.instructor_id = ?");
                $stmt->execute([$id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }                
                if ($is_correct == 1) {
                    $stmt = $dbh->prepare("UPDATE options SET is_correct = 0 WHERE question_id = ?");
                    $stmt->execute([$option["question_id"]]);
                }
                $sql = "UPDATE options SET text = ?, is_correct = ? WHERE id = ?";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$text, $is_correct, $id]);               
                header("Location: instructor_page.php?success=option_updated");
                break;
            case "delete_option":
                $id = $_GET["id"];                
                $stmt = $dbh->prepare("SELECT o.id FROM options o
                                    JOIN questions q ON o.question_id = q.id 
                                    JOIN assessments a ON q.assessment_id = a.id 
                                    WHERE o.id = ? AND a.instructor_id = ?");
                $stmt->execute([$id, $instructor["id"]]);
                if (!$stmt->fetch()) {
                    die("Unauthorized");
                }               
                $stmt = $dbh->prepare("DELETE FROM options WHERE id = ?");
                $stmt->execute([$id]);               
                header("Location: instructor_page.php?success=option_deleted");
                break;
            default:
                header("Location: instructor_page.php?error=invalid_action");
        }
    } catch (Exception $e) {
        header("Location: instructor_page.php?error=" . urlencode($e->getMessage()));
    }
?>