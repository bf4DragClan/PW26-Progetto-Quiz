<?php
// Inclusione del database
require_once 'Includes/db.php';

// --- 1. GESTIONE ELIMINAZIONE QUIZ (FIXATO) ---
// Ora gestiamo l'eliminazione direttamente qui, senza bisogno di file esterni
if (isset($_GET['elimina_quiz'])) {
    $id_quiz = (int)$_GET['elimina_quiz'];
    
    // Eliminiamo a cascata per evitare errori di vincolo (chiavi esterne)
    $pdo->prepare("DELETE FROM Risposta WHERE quiz = ?")->execute([$id_quiz]);
    $pdo->prepare("DELETE FROM Domanda WHERE quiz = ?")->execute([$id_quiz]);
    // Eliminiamo il quiz stesso
    $pdo->prepare("DELETE FROM Quiz WHERE codice = ?")->execute([$id_quiz]);
    
    // Ricarica la pagina pulita
    header("Location: index.php");
    exit;
}

// --- 2. GESTIONE SALVATAGGIO DATI DAL POPUP (SOLO AGGIUNTA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modalAction'])) {
    $titolo = trim($_POST['modalTitolo'] ?? '');
    $inizio = $_POST['modalInizio'] ?? '';
    $fine = $_POST['modalFine'] ?? '';
    $azione = $_POST['modalAction'];

    if ($azione === 'insert' && !empty($titolo)) {
        $creatore = 'user_Admin'; 
        $stmt = $pdo->prepare("INSERT INTO Quiz (titolo, creatore, dataInizio, dataFine) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titolo, $creatore, $inizio, $fine]);
        
        $new_id = $pdo->lastInsertId();
        header("Location: views/quiz_dettaglio.php?codice=" . $new_id);
        exit;
    }
}

// --- 3. GESTIONE FILTRI E PAGINAZIONE ---
$search_titolo = isset($_GET['search_titolo']) ? trim($_GET['search_titolo']) : '';
$search_creatore = isset($_GET['search_creatore']) ? trim($_GET['search_creatore']) : '';
$search_dataInizio = isset($_GET['search_dataInizio']) ? $_GET['search_dataInizio'] : '';
$search_dataFine = isset($_GET['search_dataFine']) ? $_GET['search_dataFine'] : '';

$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Conteggio totale
$count_sql = "SELECT COUNT(*) FROM Quiz WHERE 1=1";
$params = [];
if (!empty($search_titolo)) { $count_sql .= " AND titolo LIKE ?"; $params[] = "%$search_titolo%"; }
if (!empty($search_creatore)) { $count_sql .= " AND creatore LIKE ?"; $params[] = "%$search_creatore%"; }
if (!empty($search_dataInizio)) { $count_sql .= " AND dataInizio >= ?"; $params[] = $search_dataInizio; }
if (!empty($search_dataFine)) { $count_sql .= " AND dataFine <= ?"; $params[] = $search_dataFine; }

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

if ($page > $total_pages && $total_pages > 0) { $page = $total_pages; $offset = ($page - 1) * $limit; }

