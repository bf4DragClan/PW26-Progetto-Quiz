<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBg - Statistiche Utenti</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Stili per il blocco di paginazione */
        .pagination-container {
            text-align: right;
            margin-top: 20px;
            font-size: 14px;
            padding: 10px;
        }
        .btn-page {
            background-color: #613e66;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-left: 5px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-page:disabled {
            background-color: #b59eb9;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

    <header>Università degli Studi di Bergamo - Statistiche Utenti</header>
    
    <nav>
        <a href="index.php">Dashboard Quiz</a>
        <a href="utenti.php" class="active">Statistiche Utenti</a>
        <a href="partecipazioni.php">Registro Partecipazioni</a>
        <a href="lista_quiz.php">Svolgi Quiz</a>
    </nav>

    <div class="main-container">
        <aside>
            <h3>Filtro Utenti</h3>
            <div class="form-group" style="margin-top: 15px;">
                <label for="userSearchInput">Cerca Utente:</label>
                <input type="text" id="userSearchInput" placeholder="Username, nome o email..." onkeyup="filterUtentiTable()">
            </div>
        </aside>

        <main>
            <h2>Panoramica Utenti & Attività</h2>
            
            <div class="stats-container" id="statsSummary"></div>

            <h3>Rapporto Dettagliato Partecipazioni</h3>
            <table id="utentiTable">
                <thead>
                    <tr>
                        <th>Nome Utente</th>
                        <th>Nome Completo</th>
                        <th>Email</th>
                        <th>Quiz Creati (Autore)</th>
                        <th>Quiz Svolti (Partecipante)</th>
                    </tr>
                </thead>
                <tbody id="utentiTableBody"></tbody>
            </table>
            
            <!-- Contenitore della paginazione -->
            <div id="paginationControls" class="pagination-container"></div>
        </main>
    </div>

    <footer>
        <div>Pannello di Amministrazione Utenti</div>
        <div class="disclaimer">&copy; 2026 - Progetto Universitario ad uso didattico - Università degli Studi di Bergamo</div>
    </footer>

    <script>
        // Stato globale della pagina: elenco completo degli utenti, elenco filtrato e pagina corrente
        let allUtenti = [];
        let filteredUtenti = [];
        let currentPage = 1;
        const itemsPerPage = 15; // Numero di righe visualizzate per pagina

        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/utenti.php')
                .then(res => res.json())
                .then(data => {
                    // Aggiornamento del riepilogo statistico in base ai dati ricevuti dall'endpoint
                    document.getElementById('statsSummary').innerHTML = `
                        <div class="stat-card">
                            <h4>${data.totale_utenti}</h4>
                            <p>Utenti Registrati</p>
                        </div>
                        <div class="stat-card">
                            <h4>${data.totale_partecipazioni}</h4>
                            <p>Partecipazioni Totali</p>
                        </div>
                    `;

                    // Memorizzazione dei dati ricevuti, utilizzati come riferimento per il filtro e la paginazione lato client
                    allUtenti = data.utenti;
                    filteredUtenti = allUtenti; 
                    
                    // Costruzione iniziale della tabella
                    renderTable();
                });
        });

        // Ricostruisce il corpo della tabella in base alla pagina corrente e all'elenco filtrato
        function renderTable() {
            const tbody = document.getElementById('utentiTableBody');
            tbody.innerHTML = '';

            const totalPages = Math.ceil(filteredUtenti.length / itemsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const currentUtenti = filteredUtenti.slice(startIndex, endIndex);

            currentUtenti.forEach(u => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${u.nomeUtente}</strong></td>
                        <td>${u.nome} ${u.cognome}</td>
                        <td>${u.eMail}</td>
                        <td><span style="color: var(--malva-scuro); font-weight: bold;">${u.quiz_creati}</span></td>
                        <td><span style="color: green; font-weight: bold;">${u.quiz_svolti}</span></td>
                    </tr>
                `;
            });

            renderPagination(totalPages);
        }

        // Genera i controlli di paginazione: indicatore della pagina corrente e pulsanti precedente/successivo
        function renderPagination(totalPages) {
            const paginationContainer = document.getElementById('paginationControls');
            if (!paginationContainer) return;

            let html = `<span style="margin-right: 15px;">Pagina ${currentPage} di ${totalPages}</span>`;

            const prevDisabled = currentPage === 1 ? 'disabled' : '';
            html += `<button onclick="prevPage()" ${prevDisabled} class="btn-page">&larr; Prec</button>`;

            const nextDisabled = currentPage === totalPages ? 'disabled' : '';
            html += `<button onclick="nextPage()" ${nextDisabled} class="btn-page">Succ &rarr;</button>`;

            paginationContainer.innerHTML = html;
        }

        // Naviga alla pagina precedente, se disponibile, e aggiorna la tabella
        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        }

        // Naviga alla pagina successiva, se disponibile, e aggiorna la tabella
        function nextPage() {
            const totalPages = Math.ceil(filteredUtenti.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        }

        // Filtra gli utenti in base al testo digitato, confrontando username, nome completo ed email
        function filterUtentiTable() {
            const input = document.getElementById('userSearchInput').value.toLowerCase();
            
            filteredUtenti = allUtenti.filter(u => {
                const username = u.nomeUtente.toLowerCase();
                const fullName = (u.nome + ' ' + u.cognome).toLowerCase();
                const email = u.eMail.toLowerCase();

                return username.includes(input) || fullName.includes(input) || email.includes(input);
            });

            currentPage = 1; // La ricerca riparte sempre dalla prima pagina dei risultati
            renderTable();
        }
    </script>
</body>
</html>