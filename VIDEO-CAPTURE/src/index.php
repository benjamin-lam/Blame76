<?php
/**
 * Minimaler Gästebuch-PoC (Single-File-App)
 * Stack: PHP 8.3 + Apache + MySQL 8.4 in Docker
 */

// Verbindungsdaten kommen aus docker-compose Umgebungsvariablen
$dbHost = $_ENV['DB_HOST'] ?? 'db';
$dbName = $_ENV['DB_NAME'] ?? 'gaestebuch_demo';
$dbUser = $_ENV['DB_USER'] ?? 'app';
$dbPass = $_ENV['DB_PASS'] ?? 'secret123';

$error = '';
$success = '';

try {
    // PDO-Verbindung mit utf8mb4 und vernünftigen Defaults aufbauen
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // POST: Formular absenden, validieren, speichern, per PRG zurückleiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $nachricht = trim($_POST['nachricht'] ?? '');

        // Sehr einfache Pflichtfeld-Validierung
        if ($name === '' || $nachricht === '') {
            $error = 'Bitte fülle Name und Nachricht aus.';
        } else {
            // Prepared Statement mit named parameters
            $stmt = $pdo->prepare(
                'INSERT INTO gaestebuch (name, nachricht) VALUES (:name, :nachricht)'
            );
            $stmt->execute([
                ':name' => $name,
                ':nachricht' => $nachricht,
            ]);

            // PRG: Erfolg als Query-Flag mitgeben, damit Refresh keinen Re-POST macht
            header('Location: /?saved=1');
            exit;
        }
    }

    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        $success = 'Eintrag gespeichert 😄';
    }

    // Alle Einträge neueste zuerst laden
    $entriesStmt = $pdo->query(
        'SELECT id, name, nachricht, created_at FROM gaestebuch ORDER BY id DESC'
    );
    $entries = $entriesStmt->fetchAll();
} catch (Throwable $e) {
    // Für PoC bewusst einfache Fehlermeldung
    $error = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
    $entries = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gästebuch PoC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-100 to-white text-slate-800">
    <main class="max-w-3xl mx-auto px-4 py-10 md:py-14">
        <header class="mb-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900 mb-3">
                📖 Retro-Gästebuch
            </h1>
            <p class="text-lg text-slate-600">
                Schreib einen kurzen Gruß in unser Docker-Gästebuch.
            </p>
        </header>

        <section class="bg-white/90 backdrop-blur rounded-2xl shadow-xl p-6 md:p-8 border border-slate-200 mb-8">
            <h2 class="text-2xl font-semibold mb-4 text-slate-900">Neuen Eintrag schreiben</h2>

            <?php if ($error !== ''): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 font-medium">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 font-medium">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label for="name" class="block text-base font-semibold mb-1">Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                        placeholder="Dein Name"
                    >
                </div>

                <div>
                    <label for="nachricht" class="block text-base font-semibold mb-1">Nachricht</label>
                    <textarea
                        id="nachricht"
                        name="nachricht"
                        rows="5"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                        placeholder="Schreib hier deine Nachricht..."
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl px-6 py-3 text-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-sky-500 hover:from-blue-700 hover:to-sky-600 transition shadow-md"
                >
                    Eintrag speichern
                </button>
            </form>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">Einträge</h2>

            <?php if (count($entries) === 0): ?>
                <div class="rounded-xl border border-slate-200 bg-white p-5 text-slate-600 shadow-sm">
                    Noch keine Einträge vorhanden.
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($entries as $entry): ?>
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <h3 class="text-xl font-bold text-slate-900"><?= $entry['name'] ?></h3>
                                <time class="text-sm text-slate-500"><?= $entry['created_at'] ?></time>
                            </div>
                            <p class="text-lg leading-relaxed text-slate-700 whitespace-pre-wrap"><?= $entry['nachricht'] ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <footer class="text-center text-slate-600 text-sm">
            Läuft in Docker •
            <a
                href="http://localhost:8081"
                target="_blank"
                rel="noreferrer"
                class="text-blue-700 hover:text-blue-800 underline font-medium"
            >
                phpMyAdmin öffnen
            </a>
        </footer>
    </main>
</body>
</html>
