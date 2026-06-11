<?php
header('Content-Type: application/json');
require_once '../Includes/db.php';

try {
    $resUtenti = $pdo->query("SELECT COUNT(*) FROM Utente")->fetchColumn();
    $resPart = $pdo->query("SELECT COUNT(*) FROM Partecipazione")->fetchColumn();

    $sql = "SELECT 
                u.nomeUtente, u.nome, u.cognome, u.eMail,
                COUNT(DISTINCT q.codice) AS quiz_creati,
                COUNT(DISTINCT p.codice) AS quiz_svolti
            FROM Utente u
            LEFT JOIN Quiz q ON u.nomeUtente = q.creatore
            LEFT JOIN Partecipazione p ON u.nomeUtente = p.utente
            GROUP BY u.nomeUtente
            ORDER BY quiz_creati DESC, quiz_svolti DESC";
            
    $stmt = $pdo->query($sql);
    $utentiData = $stmt->fetchAll();

    echo json_encode([
        'totale_utenti' => $resUtenti,
        'totale_partecipazioni' => $resPart,
        'utenti' => $utentiData
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore DB: ' . $e->getMessage()]);
}
?>