/* === UTILITY === */
function showAlert(msg, type = 'success', container = '#contenuto') {
  const div = document.createElement('div');
  div.className = `alert alert-${type}`;
  div.textContent = msg;
  const cont = document.querySelector(container);
  cont.prepend(div);
  setTimeout(() => div.remove(), 3500);
}

function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  // reset form dentro il modal
  const form = document.querySelector(`#${id} form`);
  if (form) form.reset();
}

/* ============================
   UTENTI
   ============================ */
async function loadUtenti() {
  const cont = document.getElementById('contenuto');
  cont.innerHTML = `
    <div class="section-title">👤 Gestione Utenti</div>
    <button class="btn btn-primary" onclick="openModal('modal-utente')">+ Nuovo Utente</button>
    <div style="margin-top:1.2rem" class="table-wrap">
      <table>
        <thead><tr>
          <th>Username</th><th>Nome</th><th>Cognome</th><th>Email</th><th>Azioni</th>
        </tr></thead>
        <tbody id="tbody-utenti"></tbody>
      </table>
    </div>
    ${modalUtenteHTML()}
  `;
  await refreshUtenti();
}

async function refreshUtenti() {
  const utenti = await Api.getUtenti();
  const tbody = document.getElementById('tbody-utenti');
  if (!utenti.length) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><div class="empty-icon">👤</div><p>Nessun utente ancora.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = utenti.map(u => `
    <tr>
      <td><strong>${u.nomeUtente}</strong></td>
      <td>${u.nome}</td>
      <td>${u.cognome}</td>
      <td>${u.eMail}</td>
      <td class="actions-cell">
        <button class="btn btn-secondary btn-sm" onclick="editUtente(${JSON.stringify(u).split('"').join("'")})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteUtenteConfirm('${u.nomeUtente}')">🗑️</button>
      </td>
    </tr>
  `).join('');
}

function modalUtenteHTML(edit = false) {
  return `
  <div class="modal-overlay" id="modal-utente">
    <div class="modal">
      <button class="modal-close" onclick="closeModal('modal-utente')">✕</button>
      <h3 id="modal-utente-title">${edit ? 'Modifica' : 'Nuovo'} Utente</h3>
      <form id="form-utente" onsubmit="submitUtente(event)">
        <div class="form-group">
          <label>Username</label>
          <input id="u-nomeUtente" name="nomeUtente" required ${edit ? 'readonly' : ''}>
        </div>
        <div class="form-group">
          <label>Nome</label>
          <input id="u-nome" name="nome" required>
        </div>
        <div class="form-group">
          <label>Cognome</label>
          <input id="u-cognome" name="cognome" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input id="u-email" name="eMail" type="email" required>
        </div>
        <input type="hidden" id="u-edit-mode" value="${edit ? '1' : '0'}">
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-utente')">Annulla</button>
          <button type="submit" class="btn btn-primary">Salva</button>
        </div>
      </form>
    </div>
  </div>`;
}

function editUtente(u) {
  // popola i campi
  document.getElementById('u-nomeUtente').value = u.nomeUtente;
  document.getElementById('u-nome').value       = u.nome;
  document.getElementById('u-cognome').value    = u.cognome;
  document.getElementById('u-email').value      = u.eMail;
  document.getElementById('u-edit-mode').value  = '1';
  document.getElementById('modal-utente-title').textContent = 'Modifica Utente';
  document.getElementById('u-nomeUtente').readOnly = true;
  openModal('modal-utente');
}

