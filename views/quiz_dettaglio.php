<?php
require_once '../Includes/db.php';

$codice = isset($_GET['codice']) ? (int)$_GET['codice'] : 0;
$errore_msg = ""; 

// --- SEZIONE 1: ELIMINAZIONE DI UNA DOMANDA ---
if (isset($_GET['elimina_domanda'])) {
    $id_domanda = (int)$_GET['elimina_domanda'];
    $pdo->prepare("DELETE FROM Risposta WHERE quiz = ? AND domanda = ?")->execute([$codice, $id_domanda]);
    $pdo->prepare("DELETE FROM Domanda WHERE quiz = ? AND numero = ?")->execute([$codice, $id_domanda]);
    header("Location: quiz_dettaglio.php?codice=" . $codice);
    exit;
}

// --- SEZIONE 2: GESTIONE DELLE RICHIESTE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    
    // Azione: aggiornamento delle informazioni generali del quiz (titolo e periodo di svolgimento)
    if ($_POST['azione'] === 'modifica_info') {
        $stmt = $pdo->prepare("UPDATE Quiz SET titolo = ?, dataInizio = ?, dataFine = ? WHERE codice = ?");
        $stmt->execute([trim($_POST['titolo']), $_POST['dataInizio'], $_POST['dataFine'], $codice]);
        header("Location: quiz_dettaglio.php?codice=" . $codice);
        exit;
    }
    
    // Azione: inserimento di una nuova domanda, oppure modifica di una domanda esistente, con le relative risposte
    if ($_POST['azione'] === 'salva_domanda') {
        $testo = trim($_POST['testo_domanda']);
        $p_esatta = floatval($_POST['punti_esatta']);
        $p_errata = floatval($_POST['punti_sbagliata']);
        $num_opzioni = (int)$_POST['num_opzioni'];
        $corretta = (int)$_POST['risposta_corretta'];
        $id_domanda = !empty($_POST['id_domanda']) ? (int)$_POST['id_domanda'] : 0;
        
        // Verifica dell'unicità del testo della domanda all'interno del quiz, ignorando maiuscole/minuscole e spazi superflui
        $check_stmt = $pdo->prepare("SELECT numero FROM Domanda WHERE quiz = ? AND LOWER(TRIM(testo)) = LOWER(?) AND numero != ?");
        $check_stmt->execute([$codice, $testo, $id_domanda]);
        
        if ($check_stmt->rowCount() > 0) {
            $errore_msg = "Attenzione: Esiste già una domanda identica in questo quiz (ignorando maiuscole/minuscole)!";
        } else {
            // Distinzione tra inserimento di una nuova domanda e aggiornamento di una esistente, in base alla presenza dell'ID
            if ($id_domanda === 0) {
                $stmt_num = $pdo->prepare("SELECT MAX(numero) FROM Domanda WHERE quiz = ?");
                $stmt_num->execute([$codice]);
                $id_domanda = ($stmt_num->fetchColumn() ?: 0) + 1;
                $pdo->prepare("INSERT INTO Domanda (numero, quiz, testo) VALUES (?, ?, ?)")->execute([$id_domanda, $codice, $testo]);
            } else {
                $pdo->prepare("UPDATE Domanda SET testo = ? WHERE quiz = ? AND numero = ?")->execute([$testo, $codice, $id_domanda]);
                // Le risposte precedenti vengono rimosse e ricreate da zero, per semplificare la gestione di un numero di opzioni variabile
                $pdo->prepare("DELETE FROM Risposta WHERE quiz = ? AND domanda = ?")->execute([$codice, $id_domanda]);
            }
            
            // Inserimento delle risposte associate alla domanda, attribuendo il punteggio in base all'opzione segnata come corretta
            for ($i = 1; $i <= $num_opzioni; $i++) {
                $punteggio = ($i === $corretta) ? $p_esatta : $p_errata;
                $stmt_risp = $pdo->prepare("INSERT INTO Risposta (numero, quiz, domanda, testo, punteggio) VALUES (?, ?, ?, ?, ?)");
                $stmt_risp->execute([$i, $codice, $id_domanda, trim($_POST['risposta_' . $i]), $punteggio]);
            }
            
            header("Location: quiz_dettaglio.php?codice=" . $codice);
            exit;
        }
    }
}

