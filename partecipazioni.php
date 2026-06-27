<?php
// Inclusione del database
require_once 'Includes/db.php';

// 1. GESTIONE FILTRI
$search_utente = isset($_GET['search_utente']) ? trim($_GET['search_utente']) : '';
$search_data = isset($_GET['search_data']) ? $_GET['search_data'] : '';

// 2. GESTIONE PAGINAZIONE
$limit = 15; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Conteggio record totali
$count_sql = "SELECT COUNT(*) FROM Partecipazione p WHERE 1=1";
if (!empty($search_utente)) $count_sql .= " AND p.utente LIKE :utente";
if (!empty($search_data)) $count_sql .= " AND p.data = :data";

$count_stmt = $pdo->prepare($count_sql);
if (!empty($search_utente)) $count_stmt->bindValue(':utente', '%' . $search_utente . '%', PDO::PARAM_STR);
if (!empty($search_data)) $count_stmt->bindValue(':data', $search_data, PDO::PARAM_STR);
$count_stmt->execute();
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Query dati
$sql = "SELECT p.utente, p.data AS data_partecipazione, q.titolo AS quiz_titolo, SUM(r.punteggio) AS punteggio_totale
        FROM Partecipazione p
        JOIN Quiz q ON p.quiz = q.codice
        LEFT JOIN RispostaUtenteQuiz ruq ON p.codice = ruq.partecipazione
        LEFT JOIN Risposta r ON ruq.quiz = r.quiz AND ruq.domanda = r.domanda AND ruq.risposta = r.numero
        WHERE 1=1";

if (!empty($search_utente)) $sql .= " AND p.utente LIKE :utente";
if (!empty($search_data)) $sql .= " AND p.data = :data";

$sql .= " GROUP BY p.codice, p.utente, p.data, q.titolo 
          ORDER BY data_partecipazione DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if (!empty($search_utente)) $stmt->bindValue(':utente', '%' . $search_utente . '%', PDO::PARAM_STR);
if (!empty($search_data)) $stmt->bindValue(':data', $search_data, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$partecipazioni = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBg - Registro Partecipazioni</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .pagination-container { display: flex; justify-content: flex-end; align-items: center; margin-top: 20px; gap: 10px; }
        .pagination-btn { background-color: #6a3b5c; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .pagination-btn.disabled { opacity: 0.5; pointer-events: none; }
    </style>
</head>
<body>

    <header>Università degli Studi di Bergamo - Partecipazioni</header>

    <nav>
        <a href="index.php">Dashboard Quiz</a>
        <a href="utenti.php">Statistiche Utenti</a>
        <a href="partecipazioni.php" class="active">Registro Partecipazioni</a>
        <a href = "lista_quiz.php"> Svolgi Quiz </a>
    </nav>


    <div class="main-container">
        <aside>
            <h2>Filtri Ricerca</h2>
            <form id="filterForm" method="GET" action="partecipazioni.php">
                <div class="form-group">
                    <label for="search_utente">Cerca Utente:</label>
                    <input type="text" id="search_utente" name="search_utente" placeholder="Es. user_8..." value="<?php echo htmlspecialchars($search_utente); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_data">Svoltosi il:</label>
                    <input type="date" id="search_data" name="search_data" value="<?php echo htmlspecialchars($search_data); ?>">
                </div>
            </form>
        </aside>

        <main>
            <h2>Elenco Partecipazioni e Risultati</h2>
            <table class="quiz-table">
                <thead>
                    <tr>
                        <th>Nome Utente</th>
                        <th>Quiz Svolto</th>
                        <th>Data Sessione</th>
                        
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (count($partecipazioni) > 0): ?>
                        <?php foreach ($partecipazioni as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['utente']); ?></td>
                                <td><?php echo htmlspecialchars($p['quiz_titolo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['data_partecipazione'])); ?></td>
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #777;">Nessuna partecipazione trovata.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="paginationWrapper">
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <span style="font-size: 14px; color: #555;">Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>
                        <a href="?page=<?php echo $page - 1; ?>&search_utente=<?php echo urlencode($search_utente); ?>&search_data=<?php echo urlencode($search_data); ?>" class="pagination-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">&larr; Prec</a>
                        <a href="?page=<?php echo $page + 1; ?>&search_utente=<?php echo urlencode($search_utente); ?>&search_data=<?php echo urlencode($search_data); ?>" class="pagination-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">Succ &rarr;</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
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
    </script>

    <footer>
        <div>Visualizzazione Partecipazioni</div>
        <div class="disclaimer">&copy; 2026 - Progetto Universitario ad uso didattico - Università degli Studi di Bergamo</div>
    </footer>
    
</body>
</html>