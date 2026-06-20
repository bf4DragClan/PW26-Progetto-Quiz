<?php
require_once '../Includes/db.php'; // Adatta il percorso se necessario

// Recupero ID del quiz dall'URL (es. svolgi_quiz.php?codice=12)
$quiz_id = isset($_GET['codice']) ? (int)$_GET['codice'] : 0;

if ($quiz_id === 0) {
    die("Quiz non trovato o ID non valido.");
}

// 1. RECUPERO DETTAGLI DEL QUIZ
$stmt = $pdo->prepare("SELECT * FROM Quiz WHERE codice = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Questo quiz non esiste.");
}

// 2. RECUPERO UTENTI (Per il menu a tendina che simula il login)
$stmtUtenti = $pdo->query("SELECT nomeUtente, nome, cognome FROM utente ORDER BY nome ASC");
$utenti = $stmtUtenti->fetchAll();

// 3. RECUPERO DOMANDE IN ORDINE CASUALE (Grazie a ORDER BY RAND())
// Se nel tuo DB la tabella si chiama in minuscolo o ha un altro nome, adattala qui (es. 'domande')
$stmtDomande = $pdo->prepare("SELECT * FROM Domanda WHERE quiz = ? ORDER BY RAND()");
$stmtDomande->execute([$quiz_id]);
$domande = $stmtDomande->fetchAll();

$risultato_testo = '';

// 4. ELABORAZIONE DEL QUIZ QUANDO VIENE CONSEGNATO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $utente_partecipante = $_POST['utente'] ?? '';
    $risposte_inviate = $_POST['risposte'] ?? []; // Array strutturato come [id_domanda => id_opzione]
    $punteggio = 0;

    if (empty($utente_partecipante)) {
        $risultato_testo = "<div class='alert error'>Seleziona chi sta svolgendo il quiz!</div>";
    } else {
        // Calcolo del punteggio scorrendo le risposte inviate dall'utente
        foreach ($risposte_inviate as $domanda_id => $opzione_scelta) {
            // Verifica se l'opzione selezionata è contrassegnata come corretta (is_corretta = 1)
            $stmtCheck = $pdo->prepare("SELECT is_corretta FROM Opzione WHERE codice = ?");
            $stmtCheck->execute([$opzione_scelta]);
            $opzione = $stmtCheck->fetch();

            if ($opzione && $opzione['is_corretta'] == 1) {
                $punteggio++;
            }
        }

        // Salva il record nel Registro Partecipazioni (Tabella 'partecipazione')
        $data_attuale = date('Y-m-d H:i:s');
        $stmtPart = $pdo->prepare("INSERT INTO partecipazione (utente, quiz, data) VALUES (?, ?, ?)");
        $stmtPart->execute([$utente_partecipante, $quiz_id, $data_attuale]);

        // Messaggio di riepilogo finale
        $totale_domande = count($domande);
        $risultato_testo = "<div class='alert success'>
                                <h3>Quiz Consegnato con Successo!</h3>
                                <p>Utente: <b>" . htmlspecialchars($utente_partecipante) . "</b></p>
                                <p>Il tuo punteggio: <b>$punteggio</b> / <b>$totale_domande</b></p>
                                <a href='lista_quiz.php' class='btn-back'>Torna all'elenco dei Quiz</a>
                            </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Svolgimento Quiz: <?php echo htmlspecialchars($quiz['titolo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .quiz-container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); font-family: sans-serif; }
        .quiz-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #6a3b5c; padding-bottom: 20px; }
        .quiz-header h1 { color: #6a3b5c; margin: 0 0 10px 0; }
        .quiz-header p { color: #666; margin: 0; font-size: 14px; }
        
        .user-selection { background: #f9f4f7; padding: 15px; border-radius: 6px; margin-bottom: 30px; border: 1px solid #e0cdd8; }
        .user-selection label { font-weight: bold; color: #522d48; display: block; margin-bottom: 8px; }
        .user-selection select { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit; }

        .domanda-box { margin-bottom: 25px; padding: 20px; border: 1px solid #eee; border-radius: 6px; background: #fafafa; }
        .domanda-testo { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 15px; }
        .opzione-label { display: block; padding: 12px 15px; margin-bottom: 8px; background: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: 0.2s; font-size: 14px; }
        .opzione-label:hover { background: #f0e6eb; border-color: #6a3b5c; }
        .opzione-label input { margin-right: 10px; transform: scale(1.1); }

        .btn-invia { background-color: #6a3b5c; color: white; border: none; padding: 15px 30px; font-size: 16px; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; transition: 0.2s; font-family: inherit; }
        .btn-invia:hover { background-color: #522d48; }
        
        .alert { padding: 25px; border-radius: 6px; text-align: center; margin-bottom: 20px; font-size: 15px; }
        .alert h3 { margin-top: 0; color: inherit; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-back { display: inline-block; margin-top: 15px; padding: 10px 20px; background: #6a3b5c; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-back:hover { background: #522d48; }
    </style>
</head>
<body>

    <header style="background-color: #6a3b5c; color: white; padding: 15px; text-align: center; font-weight: bold; font-size: 18px;">
        Università degli Studi di Bergamo - Svolgimento Quiz
    </header>

    <div class="quiz-container">
        <div class="quiz-header">
            <h1><?php echo htmlspecialchars($quiz['titolo']); ?></h1>
            <p>Creatore del Quiz: <b><?php echo htmlspecialchars($quiz['creatore'] ?? 'N/D'); ?></b></p>
        </div>

        <?php 
        // Mostriamo il risultato se il modulo è stato inviato, altrimenti mostriamo il form del quiz
        if ($risultato_testo != '') {
            echo $risultato_testo;
        } else if (count($domande) === 0) {
            echo "<div class='alert error'>Questo quiz non contiene ancora nessuna domanda. Contatta il docente.</div>";
            echo "<div style='text-align:center;'><a href='lista_quiz.php' class='btn-back'>Torna alla lista</a></div>";
        } else {
        ?>

        <form method="POST" action="">
            <div class="user-selection">
                <label for="utente">Seleziona il tuo Profilo Studente:</label>
                <select id="utente" name="utente" required>
                    <option value="" disabled selected>Scegli il tuo nome utente...</option>
                    <?php foreach ($utenti as $u): ?>
                        <option value="<?php echo htmlspecialchars($u['nomeUtente']); ?>">
                            <?php echo htmlspecialchars($u['nome'] . ' ' . $u['cognome'] . ' (' . $u['nomeUtente'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($domande as $index => $domanda): ?>
                <div class="domanda-box">
                    <div class="domanda-testo">
                        Domanda <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($domanda['testo']); ?>
                    </div>
                    
                    <div class="opzioni-container">
                        <?php
                        // Recupero le opzioni relative a questa domanda
                        $stmtOpzioni = $pdo->prepare("SELECT * FROM Opzione WHERE domanda_codice = ?");
                        $stmtOpzioni->execute([$domanda['codice']]);
                        $opzioni = $stmtOpzioni->fetchAll();
                        
                        // NOTA OPZIONALE: Se vuoi che ANCHE le risposte interne siano mescolate a caso, 
                        // ti basta aggiungere "shuffle($opzioni);" proprio qui sotto!
                        
                        foreach ($opzioni as $opzione):
                        ?>
                            <label class="opzione-label">
                                <input type="radio" name="risposte[<?php echo $domanda['codice']; ?>]" value="<?php echo $opzione['codice']; ?>" required>
                                <?php echo htmlspecialchars($opzione['testo']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="submit_quiz" class="btn-invia">Consegna e Termina il Test</button>
        </form>

        <?php } ?>
    </div>

</body>
</html>