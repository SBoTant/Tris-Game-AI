<?php
// register.php
include 'config.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = registraGiocatore($username, $password);
    echo $result;
}
?>
