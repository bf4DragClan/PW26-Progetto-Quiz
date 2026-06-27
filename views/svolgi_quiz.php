<?php
require_once '../Includes/db.php';

$codice_quiz = isset($_GET['codice']) ? (int)$_GET['codice'] : 0;
$ha_consegnato = false;
$punteggio_totale = 0;
$risposte_utente = []; // Inizializzato per evitare errori
$is_expired = false; // Flag per la scadenza

// --- RECUPERO DATI QUIZ ---
$stmt_quiz = $pdo->prepare("SELECT * FROM Quiz WHERE codice = ?");
$stmt_quiz->execute([$codice_quiz]);
$quiz = $stmt_quiz->fetch();

if (!$quiz) {
    die("Quiz non trovato.");
}

// Controllo Scadenza
$oggi = date('Y-m-d');
if ($quiz['dataFine'] < $oggi) {
    $is_expired = true;
}

// --- 1. GESTIONE CONSEGNA QUIZ E CALCOLO PUNTEGGIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['utente'])) {
    
    // Sicurezza: blocchiamo il salvataggio se il quiz è scaduto
    if ($is_expired) {
        die("Errore: Il quiz è scaduto e non può essere più consegnato.");
    }

    $studente = $_POST['utente'];
    
    // Cicliamo tutti i dati inviati dal form
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'risp_') === 0) {
            $numero_domanda = str_replace('risp_', '', $key);
            $numero_risposta = $value; 
            
            $risposte_utente[$numero_domanda] = $numero_risposta;
            
            $stmt_punteggio = $pdo->prepare("SELECT punteggio FROM risposta WHERE quiz = ? AND domanda = ? AND numero = ?");
            $stmt_punteggio->execute([$codice_quiz, $numero_domanda, $numero_risposta]);
            $risp_data = $stmt_punteggio->fetch();
            
            if ($risp_data) {
                $punteggio_totale += (float)$risp_data['punteggio'];
            }
        }
    }
    
    $ha_consegnato = true;
    
    // --- INSERIMENTO DELLA PARTECIPAZIONE E DELLE RISPOSTE NEL DB ---
    try {
        $stmt_max = $pdo->query("SELECT MAX(codice) AS max_id FROM partecipazione");
        $row = $stmt_max->fetch();
        $nuovo_codice = ($row['max_id'] !== null) ? (int)$row['max_id'] + 1 : 1;
        
        $stmt_partecipazione = $pdo->prepare("INSERT INTO partecipazione (codice, quiz, utente, data) VALUES (?, ?, ?, NOW())");
        $stmt_partecipazione->execute([$nuovo_codice, $codice_quiz, $studente]);
        
        $stmt_risposta_utente = $pdo->prepare("INSERT INTO rispostautentequiz (domanda, partecipazione, quiz, risposta) VALUES (?, ?, ?, ?)");
        
        foreach ($risposte_utente as $domanda => $risposta) {
            $stmt_risposta_utente->execute([$domanda, $nuovo_codice, $codice_quiz, $risposta]);
        }
        
    } catch (PDOException $e) {
        die("Errore database durante il salvataggio: " . $e->getMessage());
    }
}

// Recupero domande
try {
    $stmt_domande = $pdo->prepare("SELECT * FROM Domanda WHERE quiz = ? ORDER BY RAND()");
    $stmt_domande->execute([$codice_quiz]);
    $domande = $stmt_domande->fetchAll();
} catch (PDOException $e) {
    die("Errore database: " . $e->getMessage());
}

$stmt_utenti = $pdo->query("SELECT * FROM Utente ORDER BY cognome ASC");
$utenti = $stmt_utenti->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Svolgimento Quiz - <?php echo htmlspecialchars($quiz['titolo']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/quiz.css">
</head>
<body>
    
    <?php if ($ha_consegnato): ?>
        <div class="risultato-overlay">
            <div class="risultato-box">
                <h2>Quiz Completato!</h2>
                <p>Hai totalizzato il seguente punteggio:</p>
                <div class="risultato-punti"><?php echo number_format($punteggio_totale, 0); ?></div>
                <a href="../lista_quiz.php" class="btn-chiudi">Torna ai Quiz</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="quiz-container">
        <div class="quiz-header">
            <h1><?php echo htmlspecialchars($quiz['titolo']); ?></h1>
            <?php if ($is_expired): ?>
                <div style="background-color: #ffcccc; color: #cc0000; padding: 10px; border: 1px solid #cc0000; margin-bottom: 20px;">
                    <strong>ATTENZIONE:</strong> Questo quiz è scaduto il giorno <?php echo htmlspecialchars($quiz['dataFine']); ?>. Non è possibile partecipare.
                </div>
            <?php else: ?>
                <p>Seleziona il tuo profilo dal menù a tendina per iniziare il test.</p>
            <?php endif; ?>
        </div>
        
        <form method="POST" action=""> 
            <div class="user-select-box">
                <label><strong>Studente:</strong></label>
                <select name="utente" required <?php echo $is_expired ? 'disabled' : ''; ?>>
                    <option value="" disabled selected>-- Seleziona il tuo nominativo --</option>
                    <?php foreach ($utenti as $u): ?>
                        <option value="<?php echo htmlspecialchars($u['nomeUtente']); ?>">
                            <?php echo htmlspecialchars($u['cognome'] . ' ' . $u['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($domande as $index => $d): ?>
                <div class="domanda-card">
                    <div class="domanda-testo">
                        <?php echo ($index + 1) . ". " . htmlspecialchars($d['testo']); ?>
                    </div>
                    
                    <div class="risposte-grid">
                        <?php
                        $stmt_r = $pdo->prepare("SELECT * FROM Risposta WHERE quiz = ? AND domanda = ? ORDER BY RAND()");
                        $stmt_r->execute([$codice_quiz, $d['numero']]);
                        $risposte = $stmt_r->fetchAll();
                        
                        foreach ($risposte as $r): ?>
                            <label class="risposta-label">
                                <input type="radio" name="risp_<?php echo $d['numero']; ?>" value="<?php echo $r['numero']; ?>" required <?php echo $is_expired ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($r['testo']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!$ha_consegnato && !$is_expired): ?>
                <button type="submit" class="btn-consegna">Consegna Definitiva</button>
            <?php elseif ($is_expired): ?>
                <button type="button" class="btn-consegna" disabled style="background-color: #999; cursor: not-allowed;">Quiz Scaduto</button>
            <?php endif; ?>
        </form>
    </div>

</body>
</html>