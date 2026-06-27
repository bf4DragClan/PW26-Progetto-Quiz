<?php
// Inclusione della configurazione di connessione al database
require_once 'Includes/db.php';

// --- LETTURA DEI PARAMETRI DI FILTRO RICEVUTI VIA GET ---
$search_titolo = isset($_GET['search_titolo']) ? trim($_GET['search_titolo']) : '';
$search_creatore = isset($_GET['search_creatore']) ? trim($_GET['search_creatore']) : '';
$search_dataInizio = isset($_GET['search_dataInizio']) ? $_GET['search_dataInizio'] : '';
$search_dataFine = isset($_GET['search_dataFine']) ? $_GET['search_dataFine'] : '';

// --- CONFIGURAZIONE DELLA PAGINAZIONE ---
$limit = 12; // Numero di schede per pagina: multiplo di 3 e 4, per ottenere una griglia uniforme su diverse risoluzioni
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Conteggio del numero totale di record corrispondenti ai filtri applicati, necessario per calcolare il numero di pagine
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

// Normalizzazione della pagina richiesta, nel caso superi il numero massimo di pagine disponibili
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Recupero del sottoinsieme di record corrispondente alla pagina corrente, applicando gli stessi filtri della query di conteggio
$sql = "SELECT * FROM Quiz WHERE 1=1";
if (!empty($search_titolo)) $sql .= " AND titolo LIKE :titolo";
if (!empty($search_creatore)) $sql .= " AND creatore LIKE :creatore";
if (!empty($search_dataInizio)) $sql .= " AND dataInizio >= :dataInizio";
if (!empty($search_dataFine)) $sql .= " AND dataFine <= :dataFine";
$sql .= " ORDER BY dataInizio DESC LIMIT :limit OFFSET :offset";

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
    <title>UniBg - Seleziona un Quiz</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Layout a griglia per le schede dei quiz */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        /* Stile della singola scheda quiz */
        .quiz-card {
            background: #ffffff;
            border: 1px solid #e0cdd8;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 6px solid #6a3b5c; /* Colore distintivo dell'istituto */
            text-decoration: none; /* Rimozione della sottolineatura predefinita del link */
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 150px;
        }

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(106, 59, 92, 0.15);
            border-left-color: #522d48;
        }

        .quiz-card h3 {
            color: #6a3b5c;
            margin: 0 0 10px 0;
            font-size: 18px;
            line-height: 1.3;
        }

        .quiz-card-info {
            font-size: 14px;
            color: #555;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }

        .quiz-card-info strong {
            color: #333;
            margin-right: 5px;
            min-width: 80px;
        }

        .btn-avvia {
            margin-top: 15px;
            background-color: #f9f4f7;
            color: #6a3b5c;
            text-align: center;
            padding: 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
            border: 1px solid #e0cdd8;
            transition: 0.2s;
        }

        .quiz-card:hover .btn-avvia {
            background-color: #6a3b5c;
            color: white;
            border-color: #6a3b5c;
        }

        /* Stili per il blocco di paginazione */
        .pagination-container { display: flex; justify-content: flex-end; align-items: center; margin-top: 20px; gap: 10px; }
        .pagination-btn { background-color: #6a3b5c; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .pagination-btn.disabled { opacity: 0.5; pointer-events: none; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #6a3b5c; padding-bottom: 10px; }
    </style>
</head>
<body>

    <header>Università degli Studi di Bergamo - Lista Quiz</header>

    <nav>
        <a href="index.php">Dashboard Quiz</a>
        <a href="utenti.php">Statistiche Utenti</a>
        <a href="partecipazioni.php">Registro Partecipazioni</a>
        <a href="lista_quiz.php" class="active">Svolgi Quiz</a>
    </nav>


    <div class="main-container">
        <aside>
            <h2>Filtra i Quiz</h2>
            <form id="filterForm" method="GET" action="">
                <div class="form-group">
                    <label for="search_titolo">Cerca Titolo:</label>
                    <input type="text" id="search_titolo" name="search_titolo" placeholder="Es. Programmazione..." value="<?php echo htmlspecialchars($search_titolo); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_creatore">Creatore:</label>
                    <input type="text" id="search_creatore" name="search_creatore" placeholder="Nome autore..." value="<?php echo htmlspecialchars($search_creatore); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_dataInizio">Dal:</label>
                    <input type="date" id="search_dataInizio" name="search_dataInizio" value="<?php echo htmlspecialchars($search_dataInizio); ?>">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="search_dataFine">Al:</label>
                    <input type="date" id="search_dataFine" name="search_dataFine" value="<?php echo htmlspecialchars($search_dataFine); ?>">
                </div>
            </form>
        </aside>

        <main>
            <div class="header-actions">
                <h2 style="margin: 0; color: #522d48;">Scegli un quiz da svolgere</h2>
            </div>

            <div class="cards-grid" id="cardsGrid">
                <?php if (count($quizzes) > 0): ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <a href="views/svolgi_quiz.php?codice=<?php echo $quiz['codice']; ?>" class="quiz-card">
                            <div>
                                <h3><?php echo htmlspecialchars($quiz['titolo']); ?></h3>
                                <div class="quiz-card-info">
                                    <strong>Docente:</strong> <?php echo htmlspecialchars($quiz['creatore'] ?? 'N/D'); ?>
                                </div>
                                <div class="quiz-card-info">
                                    <strong>Inizio:</strong> <?php echo isset($quiz['dataInizio']) ? date('d/m/Y', strtotime($quiz['dataInizio'])) : 'N/D'; ?>
                                </div>
                                <div class="quiz-card-info">
                                    <strong>Scadenza:</strong> <?php echo isset($quiz['dataFine']) ? date('d/m/Y', strtotime($quiz['dataFine'])) : 'N/D'; ?>
                                </div>
                            </div>
                            <div class="btn-avvia">
                                Inizia Test &rarr;
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #777; background: #fff; border-radius: 8px;">
                        Nessun quiz disponibile con i filtri selezionati.
                    </div>
                <?php endif; ?>
            </div>

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

    <script>
    // Gestione della ricerca dinamica: i filtri vengono applicati via AJAX, aggiornando le schede senza ricaricare la pagina
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');
        const inputs = form.querySelectorAll('input');
        let timeout = null;

        // Impedisce il submit tradizionale del form, gestito interamente lato JavaScript
        form.addEventListener('submit', e => e.preventDefault());

        inputs.forEach(input => {
            input.addEventListener('input', () => {
                // Debounce di 300ms: evita di inviare una richiesta ad ogni singolo carattere digitato
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const url = new URL(window.location.pathname, window.location.origin);
                    const params = new URLSearchParams(new FormData(form));
                    url.search = params.toString();

                    // Richiesta della pagina aggiornata e sostituzione del solo contenuto interessato (schede e paginazione)
                    fetch(url)
                        .then(res => res.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            document.getElementById('cardsGrid').innerHTML = doc.getElementById('cardsGrid').innerHTML;
                            document.getElementById('paginationWrapper').innerHTML = doc.getElementById('paginationWrapper').innerHTML;
                            window.history.replaceState({}, '', url);
                        });
                }, 300); 
            });
        });
    });
    </script>
  
    <footer>
        <div>Lista dei Quiz Disponibili</div>
        <div class="disclaimer">&copy; 2026 - Progetto Universitario ad uso didattico - Università degli Studi di Bergamo</div>
    </footer>
</body>
</html>