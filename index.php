<?php
require_once "pdo.php";
require_once "util.php";
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<title>Maged Khaled</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Resume Registry</h1>
<?php
flashMessages();
if ( ! isset($_SESSION['user_id']) && ! isset($_SESSION['name']) ) {
?>
  <div class="jumbotron text-center" style="background:#f8f9fa; margin-top:20px; border:1px solid #e9ecef; border-radius:6px; padding:30px;">
    <h2>Welcome</h2>
    <p>Please log in or sign up to view and manage entries.</p>
    <p>
      <a href="login.php" class="btn btn-primary btn-lg">Log In</a>
      <a href="signup.php" class="btn btn-success btn-lg">Sign Up</a>
    </p>
  </div>
<?php } else {
    $stmt = $pdo->prepare("SELECT first_name, last_name, headline, profile_id FROM Profile WHERE user_id = :uid");
    $stmt->execute(array(':uid' => $_SESSION['user_id']));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ( empty($rows) ) {
        echo '<div class="alert alert-info">No rows found</div>' . "\n";
    } else {
?>
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>Name</th>
        <th>Headline</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $rows as $row ) { ?>
      <tr>
        <td>
          <a href="view.php?profile_id=<?= $row['profile_id'] ?>">
            <?= htmlentities($row['first_name'] . ' ' . $row['last_name']) ?>
          </a>
        </td>
        <td><?= htmlentities($row['headline']) ?></td>
        <td>
          <a href="edit.php?profile_id=<?= $row['profile_id'] ?>" class="btn btn-xs btn-primary">Edit</a>
          <a href="delete.php?profile_id=<?= $row['profile_id'] ?>" class="btn btn-xs btn-danger">Delete</a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
<?php } ?>
  <p>
    <a href="add.php" class="btn btn-success">Add New Entry</a>
    <a href="logout.php" class="btn btn-default">Log Out</a>
  </p>
<?php } ?>
</div>
</body>
</html>
