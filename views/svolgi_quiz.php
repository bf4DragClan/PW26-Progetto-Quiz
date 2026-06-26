<?php
require_once '../Includes/db.php'; // Adatta il percorso se necessario

$codice_quiz = isset($_GET['codice']) ? (int)$_GET['codice'] : 0;
$messaggio = "";

// Recupero il Quiz
$stmt_quiz = $pdo->prepare("SELECT * FROM Quiz WHERE codice = ?");
$stmt_quiz->execute([$codice_quiz]);
$quiz = $stmt_quiz->fetch();

if (!$quiz) {
    die("<div style='text-align:center; padding: 50px; font-family:sans-serif;'><h2>Errore: Quiz non trovato o inesistente.</h2><a href='lista_quiz.php'>Torna indietro</a></div>");
}

// Recupero gli utenti per la tendina (Assumendo che la tabella si chiami "Utente")
// Se la tua tabella si chiama diversamente, cambia "Utente" con il nome corretto
$stmt_utenti = $pdo->prepare("SELECT * FROM Utente ORDER BY cognome ASC, nome ASC");
$stmt_utenti->execute();
$utenti = $stmt_utenti->fetchAll();

// Recupero le Domande (FIX ERRORI DB)
$stmt_domande = $pdo->prepare("SELECT * FROM Domanda WHERE quiz = ? ORDER BY numero ASC");
$stmt_domande->execute([$codice_quiz]);
$domande = $stmt_domande->fetchAll();

// Gestione dell'invio del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utente_selezionato = $_POST['studente'] ?? '';
    
    if (empty($utente_selezionato)) {
        $messaggio = "<div class='alert error'>Seleziona uno studente prima di inviare il quiz!</div>";
    } else {
        $punteggio_totale = 0;
        
        foreach ($domande as $d) {
            $id_domanda = $d['numero'];
            
            // Verifica se lo studente ha risposto a questa domanda
            if (isset($_POST['risposta_domanda_' . $id_domanda])) {
                $id_risposta_scelta = (int)$_POST['risposta_domanda_' . $id_domanda];
                
                // Vai a prendere i punti che vale quella specifica risposta dal DB
                $check_punti = $pdo->prepare("SELECT punteggio FROM Risposta WHERE quiz = ? AND domanda = ? AND numero = ?");
                $check_punti->execute([$codice_quiz, $id_domanda, $id_risposta_scelta]);
                $punti = $check_punti->fetchColumn();
                
                if ($punti !== false) {
                    $punteggio_totale += floatval($punti);
                }
            }
        }
        
        $messaggio = "<div class='alert success'>Quiz inviato con successo! Punteggio ottenuto: <strong>" . $punteggio_totale . "</strong></div>";
        // Qui sotto, in futuro, potrai aggiungere la query di INSERT per salvare il voto nel registro
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Svolgi Quiz - <?php echo htmlspecialchars($quiz['titolo']); ?></title>
    <link rel="stylesheet" href="css/style.css"> <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .quiz-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #522d48; border-bottom: 2px solid #f0e6ef; padding-bottom: 10px; }
        
        .form-section { margin-bottom: 25px; padding: 15px; background: #fdfafb; border: 1px solid #e2d6e0; border-radius: 8px; }
        .form-section label { font-weight: bold; color: #333; display: block; margin-bottom: 8px; }
        .form-section select { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; }
        
        .domanda-card { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dashed #ccc; }
        .domanda-testo { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 15px; }
        .risposta-lbl { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; padding: 8px; border-radius: 5px; transition: background 0.2s; border: 1px solid transparent; }
        .risposta-lbl:hover { background-color: #f0e6ef; border-color: #d1c4cd; }
        .risposta-lbl input[type="radio"] { transform: scale(1.2); accent-color: #522d48; }
        
        .btn-invia { background-color: #522d48; color: white; padding: 15px 30px; border: none; border-radius: 6px; font-size: 18px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-invia:hover { background-color: #6a3b5c; }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-size: 18px; }
        .alert.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert.error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>

    <div class="quiz-container">
        <h1><?php echo htmlspecialchars($quiz['titolo']); ?></h1>
        <p style="color: #666; margin-bottom: 30px;">Rispondi con attenzione alle seguenti domande.</p>
        
        <?php echo $messaggio; ?>
        
        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($messaggio)): ?>
        <form method="POST">
            <div class="form-section">
                <label for="studente">Seleziona il tuo profilo studente:</label>
                <select name="studente" id="studente" required>
                    <option value="">-- Clicca qui per selezionare il tuo nome --</option>
                    <?php foreach ($utenti as $u): ?>
                        <option value="<?php echo $u['email']; ?>">
                            <?php echo htmlspecialchars($u['cognome'] . ' ' . $u['nome'] . ' (' . $u['email'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($domande as $index => $d): ?>
                <div class="domanda-card">
                    <div class="domanda-testo">
                        <?php echo ($index + 1) . ". " . htmlspecialchars($d['testo']); ?>
                    </div>
                    
                    <div class="risposte-list">
                        <?php 
                            // RECUPERO RISPOSTE FIXATO
                            $stmt_risposte = $pdo->prepare("SELECT * FROM Risposta WHERE quiz = ? AND domanda = ? ORDER BY numero ASC");
                            $stmt_risposte->execute([$codice_quiz, $d['numero']]);
                            $risposte = $stmt_risposte->fetchAll();
                            
                            foreach ($risposte as $r): 
                        ?>
                            <label class="risposta-lbl">
                                <input type="radio" name="risposta_domanda_<?php echo $d['numero']; ?>" value="<?php echo $r['numero']; ?>" required>
                                <span><?php echo htmlspecialchars($r['testo']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-invia">Consegna il Quiz</button>
        </form>
        <?php endif; ?>
    </div>

</body>
</html>