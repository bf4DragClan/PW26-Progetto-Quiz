<?php
// Inclusione del database
require_once 'Includes/db.php';

// --- 1. GESTIONE SALVATAGGIO DATI DAL POPUP (SOLO AGGIUNTA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modalAction'])) {
    $titolo = trim($_POST['modalTitolo'] ?? '');
    $inizio = $_POST['modalInizio'] ?? '';
    $fine = $_POST['modalFine'] ?? '';
    $azione = $_POST['modalAction'];

    if ($azione === 'insert' && !empty($titolo)) {
        $creatore = 'user_Admin'; 
        $stmt = $pdo->prepare("INSERT INTO Quiz (titolo, creatore, dataInizio, dataFine) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titolo, $creatore, $inizio, $fine]);
        
        // RECUPERA L'ID DEL QUIZ APPENA CREATO E TI CI PORTA DENTRO!
        $new_id = $pdo->lastInsertId();
        header("Location: views/quiz_dettaglio.php?codice=" . $new_id);
        exit;
    }
}

// --- 2. GESTIONE FILTRI IN TEMPO REALE ---
$search_titolo = isset($_GET['search_titolo']) ? trim($_GET['search_titolo']) : '';
$search_creatore = isset($_GET['search_creatore']) ? trim($_GET['search_creatore']) : '';
$search_dataInizio = isset($_GET['search_dataInizio']) ? $_GET['search_dataInizio'] : '';
$search_dataFine = isset($_GET['search_dataFine']) ? $_GET['search_dataFine'] : '';

// --- 3. GESTIONE PAGINAZIONE ---
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Conteggio totale per i filtri applicati
$count_sql = "SELECT COUNT(*) FROM Quiz WHERE 1=1";
if (!empty($search_titolo)) $count_sql .= " AND titolo LIKE :titolo";
if (!empty($search_creatore)) $count_sql .= " AND creatore LIKE :creatore";
if (!empty($search_dataInizio)) $count_sql .= " AND dataInizio >= :dataInizio";
if (!empty($search_dataFine)) $count_sql .= " AND dataFine <= :dataFine";

