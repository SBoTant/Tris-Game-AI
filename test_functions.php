<?php
include 'config.php';
include 'functions.php';

// Test della registrazione
echo registraGiocatore('nuovo_utente', 'password123');

// Test del login
$idGiocatore = loginGiocatore('nuovo_utente', 'password123');
if (is_numeric($idGiocatore)) {
    echo "Login riuscito, ID Giocatore: " . $idGiocatore;
} else {
    echo $idGiocatore;
}

// Test dell'aggiornamento delle statistiche
aggiornaStatistiche($idGiocatore, "locale", true);

// Test dell'aggiornamento delle vittorie tra giocatori
aggiornaVittorieGiocatori($idGiocatore, 2, $idGiocatore);
?>
