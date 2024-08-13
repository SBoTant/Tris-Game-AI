<?php
session_start(); // Avvia la sessione
include 'config.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Chiama la funzione di login
    $userId = loginGiocatore($username, $password);

    if (is_numeric($userId)) {
        // Se il login è riuscito, salva l'ID e l'username nella sessione
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        // Reindirizza alla pagina del dettaglio del giocatore
        header("Location: player_detail.php");
        exit();
    } else {
        // Se il login fallisce, mostra un messaggio di errore
        echo "Login KO";
    }
}
?>
