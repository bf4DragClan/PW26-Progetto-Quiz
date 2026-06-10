<?php
require_once DIR . '/db.php';

class Database {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ===== QUIZ CRUD =====
    public function getQuizzes() {
        $stmt = $this->pdo->query("SELECT * FROM quiz ORDER BY dataInizio DESC");
        return $stmt->fetchAll();
    }

    public function getQuizById($codice) {
        $stmt = $this->pdo->prepare("SELECT * FROM quiz WHERE codice = ?");
        $stmt->execute([$codice]);
        return $stmt->fetch();
    }

    public function createQuiz($titolo, $creatore, $dataInizio, $dataFine) {
        $stmt = $this->pdo->prepare("INSERT INTO quiz (titolo, creatore, dataInizio, dataFine) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$titolo, $creatore, $dataInizio, $dataFine]);
    }

    public function updateQuiz($codice, $titolo, $creatore, $dataInizio, $dataFine) {
        $stmt = $this->pdo->prepare("UPDATE quiz SET titolo = ?, creatore = ?, dataInizio = ?, dataFine = ? WHERE codice = ?");
        return $stmt->execute([$titolo, $creatore, $dataInizio, $dataFine, $codice]);
    }

    public function deleteQuiz($codice) {
        $stmt = $this->pdo->prepare("DELETE FROM quiz WHERE codice = ?");
        return $stmt->execute([$codice]);
    }

    // ===== DOMANDE CRUD =====
    public function getDomandeByQuizId($quiz_codice) {
        if ($quiz_codice) {
            $stmt = $this->pdo->prepare("SELECT * FROM domanda WHERE quiz = ? ORDER BY codice ASC");
            $stmt->execute([$quiz_codice]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM domanda ORDER BY codice ASC");
        }
        return $stmt->fetchAll();
    }

    public function getDomandaById($codice) {
        $stmt = $this->pdo->prepare("SELECT * FROM domanda WHERE codice = ?");
        $stmt->execute([$codice]);
        return $stmt->fetch();
    }

    public function createDomanda($quiz, $utente, $data) {
        $stmt = $this->pdo->prepare("INSERT INTO domanda (quiz, utente, data) VALUES (?, ?, ?)");
        return $stmt->execute([$quiz, $utente, $data ?: date('Y-m-d H:i:s')]);
    }

    public function updateDomanda($codice, $quiz, $utente) {
        $stmt = $this->pdo->prepare("UPDATE domanda SET quiz = ?, utente = ? WHERE codice = ?");
        return $stmt->execute([$quiz, $utente, $codice]);
    }

    public function deleteDomanda($codice) {
        $stmt = $this->pdo->prepare("DELETE FROM domanda WHERE codice = ?");
        return $stmt->execute([$codice]);
    }

    // ===== RISPOSTE CRUD =====
    public function getRisposteByDomanda($domanda) {
        $stmt = $this->pdo->prepare("SELECT * FROM risposta WHERE domanda = ? ORDER BY numero ASC");
        $stmt->execute([$domanda]);
        return $stmt->fetchAll();
    }

    public function getRispostaById($domanda, $numero) {
        $stmt = $this->pdo->prepare("SELECT * FROM risposta WHERE domanda = ? AND numero = ?");
        $stmt->execute([$domanda, $numero]);
        return $stmt->fetch();
    }

    public function createRisposta($domanda, $numero, $testo, $tipo, $quiz, $punteggio = null) {
        $stmt = $this->pdo->prepare("INSERT INTO risposta (domanda, numero, testo, tipo, quiz, punteggio) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$domanda, $numero, $testo, $tipo, $quiz, $punteggio]);
    }

    public function updateRisposta($domanda, $numero, $testo, $tipo, $punteggio = null) {
        $stmt = $this->pdo->prepare("UPDATE risposta SET testo = ?, tipo = ?, punteggio = ? WHERE domanda = ? AND numero = ?");
        return $stmt->execute([$testo, $tipo, $punteggio, $domanda, $numero]);
    }

    public function deleteRisposta($domanda, $numero) {
        $stmt = $this->pdo->prepare("DELETE FROM risposta WHERE domanda = ? AND numero = ?");
        return $stmt->execute([$domanda, $numero]);
    }

    // ===== UTENTI CRUD =====
    public function getUtenti() {
        $stmt = $this->pdo->query("SELECT nomeUtente, nome, cognome, eMail FROM utente ORDER BY nomeUtente");
        return $stmt->fetchAll();
    }

    public function getUtenteByNomeUtente($nomeUtente) {
        $stmt = $this->pdo->prepare("SELECT * FROM utente WHERE nomeUtente = ?");
        $stmt->execute([$nomeUtente]);
        return $stmt->fetch();
    }

    public function createUtente($nomeUtente, $nome, $cognome, $eMail) {
        $stmt = $this->pdo->prepare("INSERT INTO utente (nomeUtente, nome, cognome, eMail) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nomeUtente, $nome, $cognome, $eMail]);
    }

    public function updateUtente($nomeUtente, $nome, $cognome, $eMail) {
        $stmt = $this->pdo->prepare("UPDATE utente SET nome = ?, cognome = ?, eMail = ? WHERE nomeUtente = ?");
        return $stmt->execute([$nome, $cognome, $eMail, $nomeUtente]);
    }

    public function deleteUtente($nomeUtente) {
        $stmt = $this->pdo->prepare("DELETE FROM utente WHERE nomeUtente = ?");
        return $stmt->execute([$nomeUtente]);
    }

    // ===== PARTECIPAZIONE CRUD =====
    public function getPartecipazioni($quiz = null) {
        if ($quiz) {
            $stmt = $this->pdo->prepare("SELECT * FROM partecipazione WHERE quiz = ? ORDER BY data DESC");
            $stmt->execute([$quiz]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM partecipazione ORDER BY data DESC");
        }
        return $stmt->fetchAll();
    }

    public function getPartecipazioneByCodice($codice) {
        $stmt = $this->pdo->prepare("SELECT * FROM partecipazione WHERE codice = ?");
        $stmt->execute([$codice]);
        return $stmt->fetch();
    }

    public function createPartecipazione($quiz, $utente, $data = null) {
        $stmt = $this->pdo->prepare("INSERT INTO partecipazione (quiz, utente, data) VALUES (?, ?, ?)");
        return $stmt->execute([$quiz, $utente, $data ?: date('Y-m-d H:i:s')]);
    }

    public function updatePartecipazione($codice, $quiz, $utente, $data) {
        $stmt = $this->pdo->prepare("UPDATE partecipazione SET quiz = ?, utente = ?, data = ? WHERE codice = ?");
        return $stmt->execute([$quiz, $utente, $data, $codice]);
    }

    public function deletePartecipazione($codice) {
        $stmt = $this->pdo->prepare("DELETE FROM partecipazione WHERE codice = ?");
        return $stmt->execute([$codice]);
    }

    // ===== RISPOSTA UTENTE QUIZ CRUD =====
    public function getRisposteUtenteQuiz($partecipazione = null) {
        if ($partecipazione) {
            $stmt = $this->pdo->prepare("SELECT * FROM rispostaUtenteQuiz WHERE partecipazione = ?");
            $stmt->execute([$partecipazione]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM rispostaUtenteQuiz");
        }
        return $stmt->fetchAll();
    }

    public function createRispostaUtenteQuiz($domanda, $partecipazione, $quiz, $risposta) {
        $stmt = $this->pdo->prepare("INSERT INTO rispostaUtenteQuiz (domanda, partecipazione, quiz, risposta) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$domanda, $partecipazione, $quiz, $risposta]);
    }

    public function deleteRispostaUtenteQuiz($domanda, $partecipazione, $quiz, $risposta) {
        $stmt = $this->pdo->prepare("DELETE FROM rispostaUtenteQuiz WHERE domanda = ? AND partecipazione = ? AND quiz = ? AND risposta = ?");
        return $stmt->execute([$domanda, $partecipazione, $quiz, $risposta]);
    }
}
?>
