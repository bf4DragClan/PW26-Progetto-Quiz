const API_URL = 'api/crud.php';

async function apiCall(action, payload = {}) {
  const res = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...payload }),
  });
  const json = await res.json();
  if (!json.ok) throw new Error(json.error);
  return json.data;
}

// Shorthand per ogni entitÃ 
const Api = {
  // Utenti
  getUtenti:        ()      => apiCall('get_utenti'),
  createUtente:     (d)     => apiCall('create_utente', d),
  updateUtente:     (d)     => apiCall('update_utente', d),
  deleteUtente:     (nu)    => apiCall('delete_utente', { nomeUtente: nu }),

  // Quiz
  getQuizzes:       ()      => fetch('api/get_quizzes.php').then(r=>r.json()).then(j=>{ if(!j.ok) throw new Error(j.error); return j.data; }),
  getQuiz:          (cod)   => apiCall('get_quiz', { codice: cod }),
  createQuiz:       (d)     => apiCall('create_quiz', d),
  updateQuiz:       (d)     => apiCall('update_quiz', d),
  deleteQuiz:       (cod)   => apiCall('delete_quiz', { codice: cod }),

  // Domande
  getDomande:       (quiz)  => apiCall('get_domande', { quiz }),
  createDomanda:    (d)     => apiCall('create_domanda', d),
  updateDomanda:    (d)     => apiCall('update_domanda', d),
  deleteDomanda:    (quiz, numero) => apiCall('delete_domanda', { quiz, numero }),

  // Risposte
  getRisposte:      (quiz, domanda) => apiCall('get_risposte', { quiz, domanda }),
  createRisposta:   (d)     => apiCall('create_risposta', d),
  updateRisposta:   (d)     => apiCall('update_risposta', d),
  deleteRisposta:   (quiz, domanda, numero) => apiCall('delete_risposta', { quiz, domanda, numero }),

  // Partecipazioni
  getPartecipazioni:    ()    => apiCall('get_partecipazioni'),
  createPartecipazione: (d)   => apiCall('create_partecipazione', d),
  deletePartecipazione: (cod) => apiCall('delete_partecipazione', { codice: cod }),

  // Risposte utente
  getRisposteUtente:    (part) => apiCall('get_risposte_utente', { partecipazione: part }),
  createRispostaUtente: (d)    => apiCall('create_risposta_utente', d),
};