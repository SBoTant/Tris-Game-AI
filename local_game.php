<?php
session_start();

// Controlla se l'utente ha effettuato il login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

include 'config.php';

// Recupera le informazioni del giocatore
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

// Calcolo delle vittorie dell'IA
$iaVictories = $playerInfo['partite_locali'] - $playerInfo['partite_locali_vinte'];

// Genera un nome casuale per l'IA
$iaNames = ['IA Pro', 'IA Master', 'IA Champion', 'IA Legend'];
$iaName = $iaNames[array_rand($iaNames)];

// Assegna casualmente i segni
if (rand(0, 1) == 0) {
    $_SESSION['player_sign'] = 'X';
    $_SESSION['ia_sign'] = 'O';
} else {
    $_SESSION['player_sign'] = 'O';
    $_SESSION['ia_sign'] = 'X';
}

// Decidi casualmente chi inizia
$_SESSION['current_turn'] = rand(0, 1) == 0 ? 'player' : 'ia';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gioco Locale - Tris</title>
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
            flex-direction: column;
        }
        .game-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .game-header h1 {
            margin: 0;
            font-size: 36px;
        }
        .game-header .total-games {
            font-size: 18px;
            color: #555;
        }
        .game-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 900px;
        }
        .player-info {
            width: 150px;
            text-align: center;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            padding-bottom: 120px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            margin: 0 20px;
            position: relative;
        }
        .player-info.active {
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }
        .player-info h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .player-info .wins {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }
        .sign {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .button-container {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .button-container button {
            padding: 8px;
            border: none;
            border-radius: 5px;
            margin-top: 8px;
            width: 90%;
            cursor: pointer;
        }
        .button-container button.active {
            background-color: #4CAF50;
            color: white;
        }
        .button-container button.inactive {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            grid-template-rows: repeat(3, 100px);
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .grid-item {
            width: 100px;
            height: 100px;
            background-color: #fff;
            border: 2px solid #333;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            cursor: pointer;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: border-color 0.3s;
        }
        .grid-item.player-canceled {
            border-color: blue;
        }
        .grid-item.ia-canceled {
            border-color: red;
        }
        .grid-item.player-modified {
            border-color: darkmagenta;
        }
        .grid-item.ia-modified {
            border-color: orange;
        }
        .super-tris-container {
            position: absolute;
            display: none;
            background-color: rgba(255, 255, 255, 0.95);
            border: 3px solid #333;
            z-index: 1000;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            transition: all 0.5s ease-in-out;
        }
        .super-tris-grid {
            display: grid;
            grid-template-columns: repeat(3, 80px);
            grid-template-rows: repeat(3, 80px);
            gap: 8px;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .super-tris-item {
            width: 80px;
            height: 80px;
            background-color: #fff;
            border: 2px solid #333;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            cursor: pointer;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border: 3px solid #333;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            text-align: center;
        }
        .modal.active {
            display: block;
        }
        .modal button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="game-header">
        <h1>Gioco del Tris</h1>
        <p class="total-games">Partite Totali: <span id="total-games"><?php echo $playerInfo['partite_locali']; ?></span></p>
    </div>
    
    <div class="game-container">
        <div class="player-info <?php echo $_SESSION['current_turn'] == 'player' ? 'active' : ''; ?>">
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <p class="wins">Vittorie: <span id="player-wins"><?php echo $playerInfo['partite_locali_vinte']; ?></span></p>
            <p class="sign"><?php echo $_SESSION['player_sign']; ?></p>
            <div class="button-container">
                <button id="cancellaCasella" class="active">Cancella Casella</button>
                <button id="modificaCasella" class="active">Modifica Casella</button>
                <button id="superTris" class="inactive">Super Tris</button>
            </div>
        </div>

        <div class="grid-container" id="gridContainer">
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
            <div class="grid-item"></div>
        </div>

        <div class="player-info ia-info <?php echo $_SESSION['current_turn'] == 'ia' ? 'active' : ''; ?>">
            <h2><?php echo $iaName; ?></h2>
            <p class="wins">Vittorie: <span id="ia-wins"><?php echo $iaVictories; ?></span></p>
            <p class="sign"><?php echo $_SESSION['ia_sign']; ?></p>
            <div class="button-container">
                <button class="inactive">Modifica Casella</button>
                <button class="inactive">Elimina Casella</button>
                <button class="inactive">Super Tris</button>
            </div>
        </div>
    </div>

    <!-- Super Tris Modal -->
    <div class="super-tris-container" id="superTrisContainer">
        <div class="super-tris-grid">
            <!-- Genera qui le caselle per il Super Tris -->
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
            <div class="super-tris-item"></div>
        </div>
    </div>

    <!-- Modal di vittoria -->
    <div class="modal" id="victoryModal">
        <h2 id="victoryMessage"></h2>
        <button id="newGameButton">Nuova Partita</button>
    </div>

<script>
    let currentPlayer = "<?php echo $_SESSION['current_turn']; ?>";
    let playerSign = "<?php echo $_SESSION['player_sign']; ?>";
    let iaSign = "<?php echo $_SESSION['ia_sign']; ?>";
    let gameActive = true;
    let cancellaUsed = false;
    let modificaUsed = false;
    let iaModificaUsed = false;
    let iaCancellaUsed = false;
    let superTrisEnabled = false;

    const gridItems = document.querySelectorAll('.grid-item');
    const playerWinsElement = document.getElementById('player-wins');
    const playerGamesElement = document.getElementById('total-games');
    const iaWinsElement = document.getElementById('ia-wins');
    const playerInfoElement = document.querySelector('.player-info');
    const iaInfoElement = document.querySelector('.ia-info');
    const cancellaButton = document.getElementById('cancellaCasella');
    const modificaButton = document.getElementById('modificaCasella');
    const superTrisButton = document.getElementById('superTris');
    const superTrisContainer = document.getElementById('superTrisContainer');
    const gridContainer = document.getElementById('gridContainer');
    const victoryModal = document.getElementById('victoryModal');
    const victoryMessage = document.getElementById('victoryMessage');
    const newGameButton = document.getElementById('newGameButton');

    function updateTurnHighlight() {
        if (currentPlayer === 'player') {
            playerInfoElement.classList.add('active');
            iaInfoElement.classList.remove('active');
        } else {
            playerInfoElement.classList.remove('active');
            iaInfoElement.classList.add('active');
        }
    }

    function toggleSuperTris() {
        superTrisEnabled = !superTrisEnabled;
        superTrisButton.classList.toggle('active', superTrisEnabled);
    }

    superTrisButton.addEventListener('click', () => {
        if (!superTrisEnabled) {
            superTrisEnabled = true;
            superTrisButton.classList.remove('inactive');
            superTrisButton.classList.add('active');
        } else {
            superTrisEnabled = false;
            superTrisButton.classList.remove('active');
            superTrisButton.classList.add('inactive');
        }
    });

    cancellaButton.addEventListener('click', () => {
        if (!cancellaUsed && gameActive && currentPlayer === 'player') {
            cancellaUsed = true;
            cancellaButton.classList.add('inactive');

            gridItems.forEach(item => {
                item.addEventListener('click', cancellaHandler);
            });
        }
    });

    modificaButton.addEventListener('click', () => {
        if (!modificaUsed && gameActive && currentPlayer === 'player') {
            modificaUsed = true;
            modificaButton.classList.add('inactive');

            gridItems.forEach(item => {
                item.addEventListener('click', modificaHandler);
            });
        }
    });

    function cancellaHandler(event) {
        const item = event.currentTarget;

        if (item.textContent === iaSign) {
            item.textContent = '';
            item.classList.add('player-canceled');
            rimuoviEventiCancellazione();
            currentPlayer = 'ia';
            updateTurnHighlight();
            setTimeout(iaMove, 500);
        } else if (item.textContent === '') {
            item.textContent = playerSign;
            rimuoviEventiCancellazione();
            currentPlayer = 'ia';
            updateTurnHighlight();
            setTimeout(iaMove, 500);
        }
    }

    function modificaHandler(event) {
        const item = event.currentTarget;

        if (item.textContent === iaSign || item.textContent === '') {
            item.textContent = playerSign;
            item.classList.add('player-modified');
            rimuoviEventiModifica();
            checkWinner(playerSign);
            if (gameActive) {
                currentPlayer = 'ia';
                updateTurnHighlight();
                setTimeout(iaMove, 500);
            }
        }
    }

    function rimuoviEventiCancellazione() {
        gridItems.forEach(item => {
            item.removeEventListener('click', cancellaHandler);
        });
    }

    function rimuoviEventiModifica() {
        gridItems.forEach(item => {
            item.removeEventListener('click', modificaHandler);
        });
    }

    function iaMove() {
        if (!iaCancellaUsed && Math.random() < 0.3) {
            iaCancellaUsed = true;
            const playerCells = Array.from(gridItems).filter(item => item.textContent === playerSign);

            if (playerCells.length > 0) {
                const cellToClear = playerCells[Math.floor(Math.random() * playerCells.length)];
                cellToClear.textContent = '';
                cellToClear.classList.add('ia-canceled');
                currentPlayer = 'player';
                updateTurnHighlight();
                return;
            }
        }

        if (!iaModificaUsed && Math.random() < 0.3) {
            iaModificaUsed = true;
            const playerCells = Array.from(gridItems).filter(item => item.textContent === playerSign);

            if (playerCells.length > 0) {
                const cellToModify = playerCells[Math.floor(Math.random() * playerCells.length)];
                cellToModify.textContent = iaSign;
                cellToModify.classList.add('ia-modified');
                checkWinner(iaSign);
                currentPlayer = 'player';
                updateTurnHighlight();
                return;
            }
        }

        let emptyItems = [];
        gridItems.forEach((item, index) => {
            if (item.textContent === '') {
                emptyItems.push(index);
            }
        });

        if (emptyItems.length > 0) {
            let moveIndex = emptyItems[Math.floor(Math.random() * emptyItems.length)];
            gridItems[moveIndex].textContent = iaSign;
            checkWinner(iaSign);
            if (gameActive) {
                currentPlayer = 'player';
                updateTurnHighlight();
            }
        }
    }

    gridItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            if (gameActive && item.textContent === '' && currentPlayer === 'player') {
                item.textContent = playerSign;
                checkWinner(playerSign);
                if (gameActive) {
                    currentPlayer = 'ia';
                    updateTurnHighlight();
                    setTimeout(iaMove, 500);
                }
            }
        });
    });

    function checkWinner(sign) {
        const winningCombinations = [
            [0, 1, 2],
            [3, 4, 5],
            [6, 7, 8],
            [0, 3, 6],
            [1, 4, 7],
            [2, 5, 8],
            [0, 4, 8],
            [2, 4, 6]
        ];

        let win = winningCombinations.some(combination => {
            return combination.every(index => {
                return gridItems[index].textContent === sign;
            });
        });

        if (win) {
            if (sign === iaSign && superTrisEnabled) {
                superTrisEnabled = false;  // Disattiva subito il Super Tris per evitare cicli
                activateSuperTris();
            } else {
                gameActive = false;
                showVictoryModal(sign === playerSign ? "<?php echo $_SESSION['username']; ?>" : "<?php echo $iaName; ?>");
                updateScore(sign === playerSign ? 'player' : 'ia');
            }
        } else if ([...gridItems].every(item => item.textContent !== '')) {
            gameActive = false;
            showVictoryModal('Pareggio');
            updateScore('draw');
        }
    }

    function resetSuperTrisGrid() {
        const superTrisItems = document.querySelectorAll('.super-tris-item');
        superTrisItems.forEach(item => {
            item.textContent = '';
        });
    }

    function activateSuperTris() {
        resetSuperTrisGrid();  // Resetta la griglia del Super Tris prima di iniziare una nuova partita
        const selectedCell = Array.from(gridItems).find(item => item.textContent === iaSign);
        const cellRect = selectedCell.getBoundingClientRect();

        // Posiziona e ridimensiona il contenitore del Super Tris in modo che corrisponda alla cella selezionata
        superTrisContainer.style.top = `${cellRect.top}px`;
        superTrisContainer.style.left = `${cellRect.left}px`;
        superTrisContainer.style.width = `${cellRect.width}px`;
        superTrisContainer.style.height = `${cellRect.height}px`;
        superTrisContainer.style.display = 'block';

        // Riduci la griglia principale e aumenta la trasparenza
        gridContainer.style.transform = 'scale(0.7)';
        gridContainer.style.opacity = '0.3';

        // Attiva l'animazione di ingrandimento
        setTimeout(() => {
            superTrisContainer.style.top = '50%';
            superTrisContainer.style.left = '50%';
            superTrisContainer.style.width = '300px';
            superTrisContainer.style.height = '300px';
            superTrisContainer.style.transform = 'translate(-50%, -50%)';
        }, 10);

        // Logica per il Super Tris
        startSuperTrisGame(selectedCell);
    }

    function startSuperTrisGame(originalCell) {
        let superTrisActive = true;
        let superTrisTurn = 'player'; // Il giocatore inizia

        const superTrisItems = document.querySelectorAll('.super-tris-item');

        function checkSuperTrisWinner(sign) {
            const winningCombinations = [
                [0, 1, 2],
                [3, 4, 5],
                [6, 7, 8],
                [0, 3, 6],
                [1, 4, 7],
                [2, 5, 8],
                [0, 4, 8],
                [2, 4, 6]
            ];

            return winningCombinations.some(combination => {
                return combination.every(index => {
                    return superTrisItems[index].textContent === sign;
                });
            });
        }

        function handleSuperTrisClick(event) {
            const item = event.currentTarget;

            if (item.textContent === '' && superTrisActive) {
                item.textContent = superTrisTurn === 'player' ? playerSign : iaSign;

                if (checkSuperTrisWinner(item.textContent)) {
                    superTrisActive = false;

                    // Esegui l'animazione inversa
                    superTrisContainer.style.transform = '';
                    superTrisContainer.style.top = `${originalCell.getBoundingClientRect().top}px`;
                    superTrisContainer.style.left = `${originalCell.getBoundingClientRect().left}px`;
                    superTrisContainer.style.width = `${originalCell.getBoundingClientRect().width}px`;
                    superTrisContainer.style.height = `${originalCell.getBoundingClientRect().height}px`;

                    setTimeout(() => {
                        superTrisContainer.style.display = 'none';
                        gridContainer.style.transform = ''; // Ripristina la dimensione originale della griglia principale
                        gridContainer.style.opacity = '1'; // Ripristina l'opacità originale
                        if (superTrisTurn === 'player') {
                            originalCell.textContent = playerSign;
                            checkWinner(playerSign); // Verifica la vittoria nel gioco principale
                        } else {
                            originalCell.textContent = iaSign;
                            gameActive = false;  // Termina il gioco subito se l'IA vince
                            showVictoryModal("<?php echo $iaName; ?>"); // Mostra il messaggio di vittoria dell'IA
                        }
                    }, 500); // Tempo dell'animazione inversa
                } else if ([...superTrisItems].every(i => i.textContent !== '')) {
                    superTrisActive = false;
                    superTrisContainer.style.display = 'none';
                    gridContainer.style.transform = ''; // Ripristina la dimensione originale della griglia principale
                    gridContainer.style.opacity = '1'; // Ripristina l'opacità originale
                    gameActive = false;  // Termina il gioco in caso di pareggio nel Super Tris
                    showVictoryModal("<?php echo $iaName; ?>"); // Mostra il messaggio di vittoria dell'IA in caso di pareggio
                } else {
                    superTrisTurn = superTrisTurn === 'player' ? 'ia' : 'player';

                    if (superTrisTurn === 'ia' && superTrisActive) {
                        setTimeout(iaSuperTrisMove, 500);
                    }
                }
            }
        }

        function iaSuperTrisMove() {
            let emptyItems = Array.from(superTrisItems).filter(item => item.textContent === '');
            let moveIndex = Math.floor(Math.random() * emptyItems.length);
            emptyItems[moveIndex].textContent = iaSign;

            if (checkSuperTrisWinner(iaSign)) {
                superTrisActive = false;

                // Esegui l'animazione inversa per l'IA
                superTrisContainer.style.transform = '';
                superTrisContainer.style.top = `${originalCell.getBoundingClientRect().top}px`;
                superTrisContainer.style.left = `${originalCell.getBoundingClientRect().left}px`;
                superTrisContainer.style.width = `${originalCell.getBoundingClientRect().width}px`;
                superTrisContainer.style.height = `${originalCell.getBoundingClientRect().height}px`;

                setTimeout(() => {
                    superTrisContainer.style.display = 'none';
                    gridContainer.style.transform = ''; // Ripristina la dimensione originale della griglia principale
                    gridContainer.style.opacity = '1'; // Ripristina l'opacità originale
                    originalCell.textContent = iaSign;
                    gameActive = false;  // Termina il gioco subito se l'IA vince
                    showVictoryModal("<?php echo $iaName; ?>"); // Mostra il messaggio di vittoria dell'IA
                }, 500); // Tempo dell'animazione inversa
            } else if ([...superTrisItems].every(i => i.textContent !== '')) {
                superTrisActive = false;
                superTrisContainer.style.display = 'none';
                gridContainer.style.transform = ''; // Ripristina la dimensione originale della griglia principale
                gridContainer.style.opacity = '1'; // Ripristina l'opacità originale
                gameActive = false;  // Termina il gioco in caso di pareggio nel Super Tris
                showVictoryModal("<?php echo $iaName; ?>"); // Mostra il messaggio di vittoria dell'IA in caso di pareggio
            } else {
                superTrisTurn = 'player';
            }
        }

        superTrisItems.forEach(item => {
            item.addEventListener('click', handleSuperTrisClick);
        });

        if (superTrisTurn === 'ia') {
            setTimeout(iaSuperTrisMove, 500);
        }
    }

    function updateScore(result) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "update_score.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                playerGamesElement.textContent = response.partite_locali;
                playerWinsElement.textContent = response.partite_locali_vinte;
                if (result === 'ia') {
                    iaWinsElement.textContent = response.partite_locali - response.partite_locali_vinte;
                }
                // Non resettare subito il gioco; attendi l'interazione con la modale
            }
        };
        xhr.send("result=" + result);
    }

    function resetGame() {
        gridItems.forEach(item => {
            item.textContent = '';
            item.classList.remove('player-canceled', 'ia-canceled', 'player-modified', 'ia-modified');
        });
        gameActive = true;
        cancellaUsed = false;
        modificaUsed = false;
        iaModificaUsed = false;
        iaCancellaUsed = false;
        superTrisEnabled = false;

        cancellaButton.classList.remove('inactive');
        modificaButton.classList.remove('inactive');

        superTrisButton.classList.remove('active');
        superTrisButton.classList.add('inactive');

        updateTurnHighlight();

        if (currentPlayer === 'ia') {
            setTimeout(iaMove, 500);
        }
    }

    function showVictoryModal(winner) {
        victoryMessage.textContent = winner + " ha vinto!";
        victoryModal.classList.add('active');
    }

    newGameButton.addEventListener('click', () => {
        victoryModal.classList.remove('active');
        resetGame();
    });

    if (currentPlayer === 'ia' && gameActive) {
        setTimeout(iaMove, 500);
    }

    updateTurnHighlight();
</script>

</body>
</html>
