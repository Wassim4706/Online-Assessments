<?php
    session_start();
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "instructor") {
        header("Location: ../LOGIN-REGISTER/login.php");
        exit();
    }
    require_once "../database.php";
    $question_id = $_GET["question_id"] ?? null;
    if (!$question_id) {
        die("Question ID required");
    }
    $stmt = $dbh->prepare("SELECT q.*, a.name as assessment_name 
                        FROM questions q 
                        JOIN assessments a ON q.assessment_id = a.id 
                        WHERE q.id = ?"
                    );
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$question) {
        die("Question not found");
    }
    $stmt = $dbh->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY id");
    $stmt->execute([$question_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Options</title>
    <link rel="stylesheet" href="../CSS/add_options.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <div class="card">
            <div>
                <h4>Add Options to Question</h4>
            </div>
            <div class="card-body">
                <div class="alert">
                    <strong>Assessment:</strong> <?php echo $question["assessment_name"]; ?><br>
                    <strong>Question:</strong> <?php echo $question["text"]; ?><br>
                    <strong>Type:</strong> <?php echo ucfirst(str_replace("_", " ", $question["type"])); ?><br>
                    <strong>Marks:</strong> <?php echo $question["marks"]; ?>
                </div>

                <?php if (isset($_GET["success"])): ?>
                    <div class="alert success">Option added successfully!</div>
                <?php endif; ?>

                <h5>Existing Options (<?php echo count($options); ?>)</h5>
                <?php if (empty($options)): ?>
                    <p>No options added yet.</p>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($options as $option): ?>
                            <li>
                                <?php echo $option["text"]; ?>
                                <?php if ($option["is_correct"]): ?>
                                    <span class="badge bg-success">Correct Answer</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="card">
                    <div>
                        <h5>Add New Option</h5>
                    </div>
                    <div class="card-body">
                        <form action="instructor_actions.php" method="POST">
                            <input type="hidden" name="action" value="add_option">
                            <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                            <input type="hidden" name="redirect" value="add_options.php?question_id=<?php echo $question_id; ?>">
                            
                            <div>
                                <label>Option Text</label>
                                <input type="text" class="form-control" name="text" required>
                            </div>
                            
                            <div>
                                <input class="input" type="checkbox" name="is_correct" id="isCorrect">
                                <label for="isCorrect">
                                    This is the correct answer
                                </label>
                            </div>
                            
                            <button type="submit" class="btn">
                                Add Option
                            </button>
                        </form>
                    </div>
                </div>

                <div>
                    <a href="instructor_page.php" class="btn">
                        Back to Dashboard
                    </a>
                    <?php if (count($options) >= 2): ?>
                        <a href="instructor_page.php" class="btn success">
                            Finish (<?php echo count($options); ?> options added)
                        </a>
                    <?php else: ?>
                        <span>Add at least 2 options to complete the question</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>