// --- RECUPERO DEI DATI DEL QUIZ E DELLE DOMANDE ASSOCIATE ---
$quiz = $pdo->prepare("SELECT * FROM Quiz WHERE codice = ?");
$quiz->execute([$codice]);
$quiz = $quiz->fetch();
if (!$quiz) die("Quiz non trovato.");

$domande = $pdo->prepare("SELECT * FROM Domanda WHERE quiz = ? ORDER BY numero ASC");
$domande->execute([$codice]);
$domande = $domande->fetchAll();

// Determinazione dello stato del quiz (programmato, attivo, scaduto) confrontando le date di apertura/chiusura con la data odierna
$oggi = date('Y-m-d');
if ($oggi < $quiz['dataInizio']) { $stato = "Programmato"; $color = "#FF9800"; }
elseif ($oggi <= $quiz['dataFine']) { $stato = "Attivo"; $color = "#4CAF50"; }
else { $stato = "Scaduto"; $color = "#F44336"; }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Editor Quiz - UniBg</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .sub-nav { background-color: #522d48; padding: 10px 20px; display: flex; gap: 20px; align-items: center; color: white; }
        .sub-nav a { color: #f0e6ef; text-decoration: none; font-weight: bold; }
        
        .alert-error { background-color: #ffebee; color: #c62828; padding: 15px; border: 1px solid #ef9a9a; border-radius: 8px; margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        
        .question-card { background: white; border: 1px solid #e2d6e0; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .question-info { display: flex; align-items: center; gap: 12px; }
        .question-number { background: #522d48; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
        
        .btn-group-row { display: flex; gap: 10px; }
        .btn-edit { background: #FF9800; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; font-family: inherit; }
        .btn-edit:hover { background: #e68a00; }
        .btn-del { background: #F44336; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; }
        .btn-del:hover { background: #d32f2f; }
        
        .options-list { display: flex; flex-direction: column; gap: 8px; padding-left: 42px; }
        .option-item { display: flex; justify-content: space-between; padding: 8px 12px; border-radius: 5px; font-size: 14px; border: 1px solid transparent; }
        .is-correct { background: #e8f5e9; border-color: #c8e6c9; color: #2e7d32; }
        .is-wrong { background: #fdf2f2; border-color: #fbd5d5; color: #c81e1e; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-box { background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 550px; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-box h2 { color: #522d48; margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0e6ef; font-size: 22px; }
        .close { position: absolute; top: 20px; right: 20px; font-size: 24px; cursor: pointer; border: none; background: none; color: #888; transition: color 0.2s; }
        .close:hover { color: #333; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: #522d48; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1c4cd; border-radius: 6px; box-sizing: border-box; font-family: inherit; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: #6a3b5c; outline: none; box-shadow: 0 0 5px rgba(106,59,92,0.2); }
        
        .risposte-container { background: #fcfafb; border: 1px solid #f0e6ef; padding: 15px; border-radius: 8px; margin-top: 10px; width: 100%; box-sizing: border-box; }
        
        .risposta-row { display: flex !important; flex-direction: row !important; align-items: center !important; gap: 15px !important; margin-bottom: 12px !important; }
        .risposta-row:last-child { margin-bottom: 0 !important; }
        
        .risposta-row input[type="radio"] { flex: 0 0 20px !important; width: 20px !important; height: 20px !important; margin: 0 !important; cursor: pointer; accent-color: #4CAF50; }
        .risposta-row input[type="text"] { flex: 1 1 auto !important; min-width: 0 !important; width: 100% !important; margin: 0 !important; }
        
        .btn-primary { background: #6a3b5c; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; margin-top: 10px; transition: background 0.2s; }
        .btn-primary:hover { background: #522d48; }
    </style>
</head>
<body>

<header>Università degli Studi di Bergamo - Editor</header>

<div class="sub-nav">
    <a href="../index.php">&larr; Dashboard</a>
    <span>|</span>
    <span>Quiz: <?php echo htmlspecialchars($quiz['titolo']); ?></span>
</div>

<div class="main-container">
    <aside>
        <h2>Info Quiz</h2>
        <p><strong>Creatore:</strong> @<?php echo htmlspecialchars($quiz['creatore']); ?></p>
        <p><strong>Inizio:</strong> <?php echo date('d/m/Y', strtotime($quiz['dataInizio'])); ?></p>
        <p><strong>Fine:</strong> <?php echo date('d/m/Y', strtotime($quiz['dataFine'])); ?></p>
        <p><strong>Stato:</strong> <span style="color:<?php echo $color; ?>; font-weight:bold;"><?php echo $stato; ?></span></p>
        <button onclick="document.getElementById('modalQuiz').style.display='flex'" class="btn-primary" style="background:#522d48">Modifica Impostazioni</button>
    </aside>

    <main>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Domande del Quiz</h2>
            <button onclick="apriModalDomanda()" class="btn-primary" style="width:auto; margin-top:0; background:#4CAF50; padding: 10px 20px;">+ Aggiungi Domanda</button>
        </div>
        
        <?php foreach ($domande as $idx => $d): ?>
        <div class="question-card">
            <div class="question-header">
                <div class="question-info">
                    <div class="question-number"><?php echo $idx + 1; ?></div>
                    <span style="font-weight:bold; font-size: 15px; color: #333;"><?php echo htmlspecialchars($d['testo']); ?></span>
                </div>
                <div class="btn-group-row">
                    <?php
                    $q_risp = $pdo->prepare("SELECT * FROM Risposta WHERE quiz=? AND domanda=? ORDER BY numero ASC");
                    $q_risp->execute([$codice, $d['numero']]);
                    $rs = $q_risp->fetchAll();
                    
                    // Serializzazione di testo e risposte in JSON, per poterli passare in sicurezza come argomenti dell'evento onclick
                    $testo_js = htmlspecialchars(json_encode($d['testo']), ENT_QUOTES, 'UTF-8');
                    $risposte_js = htmlspecialchars(json_encode($rs), ENT_QUOTES, 'UTF-8');
                    ?>
                    <button class="btn-edit" onclick="apriModalModifica(<?php echo $d['numero']; ?>, <?php echo $testo_js; ?>, <?php echo $risposte_js; ?>)">Modifica</button>
                    <a href="?codice=<?php echo $codice; ?>&elimina_domanda=<?php echo $d['numero']; ?>" class="btn-del" onclick="return confirm('Sicuro di voler eliminare questa domanda?')">Elimina</a>
                </div>
            </div>
            <div class="options-list">
                <?php foreach($rs as $r): ?>
                <div class="option-item <?php echo ($r['punteggio']>0) ? 'is-correct' : 'is-wrong'; ?>">
                    <span>• <?php echo htmlspecialchars($r['testo']); ?></span>
                    <span style="font-weight:bold;"><?php echo floatval($r['punteggio']); ?> pt</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </main>
</div>

<div id="modalDomanda" class="modal-overlay">
    <div class="modal-box">
        <button class="close" onclick="chiudiModal('modalDomanda')">&times;</button>
        <h2 id="modalDomandaTitolo">Nuova Domanda</h2>
        
        <?php if ($errore_msg): ?>
            <div class="alert-error" id="box_errore_duplicato">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <?php echo $errore_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="azione" value="salva_domanda">
            <input type="hidden" name="id_domanda" id="form_id_domanda" value="<?php echo isset($_POST['id_domanda']) ? htmlspecialchars($_POST['id_domanda']) : ''; ?>">
            
            <div class="form-group">
                <label>Testo della Domanda</label>
                <input type="text" name="testo_domanda" id="form_testo" placeholder="Es. Cosa indica l'acronimo SQL?" value="<?php echo isset($_POST['testo_domanda']) ? htmlspecialchars($_POST['testo_domanda']) : ''; ?>" required>
            </div>
            
            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1">
                    <label>Punti Risposta Esatta</label>
                    <input type="number" name="punti_esatta" id="form_p_esatta" value="<?php echo isset($_POST['punti_esatta']) ? htmlspecialchars($_POST['punti_esatta']) : '1'; ?>" step="1" min="0" required>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Punti Errata (Penalità)</label>
                    <input type="number" name="punti_sbagliata" id="form_p_errata" value="<?php echo isset($_POST['punti_sbagliata']) ? htmlspecialchars($_POST['punti_sbagliata']) : '0'; ?>" step="1" max="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Quante opzioni di risposta vuoi inserire?</label>
                <select name="num_opzioni" id="form_num_opzioni" onchange="regolaOpzioni()">
                    <option value="2" <?php echo (isset($_POST['num_opzioni']) && $_POST['num_opzioni'] == 2) ? 'selected' : ''; ?>>2 Opzioni</option>
                    <option value="3" <?php echo (isset($_POST['num_opzioni']) && $_POST['num_opzioni'] == 3) ? 'selected' : ''; ?>>3 Opzioni</option>
                    <option value="4" <?php echo (!isset($_POST['num_opzioni']) || $_POST['num_opzioni'] == 4) ? 'selected' : ''; ?>>4 Opzioni</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Seleziona la risposta esatta e scrivi le opzioni:</label>
                <div class="risposte-container" id="box_risposte">
                    <?php for($i=1; $i<=4; $i++): 
                        $val_risposta = isset($_POST['risposta_'.$i]) ? htmlspecialchars($_POST['risposta_'.$i]) : '';
                        $is_checked = (isset($_POST['risposta_corretta']) && $_POST['risposta_corretta'] == $i) || (!isset($_POST['risposta_corretta']) && $i == 1) ? 'checked' : '';
                    ?>
                    <div class="risposta-row" id="row_<?php echo $i; ?>">
                        <input type="radio" name="risposta_corretta" value="<?php echo $i; ?>" id="radio_<?php echo $i; ?>" required title="Segna come corretta" <?php echo $is_checked; ?>>
                        <input type="text" name="risposta_<?php echo $i; ?>" id="input_<?php echo $i; ?>" placeholder="Opzione <?php echo $i; ?>" value="<?php echo $val_risposta; ?>">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="background-color: #4CAF50;">Salva Domanda</button>
        </form>
    </div>
</div>

<div id="modalQuiz" class="modal-overlay">
    <div class="modal-box">
        <button class="close" onclick="chiudiModal('modalQuiz')">&times;</button>
        <h2>Impostazioni Quiz</h2>
        <form method="POST">
            <input type="hidden" name="azione" value="modifica_info">
            <div class="form-group"><label>Titolo del Quiz</label><input type="text" name="titolo" value="<?php echo htmlspecialchars($quiz['titolo']); ?>" required></div>
            <div class="form-group"><label>Data Inizio</label><input type="date" name="dataInizio" value="<?php echo $quiz['dataInizio']; ?>" required></div>
            <div class="form-group"><label>Data Fine</label><input type="date" name="dataFine" value="<?php echo $quiz['dataFine']; ?>" required></div>
            <button type="submit" class="btn-primary">Aggiorna Dati</button>
        </form>
    </div>
</div>

<script>
    // Chiude la modale indicata, nascondendola
    function chiudiModal(id) { 
        document.getElementById(id).style.display = 'none'; 
    }

    // Mostra o nasconde le righe delle opzioni di risposta in base al numero scelto,
    // e assicura che la risposta corretta selezionata sia sempre tra le opzioni visibili
    function regolaOpzioni() {
        const num = parseInt(document.getElementById('form_num_opzioni').value);
        for(let i=1; i<=4; i++) {
            const row = document.getElementById('row_'+i);
            const input = document.getElementById('input_'+i);
            if(i <= num) {
                row.style.setProperty('display', 'flex', 'important');
                input.required = true;
            } else {
                row.style.setProperty('display', 'none', 'important');
                input.required = false;
                input.value = '';
            }
        }
        
        const radioSpuntato = document.querySelector('input[name="risposta_corretta"]:checked');
        if (!radioSpuntato || radioSpuntato.parentElement.style.display === 'none') {
            document.getElementById('radio_1').checked = true;
        }
    }

    // Apre la modale in modalità "nuova domanda", azzerando tutti i campi del form
    function apriModalDomanda() {
        // Nasconde un eventuale messaggio di errore residuo, mostrato da un precedente tentativo di salvataggio
        const errBox = document.getElementById('box_errore_duplicato');
        if (errBox) errBox.style.setProperty('display', 'none', 'important');

        document.getElementById('modalDomandaTitolo').innerText = "Nuova Domanda";
        document.getElementById('form_id_domanda').value = "0";
        document.getElementById('form_testo').value = "";
        document.getElementById('form_p_esatta').value = "1";
        document.getElementById('form_p_errata').value = "0";
        document.getElementById('form_num_opzioni').value = "4";
        for(let i=1; i<=4; i++) document.getElementById('input_'+i).value = "";
        document.getElementById('radio_1').checked = true;
        
        document.getElementById('modalDomanda').style.display = 'flex';
        regolaOpzioni();
    }

    // Apre la modale in modalità "modifica domanda", precompilando testo, punteggi e opzioni di risposta esistenti
    function apriModalModifica(id, testo, risposte) {
        // Nasconde un eventuale messaggio di errore residuo, anche quando si entra in modalità modifica
        const errBox = document.getElementById('box_errore_duplicato');
        if (errBox) errBox.style.setProperty('display', 'none', 'important');

        document.getElementById('modalDomandaTitolo').innerText = "Modifica Domanda";
        document.getElementById('form_id_domanda').value = id;
        document.getElementById('form_testo').value = testo;
        
        const num = risposte.length;
        document.getElementById('form_num_opzioni').value = num;
        
        // Per ciascuna risposta, individua quella corretta (punteggio positivo) per impostare il punteggio "esatta" e il radio corrispondente
        risposte.forEach((r, index) => {
            const i = index + 1;
            document.getElementById('input_'+i).value = r.testo;
            
            const pt = parseFloat(r.punteggio);
            if(pt > 0) {
                document.getElementById('radio_'+i).checked = true;
                document.getElementById('form_p_esatta').value = pt;
            } else {
                document.getElementById('form_p_errata').value = pt;
            }
        });
        
        document.getElementById('modalDomanda').style.display = 'flex';
        regolaOpzioni();
    }

    // Chiude una modale aperta se l'utente clicca sull'area scura esterna al riquadro
    window.onclick = function(e) {
        if(e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
    }

    <?php if($errore_msg !== ""): ?>
        // Se il server ha rilevato una domanda duplicata, riapre automaticamente la modale mostrando l'errore corrente
        document.getElementById('modalDomandaTitolo').innerText = "<?php echo (!empty($_POST['id_domanda']) && $_POST['id_domanda'] != '0') ? 'Modifica Domanda' : 'Nuova Domanda'; ?>";
        document.getElementById('modalDomanda').style.display = 'flex';
        regolaOpzioni();
    <?php endif; ?>
</script>

    <footer>
        <div>Dettaglio Quiz</div>
        <div class="disclaimer">&copy; 2026 - Progetto Universitario ad uso didattico - Università degli Studi di Bergamo</div>
    </footer>
</body>
</html>