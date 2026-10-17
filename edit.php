<?php
require_once "pdo.php";
require_once "util.php";
session_start();

if ( ! isset($_SESSION['user_id']) && ! isset($_SESSION['name']) ) {
    die("ACCESS DENIED");
}

if ( ! isset($_REQUEST['profile_id']) ) {
    $_SESSION['error'] = "Missing profile_id";
    header('Location: index.php');
    return;
}

if ( isset($_POST['first_name']) && isset($_POST['last_name']) 
     && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary']) ) {

    $check_stmt = $pdo->prepare("SELECT profile_id FROM Profile WHERE profile_id = :pid AND user_id = :uid");
    $check_stmt->execute(array(':pid' => $_REQUEST['profile_id'], ':uid' => $_SESSION['user_id']));
    if ( $check_stmt->fetch() === false ) {
        $_SESSION['error'] = 'Could not load profile';
        header('Location: index.php');
        return;
    }

    $msg = validateProfile();
    if ( is_string($msg) ) {
        $_SESSION['error'] = $msg;
        header("Location: edit.php?profile_id=".$_REQUEST['profile_id']);
        return;
    }

    $msg = validatePos();
    if ( is_string($msg) ) {
        $_SESSION['error'] = $msg;
        header("Location: edit.php?profile_id=".$_REQUEST['profile_id']);
        return;
    }

    $msg = validateEdu();
    if ( is_string($msg) ) {
        $_SESSION['error'] = $msg;
        header("Location: edit.php?profile_id=".$_REQUEST['profile_id']);
        return;
    }

    $sql = "UPDATE Profile SET first_name = :first_name,
            last_name = :last_name, email = :email, headline = :headline, summary = :summary 
            WHERE profile_id = :profile_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        ':first_name' => trim($_POST['first_name']),
        ':last_name' => trim($_POST['last_name']),
        ':email' => trim($_POST['email']),
        ':headline' => trim($_POST['headline']),
        ':user_id' => $_SESSION['user_id'],
        ':profile_id' => $_REQUEST['profile_id'],
        ':summary' => trim($_POST['summary'])
    ));

    $stmt = $pdo->prepare('DELETE FROM Position WHERE profile_id=:pid');
    $stmt->execute(array(':pid' => $_REQUEST['profile_id']));
    insertPositions($pdo, $_REQUEST['profile_id']);

    $stmt = $pdo->prepare('DELETE FROM Education WHERE profile_id=:pid');
    $stmt->execute(array(':pid' => $_REQUEST['profile_id']));
    insertEducations($pdo, $_REQUEST['profile_id']);
       
    $_SESSION['success'] = 'Record edited';
    header('Location: index.php');
    return;
}

