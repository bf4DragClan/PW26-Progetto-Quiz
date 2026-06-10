document.addEventListener('DOMContentLoaded', () => {
  // navigazione
  document.querySelectorAll('#navigazione a[data-section]').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      document.querySelectorAll('#navigazione a').forEach(a => a.classList.remove('active'));
      link.classList.add('active');
      const section = link.dataset.section;
      navigateTo(section);
      // aggiorna filtro ricerca
      updateFiltro(section);
    });
  });

  // sezione di default
  navigateTo('quiz');
  document.querySelector('[data-section="quiz"]')?.classList.add('active');
  updateFiltro('quiz');
});

function navigateTo(section) {
  switch (section) {
    case 'utenti':       loadUtenti();         break;
    case 'quiz':         loadQuiz();           break;
    case 'partecipazioni': loadPartecipazioni(); break;
  }
}

function updateFiltro(section) {
  const sidebar = document.getElementById('filtro-ricerca');
  sidebar.innerHTML = '';

  if (section === 'quiz') {
    sidebar.innerHTML = `
      <h3>Filtro Quiz</h3>
      <input class="filter-input" placeholder="Cerca per titolo..." oninput="filterQuizCards(this.value)">
      <h3 style="margin-top:1rem">Ordina per</h3>
      <select class="filter-input">
        <option>Data (recente)</option>
        <option>Titolo A-Z</option>
      </select>
    `;
  } else if (section === 'utenti') {
    sidebar.innerHTML = `
      <h3>Filtro Utenti</h3>
      <input class="filter-input" placeholder="Cerca username..." oninput="filterTable(this.value, 'tbody-utenti', 0)">
    `;
  } else {
    sidebar.innerHTML = `<h3>Filtri</h3><p style="font-size:.82rem;color:var(--text-mid)">Seleziona una sezione per filtrare.</p>`;
  }
}

function filterQuizCards(query) {
  const q = query.toLowerCase();
  document.querySelectorAll('#quiz-list .card').forEach(card => {
    const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
    card.style.display = title.includes(q) ? '' : 'none';
  });
}

function filterTable(query, tbodyId, colIndex) {
  const q = query.toLowerCase();
  const tbody = document.getElementById(tbodyId);
  if (!tbody) return;
  tbody.querySelectorAll('tr').forEach(row => {
    const cell = row.cells[colIndex]?.textContent.toLowerCase() || '';
    row.style.display = cell.includes(q) ? '' : 'none';
  });
}