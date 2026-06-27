<?php
require_once '../Includes/db.php';

$codice_quiz = isset($_GET['codice']) ? (int)$_GET['codice'] : 0;
$ha_consegnato = false;
$punteggio_totale = 0;

// --- 1. GESTIONE CONSEGNA QUIZ E CALCOLO PUNTEGGIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['utente'])) {
    $studente = $_POST['utente'];
    
    // Cicliamo tutti i dati inviati dal form
    foreach ($_POST as $key => $value) {
        // Controlliamo se il campo è una risposta (inizia con "risp_")
        if (strpos($key, 'risp_') === 0) {
            // Estrapoliamo l'ID della domanda dal nome dell'input
            $numero_domanda = str_replace('risp_', '', $key);
            $numero_risposta = $value; // Questa è la risposta selezionata dall'utente
            
            // Andiamo a leggere il punteggio di questa specifica risposta nel DB
            $stmt_punteggio = $pdo->prepare("SELECT punteggio FROM Risposta WHERE quiz = ? AND domanda = ? AND numero = ?");
            $stmt_punteggio->execute([$codice_quiz, $numero_domanda, $numero_risposta]);
            $risp_data = $stmt_punteggio->fetch();
            
            // Se la risposta esiste, sommiamo (o sottraiamo, se negativo) il punteggio
            if ($risp_data) {
                $punteggio_totale += (float)$risp_data['punteggio'];
            }
        }
    }
    
    $ha_consegnato = true;
    
    // NOTA: Qui potresti anche inserire la logica per salvare il voto nella tabella "Partecipazione"
    // Esempio: $pdo->prepare("INSERT INTO Partecipazione (utente, codice, data, voto) VALUES (?, ?, NOW(), ?)")->execute([$studente, $codice_quiz, $punteggio_totale]);
}

// --- 2. RECUPERO DATI PER L'INTERFACCIA ---
$stmt_quiz = $pdo->prepare("SELECT * FROM Quiz WHERE codice = ?");
$stmt_quiz->execute([$codice_quiz]);
$quiz = $stmt_quiz->fetch();

if (!$quiz) {
    die("Quiz non trovato.");
}

$stmt_utenti = $pdo->query("SELECT * FROM Utente ORDER BY cognome ASC");
$utenti = $stmt_utenti->fetchAll();

// Recupero domande in ordine casuale
try {
    $stmt_domande = $pdo->prepare("SELECT * FROM Domanda WHERE quiz = ? ORDER BY RAND()");
    $stmt_domande->execute([$codice_quiz]);
    $domande = $stmt_domande->fetchAll();
} catch (PDOException $e) {
    die("Errore database: " . $e->getMessage());
}
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
            <p>Seleziona il tuo profilo dal menù a tendina per iniziare il test.</p>
        </div>
        
        <form method="POST" action=""> 
            <div class="user-select-box">
                <label><strong>Studente:</strong></label>
                <select name="utente" required>
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
                        // Recupero risposte in ordine casuale
                        $stmt_r = $pdo->prepare("SELECT * FROM Risposta WHERE quiz = ? AND domanda = ? ORDER BY RAND()");
                        $stmt_r->execute([$codice_quiz, $d['numero']]);
                        $risposte = $stmt_r->fetchAll();
                        
                        foreach ($risposte as $r): ?>
                            <label class="risposta-label">
                                <input type="radio" name="risp_<?php echo $d['numero']; ?>" value="<?php echo $r['numero']; ?>" required>
                                <?php echo htmlspecialchars($r['testo']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!$ha_consegnato): ?>
                <button type="submit" class="btn-consegna">Consegna Definitiva</button>
            <?php endif; ?>
        </form>
    </div>

</body>
</html>