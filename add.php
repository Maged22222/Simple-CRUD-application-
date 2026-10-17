<?php
require_once "pdo.php";
require_once "util.php";
session_start();

if ( ! isset($_SESSION['user_id']) && ! isset($_SESSION['name']) ) {
    die("ACCESS DENIED");
}

if ( isset($_POST['cancel']) ) {
    header('Location: index.php');
    return;
}

if ( isset($_POST['first_name']) && isset($_POST['last_name']) 
     && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary']) ) {
    
    $msg = validateProfile();
    if ( is_string($msg) ) {
        $_SESSION['error'] = $msg;
        header("Location: add.php");
        return;
    }

    $msg = validatePos();
    if ( is_string($msg) ) {
        $_SESSION['error'] = $msg;
        header("Location: add.php");
        return;
    }

    $msg = validateEdu();
    if ( is_string($msg) ) {
        $_SESSION['error'] = $msg;
        header("Location: add.php");
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO Profile
          (user_id, first_name, last_name, email, headline, summary)
          VALUES ( :uid, :fn, :ln, :em, :he, :su)');

    $stmt->execute(array(
        ':uid' => $_SESSION['user_id'],
        ':fn' => trim($_POST['first_name']),
        ':ln' => trim($_POST['last_name']),
        ':em' => trim($_POST['email']),
        ':he' => trim($_POST['headline']),
        ':su' => trim($_POST['summary'])
    ));
    $profile_id = $pdo->lastInsertId();

    insertPositions($pdo, $profile_id);
    insertEducations($pdo, $profile_id);
    $_SESSION['success'] = "Record added";
    header("Location: index.php");
    return;
}

$schools = getSchools($pdo);
$school_options = '<option value="">-- Select School --</option>';
foreach ($schools as $sch) {
    $s_name = htmlentities($sch['name']);
    $school_options .= '<option value="' . $s_name . '">' . $s_name . '</option>';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Profile - <?= htmlentities($_SESSION['name']); ?></title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Adding Profile for <?= htmlentities($_SESSION['name']); ?></h1>
<?php flashMessages(); ?>

<form method="post">
  <div class="form-group">
    <label for="first_name">First Name:</label>
    <input type="text" class="form-control" name="first_name" id="first_name">
  </div>
  <div class="form-group">
    <label for="last_name">Last Name:</label>
    <input type="text" class="form-control" name="last_name" id="last_name">
  </div>
  <div class="form-group">
    <label for="email">Email:</label>
    <input type="text" class="form-control" name="email" id="email">
  </div>
  <div class="form-group">
    <label for="headline">Headline:</label>
    <input type="text" class="form-control" name="headline" id="headline">
  </div>
  <div class="form-group">
    <label for="summary">Summary:</label>
    <textarea class="form-control" name="summary" id="summary" rows="4"></textarea>
  </div>

  <div class="form-group">
    <label>Education:</label>
    <input type="button" id="addEdu" class="btn btn-info btn-xs" value="+">
    <div id="edu_fields" style="margin-top:10px;"></div>
  </div>

  <div class="form-group">
    <label>Position:</label>
    <input type="button" id="addPos" class="btn btn-info btn-xs" value="+">
    <div id="position_fields" style="margin-top:10px;"></div>
  </div>

  <div class="form-group">
    <input type="submit" class="btn btn-primary" onclick="return validateForm();" value="Add">
    <input type="submit" class="btn btn-default" name="cancel" value="Cancel">
  </div>
</form>

<script id="edu-template" type="text/template">
  <div id="edu@COUNT@" class="entry-card">
    <div class="row">
      <div class="col-sm-3 form-group">
        <label>Year:</label>
        <input type="text" class="form-control" name="edu_year@COUNT@" id="edu_year@COUNT@">
      </div>
      <div class="col-sm-7 form-group">
        <label>School:</label>
        <select class="form-control" name="edu_school@COUNT@" id="edu_school@COUNT@">
          <?= $school_options ?>
        </select>
      </div>
      <div class="col-sm-2 form-group" style="padding-top:25px;">
        <input type="button" class="btn btn-danger btn-sm" value="Remove" onclick="$('#edu@COUNT@').remove();return false;">
      </div>
    </div>
  </div>
</script>

<script id="pos-template" type="text/template">
  <div id="position@COUNT@" class="entry-card">
    <div class="row">
      <div class="col-sm-3 form-group">
        <label>Year:</label>
        <input type="text" class="form-control" name="year@COUNT@" id="year@COUNT@">
      </div>
      <div class="col-sm-7 form-group">
        <label>Description:</label>
        <textarea class="form-control" name="desc@COUNT@" id="desc@COUNT@" rows="2"></textarea>
      </div>
      <div class="col-sm-2 form-group" style="padding-top:25px;">
        <input type="button" class="btn btn-danger btn-sm" value="Remove" onclick="$('#position@COUNT@').remove();return false;">
      </div>
    </div>
  </div>
</script>

<script>
var countPos = 0;
var countEdu = 0;

$(document).ready(function(){
    $('#addPos').click(function(event){
        event.preventDefault();
        if ( countPos >= 9 ) {
            alert("Maximum of nine position entries exceeded");
            return;
        }
        countPos++;
        var source = $("#pos-template").html();
        $('#position_fields').append(source.replace(/@COUNT@/g, countPos));
    });

    $('#addEdu').click(function(event){
        event.preventDefault();
        if ( countEdu >= 9 ) {
            alert("Maximum of nine education entries exceeded");
            return;
        }
        countEdu++;
        var source = $("#edu-template").html();
        $('#edu_fields').append(source.replace(/@COUNT@/g, countEdu));
    });
});

function validateForm() {
    try {
        var fn = document.getElementById('first_name').value.trim();
        var ln = document.getElementById('last_name').value.trim();
        var em = document.getElementById('email').value.trim();
        var he = document.getElementById('headline').value.trim();
        var su = document.getElementById('summary').value.trim();

        if (fn == '' || ln == '' || em == '' || he == '' || su == '') {
            alert('All fields are required');
            return false;
        }
        if (em.indexOf('@') == -1) {
            alert('Invalid email address');
            return false;
        }
        for (var i = 1; i <= 9; i++) {
            var yr = document.getElementById('year' + i);
            var ds = document.getElementById('desc' + i);
            if (yr && ds) {
                if (yr.value.trim() == '' || ds.value.trim() == '') {
                    alert('All fields are required');
                    return false;
                }
                if (isNaN(yr.value.trim()) || yr.value.trim().length == 0) {
                    alert('Position year must be numeric');
                    return false;
                }
            }
            var eyr = document.getElementById('edu_year' + i);
            var esc = document.getElementById('edu_school' + i);
            if (eyr && esc) {
                if (eyr.value.trim() == '' || esc.value.trim() == '') {
                    alert('All fields are required');
                    return false;
                }
                if (isNaN(eyr.value.trim()) || eyr.value.trim().length == 0) {
                    alert('Education year must be numeric');
                    return false;
                }
            }
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
