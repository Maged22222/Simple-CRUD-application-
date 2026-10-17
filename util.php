<?php
function flashMessages() {
    if ( isset($_SESSION['error']) ) {
        echo('<div class="alert alert-danger">'.htmlentities($_SESSION['error'])."</div>\n");
        unset($_SESSION['error']);
    }
    if ( isset($_SESSION['success']) ) {
        echo('<div class="alert alert-success">'.htmlentities($_SESSION['success'])."</div>\n");
        unset($_SESSION['success']);
    }
}

function validateProfile() {
    if ( strlen(trim($_POST['first_name'])) == 0 || strlen(trim($_POST['last_name'])) == 0 ||
         strlen(trim($_POST['email'])) == 0 || strlen(trim($_POST['headline'])) == 0 ||
         strlen(trim($_POST['summary'])) == 0 ) {
        return "All fields are required";
    }

    if ( strpos($_POST['email'], '@') === false ) {
        return "Email address must contain @";
    }
    return true;
}

function validatePos() {
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['year'.$i]) && ! isset($_POST['desc'.$i]) ) continue;
        if ( ! isset($_POST['year'.$i]) || ! isset($_POST['desc'.$i]) ) {
            return "All fields are required";
        }
        $year = trim($_POST['year'.$i]);
        $desc = trim($_POST['desc'.$i]);
        if ( strlen($year) == 0 || strlen($desc) == 0 ) {
            return "All fields are required";
        }

        if ( ! is_numeric($year) ) {
            return "Position year must be numeric";
        }
    }
    return true;
}

function validateEdu() {
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['edu_year'.$i]) && ! isset($_POST['edu_school'.$i]) ) continue;
        if ( ! isset($_POST['edu_year'.$i]) || ! isset($_POST['edu_school'.$i]) ) {
            return "All fields are required";
        }
        $year = trim($_POST['edu_year'.$i]);
        $school = trim($_POST['edu_school'.$i]);
        if ( strlen($year) == 0 || strlen($school) == 0 ) {
            return "All fields are required";
        }

        if ( ! is_numeric($year) ) {
            return "Education year must be numeric";
        }
    }
    return true;
}

function getSchools($pdo) {
    $stmt = $pdo->query('SELECT institution_id, name FROM Institution ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function loadPos($pdo, $profile_id) {
    $stmt = $pdo->prepare('SELECT * FROM Position 
        WHERE profile_id = :prof ORDER BY rank');
    $stmt->execute(array( ':prof' => $profile_id)) ;
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function loadEdu($pdo, $profile_id) {
    $stmt = $pdo->prepare('SELECT year, name FROM Education 
        JOIN Institution 
            ON Education.institution_id = Institution.institution_id
        WHERE profile_id = :prof ORDER BY rank');
    $stmt->execute(array(':prof' => $profile_id));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertPositions($pdo, $profile_id) {
    $rank = 1;
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['year'.$i]) ) continue;
        if ( ! isset($_POST['desc'.$i]) ) continue;
        $year = trim($_POST['year'.$i]);
        $desc = trim($_POST['desc'.$i]);

        $stmt = $pdo->prepare('INSERT INTO Position
            (profile_id, rank, year, description)
            VALUES ( :pid, :rank, :year, :desc)');
        $stmt->execute(array(
            ':pid' => $profile_id,
            ':rank' => $rank,
            ':year' => $year,
            ':desc' => $desc)
        );
        $rank++;
    }
}

function insertEducations($pdo, $profile_id) {
    $rank = 1;
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['edu_year'.$i]) ) continue;
        if ( ! isset($_POST['edu_school'.$i]) ) continue;
        $year = trim($_POST['edu_year'.$i]);
        $school = trim($_POST['edu_school'.$i]);

        $institution_id = false;
        $stmt = $pdo->prepare('SELECT institution_id FROM Institution WHERE name = :name');
        $stmt->execute(array(':name' => $school));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ( $row !== false ) {
            $institution_id = $row['institution_id'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO Institution (name) VALUES (:name)');
            $stmt->execute(array(':name' => $school));
            $institution_id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('INSERT INTO Education
            (profile_id, rank, year, institution_id)
            VALUES ( :pid, :rank, :year, :iid)');
        $stmt->execute(array(
            ':pid' => $profile_id,
            ':rank' => $rank,
            ':year' => $year,
            ':iid' => $institution_id)
        );
        $rank++;
    }
}