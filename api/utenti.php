<?php
// Endpoint REST: restituisce le statistiche aggregate su utenti, quiz creati e partecipazioni
header('Content-Type: application/json');
require_once '../Includes/db.php';

try {
    // Conteggi complessivi mostrati nel riepilogo statistico in cima alla pagina
    $resUtenti = $pdo->query("SELECT COUNT(*) FROM Utente")->fetchColumn();
    $resPart = $pdo->query("SELECT COUNT(*) FROM Partecipazione")->fetchColumn();

    // Per ciascun utente, conteggio dei quiz creati (come autore) e svolti (come partecipante).
    // Le COUNT DISTINCT sono necessarie poiché le due LEFT JOIN, eseguite sulla stessa riga utente,
    // generano un prodotto cartesiano tra le partecipazioni e i quiz creati.
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

    // Risposta JSON con riepilogo statistico ed elenco dettagliato degli utenti
    echo json_encode([
        'totale_utenti' => $resUtenti,
        'totale_partecipazioni' => $resPart,
        'utenti' => $utentiData
    ]);

} catch (PDOException $e) {
    // In caso di errore sul database, restituisce il dettaglio dell'eccezione al chiamante
    http_response_code(500);
    echo json_encode(['error' => 'Errore DB: ' . $e->getMessage()]);
}
?>