<?php
session_start();

include 'config.php';

if (isset($_POST['result'])) {
    $result = $_POST['result'];
    $userId = $_SESSION['user_id'];

    // Aggiorna il numero di partite locali giocate
    $sql = "UPDATE info_giocatori SET partite_locali = partite_locali + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Se il giocatore ha vinto, aggiorna anche il numero di vittorie
    if ($result === 'player') {
        $sql = "UPDATE info_giocatori SET partite_locali_vinte = partite_locali_vinte + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    // Restituisci i dati aggiornati al client
    $sql = "SELECT partite_locali, partite_locali_vinte FROM info_giocatori WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode($result);
} else {
    echo "Errore: dati non validi.";
}
?>
