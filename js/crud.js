// CRUD Manager
class CRUDManager {
    constructor() {
        this.currentTab = 'quiz';
        this.currentData = [];
        this.editingId = null;
        this.selectedQuizId = null;
        this.init();
    }

    async init() {
        await this.showTab('quiz');
    }

    async showTab(tab) {
        this.currentTab = tab;
        document.getElementById('tabTitle').textContent = this.getTabTitle(tab);
        this.editingId = null;
        await this.loadData(tab);
    }

    async loadData(tab) {
        try {
            console.log('Loading data for tab:', tab);
            console.log('Selected Quiz ID:', this.selectedQuizId);
            let data;
            switch (tab) {
                case 'quiz':
                    data = await api.getQuizzes();
                    break;
                case 'domanda':
                    console.log('Loading domande with quiz filter:', this.selectedQuizId);
                    data = await api.getDomande(this.selectedQuizId);
                    break;
                case 'risposta':
                    const domandaCodice = this.lastDomandaCodice || 1;
                    data = await api.getRisposte(domandaCodice);
                    break;
                case 'partecipazione':
                    data = await api.getPartecipazioni(this.selectedQuizId);
                    break;
                case 'rispostaUtenteQuiz':
                    data = await api.getRisposteUtenteQuiz();
                    break;
                case 'utente':
                    console.log('Loading utenti');
                    data = await api.getUtenti();
                    break;
            }

            console.log('Data received:', data);
            this.currentData = Array.isArray(data) ? data : [data];
            console.log('Current data:', this.currentData);
            this.renderTable(tab);
        } catch (error) {
            console.error('Errore nel caricamento dei dati:', error);
            this.showEmptyState();
        }
    }

