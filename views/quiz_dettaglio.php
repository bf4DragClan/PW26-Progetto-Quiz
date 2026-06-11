<?php
// Inclusione del database (notare il ../ per uscire dalla cartella views)
require_once '../Includes/db.php';

// Recupero del codice del quiz dalla URL
$codice = isset($_GET['codice']) ? (int)$_GET['codice'] : 0;

// 1. Query per recuperare i dettagli del Quiz specifico
$quiz_stmt = $pdo->prepare("SELECT * FROM Quiz WHERE codice = :codice");
$quiz_stmt->execute([':codice' => $codice]);
$quiz = $quiz_stmt->fetch();

if (!$quiz) {
    die("<div style='padding: 20px; color: red; font-weight: bold;'>Errore: Quiz non trovato nel database!</div>");
}

// 2. Query per recuperare le domande associate a questo quiz
$domande_stmt = $pdo->prepare("SELECT * FROM Domanda WHERE quiz = :codice ORDER BY numero ASC");
$domande_stmt->execute([':codice' => $codice]);
$domande = $domande_stmt->fetchAll();

// 3. Calcolo dinamico dello Stato del Quiz (Oggi è l'11 Giugno 2026)
$oggi = date('Y-m-d');
if ($oggi < $quiz['dataInizio']) {
    $stato = "Programmato";
    $stato_color = "#FF9800";
} elseif ($oggi >= $quiz['dataInizio'] && $oggi <= $quiz['dataFine']) {
    $stato = "Attivo";
    $stato_color = "#4CAF50";
} else {
    $stato = "Scaduto";
    $stato_color = "#F44336";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBg - Dettaglio Contenuti</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Sotto-barra di navigazione scura */
        .sub-nav { background-color: #522d48; padding: 10px 20px; display: flex; gap: 20px; align-items: center; }
        .sub-nav a { color: #f0e6ef; text-decoration: none; font-size: 14px; font-weight: bold; }
        .sub-nav a:hover { text-decoration: underline; }
        .sub-nav span { color: white; font-weight: bold; font-size: 14px; }

        /* Struttura a schede delle domande */
        .question-card { background: white; border: 1px solid #e2d6e0; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .question-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px; }
        .question-number { background-color: #522d48; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; flex-shrink: 0; }
        .question-text { font-size: 15px; font-weight: bold; color: #333; margin-top: 3px; line-height: 1.4; }

        /* Opzioni di risposta */
        .options-list { display: flex; flex-direction: column; gap: 8px; padding-left: 40px; }
        .option-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; border-radius: 6px; font-size: 14px; font-weight: 500; }
        
        /* Classi di feedback per i punteggi delle risposte */
        .option-correct { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .option-wrong { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .points-badge { font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

    <header>Università degli Studi di Bergamo - Dettaglio Contenuti</header>

    <div class="sub-nav">
        <a href="../index.php">&larr; Torna alla Dashboard</a>
        <span style="opacity: 0.5;">|</span>
        <span><?php echo htmlspecialchars($quiz['titolo']); ?></span>
    </div>

    <div class="main-container">
        <aside>
            <h2>Info Quiz</h2>
            <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 10px;">
                <div>
                    <label style="font-weight: bold; color: #555; display: block; font-size: 13px;">Creatore:</label>
                    <span style="color: #333; font-weight: 600;">@<?php echo htmlspecialchars($quiz['creatore'] ?? 'N/D'); ?></span>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; display: block; font-size: 13px;">Data Apertura:</label>
                    <span style="color: #333;"><?php echo date('d/m/Y', strtotime($quiz['dataInizio'])); ?></span>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; display: block; font-size: 13px;">Data Chiusura:</label>
                    <span style="color: #333;"><?php echo date('d/m/Y', strtotime($quiz['dataFine'])); ?></span>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; display: block; font-size: 13px;">Stato:</label>
                    <span style="color: <?php echo $stato_color; ?>; font-weight: bold;"><?php echo $stato; ?></span>
                </div>
            </div>
        </aside>

        <main>
            <h2 style="margin-bottom: 5px;">Quiz: <?php echo htmlspecialchars($quiz['titolo']); ?></h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Di seguito l'elenco delle domande caricate per questo quiz.</p>

            <?php if (count($domande) > 0): ?>
                <?php foreach ($domande as $index => $domanda): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div class="question-number"><?php echo $index + 1; ?></div>
                            <div class="question-text"><?php echo htmlspecialchars($domanda['testo']); ?></div>
                        </div>

                        <div class="options-list">
                            <?php
                            $risposte_stmt = $pdo->prepare("SELECT * FROM Risposta WHERE quiz = :quiz AND domanda = :domanda ORDER BY numero ASC");
                            $risposte_stmt->execute([':quiz' => $codice, ':domanda' => $domanda['numero']]);
                            $risposte = $risposte_stmt->fetchAll();
                            
                            foreach ($risposte as $risposta):
                                // Verifica se assegna punteggio positivo o zero per decidere lo stile grafico verde/rosso
                                $is_correct = ($risposta['punteggio'] > 0);
                                $class = $is_correct ? 'option-correct' : 'option-wrong';
                            ?>
                                <div class="option-item <?php echo $class; ?>">
                                    <span>• <?php echo htmlspecialchars($risposta['testo']); ?></span>
                                    <span class="points-badge"><?php echo (int)$risposta['punteggio']; ?> pt.</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="background: white; border: 1px dashed #ccc; padding: 30px; text-align: center; border-radius: 8px; color: #777;">
                    Nessuna domanda inserita in questo quiz.
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>