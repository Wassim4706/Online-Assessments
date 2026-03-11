<?php
    session_start();
    if (isset($_SESSION["user_id"])) {
        if($_SESSION["role"] == "instructor"){
            $r = "../PAGES/instructor_page.php";
        }else{
            $r = "../PAGES/student_page.php";
        }
        header("Location: $r");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Form</title>
        <link rel="stylesheet" href="../CSS/login_register.css?v=<?= time() ?>">
    </head>
    <body>
        <div class="container">
            <h1>Register</h1>
            <?php
                if (isset($_POST["submit"])) {
                    require_once "../database.php";
                    $fullName = $_POST["fullname"];
                    $dob = $_POST["dob"];
                    $role = $_POST["role"];
                    $email = $_POST["email"];
                    $password = $_POST["password"];
                    $passwordRepeat = $_POST["repeat_password"];
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $errors = [];
                    $current_year = null;
                    $group = null;
                    if ($role == "student") {
                        $current_year = strtolower(trim($_POST["current-year"]));
                        $group = $_POST["group"];
                    }
                    $module_id = null;
                    if ($role == "instructor") {
                        $module_id = $_POST["module_id"];
                    }
                    if (empty($fullName) || empty($dob) || empty($role) || empty($email) || empty($password) || empty($passwordRepeat)) {
                        $errors[] = "All required fields must be filled";
                    }
                    if ($role == "student" && (empty($current_year) || empty($group))) {
                        $errors[] = "Students must provide year and group";
                    }
                    if ($role == "instructor" && empty($module_id)) {
                        $errors[] = "Instructors must select a module";
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Invalid email format";
                    }
                    if ($password !== $passwordRepeat) {
                        $errors[] = "Passwords do not match";
                    }
                    if (strlen($password) < 8) {
                        $errors[] = "Password must be at least 8 characters long";
                    }
                    
                    $stmt = $dbh->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                        $errors[] = "Email already registered";
                    }

                    if (!empty($errors)) {
                        foreach ($errors as $e) {
                            echo "<div class='alert'>$e</div>";
                        }
                    } else {
                        try {
                            $dbh->beginTransaction();                            
                            $sql = "INSERT INTO users (full_name, dob, current_year, `group`, email, password, role)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute([$fullName, $dob, $current_year, $group, $email, $passwordHash, $role]);
                            $user_id = $dbh->lastInsertId();
                            if ($role == "instructor") {
                                $sql = "INSERT INTO instructors (user_id, module_id) VALUES (?, ?)";
                                $stmt = $dbh->prepare($sql);
                                $stmt->execute([$user_id, $module_id]);
                            }
                            $dbh->commit();
                            echo "<div class='alert success'>Registration successful. You can now login.</div>";
                        } catch (Exception $e) {
                            $dbh->rollBack();
                            echo "<div class='alert'>Registration failed: " . $e->getMessage() . "</div>";
                        }
                    }
                }
                require_once "../database.php";
                $modules_stmt = $dbh->query("SELECT id, name, year, semester FROM modules ORDER BY year, semester, name");
                $modules = $modules_stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <form action="registration.php" method="post">
                <div class="form">
                    <input type="text" class="input" name="fullname" placeholder="Full Name" required>
                </div>
                <div class="form">
                    <input type="date" class="input" name="dob" required>
                </div>
                <div class="form">
                    <select class="input" name="role" id="roleSelect" required>
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                    </select>
                </div>
                <div id="student" class="conditional">
                    <div class="form">
                        <input type="text" class="input" name="current-year" placeholder="ing2 / ing3-AI / ing3-SEC ...">
                    </div>
                    <div class="form">
                        <input type="number" class="input" name="group" placeholder="Group Number" min="1" max="6">
                    </div>
                </div>
                <div id="instructor" class="conditional">
                    <div class="form">
                        <select class="input" name="module_id">
                            <option value="">Select Module to Teach</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo $module['id']; ?>">
                                    <?php echo $module['name'] . " (" . $module['year'] . " - Sem " . $module['semester'] . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form">
                    <input type="email" class="input" name="email" placeholder="Email" required>
                </div>
                <div class="form">
                    <input type="password" class="input" name="password" id="pass" placeholder="Password" required>
                    <button type="button" id="toggle">Show</button>            
                </div>
                <div class="form">
                    <input type="password" class="input" name="repeat_password" id="repeat-pass" placeholder="Repeat Password" required>
                    <button type="button" id="toggle-repeat">Show</button> 
                </div>
                <button type="submit" name="submit" class="btn">Register</button>
            </form>
            <p>Already registered? <a href="login.php">Login</a></p>
        </div>
        <script>
            roleSelect = document.getElementById('roleSelect');
            roleSelect.addEventListener('change', function() {
                const role = this.value;
                student = document.getElementById('student');
                instructor = document.getElementById('instructor');
                
                if (role === 'student') {
                    student.classList.add('show');
                    instructor.classList.remove('show');
                    student.querySelectorAll('input, select').forEach(i => i.required = true);
                    instructor.querySelectorAll('input, select').forEach(i => i.required = false);
                } else if (role === 'instructor') {
                    instructor.classList.add('show');
                    student.classList.remove('show');
                    instructor.querySelectorAll('input, select').forEach(i => i.required = true);
                    student.querySelectorAll('input, select').forEach(i => i.required = false);
                } else {
                    student.classList.remove('show');
                    instructor.classList.remove('show');
                    student.querySelectorAll('input, select').forEach(i => i.required = false);
                    instructor.querySelectorAll('input, select').forEach(i => i.required = false);
                }
            });
            toggleBtn = document.getElementById('toggle');
            passInput = document.getElementById('pass');
            toggleRepeatBtn = document.getElementById('toggle-repeat');
            passRepeatInput = document.getElementById('repeat-pass');
            toggleBtn.addEventListener('click', function(){
                if(passInput.type === "password"){
                    passInput.type = "text";
                    toggleBtn.textContent = "Hide";
                }else{
                    passInput.type = "password";
                    toggleBtn.textContent = "Show";
                }
            });
            toggleRepeatBtn.addEventListener('click', function(){
                if(passRepeatInput.type === "password"){
                    passRepeatInput.type = "text";
                    toggleRepeatBtn.textContent = "Hide";
                }else{
                    passRepeatInput.type = "password";
                    toggleRepeatBtn.textContent = "Show";
                }
            });                        
        </script>
    </body>
</html>