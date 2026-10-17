<?php
require_once "pdo.php";
require_once "util.php";
session_start();

if ( ! isset($_SESSION['user_id']) && ! isset($_SESSION['name']) ) {
    die("ACCESS DENIED");
}

if ( ! isset($_GET['profile_id']) ) {
    $_SESSION['error'] = 'Missing profile_id';
    header('Location: index.php');
    return;
}

$stmt = $pdo->prepare("SELECT * FROM Profile WHERE profile_id = :xyz AND user_id = :uid");
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

$positions = loadPos($pdo, $_GET['profile_id']);
$educations = loadEdu($pdo, $_GET['profile_id']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Profile Information</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Profile Information</h1>
<?php flashMessages(); ?>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title"><?= htmlentities($row['first_name'] . ' ' . $row['last_name']) ?></h3>
  </div>
  <div class="panel-body">
    <p><strong>First Name:</strong> <?= htmlentities($row['first_name']) ?></p>
    <p><strong>Last Name:</strong> <?= htmlentities($row['last_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlentities($row['email']) ?></p>
    <p><strong>Headline:</strong> <?= htmlentities($row['headline']) ?></p>
    <p><strong>Summary:</strong></p>
    <p><?= nl2br(htmlentities($row['summary'])) ?></p>

    <?php if ( ! empty($educations) ) { ?>
      <p><strong>Education:</strong></p>
      <ul>
        <?php foreach ( $educations as $education ) { ?>
          <li><?= htmlentities($education['year']) ?>: <?= htmlentities($education['name']) ?></li>
        <?php } ?>
      </ul>
    <?php } ?>

    <?php if ( ! empty($positions) ) { ?>
      <p><strong>Position:</strong></p>
      <ul>
        <?php foreach ( $positions as $position ) { ?>
          <li><?= htmlentities($position['year']) ?>: <?= htmlentities($position['description']) ?></li>
        <?php } ?>
      </ul>
    <?php } ?>
  </div>
</div>

<p>
<a href="index.php" class="btn btn-primary">Done</a>
</p>
</div>
</body>
</html>