async function submitUtente(e) {
  e.preventDefault();
  const editMode = document.getElementById('u-edit-mode').value === '1';
  const payload = {
    nomeUtente: document.getElementById('u-nomeUtente').value,
    nome:       document.getElementById('u-nome').value,
    cognome:    document.getElementById('u-cognome').value,
    eMail:      document.getElementById('u-email').value,
  };
  try {
    editMode ? await Api.updateUtente(payload) : await Api.createUtente(payload);
    closeModal('modal-utente');
    await refreshUtenti();
    showAlert(editMode ? 'Utente aggiornato.' : 'Utente creato.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

async function deleteUtenteConfirm(nu) {
  if (!confirm(`Eliminare l'utente "${nu}"? Verranno eliminate anche le sue partecipazioni.`)) return;
  try {
    await Api.deleteUtente(nu);
    await refreshUtenti();
    showAlert('Utente eliminato.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

/* ============================
   QUIZ
   ============================ */
async function loadQuiz() {
  const cont = document.getElementById('contenuto');
  cont.innerHTML = `
    <div class="section-title">📋 Gestione Quiz</div>
    <div style="display:flex; gap:.8rem; flex-wrap:wrap; margin-bottom:1rem">
      <button class="btn btn-primary" onclick="openModalCreaQuiz()">+ Nuovo Quiz</button>
    </div>
    <div id="quiz-list"></div>
    <div class="modal-overlay" id="modal-quiz">
      <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-quiz')">✕</button>
        <h3 id="modal-quiz-title">Nuovo Quiz</h3>
        <form id="form-quiz" onsubmit="submitQuiz(event)">
          <div class="form-group"><label>Codice</label><input id="q-codice" required></div>
          <div class="form-group"><label>Titolo</label><input id="q-titolo" required></div>
          <div class="form-group"><label>Creatore (username)</label><input id="q-creatore" required></div>
          <div class="form-group"><label>Data Inizio</label><input id="q-dataInizio" type="date" required></div>
          <div class="form-group"><label>Data Fine</label><input id="q-dataFine" type="date"></div>
          <input type="hidden" id="q-edit-mode" value="0">
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-quiz')">Annulla</button>
            <button type="submit" class="btn btn-primary">Salva</button>
          </div>
        </form>
      </div>
    </div>
  `;
  await refreshQuiz();
}

function openModalCreaQuiz() {
  document.getElementById('modal-quiz-title').textContent = 'Nuovo Quiz';
  document.getElementById('q-edit-mode').value = '0';
  document.getElementById('q-codice').readOnly = false;
  document.getElementById('form-quiz').reset();
  openModal('modal-quiz');
}

async function refreshQuiz() {
  const quizzes = await Api.getQuizzes();
  const list = document.getElementById('quiz-list');
  if (!quizzes.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-icon">📋</div><p>Nessun quiz ancora.</p></div>`;
    return;
  }
  list.innerHTML = quizzes.map(q => `
    <div class="card">
      <h2>${q.titolo} <small style="font-size:.75rem;color:var(--malva-400)">#${q.codice}</small></h2>
      <p>Creatore: <strong>${q.nome} ${q.cognome}</strong> (@${q.creatore}) &nbsp;·&nbsp;
         Domande: <strong>${q.num_domande}</strong></p>
      <div class="meta">📅 ${q.dataInizio} → ${q.dataFine || '—'}</div>
      <div style="margin-top:.8rem; display:flex; gap:.5rem; flex-wrap:wrap">
        <button class="btn btn-secondary btn-sm" onclick="editQuizModal('${q.codice}','${q.titolo}','${q.creatore}','${q.dataInizio}','${q.dataFine||''}')">✏️ Modifica</button>
        <button class="btn btn-secondary btn-sm" onclick="loadDomande('${q.codice}')">❓ Domande</button>
        <button class="btn btn-danger btn-sm" onclick="deleteQuizConfirm('${q.codice}')">🗑️ Elimina</button>
      </div>
    </div>
  `).join('');
}

function editQuizModal(codice, titolo, creatore, di, df) {
  document.getElementById('modal-quiz-title').textContent = 'Modifica Quiz';
  document.getElementById('q-edit-mode').value  = '1';
  document.getElementById('q-codice').value     = codice;
  document.getElementById('q-codice').readOnly  = true;
  document.getElementById('q-titolo').value     = titolo;
  document.getElementById('q-creatore').value   = creatore;
  document.getElementById('q-dataInizio').value = di;
  document.getElementById('q-dataFine').value   = df;
  openModal('modal-quiz');
}

async function submitQuiz(e) {
  e.preventDefault();
  const editMode = document.getElementById('q-edit-mode').value === '1';
  const payload = {
    codice:     document.getElementById('q-codice').value,
    titolo:     document.getElementById('q-titolo').value,
    creatore:   document.getElementById('q-creatore').value,
    dataInizio: document.getElementById('q-dataInizio').value,
    dataFine:   document.getElementById('q-dataFine').value || null,
  };
  try {
    editMode ? await Api.updateQuiz(payload) : await Api.createQuiz(payload);
    closeModal('modal-quiz');
    await refreshQuiz();
    showAlert(editMode ? 'Quiz aggiornato.' : 'Quiz creato.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

async function deleteQuizConfirm(cod) {
  if (!confirm(`Eliminare il quiz "${cod}"?`)) return;
  try {
    await Api.deleteQuiz(cod);
    await refreshQuiz();
    showAlert('Quiz eliminato.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

/* ============================
   DOMANDE
   ============================ */
async function loadDomande(quizCodice) {
  const cont = document.getElementById('contenuto');
  cont.innerHTML = `
    <div class="section-title">❓ Domande — Quiz: ${quizCodice}</div>
    <div style="display:flex; gap:.8rem; margin-bottom:1rem">
      <button class="btn btn-secondary" onclick="loadQuiz()">← Torna ai Quiz</button>
      <button class="btn btn-primary" onclick="openModalDomanda('${quizCodice}')">+ Nuova Domanda</button>
    </div>
    <div id="domande-list"></div>
    <div class="modal-overlay" id="modal-domanda">
      <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-domanda')">✕</button>
        <h3 id="modal-dom-title">Nuova Domanda</h3>
        <form id="form-domanda" onsubmit="submitDomanda(event)">
          <input type="hidden" id="dom-quiz" value="${quizCodice}">
          <div class="form-group"><label>Numero</label><input id="dom-numero" type="number" min="1" required></div>
          <div class="form-group"><label>Testo</label><textarea id="dom-testo" required></textarea></div>
          <input type="hidden" id="dom-edit-mode" value="0">
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-domanda')">Annulla</button>
            <button type="submit" class="btn btn-primary">Salva</button>
          </div>
        </form>
      </div>
    </div>
  `;
  await refreshDomande(quizCodice);
}

async function refreshDomande(quizCodice) {
  const domande = await Api.getDomande(quizCodice);
  const list = document.getElementById('domande-list');
  if (!domande.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-icon">❓</div><p>Nessuna domanda ancora.</p></div>`;
    return;
  }
  list.innerHTML = domande.map(d => `
    <div class="card">
      <h2>Domanda #${d.numero}</h2>
      <p>${d.testo}</p>
      <div style="margin-top:.7rem; display:flex; gap:.5rem; flex-wrap:wrap">
        <button class="btn btn-secondary btn-sm" onclick="editDomandaModal(${d.numero},'${d.testo.replace(/'/g,"\\'")}','${quizCodice}')">✏️</button>
        <button class="btn btn-secondary btn-sm" onclick="loadRisposte('${quizCodice}',${d.numero})">💬 Risposte</button>
        <button class="btn btn-danger btn-sm" onclick="deleteDomandaConfirm('${quizCodice}',${d.numero})">🗑️</button>
      </div>
    </div>
  `).join('');
}

function openModalDomanda(quiz) {
  document.getElementById('modal-dom-title').textContent = 'Nuova Domanda';
  document.getElementById('dom-edit-mode').value = '0';
  document.getElementById('form-domanda').reset();
  document.getElementById('dom-quiz').value = quiz;
  openModal('modal-domanda');
}

function editDomandaModal(numero, testo, quiz) {
  document.getElementById('modal-dom-title').textContent = 'Modifica Domanda';
  document.getElementById('dom-edit-mode').value = '1';
  document.getElementById('dom-numero').value = numero;
  document.getElementById('dom-testo').value  = testo;
  document.getElementById('dom-quiz').value   = quiz;
  openModal('modal-domanda');
}

async function submitDomanda(e) {
  e.preventDefault();
  const editMode = document.getElementById('dom-edit-mode').value === '1';
  const quiz = document.getElementById('dom-quiz').value;
  const payload = {
    quiz,
    numero: document.getElementById('dom-numero').value,
    testo:  document.getElementById('dom-testo').value,
  };
  try {
    editMode ? await Api.updateDomanda(payload) : await Api.createDomanda(payload);
    closeModal('modal-domanda');
    await refreshDomande(quiz);
    showAlert(editMode ? 'Domanda aggiornata.' : 'Domanda creata.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

async function deleteDomandaConfirm(quiz, numero) {
  if (!confirm(`Eliminare la domanda #${numero}?`)) return;
  try {
    await Api.deleteDomanda(quiz, numero);
    await refreshDomande(quiz);
    showAlert('Domanda eliminata.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

/* ============================
   RISPOSTE
   ============================ */
async function loadRisposte(quizCodice, domandaNum) {
  const cont = document.getElementById('contenuto');
  cont.innerHTML = `
    <div class="section-title">💬 Risposte — Quiz: ${quizCodice} · Dom. #${domandaNum}</div>
    <div style="display:flex; gap:.8rem; margin-bottom:1rem">
      <button class="btn btn-secondary" onclick="loadDomande('${quizCodice}')">← Torna alle Domande</button>
      <button class="btn btn-primary" onclick="openModalRisposta('${quizCodice}',${domandaNum})">+ Nuova Risposta</button>
    </div>
    <div id="risposte-list"></div>
    <div class="modal-overlay" id="modal-risposta">
      <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-risposta')">✕</button>
        <h3 id="modal-risp-title">Nuova Risposta</h3>
        <form id="form-risposta" onsubmit="submitRisposta(event)">
          <input type="hidden" id="risp-quiz" value="${quizCodice}">
          <input type="hidden" id="risp-domanda" value="${domandaNum}">
          <div class="form-group"><label>Numero</label><input id="risp-numero" type="number" min="1" required></div>
          <div class="form-group"><label>Testo</label><textarea id="risp-testo" required></textarea></div>
          <div class="form-group">
            <label>Tipo</label>
            <select id="risp-tipo" required>
              <option value="">— seleziona —</option>
              <option value="Corretta">Corretta</option>
              <option value="Sbagliata">Sbagliata</option>
            </select>
          </div>
          <div class="form-group"><label>Punteggio (solo se Corretta)</label><input id="risp-punteggio" type="number" step="0.01"></div>
          <input type="hidden" id="risp-edit-mode" value="0">
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-risposta')">Annulla</button>
            <button type="submit" class="btn btn-primary">Salva</button>
          </div>
        </form>
      </div>
    </div>
  `;
  await refreshRisposte(quizCodice, domandaNum);
}

async function refreshRisposte(quizCodice, domandaNum) {
  const risposte = await Api.getRisposte(quizCodice, domandaNum);
  const list = document.getElementById('risposte-list');
  if (!risposte.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-icon">💬</div><p>Nessuna risposta ancora.</p></div>`;
    return;
  }
  list.innerHTML = risposte.map(r => `
    <div class="card">
      <h2>Risposta #${r.numero}
        <span class="badge ${r.tipo === 'Corretta' ? 'badge-correct' : 'badge-wrong'}">${r.tipo}</span>
        ${r.punteggio != null ? `<small style="font-size:.75rem;color:var(--malva-400)">+${r.punteggio} pt</small>` : ''}
      </h2>
      <p>${r.testo}</p>
      <div style="margin-top:.7rem; display:flex; gap:.5rem">
        <button class="btn btn-secondary btn-sm" onclick="editRispostaModal(${JSON.stringify(r).replace(/"/g,"'")})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteRispostaConfirm('${quizCodice}',${domandaNum},${r.numero})">🗑️</button>
      </div>
    </div>
  `).join('');
}

function openModalRisposta(quiz, domanda) {
  document.getElementById('modal-risp-title').textContent = 'Nuova Risposta';
  document.getElementById('risp-edit-mode').value = '0';
  document.getElementById('form-risposta').reset();
  document.getElementById('risp-quiz').value    = quiz;
  document.getElementById('risp-domanda').value = domanda;
  openModal('modal-risposta');
}

function editRispostaModal(r) {
  document.getElementById('modal-risp-title').textContent = 'Modifica Risposta';
  document.getElementById('risp-edit-mode').value  = '1';
  document.getElementById('risp-numero').value     = r.numero;
  document.getElementById('risp-testo').value      = r.testo;
  document.getElementById('risp-tipo').value       = r.tipo;
  document.getElementById('risp-punteggio').value  = r.punteggio ?? '';
  openModal('modal-risposta');
}

async function submitRisposta(e) {
  e.preventDefault();
  const editMode = document.getElementById('risp-edit-mode').value === '1';
  const quiz    = document.getElementById('risp-quiz').value;
  const domanda = document.getElementById('risp-domanda').value;
  const tipo    = document.getElementById('risp-tipo').value;
  const punt    = document.getElementById('risp-punteggio').value;
  const payload = {
    quiz, domanda,
    numero:    document.getElementById('risp-numero').value,
    testo:     document.getElementById('risp-testo').value,
    tipo,
    punteggio: tipo === 'Corretta' && punt !== '' ? punt : null,
  };
  try {
    editMode ? await Api.updateRisposta(payload) : await Api.createRisposta(payload);
    closeModal('modal-risposta');
    await refreshRisposte(quiz, domanda);
    showAlert(editMode ? 'Risposta aggiornata.' : 'Risposta creata.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

async function deleteRispostaConfirm(quiz, domanda, numero) {
  if (!confirm(`Eliminare la risposta #${numero}?`)) return;
  try {
    await Api.deleteRisposta(quiz, domanda, numero);
    await refreshRisposte(quiz, domanda);
    showAlert('Risposta eliminata.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

/* ============================
   PARTECIPAZIONI
   ============================ */
async function loadPartecipazioni() {
  const cont = document.getElementById('contenuto');
  const [partecipazioni, quizzes, utenti] = await Promise.all([
    Api.getPartecipazioni(), Api.getQuizzes(), Api.getUtenti()
  ]);

  cont.innerHTML = `
    <div class="section-title">🏆 Partecipazioni</div>
    <button class="btn btn-primary" onclick="openModal('modal-part')">+ Nuova Partecipazione</button>
    <div style="margin-top:1.2rem" class="table-wrap">
      <table>
        <thead><tr>
          <th>Codice</th><th>Utente</th><th>Quiz</th><th>Data</th><th>Azioni</th>
        </tr></thead>
        <tbody>
          ${partecipazioni.length ? partecipazioni.map(p => `
            <tr>
              <td>${p.codice}</td>
              <td>${p.nome} ${p.cognome} <small>@${p.utente}</small></td>
              <td>${p.titolo}</td>
              <td>${p.data}</td>
              <td class="actions-cell">
                <button class="btn btn-danger btn-sm" onclick="deletePartConf('${p.codice}')">🗑️</button>
              </td>
            </tr>
          `).join('') : `<tr><td colspan="5"><div class="empty-state"><div class="empty-icon">🏆</div><p>Nessuna partecipazione.</p></div></td></tr>`}
        </tbody>
      </table>
    </div>
    <div class="modal-overlay" id="modal-part">
      <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-part')">✕</button>
        <h3>Nuova Partecipazione</h3>
        <form id="form-part" onsubmit="submitPart(event)">
          <div class="form-group"><label>Codice</label><input id="part-codice" required></div>
          <div class="form-group">
            <label>Utente</label>
            <select id="part-utente" required>
              <option value="">— seleziona —</option>
              ${utenti.map(u => `<option value="${u.nomeUtente}">${u.nome} ${u.cognome}</option>`).join('')}
            </select>
          </div>
          <div class="form-group">
            <label>Quiz</label>
            <select id="part-quiz" required>
              <option value="">— seleziona —</option>
              ${quizzes.map(q => `<option value="${q.codice}">${q.titolo}</option>`).join('')}
            </select>
          </div>
          <div class="form-group"><label>Data</label><input id="part-data" type="date" value="${new Date().toISOString().split('T')[0]}" required></div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-part')">Annulla</button>
            <button type="submit" class="btn btn-primary">Registra</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

async function submitPart(e) {
  e.preventDefault();
  const payload = {
    codice:  document.getElementById('part-codice').value,
    utente:  document.getElementById('part-utente').value,
    quiz:    document.getElementById('part-quiz').value,
    data:    document.getElementById('part-data').value,
  };
  try {
    await Api.createPartecipazione(payload);
    closeModal('modal-part');
    await loadPartecipazioni();
    showAlert('Partecipazione registrata.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

async function deletePartConf(cod) {
  if (!confirm(`Eliminare la partecipazione "${cod}"?`)) return;
  try {
    await Api.deletePartecipazione(cod);
    await loadPartecipazioni();
    showAlert('Partecipazione eliminata.');
  } catch (err) {
    showAlert(err.message, 'error');
  }
}