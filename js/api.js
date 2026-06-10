// API Helper Class
class CRUDApi {
    constructor(baseUrl = null) {
        // Se baseUrl non è specificato, usa il percorso relativo
        this.baseUrl = baseUrl || './api/crud.php';
    }

    async request(entity, action, method = 'GET', data = null, params = {}) {
        // Costruisci l'URL basato sul percorso corrente
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/').filter(p => p);
        
        // Rimuovi l'ultimo elemento (template.php) e costruisci il base path
        pathParts.pop();
        const basePath = '/' + pathParts.join('/');
        
        // Costruisci l'URL completo
        const apiUrl = window.location.origin + basePath + '/api/crud.php';
        
        const url = new URL(apiUrl);
        url.searchParams.append('entity', entity);
        url.searchParams.append('action', action);
        
        for (let key in params) {
            url.searchParams.append(key, params[key]);
        }

        console.log('Current path:', currentPath);
        console.log('Base path:', basePath);
        console.log('API Request:', url.toString());

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url.toString(), options);
            const result = await response.json();
            
            console.log('API Response:', result);
            
            if (!response.ok && !result.success) {
                throw new Error(result.error || 'Errore nella richiesta');
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // ===== QUIZ =====
    getQuizzes() {
        return this.request('quiz', 'list', 'GET');
    }

    getQuiz(codice) {
        return this.request('quiz', 'get', 'GET', null, { codice });
    }

    createQuiz(titolo, creatore, dataInizio, dataFine) {
        return this.request('quiz', 'create', 'POST', { titolo, creatore, dataInizio, dataFine });
    }

    updateQuiz(codice, titolo, creatore, dataInizio, dataFine) {
        return this.request('quiz', 'update', 'PUT', { codice, titolo, creatore, dataInizio, dataFine });
    }

    deleteQuiz(codice) {
        return this.request('quiz', 'delete', 'DELETE', { codice });
    }

    // ===== DOMANDE =====
    getDomande(quiz) {
        return this.request('domanda', 'list', 'GET', null, { quiz });
    }

    getDomanda(codice) {
        return this.request('domanda', 'get', 'GET', null, { codice });
    }

    createDomanda(quiz, utente, data) {
        return this.request('domanda', 'create', 'POST', { quiz, utente, data });
    }

    updateDomanda(codice, quiz, utente) {
        return this.request('domanda', 'update', 'PUT', { codice, quiz, utente });
    }

    deleteDomanda(codice) {
        return this.request('domanda', 'delete', 'DELETE', { codice });
    }

    // ===== RISPOSTE =====
    getRisposte(domanda) {
        return this.request('risposta', 'list', 'GET', null, { domanda });
    }

    getRisposta(domanda, numero) {
        return this.request('risposta', 'get', 'GET', null, { domanda, numero });
    }

    createRisposta(domanda, numero, testo, tipo, quiz, punteggio) {
        return this.request('risposta', 'create', 'POST', { domanda, numero, testo, tipo, quiz, punteggio });
    }

    updateRisposta(domanda, numero, testo, tipo, punteggio) {
        return this.request('risposta', 'update', 'PUT', { domanda, numero, testo, tipo, punteggio });
    }

    deleteRisposta(domanda, numero) {
        return this.request('risposta', 'delete', 'DELETE', { domanda, numero });
    }

    // ===== UTENTI =====
    getUtenti() {
        return this.request('utente', 'list', 'GET');
    }

    getUtente(nomeUtente) {
        return this.request('utente', 'get', 'GET', null, { nomeUtente });
    }

    createUtente(nomeUtente, nome, cognome, eMail) {
        return this.request('utente', 'create', 'POST', { nomeUtente, nome, cognome, eMail });
    }

    updateUtente(nomeUtente, nome, cognome, eMail) {
        return this.request('utente', 'update', 'PUT', { nomeUtente, nome, cognome, eMail });
    }

    deleteUtente(nomeUtente) {
        return this.request('utente', 'delete', 'DELETE', { nomeUtente });
    }

    // ===== PARTECIPAZIONE =====
    getPartecipazioni(quiz = null) {
        const params = quiz ? { quiz } : {};
        return this.request('partecipazione', 'list', 'GET', null, params);
    }

    getPartecipazione(codice) {
        return this.request('partecipazione', 'get', 'GET', null, { codice });
    }

    createPartecipazione(quiz, utente, data) {
        return this.request('partecipazione', 'create', 'POST', { quiz, utente, data });
    }

    updatePartecipazione(codice, quiz, utente, data) {
        return this.request('partecipazione', 'update', 'PUT', { codice, quiz, utente, data });
    }

    deletePartecipazione(codice) {
        return this.request('partecipazione', 'delete', 'DELETE', { codice });
    }

    // ===== RISPOSTE UTENTE QUIZ =====
    getRisposteUtenteQuiz(partecipazione = null) {
        const params = partecipazione ? { partecipazione } : {};
        return this.request('rispostaUtenteQuiz', 'list', 'GET', null, params);
    }

    createRispostaUtenteQuiz(domanda, partecipazione, quiz, risposta) {
        return this.request('rispostaUtenteQuiz', 'create', 'POST', { domanda, partecipazione, quiz, risposta });
    }

    deleteRispostaUtenteQuiz(domanda, partecipazione, quiz, risposta) {
        return this.request('rispostaUtenteQuiz', 'delete', 'DELETE', { domanda, partecipazione, quiz, risposta });
    }
}

// Initialize API
const api = new CRUDApi();
