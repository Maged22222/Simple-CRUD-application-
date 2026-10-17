<?php
// enter your datbase, username ans password
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=', '', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