// Recupero Quiz
$sql = "SELECT * FROM Quiz WHERE 1=1";
if (!empty($search_titolo)) $sql .= " AND titolo LIKE ?";
if (!empty($search_creatore)) $sql .= " AND creatore LIKE ?";
if (!empty($search_dataInizio)) $sql .= " AND dataInizio >= ?";
if (!empty($search_dataFine)) $sql .= " AND dataFine <= ?";
$sql .= " ORDER BY codice DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBg - Dashboard Quiz</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .pagination-container { display: flex; justify-content: flex-end; align-items: center; margin-top: 20px; gap: 10px; }
        .pagination-btn { background-color: #6a3b5c; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .pagination-btn.disabled { opacity: 0.5; pointer-events: none; }
        
        .action-btns button { margin-right: 5px; padding: 6px 12px; border-radius: 4px; color: white; font-size: 13px; font-weight: bold; border: none; cursor: pointer; }
        .btn-dettagli { background-color: #2196F3; }
        .btn-dettagli:hover { background-color: #1976D2; }
        .btn-elimina { background-color: #F44336; }
        .btn-elimina:hover { background-color: #D32F2F; }
        
        .quiz-title-link { color: #6a3b5c; text-decoration: none; font-weight: bold; }
        .quiz-title-link:hover { text-decoration: underline; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-aggiungi-malva { background-color: #6a3b5c; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; font-size: 14px; border: none; cursor: pointer; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; }
        .modal-box { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 450px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .close-modal { position: absolute; top: 15px; right: 15px; font-size: 24px; cursor: pointer; border: none; background: none; }
        .form-group-modal { margin-bottom: 15px; text-align: left; }
        .form-group-modal label { display: block; margin-bottom: 5px; font-weight: bold; color: #522d48; }
        .form-group-modal input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-salva-malva { background-color: #6a3b5c; color: white; border: none; padding: 12px; width: 100%; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 10px; }
    </style>
</head>


<body>

    <header>Università degli Studi di Bergamo - Dashboard</header>

    <nav>
        <a href="index.php" class="active">Dashboard Quiz</a>
        <a href="utenti.php">Statistiche Utenti</a>
        <a href="partecipazioni.php">Registro Partecipazioni</a>
        <a href = "lista_quiz.php"> Svolgi Quiz </a>
    </nav>
    

    <div class="main-container">
        <aside>
            <h2>Filtri Ricerca</h2>
            <form id="filterForm" method="GET" action="index.php">
                <div class="form-group"><label>Cerca Quiz:</label><input type="text" name="search_titolo" placeholder="Es. Mondiali..." value="<?php echo htmlspecialchars($search_titolo); ?>"></div>
                <div class="form-group" style="margin-top: 10px;"><label>Creatore:</label><input type="text" name="search_creatore" placeholder="Username o Nome utente" value="<?php echo htmlspecialchars($search_creatore); ?>"></div>
                <div class="form-group" style="margin-top: 10px;"><label>A partire dal:</label><input type="date" name="search_dataInizio" value="<?php echo htmlspecialchars($search_dataInizio); ?>"></div>
                <div class="form-group" style="margin-top: 10px;"><label>Fino al:</label><input type="date" name="search_dataFine" value="<?php echo htmlspecialchars($search_dataFine); ?>"></div>
                <button type="submit" style="margin-top:15px; width:100%; padding:8px; background:#6a3b5c; color:white; border:none; border-radius:4px; cursor:pointer;">Applica Filtri</button>
            </form>
        </aside>


        <main>
            <div class="header-actions">
                <h2 style="margin: 0;">I tuoi Quiz</h2>
                <button onclick="document.getElementById('quizModal').style.display = 'flex'" class="btn-aggiungi-malva">+ Aggiungi Nuovo Quiz</button>
            </div>

            <table class="quiz-table">
                <thead>
                    <tr><th>Titolo</th><th>Creatore</th><th>Data Inizio</th><th>Data Fine</th><th>Azioni</th></tr>
                </thead>
                <tbody>
                    <?php if (count($quizzes) > 0): ?>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><a href="views/quiz_dettaglio.php?codice=<?php echo $quiz['codice']; ?>" class="quiz-title-link"><?php echo htmlspecialchars($quiz['titolo']); ?></a></td>
                                <td><?php echo htmlspecialchars($quiz['creatore'] ?? 'N/D'); ?></td>
                                <td><?php echo isset($quiz['dataInizio']) ? date('d/m/Y', strtotime($quiz['dataInizio'])) : 'N/D'; ?></td>
                                <td><?php echo isset($quiz['dataFine']) ? date('d/m/Y', strtotime($quiz['dataFine'])) : 'N/D'; ?></td>
                                <td class="action-btns">
                                    <a href="views/quiz_dettaglio.php?codice=<?php echo $quiz['codice']; ?>" style="text-decoration: none;"><button class="btn-dettagli">Modifica</button></a>
                                    <a href="index.php?elimina_quiz=<?php echo $quiz['codice']; ?>" style="text-decoration: none;">
                                        <button class="btn-elimina" onclick="return confirm('Sei sicuro di voler eliminare l\'intero quiz e tutte le sue domande?');">Elimina</button>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">Nessun quiz trovato.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <span>Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>
                    <a href="?page=<?php echo $page - 1; ?>&search_titolo=<?php echo urlencode($search_titolo); ?>" class="pagination-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">&larr; Prec</a>
                    <a href="?page=<?php echo $page + 1; ?>&search_titolo=<?php echo urlencode($search_titolo); ?>" class="pagination-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">Succ &rarr;</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="quizModal" class="modal-overlay">
        <div class="modal-box">
            <button class="close-modal" onclick="document.getElementById('quizModal').style.display='none'">&times;</button>
            <h2 style="margin-top: 0; color: #522d48;">Crea Nuovo Quiz</h2>
            <form method="POST" action="index.php">
                <input type="hidden" name="modalAction" value="insert">
                <div class="form-group-modal"><label>Titolo del Quiz</label><input type="text" name="modalTitolo" required></div>
                <div class="form-group-modal"><label>Data di Apertura</label><input type="date" name="modalInizio" required></div>
                <div class="form-group-modal"><label>Data di Chiusura</label><input type="date" name="modalFine" required></div>
                <button type="submit" class="btn-salva-malva">Crea e Procedi</button>
            </form>
        </div>
    </div>

    
    <footer>
        <div>Pannello di Amministrazione Quiz</div>
        <div class="disclaimer">&copy; 2026 - Progetto Universitario ad uso didattico - Università degli Studi di Bergamo</div>
    </footer>

</body>
</html>