    renderTable(tab) {
        const contentTable = document.getElementById('contentTable');
        
        if (!this.currentData || this.currentData.length === 0) {
            this.showEmptyState();
            return;
        }

        let html = '<table>';
        html += '<thead><tr>';

        const headers = this.getHeaders(tab);
        headers.forEach(header => {
            html += `<th>${header}</th>`;
        });
        html += '<th>Azioni</th>';
        html += '</tr></thead><tbody>';

        this.currentData.forEach(item => {
            html += '<tr>';
            headers.forEach(header => {
                let value = '-';
                
                // Mappatura diretta tra header display e chiavi nel database
                const headerToKeyMap = {
                    'Nome Utente': 'nomeUtente',
                    'Nome': 'nome',
                    'Cognome': 'cognome',
                    'Email': 'eMail',
                    'Titolo': 'titolo',
                    'Creatore': 'creatore',
                    'Data Inizio': 'dataInizio',
                    'Data Fine': 'dataFine',
                    'Quiz': 'quiz',
                    'Utente': 'utente',
                    'Data': 'data',
                    'Codice': 'codice',
                    'Numero': 'numero',
                    'Testo': 'testo',
                    'Tipo': 'tipo',
                    'Punteggio': 'punteggio',
                    'Partecipazione': 'partecipazione',
                    'Risposta': 'risposta',
                    'Domanda': 'domanda'
                };
                
                const key = headerToKeyMap[header];
                if (key && item[key] !== undefined) {
                    value = item[key];
                }
                
                html += `<td>${value}</td>`;
            });
            const itemId = item.codice || item.nomeUtente || item.id;
            html += `<td><div class="action-buttons">
                        <button class="btn-edit" onclick="crudManager.editItem('${itemId}')">Modifica</button>
                        <button class="btn-delete" onclick="crudManager.deleteItem('${itemId}')">Elimina</button>
                        ${tab === 'quiz' ? `<button class="btn-view" onclick="crudManager.viewQuizQuestions('${itemId}')">Visualizza Domande</button>` : ''}
                      </div></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        contentTable.innerHTML = html;
    }

    async viewQuizQuestions(quizId) {
        this.selectedQuizId = quizId;
        await this.showTab('domanda');
    }

    showEmptyState() {
        const contentTable = document.getElementById('contentTable');
        contentTable.innerHTML = '<div class="empty-state"><p>Nessun dato disponibile</p><p>Clicca su "+ Aggiungi" per crearne uno nuovo</p></div>';
    }

    getHeaders(tab) {
        const headers = {
            'quiz': ['Codice', 'Titolo', 'Creatore', 'Data Inizio', 'Data Fine'],
            'domanda': ['Codice', 'Quiz', 'Utente', 'Data'],
            'risposta': ['Domanda', 'Numero', 'Testo', 'Tipo', 'Quiz', 'Punteggio'],
            'partecipazione': ['Codice', 'Quiz', 'Utente', 'Data'],
            'rispostaUtenteQuiz': ['Domanda', 'Partecipazione', 'Quiz', 'Risposta'],
            'utente': ['Nome Utente', 'Nome', 'Cognome', 'Email']
        };
        return headers[tab] || [];
    }

    getTabTitle(tab) {
        const titles = {
            'quiz': 'Gestione Quiz',
            'domanda': this.selectedQuizId ? `Gestione Domande (Quiz ${this.selectedQuizId})` : 'Gestione Domande',
            'risposta': 'Gestione Risposte',
            'partecipazione': 'Gestione Partecipazioni',
            'rispostaUtenteQuiz': 'Risposte Utente Quiz',
            'utente': 'Gestione Utenti'
        };
        return titles[tab] || 'Gestione';
    }

    getFormFields(tab) {
        const fields = {
            'quiz': [
                { name: 'titolo', label: 'Titolo', type: 'text', required: true },
                { name: 'creatore', label: 'Creatore', type: 'text', required: true },
                { name: 'dataInizio', label: 'Data Inizio', type: 'date', required: false },
                { name: 'dataFine', label: 'Data Fine', type: 'date', required: false }
            ],
            'domanda': [
                { name: 'quiz', label: 'Quiz', type: 'number', required: true },
                { name: 'utente', label: 'Utente', type: 'text', required: true },
                { name: 'data', label: 'Data', type: 'datetime-local', required: false }
            ],
            'risposta': [
                { name: 'domanda', label: 'Domanda', type: 'number', required: true },
                { name: 'numero', label: 'Numero', type: 'number', required: true },
                { name: 'testo', label: 'Testo', type: 'textarea', required: true },
                { name: 'tipo', label: 'Tipo', type: 'select', options: ['text', 'multiple', 'true_false'], required: true },
                { name: 'quiz', label: 'Quiz', type: 'number', required: true },
                { name: 'punteggio', label: 'Punteggio', type: 'number', required: false }
            ],
            'partecipazione': [
                { name: 'quiz', label: 'Quiz', type: 'number', required: true },
                { name: 'utente', label: 'Utente', type: 'text', required: true },
                { name: 'data', label: 'Data', type: 'datetime-local', required: false }
            ],
            'rispostaUtenteQuiz': [
                { name: 'domanda', label: 'Domanda', type: 'number', required: true },
                { name: 'partecipazione', label: 'Partecipazione', type: 'number', required: true },
                { name: 'quiz', label: 'Quiz', type: 'number', required: true },
                { name: 'risposta', label: 'Risposta', type: 'number', required: true }
            ],
            'utente': [
                { name: 'nomeUtente', label: 'Nome Utente', type: 'text', required: true },
                { name: 'nome', label: 'Nome', type: 'text', required: false },
                { name: 'cognome', label: 'Cognome', type: 'text', required: false },
                { name: 'eMail', label: 'Email', type: 'email', required: true }
            ]
        };
        return fields[tab] || [];
    }

    openCreateModal() {
        this.editingId = null;
        document.getElementById('modalTitle').textContent = 'Aggiungi Nuovo';
        this.renderFormFields();
        this.openModal();
    }

    async editItem(id) {
        this.editingId = id;
        document.getElementById('modalTitle').textContent = 'Modifica';
        this.renderFormFields();
        this.openModal();

        const item = this.currentData.find(d => d.codice === parseInt(id) || d.nomeUtente === id);
        if (item) {
            const fields = this.getFormFields(this.currentTab);
            fields.forEach(field => {
                const input = document.querySelector(`[name="${field.name}"]`);
                if (input) {
                    if (field.type === 'checkbox') {
                        input.checked = item[field.name] === 1 || item[field.name] === true;
                    } else {
                        input.value = item[field.name] || '';
                    }
                }
            });
        }
    }

    renderFormFields() {
        const formFields = document.getElementById('formFields');
        const fields = this.getFormFields(this.currentTab);
        
        let html = '';
        fields.forEach(field => {
            html += '<div class="form-group">';
            html += `<label for="${field.name}">${field.label}</label>`;

            if (field.type === 'textarea') {
                html += `<textarea name="${field.name}" id="${field.name}" ${field.required ? 'required' : ''}></textarea>`;
            } else if (field.type === 'select') {
                html += `<select name="${field.name}" id="${field.name}" ${field.required ? 'required' : ''}>`;
                field.options.forEach(option => {
                    html += `<option value="${option}">${option}</option>`;
                });
                html += '</select>';
            } else if (field.type === 'checkbox') {
                html += `<input type="checkbox" name="${field.name}" id="${field.name}">`;
            } else {
                html += `<input type="${field.type}" name="${field.name}" id="${field.name}" ${field.required ? 'required' : ''}>`;
            }

            html += '</div>';
        });

        formFields.innerHTML = html;
    }

    async handleSubmit(event) {
        event.preventDefault();
        const formData = new FormData(document.getElementById('crudForm'));
        const data = Object.fromEntries(formData);

        try {
            if (this.editingId) {
                await this.updateItem(this.currentTab, data);
            } else {
                await this.createItem(this.currentTab, data);
            }
            this.closeModal();
            await this.loadData(this.currentTab);
        } catch (error) {
            alert('Errore: ' + error.message);
        }
    }

    async createItem(tab, data) {
        switch (tab) {
            case 'quiz':
                return await api.createQuiz(data.titolo, data.creatore, data.dataInizio, data.dataFine);
            case 'domanda':
                return await api.createDomanda(data.quiz, data.utente, data.data);
            case 'risposta':
                return await api.createRisposta(data.domanda, data.numero, data.testo, data.tipo, data.quiz, data.punteggio);
            case 'partecipazione':
                return await api.createPartecipazione(data.quiz, data.utente, data.data);
            case 'rispostaUtenteQuiz':
                return await api.createRispostaUtenteQuiz(data.domanda, data.partecipazione, data.quiz, data.risposta);
            case 'utente':
                return await api.createUtente(data.nomeUtente, data.nome, data.cognome, data.eMail);
        }
    }

    async updateItem(tab, data) {
        switch (tab) {
            case 'quiz':
                return await api.updateQuiz(this.editingId, data.titolo, data.creatore, data.dataInizio, data.dataFine);
            case 'domanda':
                return await api.updateDomanda(this.editingId, data.quiz, data.utente);
            case 'risposta':
                return await api.updateRisposta(data.domanda, data.numero, data.testo, data.tipo, data.punteggio);
            case 'partecipazione':
                return await api.updatePartecipazione(this.editingId, data.quiz, data.utente, data.data);
            case 'utente':
                return await api.updateUtente(this.editingId, data.nome, data.cognome, data.eMail);
        }
    }

    async deleteItem(id) {
        if (!confirm('Sei sicuro di voler eliminare questo elemento?')) return;

        try {
            switch (this.currentTab) {
                case 'quiz':
                    await api.deleteQuiz(id);
                    break;
                case 'domanda':
                    await api.deleteDomanda(id);
                    break;
                case 'risposta':
                    await api.deleteRisposta(id);
                    break;
                case 'partecipazione':
                    await api.deletePartecipazione(id);
                    break;
                case 'rispostaUtenteQuiz':
                    // Questo richiederebbe più parametri
                    alert('Eliminazione non supportata da questa interfaccia');
                    return;
                case 'utente':
                    await api.deleteUtente(id);
                    break;
            }
            await this.loadData(this.currentTab);
        } catch (error) {
            alert('Errore nell\'eliminazione: ' + error.message);
        }
    }

    openModal() {
        document.getElementById('crudModal').style.display = 'block';
    }

    closeModal() {
        document.getElementById('crudModal').style.display = 'none';
    }
}

const crudManager = new CRUDManager();

function showTab(tab) {
    crudManager.showTab(tab);
}

function openCreateModal() {
    crudManager.openCreateModal();
}

function closeModal() {
    crudManager.closeModal();
}

function handleSubmit(event) {
    crudManager.handleSubmit(event);
}

window.onclick = function(event) {
    const modal = document.getElementById('crudModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
