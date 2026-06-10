<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/Database.php';

$db = new Database($pdo);
$action = $_GET['action'] ?? '';
$entity = $_GET['entity'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // ===== QUIZ =====
    if ($entity === 'quiz') {
        if ($action === 'list' && $method === 'GET') {
            echo json_encode($db->getQuizzes());
        } elseif ($action === 'get' && $method === 'GET') {
            $codice = $_GET['codice'] ?? null;
            if (!$codice) throw new Exception('Codice mancante');
            echo json_encode($db->getQuizById($codice));
        } elseif ($action === 'create' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $titolo = $data['titolo'] ?? null;
            $creatore = $data['creatore'] ?? null;
            $dataInizio = $data['dataInizio'] ?? null;
            $dataFine = $data['dataFine'] ?? null;
            if (!$titolo || !$creatore) throw new Exception('Titolo e Creatore obbligatori');
            $result = $db->createQuiz($titolo, $creatore, $dataInizio, $dataFine);
            echo json_encode(['success' => $result, 'message' => 'Quiz creato']);
        } elseif ($action === 'update' && $method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codice = $data['codice'] ?? null;
            $titolo = $data['titolo'] ?? null;
            $creatore = $data['creatore'] ?? null;
            $dataInizio = $data['dataInizio'] ?? null;
            $dataFine = $data['dataFine'] ?? null;
            if (!$codice || !$titolo) throw new Exception('Codice e Titolo obbligatori');
            $result = $db->updateQuiz($codice, $titolo, $creatore, $dataInizio, $dataFine);
            echo json_encode(['success' => $result, 'message' => 'Quiz aggiornato']);
        } elseif ($action === 'delete' && $method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codice = $data['codice'] ?? null;
            if (!$codice) throw new Exception('Codice mancante');
            $result = $db->deleteQuiz($codice);
            echo json_encode(['success' => $result, 'message' => 'Quiz eliminato']);
        }
    }
    
    // ===== DOMANDE =====
    elseif ($entity === 'domanda') {
        if ($action === 'list' && $method === 'GET') {
            $quiz = $_GET['quiz'] ?? null;
            if (!$quiz) throw new Exception('Quiz mancante');
            echo json_encode($db->getDomandeByQuizId($quiz));
        } elseif ($action === 'get' && $method === 'GET') {
            $codice = $_GET['codice'] ?? null;
            if (!$codice) throw new Exception('Codice mancante');
            echo json_encode($db->getDomandaById($codice));
        } elseif ($action === 'create' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $quiz = $data['quiz'] ?? null;
            $utente = $data['utente'] ?? null;
            $data_value = $data['data'] ?? null;
            if (!$quiz || !$utente) throw new Exception('Quiz e Utente obbligatori');
            $result = $db->createDomanda($quiz, $utente, $data_value);
            echo json_encode(['success' => $result, 'message' => 'Domanda creata']);
        } elseif ($action === 'update' && $method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codice = $data['codice'] ?? null;
            $quiz = $data['quiz'] ?? null;
            $utente = $data['utente'] ?? null;
            if (!$codice) throw new Exception('Codice obbligatorio');
            $result = $db->updateDomanda($codice, $quiz, $utente);
            echo json_encode(['success' => $result, 'message' => 'Domanda aggiornata']);
        } elseif ($action === 'delete' && $method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codice = $data['codice'] ?? null;
            if (!$codice) throw new Exception('Codice mancante');
            $result = $db->deleteDomanda($codice);
            echo json_encode(['success' => $result, 'message' => 'Domanda eliminata']);
        }
    }
    
    // ===== RISPOSTE =====
    elseif ($entity === 'risposta') {
        if ($action === 'list' && $method === 'GET') {
            $domanda = $_GET['domanda'] ?? null;
            if (!$domanda) throw new Exception('Domanda mancante');
            echo json_encode($db->getRisposteByDomanda($domanda));
        } elseif ($action === 'get' && $method === 'GET') {
            $domanda = $_GET['domanda'] ?? null;
            $numero = $_GET['numero'] ?? null;
            if (!$domanda || !$numero) throw new Exception('Domanda e Numero obbligatori');
            echo json_encode($db->getRispostaById($domanda, $numero));
        } elseif ($action === 'create' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $domanda = $data['domanda'] ?? null;
            $numero = $data['numero'] ?? null;
            $testo = $data['testo'] ?? null;
            $tipo = $data['tipo'] ?? 'text';
            $quiz = $data['quiz'] ?? null;
            $punteggio = $data['punteggio'] ?? null;
            if (!$domanda || !$numero || !$testo || !$quiz) throw new Exception('Domanda, Numero, Testo e Quiz obbligatori');
            $result = $db->createRisposta($domanda, $numero, $testo, $tipo, $quiz, $punteggio);
            echo json_encode(['success' => $result, 'message' => 'Risposta creata']);
        } elseif ($action === 'update' && $method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $domanda = $data['domanda'] ?? null;
            $numero = $data['numero'] ?? null;
            $testo = $data['testo'] ?? null;
            $tipo = $data['tipo'] ?? 'text';
            $punteggio = $data['punteggio'] ?? null;
            if (!$domanda || !$numero || !$testo) throw new Exception('Domanda, Numero e Testo obbligatori');
            $result = $db->updateRisposta($domanda, $numero, $testo, $tipo, $punteggio);
            echo json_encode(['success' => $result, 'message' => 'Risposta aggiornata']);
        } elseif ($action === 'delete' && $method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $domanda = $data['domanda'] ?? null;
            $numero = $data['numero'] ?? null;
            if (!$domanda || !$numero) throw new Exception('Domanda e Numero mancanti');
            $result = $db->deleteRisposta($domanda, $numero);
            echo json_encode(['success' => $result, 'message' => 'Risposta eliminata']);
        }
    }
    
    // ===== UTENTI =====
    elseif ($entity === 'utente') {
        if ($action === 'list' && $method === 'GET') {
            echo json_encode($db->getUtenti());
        } elseif ($action === 'get' && $method === 'GET') {
            $nomeUtente = $_GET['nomeUtente'] ?? null;
            if (!$nomeUtente) throw new Exception('Nome Utente mancante');
            echo json_encode($db->getUtenteByNomeUtente($nomeUtente));
        } elseif ($action === 'create' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $nomeUtente = $data['nomeUtente'] ?? null;
            $nome = $data['nome'] ?? null;
            $cognome = $data['cognome'] ?? null;
            $eMail = $data['eMail'] ?? null;
            if (!$nomeUtente || !$eMail) throw new Exception('Nome Utente e Email obbligatori');
            $result = $db->createUtente($nomeUtente, $nome, $cognome, $eMail);
            echo json_encode(['success' => $result, 'message' => 'Utente creato']);
        } elseif ($action === 'update' && $method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $nomeUtente = $data['nomeUtente'] ?? null;
            $nome = $data['nome'] ?? null;
            $cognome = $data['cognome'] ?? null;
            $eMail = $data['eMail'] ?? null;
            if (!$nomeUtente) throw new Exception('Nome Utente obbligatorio');
            $result = $db->updateUtente($nomeUtente, $nome, $cognome, $eMail);
            echo json_encode(['success' => $result, 'message' => 'Utente aggiornato']);
        } elseif ($action === 'delete' && $method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $nomeUtente = $data['nomeUtente'] ?? null;
            if (!$nomeUtente) throw new Exception('Nome Utente mancante');
            $result = $db->deleteUtente($nomeUtente);
            echo json_encode(['success' => $result, 'message' => 'Utente eliminato']);
        }
    }
    
    // ===== PARTECIPAZIONE =====
    elseif ($entity === 'partecipazione') {
        if ($action === 'list' && $method === 'GET') {
            $quiz = $_GET['quiz'] ?? null;
            echo json_encode($db->getPartecipazioni($quiz));
        } elseif ($action === 'get' && $method === 'GET') {
            $codice = $_GET['codice'] ?? null;
            if (!$codice) throw new Exception('Codice mancante');
            echo json_encode($db->getPartecipazioneByCodice($codice));
        } elseif ($action === 'create' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $quiz = $data['quiz'] ?? null;
            $utente = $data['utente'] ?? null;
            $data_value = $data['data'] ?? null;
            if (!$quiz || !$utente) throw new Exception('Quiz e Utente obbligatori');
            $result = $db->createPartecipazione($quiz, $utente, $data_value);
            echo json_encode(['success' => $result, 'message' => 'Partecipazione creata']);
        } elseif ($action === 'update' && $method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codice = $data['codice'] ?? null;
            $quiz = $data['quiz'] ?? null;
            $utente = $data['utente'] ?? null;
            $data_value = $data['data'] ?? null;
            if (!$codice) throw new Exception('Codice obbligatorio');
            $result = $db->updatePartecipazione($codice, $quiz, $utente, $data_value);
            echo json_encode(['success' => $result, 'message' => 'Partecipazione aggiornata']);
        } elseif ($action === 'delete' && $method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codice = $data['codice'] ?? null;
            if (!$codice) throw new Exception('Codice mancante');
            $result = $db->deletePartecipazione($codice);
            echo json_encode(['success' => $result, 'message' => 'Partecipazione eliminata']);
        }
    }
    
    // ===== RISPOSTE UTENTE QUIZ =====
    elseif ($entity === 'rispostaUtenteQuiz') {
        if ($action === 'list' && $method === 'GET') {
            $partecipazione = $_GET['partecipazione'] ?? null;
            echo json_encode($db->getRisposteUtenteQuiz($partecipazione));
        } elseif ($action === 'create' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $domanda = $data['domanda'] ?? null;
            $partecipazione = $data['partecipazione'] ?? null;
            $quiz = $data['quiz'] ?? null;
            $risposta = $data['risposta'] ?? null;
            if (!$domanda || !$partecipazione || !$quiz || !$risposta) throw new Exception('Tutti i campi sono obbligatori');
            $result = $db->createRispostaUtenteQuiz($domanda, $partecipazione, $quiz, $risposta);
            echo json_encode(['success' => $result, 'message' => 'Risposta registrata']);
        } elseif ($action === 'delete' && $method === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);
            $domanda = $data['domanda'] ?? null;
            $partecipazione = $data['partecipazione'] ?? null;
            $quiz = $data['quiz'] ?? null;
            $risposta = $data['risposta'] ?? null;
            if (!$domanda || !$partecipazione || !$quiz || !$risposta) throw new Exception('Tutti i campi sono obbligatori');
            $result = $db->deleteRispostaUtenteQuiz($domanda, $partecipazione, $quiz, $risposta);
            echo json_encode(['success' => $result, 'message' => 'Risposta eliminata']);
        }
    }
    
    else {
        throw new Exception('Entità non trovata');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
