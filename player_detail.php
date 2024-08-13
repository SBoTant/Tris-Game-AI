<?php
session_start(); // Avvia la sessione

// Controlla se l'utente ha effettuato il login
if (!isset($_SESSION['user_id'])) {
    // Se non è loggato, reindirizza alla pagina di login
    header("Location: index.html");
    exit();
}

include 'config.php';

// Recupera le informazioni del giocatore dal database
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM info_giocatori WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $playerInfo = $result->fetch_assoc();
} else {
    echo "Errore nel recupero delle informazioni del giocatore.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Giocatore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            min-width: 300px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            color: #555;
        }
        .button-container {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            text-decoration: none; /* Assicura che il link non abbia sottolineature */
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Partite Locali Giocate: <?php echo $playerInfo['partite_locali']; ?></p>
        <p>Partite Locali Vinte: <?php echo $playerInfo['partite_locali_vinte']; ?></p>
        <p>Partite Globali Giocate: <?php echo $playerInfo['partite_globali']; ?></p>
        <p>Partite Globali Vinte: <?php echo $playerInfo['partite_globali_vinte']; ?></p>

        <div class="button-container">
            <!-- Bottone per andare alla pagina della partita locale -->
            <a href="local_game.php" class="button">Partita Locale</a>
            
            <!-- Bottone per la partita globale (potrebbe essere implementato in futuro) -->
            <a href="#" class="button">Partita Globale</a>
        </div>
    </div>
</body>
</html>
