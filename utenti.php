<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniBg - Statistiche Utenti</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>Università degli Studi di Bergamo - Programmazione Web</header>
    
    <nav>
        <a href="index.php">Dashboard Quiz</a>
        <a href="utenti.php" class="active">Statistiche Utenti</a>
        <a href="partecipazioni.php">Registro Partecipazioni</a>
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
        </main>
    </div>

    <footer>
        <div>Pannello di Amministrazione Quiz</div>
        <div class="disclaimer">&copy; 2026 - Progetto Universitario ad uso didattico - Università degli Studi di Bergamo</div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/utenti.php')
                .then(res => res.json())
                .then(data => {
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

                    const tbody = document.getElementById('utentiTableBody');
                    tbody.innerHTML = '';
                    data.utenti.forEach(u => {
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
                });
        });

        // Filtraggio Client-Side Istantaneo
        function filterUtentiTable() {
            const input = document.getElementById('userSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#utentiTable tbody tr');

            rows.forEach(row => {
                const username = row.cells[0].textContent.toLowerCase();
                const fullName = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();

                if (username.includes(input) || fullName.includes(input) || email.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>