<?php
require_once "pdo.php";
require_once "util.php";
session_start();



if ( ! isset($_SESSION['user_id']) && ! isset($_SESSION['name']) ) {
    die("ACCESS DENIED");
}

if ( ! isset($_GET['profile_id']) && ! isset($_POST['profile_id']) ) {
    $_SESSION['error'] = "Missing profile_id";
    header('Location: index.php');
    return;
}

if ( isset($_POST['delete']) && isset($_POST['profile_id']) ) {
    $stmt = $pdo->prepare("SELECT profile_id FROM Profile WHERE profile_id = :pid AND user_id = :uid");
    $stmt->execute(array(
        ':pid' => $_POST['profile_id'],
        ':uid' => $_SESSION['user_id']
    ));
    $check = $stmt->fetch(PDO::FETCH_ASSOC);

    if ( $check !== false ) {
        $stmt = $pdo->prepare("DELETE FROM Position WHERE profile_id = :pid");
        $stmt->execute(array(':pid' => $_POST['profile_id']));

        $stmt = $pdo->prepare("DELETE FROM Education WHERE profile_id = :pid");
        $stmt->execute(array(':pid' => $_POST['profile_id']));

        $stmt = $pdo->prepare("DELETE FROM Profile WHERE profile_id = :zip AND user_id = :uid");
        $stmt->execute(array(
            ':zip' => $_POST['profile_id'],
            ':uid' => $_SESSION['user_id']
        ));
        $_SESSION['success'] = 'Record deleted';
    } else {
        $_SESSION['error'] = 'Could not delete record';
    }
    header('Location: index.php');
    return;
}

$stmt = $pdo->prepare("SELECT first_name, last_name, profile_id FROM Profile WHERE profile_id = :xyz AND user_id = :uid");
$stmt->execute(array(
    ":xyz" => $_GET['profile_id'],
    ":uid" => $_SESSION['user_id']
));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Could not load profile';
    header('Location: index.php');
    return;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Delete Profile</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Deleting Profile</h1>
<?php flashMessages(); ?>
<div class="panel panel-danger">
  <div class="panel-heading">
    <h3 class="panel-title">Confirm Deletion</h3>
  </div>
  <div class="panel-body">
    <p><strong>First Name:</strong> <?= htmlentities($row['first_name']) ?></p>
    <p><strong>Last Name:</strong> <?= htmlentities($row['last_name']) ?></p>
    <p class="text-danger">Are you sure you want to delete this record? This action cannot be undone.</p>
  </div>
</div>
<form method="post">
<input type="hidden" name="profile_id" value="<?= $row['profile_id'] ?>">
<input type="submit" class="btn btn-danger" value="Delete" onclick="return confirm('Do you really want to delete this profile?');" name="delete">
<a href="index.php" class="btn btn-default">Cancel</a>
</form>
</div>
</body>
</html>