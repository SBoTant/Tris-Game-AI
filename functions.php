<?php
// Include il file di configurazione per connettersi al database
include 'config.php';

// Funzione per registrare un nuovo giocatore
function registraGiocatore($username, $password) {
    global $conn;

    // Verifica se l'username esiste già
    $sql = "SELECT * FROM giocatori WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return "Username già esistente";
    }

    // Hash della password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Inserisci il nuovo giocatore nel database
    $sql = "INSERT INTO giocatori (username, password, user_type) VALUES (?, ?, 2)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        // Ottieni l'ID del nuovo giocatore appena inserito
        $id_giocatore = $conn->insert_id;

        // Inserisci un record nella tabella info_giocatori con l'ID del giocatore e i valori di default
        $sql = "INSERT INTO info_giocatori (id, partite_locali, partite_globali, partite_locali_vinte, partite_globali_vinte) VALUES (?, 0, 0, 0, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_giocatore);

        if ($stmt->execute()) {
            return "Registrazione avvenuta con successo e record in info_giocatori creato.";
        } else {
            return "Registrazione avvenuta, ma errore durante la creazione del record in info_giocatori.";
        }
    } else {
        return "Errore durante la registrazione";
    }
}

// Funzione per il login del giocatore
function loginGiocatore($username, $password) {
    global $conn;

    $sql = "SELECT * FROM giocatori WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verifica la password
        if (password_verify($password, $user['password'])) {
            return $user['id']; // Restituisce l'ID del giocatore per le sessioni
        } else {
            return "Password errata";
        }
    } else {
        return "Username non trovato";
    }
}

// Funzione per aggiornare le statistiche del giocatore
function aggiornaStatistiche($idGiocatore, $tipoPartita, $vittoria) {
    global $conn;

    if ($tipoPartita == "locale") {
        $sql = "UPDATE info_giocatori SET partite_locali = partite_locali + 1 WHERE id = ?";
        if ($vittoria) {
            $sql = "UPDATE info_giocatori SET partite_locali = partite_locali + 1, partite_locali_vinte = partite_locali_vinte + 1 WHERE id = ?";
        }
    } else if ($tipoPartita == "globale") {
        $sql = "UPDATE info_giocatori SET partite_globali = partite_globali + 1 WHERE id = ?";
        if ($vittoria) {
            $sql = "UPDATE info_giocatori SET partite_globali = partite_globali + 1, partite_globali_vinte = partite_globali_vinte + 1 WHERE id = ?";
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idGiocatore);
    $stmt->execute();
}

// Funzione per aggiornare le vittorie tra giocatori
function aggiornaVittorieGiocatori($id1, $id2, $vincitore) {
    global $conn;

    // Verifica che entrambi i giocatori esistano
    $sql = "SELECT COUNT(*) FROM giocatori WHERE id IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id1, $id2);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    if ($count != 2) {
        return "Uno o entrambi i giocatori non esistono.";
    }

    // Resto della funzione...
    $stmt->close();

    // Verifica se esiste già un record tra i due giocatori
    $sql = "SELECT * FROM vittorie_giocatori WHERE id1 = ? AND id2 = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id1, $id2);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Se non esiste un record, creane uno
        $sql = "INSERT INTO vittorie_giocatori (id1, id2, partite_fatte, partite_vinte_1, partite_vinte_2) VALUES (?, ?, 1, ?, ?)";
        $stmt = $conn->prepare($sql);

        $partite_vinte_1 = 0;
        $partite_vinte_2 = 0;

        if ($vincitore == $id1) {
            $partite_vinte_1 = 1;
        } else {
            $partite_vinte_2 = 1;
        }

        $stmt->bind_param("iiii", $id1, $id2, $partite_vinte_1, $partite_vinte_2);
    } else {
        // Aggiorna il record esistente
        $sql = "UPDATE vittorie_giocatori SET partite_fatte = partite_fatte + 1 WHERE id1 = ? AND id2 = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id1, $id2);
        $stmt->execute();

        if ($vincitore == $id1) {
            $sql = "UPDATE vittorie_giocatori SET partite_vinte_1 = partite_vinte_1 + 1 WHERE id1 = ? AND id2 = ?";
        } else {
            $sql = "UPDATE vittorie_giocatori SET partite_vinte_2 = partite_vinte_2 + 1 WHERE id1 = ? AND id2 = ?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id1, $id2);
    }

    $stmt->execute();
}
?>
