<?php
header('Content-Type: application/json');
require_once DIR . '/../Includes/db.php';

try {
    $rows = $pdo->query(
        "SELECT q.codice, q.titolo, q.dataInizio, q.dataFine,
                q.creatore, u.nome, u.cognome,
                COUNT(DISTINCT d.numero) AS num_domande
         FROM Quiz q
         JOIN Utente u ON u.nomeUtente = q.creatore
         LEFT JOIN Domanda d ON d.quiz = q.codice
         GROUP BY q.codice
         ORDER BY q.dataInizio DESC"
    )->fetchAll();
    echo json_encode(['ok' => true, 'data' => $rows]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} 
