<?php 
require_once "pdo.php";
require_once "util.php";
session_start();

if ( isset($_POST['cancel']) ) {
    header("Location: index.php");
    return;
}

$salt = 'XyZzy12*_';

if ( isset($_POST['name']) && isset($_POST['email']) && isset($_POST['pass']) ) {
    if ( strlen(trim($_POST['name'])) < 1 || strlen(trim($_POST['email'])) < 1 || strlen(trim($_POST['pass'])) < 1 ) {
        $_SESSION['error'] = "All fields are required";
        header("Location: signup.php");
        return;
    }

    if ( strpos($_POST['email'], '@') === false ) {
        $_SESSION['error'] = "Email must have an at-sign (@)";
        header("Location: signup.php");
        return;
    }

    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :em');
    $stmt->execute(array(':em' => trim($_POST['email'])));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ( $row !== false ) {
        $_SESSION['error'] = "Email is already registered";
        header("Location: signup.php");
        return;
    }

    $pass_hash = hash('md5', $salt . $_POST['pass']);

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
    $stmt->execute(array(
        ':name' => trim($_POST['name']),
        ':email' => trim($_POST['email']),
        ':password' => $pass_hash
    ));

    $user_id = $pdo->lastInsertId();

    $_SESSION['name'] = trim($_POST['name']);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['success'] = "Account created and logged in successfully!";
    header("Location: index.php");
    return;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Maged Khaled - Sign Up</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Please Sign Up</h1>
<?php flashMessages(); ?>
<form method="POST">
<div class="form-group">
<label for="name">Name</label>
<input type="text" class="form-control" name="name" id="name">
</div>
<div class="form-group">
<label for="email">Email</label>
<input type="text" class="form-control" name="email" id="email">
</div>
<div class="form-group">
<label for="pass">Password</label>
<input type="password" class="form-control" name="pass" id="pass">
</div>
<input type="submit" class="btn btn-primary" onclick="return doValidate();" value="Sign Up">
<input type="submit" class="btn btn-default" name="cancel" value="Cancel">
</form>

<p style="margin-top: 20px;">
Already have an account? <a href="login.php">Log In</a>
</p>

<script>
function doValidate() {
    try {
        var name = document.getElementById('name').value;
        var addr = document.getElementById('email').value;
        var pw = document.getElementById('pass').value;
        if (name == null || name.trim() == "" || addr == null || addr.trim() == "" || pw == null || pw == "") {
            alert("All fields must be filled out");
            return false;
        }
        if (addr.indexOf('@') == -1) {
            alert("Invalid email address");
            return false;
        }
        return true;
    } catch(e) {
        return false;
    }
}
</script>
</div>
</body>
</html>
