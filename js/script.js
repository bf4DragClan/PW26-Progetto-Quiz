document.addEventListener('DOMContentLoaded', () => {
    loadQuizzes();
    document.getElementById('quizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveQuiz();
    });
});

// Gestione Modale
function openAddModal() {
    resetForm();
    document.getElementById('modalTitle').innerText = "Crea Nuovo Quiz";
    document.getElementById('creatore').readOnly = false;
    document.getElementById('quizModal').classList.add('open');
}

function openEditModal(codice, creatore, titolo, dataInizio, dataFine) {
    document.getElementById('modalTitle').innerText = "Modifica Quiz #" + codice;
    document.getElementById('quizId').value = codice;
    document.getElementById('creatore').value = creatore;
    document.getElementById('creatore').readOnly = true; 
    document.getElementById('titolo').value = titolo;
    document.getElementById('dataInizio').value = dataInizio;
    document.getElementById('dataFine').value = dataFine;
    document.getElementById('quizModal').classList.add('open');
}

function closeModal() {
    document.getElementById('quizModal').classList.remove('open');
}

// READ (Con invio Multi-Filtro)
function loadQuizzes() {
    const search = document.getElementById('searchInput').value;
    const creator = document.getElementById('creatorInput').value;
    const date = document.getElementById('dateInput').value;

    const queryParams = new URLSearchParams({
        search: search,
        creator: creator,
        date: date
    });

    fetch(`api/quiz.php?${queryParams.toString()}`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('#quizTable tbody');
            tbody.innerHTML = '';
            data.forEach(quiz => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${quiz.codice}</strong></td>
                        <td><a href="views/quiz_dettaglio.php?codice=${quiz.codice}" style="color: var(--malva-scuro); font-weight:600; text-decoration:none;">${quiz.titolo}</a></td>
                        <td>${quiz.creatore}</td>
                        <td>${quiz.dataInizio}</td>
                        <td>${quiz.dataFine}</td>
                        <td>
                            <button class="btn-secondary" onclick="openEditModal(${quiz.codice}, '${quiz.creatore}', '${quiz.titolo}', '${quiz.dataInizio}', '${quiz.dataFine}')">Modifica</button>
                            <button class="btn-secondary" style="color: red;" onclick="deleteQuiz(${quiz.codice})">Elimina</button>
                        </td>
                    </tr>
                `;
            });
        });
}

// CREATE & UPDATE
function saveQuiz() {
    const id = document.getElementById('quizId').value;
    const data = {
        creatore: document.getElementById('creatore').value,
        titolo: document.getElementById('titolo').value,
        dataInizio: document.getElementById('dataInizio').value,
        dataFine: document.getElementById('dataFine').value
    };

    const method = id ? 'PUT' : 'POST';
    if (id) data.codice = id;

    fetch('api/quiz.php', {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => res.json())
      .then(resData => {
          if(resData.error) {
              alert(resData.error);
          } else {
              closeModal();
              loadQuizzes();
          }
      });
}

// DELETE
function deleteQuiz(codice) {
    if (confirm("Vuoi davvero eliminare questo quiz?")) {
        fetch('api/quiz.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codice: codice })
        }).then(() => loadQuizzes());
    }
}

function clearQuizFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('creatorInput').value = '';
    document.getElementById('dateInput').value = '';
    loadQuizzes();
}

function resetForm() {
    document.getElementById('quizForm').reset();
    document.getElementById('quizId').value = '';
    document.getElementById('btnSubmit').innerText = "Salva Quiz";
}