$count_stmt = $pdo->prepare($count_sql);
if (!empty($search_titolo)) $count_stmt->bindValue(':titolo', '%' . $search_titolo . '%', PDO::PARAM_STR);
if (!empty($search_creatore)) $count_stmt->bindValue(':creatore', '%' . $search_creatore . '%', PDO::PARAM_STR);
if (!empty($search_dataInizio)) $count_stmt->bindValue(':dataInizio', $search_dataInizio, PDO::PARAM_STR);
if (!empty($search_dataFine)) $count_stmt->bindValue(':dataFine', $search_dataFine, PDO::PARAM_STR);
$count_stmt->execute();
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Recupero dei dati dal database
$sql = "SELECT * FROM Quiz WHERE 1=1";
if (!empty($search_titolo)) $sql .= " AND titolo LIKE :titolo";
if (!empty($search_creatore)) $sql .= " AND creatore LIKE :creatore";
if (!empty($search_dataInizio)) $sql .= " AND dataInizio >= :dataInizio";
if (!empty($search_dataFine)) $sql .= " AND dataFine <= :dataFine";
$sql .= " ORDER BY codice DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if (!empty($search_titolo)) $stmt->bindValue(':titolo', '%' . $search_titolo . '%', PDO::PARAM_STR);
if (!empty($search_creatore)) $stmt->bindValue(':creatore', '%' . $search_creatore . '%', PDO::PARAM_STR);
if (!empty($search_dataInizio)) $stmt->bindValue(':dataInizio', $search_dataInizio, PDO::PARAM_STR);
if (!empty($search_dataFine)) $stmt->bindValue(':dataFine', $search_dataFine, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
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
        /* Paginazione e Bottoni Tabella */
        .pagination-container { display: flex; justify-content: flex-end; align-items: center; margin-top: 20px; gap: 10px; }
        .pagination-btn { background-color: #6a3b5c; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .pagination-btn.disabled { opacity: 0.5; pointer-events: none; }
        
        /* Bottoni Azioni */
        .action-btns button { margin-right: 5px; text-decoration: none; padding: 6px 12px; border-radius: 4px; color: white; font-size: 13px; font-weight: bold; border: none; cursor: pointer; font-family: inherit; }
        .btn-dettagli { background-color: #2196F3; }
        .btn-dettagli:hover { background-color: #1976D2; }
        .btn-elimina { background-color: #F44336; }
        .btn-elimina:hover { background-color: #D32F2F; }
        
        .quiz-title-link { color: #6a3b5c; text-decoration: none; font-weight: bold; }
        .quiz-title-link:hover { text-decoration: underline; }
        
        /* Struttura Header con Tasto Aggiungi */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-aggiungi-malva { background-color: #6a3b5c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; transition: 0.2s; border: none; cursor: pointer; font-family: inherit; }
        .btn-aggiungi-malva:hover { background-color: #522d48; }

        /* Stili Pop-up Aggiungi Quiz */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 9999; align-items: center; justify-content: center; }
        .modal-box { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 450px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .close-modal { position: absolute; top: 15px; right: 15px; font-size: 24px; cursor: pointer; color: #888; background: none; border: none; padding: 0; line-height: 1; }
        .close-modal:hover { color: #333; }
        .form-group-modal { margin-bottom: 15px; text-align: left; }
        .form-group-modal label { display: block; margin-bottom: 5px; font-weight: bold; color: #522d48; font-size: 14px; }
        .form-group-modal input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        .btn-salva-malva { background-color: #6a3b5c; color: white; border: none; padding: 12px; width: 100%; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 15px; margin-top: 10px; transition: 0.2s; }
        .btn-salva-malva:hover { background-color: #522d48; }
    </style>
</head>
<body>

    <header>Università degli Studi di Bergamo - Dashboard</header>

    <nav>
        <a href="index.php" class="active">Dashboard Quiz</a>
        <a href="utenti.php">Statistiche Utenti</a>
        <a href="partecipazioni.php">Registro Partecipazioni</a>
    </nav>

    <div class="main-container">
        <aside>
            <h2>Filtri Ricerca</h2>
            <form id="filterForm" method="GET" action="index.php">
                <div class="form-group">
                    <label for="search_titolo">Cerca Quiz:</label>
                    <input type="text" id="search_titolo" name="search_titolo" placeholder="Titolo del quiz..." value="<?php echo htmlspecialchars($search_titolo); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_creatore">Creatore:</label>
                    <input type="text" id="search_creatore" name="search_creatore" placeholder="Nome autore..." value="<?php echo htmlspecialchars($search_creatore); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_dataInizio">A partire dal:</label>
                    <input type="date" id="search_dataInizio" name="search_dataInizio" value="<?php echo htmlspecialchars($search_dataInizio); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_dataFine">Fino al:</label>
                    <input type="date" id="search_dataFine" name="search_dataFine" value="<?php echo htmlspecialchars($search_dataFine); ?>">
                </div>
            </form>
        </aside>

        <main>
            <div class="header-actions">
                <h2 style="margin: 0;">I tuoi Quiz</h2>
                <button onclick="apriModalAggiungi()" class="btn-aggiungi-malva">+ Aggiungi Nuovo Quiz</button>
            </div>

            <table class="quiz-table">
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Creatore</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (count($quizzes) > 0): ?>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td>
                                    <a href="views/quiz_dettaglio.php?codice=<?php echo $quiz['codice']; ?>" class="quiz-title-link">
                                        <?php echo htmlspecialchars($quiz['titolo']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($quiz['creatore'] ?? 'N/D'); ?></td>
                                <td><?php echo isset($quiz['dataInizio']) ? date('d/m/Y', strtotime($quiz['dataInizio'])) : 'N/D'; ?></td>
                                <td><?php echo isset($quiz['dataFine']) ? date('d/m/Y', strtotime($quiz['dataFine'])) : 'N/D'; ?></td>
                                <td class="action-btns">
                                    
                                    <a href="views/quiz_dettaglio.php?codice=<?php echo $quiz['codice']; ?>" style="text-decoration: none;">
                                        <button class="btn-dettagli">Gestisci / Modifica</button>
                                    </a>
                                    
                                    <a href="elimina_quiz.php?id=<?php echo $quiz['codice']; ?>" style="text-decoration: none;">
                                        <button class="btn-elimina" onclick="return confirm('Sei sicuro di voler eliminare questo quiz?');">Elimina</button>
                                    </a>
                                    
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #777;">Nessun quiz trovato con questi criteri.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="paginationWrapper">
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <span style="font-size: 14px; color: #555;">Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>
                        <a href="?page=<?php echo $page - 1; ?>&search_titolo=<?php echo urlencode($search_titolo); ?>&search_creatore=<?php echo urlencode($search_creatore); ?>&search_dataInizio=<?php echo urlencode($search_dataInizio); ?>&search_dataFine=<?php echo urlencode($search_dataFine); ?>" class="pagination-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">&larr; Prec</a>
                        <a href="?page=<?php echo $page + 1; ?>&search_titolo=<?php echo urlencode($search_titolo); ?>&search_creatore=<?php echo urlencode($search_creatore); ?>&search_dataInizio=<?php echo urlencode($search_dataInizio); ?>&search_dataFine=<?php echo urlencode($search_dataFine); ?>" class="pagination-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">Succ &rarr;</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="quizModal" class="modal-overlay">
        <div class="modal-box">
            <button class="close-modal" onclick="chiudiModal()">&times;</button>
            <h2 style="margin-top: 0; color: #522d48;">Crea Nuovo Quiz</h2>
            
            <form id="modalForm" method="POST" action="index.php">
                <input type="hidden" name="modalAction" value="insert">

                <div class="form-group-modal">
                    <label for="modalTitolo">Titolo del Quiz</label>
                    <input type="text" id="modalTitolo" name="modalTitolo" required placeholder="Inserisci il titolo...">
                </div>
                
                <div class="form-group-modal">
                    <label for="modalInizio">Data di Apertura</label>
                    <input type="date" id="modalInizio" name="modalInizio" required>
                </div>
                
                <div class="form-group-modal">
                    <label for="modalFine">Data di Chiusura</label>
                    <input type="date" id="modalFine" name="modalFine" required>
                </div>

                <button type="submit" class="btn-salva-malva">Crea e Procedi alle Domande</button>
            </form>
        </div>
    </div>

    <script>
    // 1. GESTIONE RICERCA IN TEMPO REALE
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');
        const inputs = form.querySelectorAll('input');
        let timeout = null;

        form.addEventListener('submit', e => e.preventDefault());

        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const url = new URL(window.location.pathname, window.location.origin);
                    const params = new URLSearchParams(new FormData(form));
                    url.search = params.toString();

                    fetch(url)
                        .then(res => res.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            document.getElementById('tableBody').innerHTML = doc.getElementById('tableBody').innerHTML;
                            document.getElementById('paginationWrapper').innerHTML = doc.getElementById('paginationWrapper').innerHTML;
                            window.history.replaceState({}, '', url);
                        });
                }, 300); 
            });
        });
    });

    // 2. GESTIONE APERTURA/CHIUSURA POP-UP
    function apriModalAggiungi() {
        document.getElementById('modalForm').reset();
        document.getElementById('quizModal').style.display = 'flex';
    }

    function chiudiModal() {
        document.getElementById('quizModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('quizModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    </script>
</body>
</html>