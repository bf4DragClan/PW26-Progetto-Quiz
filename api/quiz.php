<?php
// Endpoint REST per la gestione dei quiz: smista la richiesta in base al metodo HTTP utilizzato
header('Content-Type: application/json');
require_once '../Includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET': // Restituisce l'elenco dei quiz, con filtri opzionali su titolo, creatore e data
        $search = $_GET['search'] ?? '';
        $creator = $_GET['creator'] ?? '';
        $date = $_GET['date'] ?? '';

        // Costruzione dinamica della query in base ai filtri effettivamente forniti
        $sql = "SELECT * FROM Quiz WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND titolo LIKE :search";
            $params['search'] = "%$search%";
        }
        if (!empty($creator)) {
            $sql .= " AND creatore LIKE :creator";
            $params['creator'] = "%$creator%";
        }
        if (!empty($date)) {
            $sql .= " AND dataInizio >= :date";
            $params['date'] = $date;
        }

        $sql .= " ORDER BY codice DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST': // Crea un nuovo quiz a partire dai dati ricevuti in formato JSON
        $data = json_decode(file_get_contents('php://input'), true);

        // Validazione dei campi obbligatori prima di procedere con l'inserimento
        if(empty($data['creatore']) || empty($data['titolo'])) {
            echo json_encode(['error' => 'Campi obbligatori mancanti']);
            http_response_code(400);
            exit;
        }

        $sql = "INSERT INTO Quiz (creatore, titolo, dataInizio, dataFine) VALUES (:creatore, :titolo, :dataInizio, :dataFine)";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([
                'creatore' => $data['creatore'],
                'titolo' => $data['titolo'],
                'dataInizio' => $data['dataInizio'],
                'dataFine' => $data['dataFine']
            ]);
            echo json_encode(['success' => 'Quiz creato']);
        } catch (PDOException $e) {
            // L'errore più probabile è la violazione del vincolo di chiave esterna sul creatore
            http_response_code(500);
            echo json_encode(['error' => 'Impossibile creare: l\'utente autore inserito esiste nel Database?']);
        }
        break;

    case 'PUT': // Aggiorna i dati di un quiz esistente, identificato dal codice
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "UPDATE Quiz SET titolo = :titolo, dataInizio = :dataInizio, dataFine = :dataFine WHERE codice = :codice";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'titolo' => $data['titolo'],
            'dataInizio' => $data['dataInizio'],
            'dataFine' => $data['dataFine'],
            'codice' => $data['codice']
        ]);
        echo json_encode(['success' => 'Quiz modificato']);
        break;

    case 'DELETE': // Elimina il quiz identificato dal codice ricevuto
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "DELETE FROM Quiz WHERE codice = :codice";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['codice' => $data['codice']]);
        echo json_encode(['success' => 'Quiz eliminato']);
        break;
}
?>