$stmt = $pdo->prepare("SELECT * FROM Profile WHERE profile_id = :xyz AND user_id = :uid");
$stmt->execute(array(":xyz" => $_REQUEST['profile_id'], ":uid" => $_SESSION['user_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Could not load profile';
    header('Location: index.php');
    return;
}

$positions = loadPos($pdo, $_REQUEST['profile_id']);
$educations = loadEdu($pdo, $_REQUEST['profile_id']);
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
<title>Edit Profile - <?= htmlentities($_SESSION['name']); ?></title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<h1>Editing Profile for <?= htmlentities($_SESSION['name']); ?></h1>
<?php flashMessages(); ?>

<form method="post">
  <input type="hidden" name="profile_id" value="<?= htmlentities($_REQUEST['profile_id']) ?>">

  <div class="form-group">
    <label for="first_name">First Name:</label>
    <input type="text" class="form-control" name="first_name" id="first_name" value="<?= htmlentities($row['first_name']) ?>">
  </div>
  <div class="form-group">
    <label for="last_name">Last Name:</label>
    <input type="text" class="form-control" name="last_name" id="last_name" value="<?= htmlentities($row['last_name']) ?>">
  </div>
  <div class="form-group">
    <label for="email">Email:</label>
    <input type="text" class="form-control" name="email" id="email" value="<?= htmlentities($row['email']) ?>">
  </div>
  <div class="form-group">
    <label for="headline">Headline:</label>
    <input type="text" class="form-control" name="headline" id="headline" value="<?= htmlentities($row['headline']) ?>">
  </div>
  <div class="form-group">
    <label for="summary">Summary:</label>
    <textarea class="form-control" name="summary" id="summary" rows="4"><?= htmlentities($row['summary']) ?></textarea>
  </div>

  <div class="form-group">
    <label>Education:</label>
    <input type="button" id="addEdu" class="btn btn-info btn-xs" value="+">
    <div id="edu_fields" style="margin-top:10px;">
      <?php
      $Edu = 0;
      foreach( $educations as $education ) {
          $Edu++;
          echo '<div id="edu' . $Edu . '" class="entry-card">' . "\n";
          echo '  <div class="row">' . "\n";
          echo '    <div class="col-sm-3 form-group">' . "\n";
          echo '      <label>Year:</label>' . "\n";
          echo '      <input type="text" class="form-control" name="edu_year' . $Edu . '" id="edu_year' . $Edu . '" value="' . htmlentities($education['year']) . '">' . "\n";
          echo '    </div>' . "\n";
          echo '    <div class="col-sm-7 form-group">' . "\n";
          echo '      <label>School:</label>' . "\n";
          echo '      <select class="form-control" name="edu_school' . $Edu . '" id="edu_school' . $Edu . '">' . "\n";
          echo '        <option value="">-- Select School --</option>' . "\n";
          foreach ($schools as $sch) {
              $sel = ($sch['name'] === $education['name']) ? ' selected' : '';
              echo '        <option value="' . htmlentities($sch['name']) . '"' . $sel . '>' . htmlentities($sch['name']) . '</option>' . "\n";
          }
          echo '      </select>' . "\n";
          echo '    </div>' . "\n";
          echo '    <div class="col-sm-2 form-group" style="padding-top:25px;">' . "\n";
          echo '      <input type="button" class="btn btn-danger btn-sm" value="Remove" onclick="$(\'#edu' . $Edu . '\').remove();return false;">' . "\n";
          echo '    </div>' . "\n";
          echo '  </div>' . "\n";
          echo '</div>' . "\n";
      }
      ?>
    </div>
  </div>

  <div class="form-group">
    <label>Position:</label>
    <input type="button" id="addPos" class="btn btn-info btn-xs" value="+">
    <div id="position_fields" style="margin-top:10px;">
      <?php
      $pos = 0;
      foreach( $positions as $position ) {
          $pos++;
          echo '<div id="position' . $pos . '" class="entry-card">' . "\n";
          echo '  <div class="row">' . "\n";
          echo '    <div class="col-sm-3 form-group">' . "\n";
          echo '      <label>Year:</label>' . "\n";
          echo '      <input type="text" class="form-control" name="year' . $pos . '" id="year' . $pos . '" value="' . htmlentities($position['year']) . '">' . "\n";
          echo '    </div>' . "\n";
          echo '    <div class="col-sm-7 form-group">' . "\n";
          echo '      <label>Description:</label>' . "\n";
          echo '      <textarea class="form-control" name="desc' . $pos . '" id="desc' . $pos . '" rows="2">' . htmlentities($position['description']) . '</textarea>' . "\n";
          echo '    </div>' . "\n";
          echo '    <div class="col-sm-2 form-group" style="padding-top:25px;">' . "\n";
          echo '      <input type="button" class="btn btn-danger btn-sm" value="Remove" onclick="$(\'#position' . $pos . '\').remove();return false;">' . "\n";
          echo '    </div>' . "\n";
          echo '  </div>' . "\n";
          echo '</div>' . "\n";
      }
      ?>
    </div>
  </div>

  <div class="form-group">
    <input type="submit" class="btn btn-primary" onclick="return validateForm();" value="Save">
    <a href="index.php" class="btn btn-default">Cancel</a>
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
var countPos = <?= $pos ?>;
var countEdu = <?= $Edu ?>;

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