<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../Includes/db.php';

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

function jsonOk($data)    { echo json_encode(['ok' => true,  'data' => $data]); exit; }
function jsonErr($msg)    { echo json_encode(['ok' => false, 'error' => $msg]); exit; }

try {
    switch ($action) {

        /* ========== UTENTE ========== */
        case 'create_utente': {
            $stmt = $pdo->prepare(
                "INSERT INTO Utente (nomeUtente, nome, cognome, eMail)
                 VALUES (:nu, :n, :c, :e)"
            );
            $stmt->execute([
                ':nu' => $input['nomeUtente'],
                ':n'  => $input['nome'],
                ':c'  => $input['cognome'],
                ':e'  => $input['eMail'],
            ]);
            jsonOk(['nomeUtente' => $input['nomeUtente']]);
        }
        case 'get_utenti': {
            $rows = $pdo->query("SELECT * FROM Utente ORDER BY nomeUtente")->fetchAll();
            jsonOk($rows);
        }
        case 'update_utente': {
            $stmt = $pdo->prepare(
                "UPDATE Utente SET nome=:n, cognome=:c, eMail=:e WHERE nomeUtente=:nu"
            );
            $stmt->execute([
                ':nu' => $input['nomeUtente'],
                ':n'  => $input['nome'],
                ':c'  => $input['cognome'],
                ':e'  => $input['eMail'],
            ]);
            jsonOk(null);
        }
        case 'delete_utente': {
            $stmt = $pdo->prepare("DELETE FROM Utente WHERE nomeUtente=:nu");
            $stmt->execute([':nu' => $input['nomeUtente']]);
            jsonOk(null);
        }

        /* ========== QUIZ ========== */
        case 'create_quiz': {
            $stmt = $pdo->prepare(
                "INSERT INTO Quiz (codice, creatore, titolo, dataInizio, dataFine)
                 VALUES (:cod, :cr, :tit, :di, :df)"
            );
            $stmt->execute([
                ':cod' => $input['codice'],
                ':cr'  => $input['creatore'],
                ':tit' => $input['titolo'],
                ':di'  => $input['dataInizio'],
                ':df'  => $input['dataFine'] ?? null,
            ]);
            jsonOk(['codice' => $input['codice']]);
        }
        case 'get_quizzes': {
            $rows = $pdo->query(
                "SELECT q.*, u.nome, u.cognome
                 FROM Quiz q
                 JOIN Utente u ON u.nomeUtente = q.creatore
                 ORDER BY q.dataInizio DESC"
            )->fetchAll();
            jsonOk($rows);
        }
        case 'get_quiz': {
            $stmt = $pdo->prepare("SELECT * FROM Quiz WHERE codice=:cod");
            $stmt->execute([':cod' => $input['codice'] ?? $_GET['codice']]);
            jsonOk($stmt->fetch());
        }
        case 'update_quiz': {
            $stmt = $pdo->prepare(
                "UPDATE Quiz SET titolo=:tit, dataInizio=:di, dataFine=:df WHERE codice=:cod"
            );
            $stmt->execute([
                ':cod' => $input['codice'],
                ':tit' => $input['titolo'],
                ':di'  => $input['dataInizio'],
                ':df'  => $input['dataFine'] ?? null,
            ]);
            jsonOk(null);
        }
        case 'delete_quiz': {
            $stmt = $pdo->prepare("DELETE FROM Quiz WHERE codice=:cod");
            $stmt->execute([':cod' => $input['codice']]);
            jsonOk(null);
        }

        /* ========== DOMANDA ========== */
        case 'create_domanda': {
            $stmt = $pdo->prepare(
                "INSERT INTO Domanda (quiz, numero, testo) VALUES (:q, :num, :t)"
            );
            $stmt->execute([
                ':q'   => $input['quiz'],
                ':num' => $input['numero'],
                ':t'   => $input['testo'],
            ]);
            jsonOk(null);
        }
        case 'get_domande': {
            $quiz = $input['quiz'] ?? $_GET['quiz'];
            $stmt = $pdo->prepare("SELECT * FROM Domanda WHERE quiz=:q ORDER BY numero");
            $stmt->execute([':q' => $quiz]);
            jsonOk($stmt->fetchAll());
        }
        case 'update_domanda': {
            $stmt = $pdo->prepare(
                "UPDATE Domanda SET testo=:t WHERE quiz=:q AND numero=:num"
            );
            $stmt->execute([
                ':q'   => $input['quiz'],
                ':num' => $input['numero'],
                ':t'   => $input['testo'],
            ]);
            jsonOk(null);
        }
        case 'delete_domanda': {
            $stmt = $pdo->prepare("DELETE FROM Domanda WHERE quiz=:q AND numero=:num");
            $stmt->execute([':q' => $input['quiz'], ':num' => $input['numero']]);
            jsonOk(null);
        }

        /* ========== RISPOSTA ========== */
        case 'create_risposta': {
            $stmt = $pdo->prepare(
                "INSERT INTO Risposta (quiz, domanda, numero, testo, tipo, punteggio)
                 VALUES (:q, :d, :num, :t, :tipo, :p)"
            );
            $stmt->execute([
                ':q'    => $input['quiz'],
                ':d'    => $input['domanda'],
                ':num'  => $input['numero'],
                ':t'    => $input['testo'],
                ':tipo' => $input['tipo'],     // 'Corretta' | 'Sbagliata'
                ':p'    => $input['punteggio'] ?? null,
            ]);
            jsonOk(null);
        }
        case 'get_risposte': {
            $stmt = $pdo->prepare(
                "SELECT * FROM Risposta WHERE quiz=:q AND domanda=:d ORDER BY numero"
            );
            $stmt->execute([
                ':q' => $input['quiz'] ?? $_GET['quiz'],
                ':d' => $input['domanda'] ?? $_GET['domanda'],
            ]);
            jsonOk($stmt->fetchAll());
        }
        case 'update_risposta': {
            $stmt = $pdo->prepare(
                "UPDATE Risposta SET testo=:t, tipo=:tipo, punteggio=:p
                 WHERE quiz=:q AND domanda=:d AND numero=:num"
            );
            $stmt->execute([
                ':q'    => $input['quiz'],
                ':d'    => $input['domanda'],
                ':num'  => $input['numero'],
                ':t'    => $input['testo'],
                ':tipo' => $input['tipo'],
                ':p'    => $input['punteggio'] ?? null,
            ]);
            jsonOk(null);
        }
        case 'delete_risposta': {
            $stmt = $pdo->prepare(
                "DELETE FROM Risposta WHERE quiz=:q AND domanda=:d AND numero=:num"
            );
            $stmt->execute([
                ':q' => $input['quiz'], ':d' => $input['domanda'], ':num' => $input['numero']
            ]);
            jsonOk(null);
        }

        /* ========== PARTECIPAZIONE ========== */
        case 'create_partecipazione': {
            $stmt = $pdo->prepare(
                "INSERT INTO Partecipazione (codice, utente, quiz, data)
                 VALUES (:cod, :u, :q, :data)"
            );
            $stmt->execute([
                ':cod'  => $input['codice'],
                ':u'    => $input['utente'],
                ':q'    => $input['quiz'],
                ':data' => $input['data'] ?? date('Y-m-d'),
            ]);
            jsonOk(['codice' => $input['codice']]);
        }
        case 'get_partecipazioni': {
            $rows = $pdo->query(
                "SELECT p.*, u.nome, u.cognome, q.titolo
                 FROM Partecipazione p
                 JOIN Utente u ON u.nomeUtente = p.utente
                 JOIN Quiz   q ON q.codice     = p.quiz
                 ORDER BY p.data DESC"
            )->fetchAll();
            jsonOk($rows);
        }
        case 'delete_partecipazione': {
            $stmt = $pdo->prepare("DELETE FROM Partecipazione WHERE codice=:cod");
            $stmt->execute([':cod' => $input['codice']]);
            jsonOk(null);
        }

        /* ========== RISPOSTA UTENTE QUIZ ========== */
        case 'create_risposta_utente': {
            $stmt = $pdo->prepare(
                "INSERT INTO RispostaUtenteQuiz (partecipazione, quiz, domanda, risposta)
                 VALUES (:part, :q, :d, :r)"
            );
            $stmt->execute([
                ':part' => $input['partecipazione'],
                ':q'    => $input['quiz'],
                ':d'    => $input['domanda'],
                ':r'    => $input['risposta'],
            ]);
            jsonOk(null);
        }
        case 'get_risposte_utente': {
            $stmt = $pdo->prepare(
                "SELECT ruq.*, r.testo, r.tipo, r.punteggio
                 FROM RispostaUtenteQuiz ruq
                 JOIN Risposta r ON r.quiz=ruq.quiz AND r.domanda=ruq.domanda AND r.numero=ruq.risposta
                 WHERE ruq.partecipazione=:part"
            );
            $stmt->execute([':part' => $input['partecipazione'] ?? $_GET['partecipazione']]);
            jsonOk($stmt->fetchAll());
        }

        default:
            jsonErr("Azione '$action' non riconosciuta.");
    }
} catch (PDOException $e) {
    jsonErr($e->getMessage());
}