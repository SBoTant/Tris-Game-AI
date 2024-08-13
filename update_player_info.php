<?php
// update_player_info.php
include 'config.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $partite_locali = $_POST['partite_locali'];
    $partite_globali = $_POST['partite_globali'];
    $partite_locali_vinte = $_POST['partite_locali_vinte'];
    $partite_globali_vinte = $_POST['partite_globali_vinte'];

    // Controllo di base: assicurarsi che l'ID del giocatore esista nella tabella
    $sql = "SELECT * FROM info_giocatori WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Aggiornamento delle informazioni del giocatore
        $sql = "UPDATE info_giocatori SET partite_locali = ?, partite_globali = ?, partite_locali_vinte = ?, partite_globali_vinte = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiii", $partite_locali, $partite_globali, $partite_locali_vinte, $partite_globali_vinte, $id);

        if ($stmt->execute()) {
            echo "Informazioni aggiornate correttamente per il giocatore con ID $id.";
        } else {
            echo "Errore durante l'aggiornamento delle informazioni.";
        }
    } else {
        echo "Giocatore non trovato.";
    }
}
?>
