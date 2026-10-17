<?php 
require_once "pdo.php";
require_once "util.php";
session_start();
if ( isset($_POST['cancel'] ) ) {
    header("Location: index.php");
    return;
}
$salt = 'XyZzy12*_';

if ( isset($_POST['email']) && isset($_POST['pass']) ) {
    unset($_SESSION['name']); 
    unset($_SESSION['user_id']); 

    $check = hash('md5', $salt.$_POST['pass']);
    $stmt = $pdo->prepare('SELECT user_id, name FROM users
                    WHERE email = :em AND password = :pw');
    $stmt->execute(array( ':em' => $_POST['email'], ':pw' => $check));                         
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ( $row !== false ) {
        $_SESSION['name'] = $row['name'];
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['success'] = "Login success";
        header("Location: index.php");
        return;
    } else {
        $_SESSION['error'] = "Incorrect password or email";
        header("Location: login.php");
        return;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Maged Khaled</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Please Log In</h1>
<?php flashMessages(); ?>
<form method="POST">
<div class="form-group">
<label for="email">User Email</label>
<input type="text" class="form-control" name="email" id="email">
</div>
<div class="form-group">
<label for="id_1723">Password</label>
<input type="password" class="form-control" name="pass" id="id_1723">
</div>
<input type="submit" class="btn btn-primary" onclick="return doValidate();" value="Log In">
<input type="submit" class="btn btn-default" name="cancel" value="Cancel">
</form>

<p style="margin-top: 20px;">
Don't have an account? <a href="signup.php">Sign Up</a>
</p>

<script>
function doValidate() {
    try {
        var addr = document.getElementById('email').value;
        var pw = document.getElementById('id_1723').value;
        if (addr == null || addr == "" || pw == null || pw == "") {
            alert("Both fields must be filled out");
            return false;
        }
        if ( addr.indexOf('@') == -1 ) {
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
