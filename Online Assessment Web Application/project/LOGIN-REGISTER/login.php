<?php
    session_start();
    if (isset($_SESSION["user_id"])) {
        if ($_SESSION["role"] == "instructor"){
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
        <title>Login Form</title>
        <link rel="stylesheet" href="../CSS/login_register.css?v=<?= time() ?>">
    </head>
    <body>
        <div class="container">
            <h1>Login</h1>
            <?php
                if (isset($_POST["login"])) {
                    require_once "../database.php";
                    $email = $_POST["email"];
                    $password = $_POST["password"];
                    $stmt = $dbh->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        if (password_verify($password, $user["password"])) {
                            $_SESSION["user_id"] = $user["id"];
                            $_SESSION["role"] = $user["role"];
                            $_SESSION["full_name"] = $user["full_name"];
                            if ($user["role"] == "instructor") {
                                header("Location: ../PAGES/instructor_page.php");
                            } else {
                                header("Location: ../PAGES/student_page.php");
                            }
                            exit();
                        } else {
                            echo "<div class='alert'>Password does not match</div>";
                        }
                    } else {
                        echo "<div class='alert'>Email not found</div>";
                    }
                }
            ?>
            <form action="login.php" method="post">
                <div class="form">
                    <input type="email" placeholder="Enter Email" name="email" class="input" required>
                </div>
                <div class="form">
                    <input type="password" placeholder="Enter Password" name="password" class="input" id="pass"required>
                    <button type="button" id="toggle">Show</button>
                </div>
                <input type="submit" value="Login" name="login" class="btn">
            </form>
            <p>Not registered? <a href="registration.php">Register</a></p>
        </div>
        <script>
            toggleBtn = document.getElementById('toggle');
            passInput = document.getElementById('pass');
            toggleBtn.addEventListener('click', function(){
                if(passInput.type === "password"){
                    passInput.type = "text";
                    toggleBtn.textContent = "Hide";
                }else{
                    passInput.type = "password";
                    toggleBtn.textContent = "Show";
                }
            });
        </script>
    </body>
</html>