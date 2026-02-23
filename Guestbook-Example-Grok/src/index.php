<?php
// Minimaler Gästebuch-PoC (alles in einer Datei).
// Hinweis: Für diesen ersten PoC sind Sicherheitsfeatures wie XSS-Schutz/CSRF bewusst nicht enthalten.

$dsn = 'mysql:host=db;dbname=gaestebuch_demo;charset=utf8mb4';
$dbUser = 'app';
$dbPass = 'secret123';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h1>Datenbankverbindung fehlgeschlagen</h1>';
    echo '<p>Bitte prüfen, ob der MySQL-Container läuft.</p>';
    echo '<pre>' . $e->getMessage() . '</pre>';
    exit;
}

// POST-Handling (PRG-Muster): validieren -> speichern -> redirect.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $nachricht = trim($_POST['nachricht'] ?? '');

    if ($name === '' || $nachricht === '') {
        header('Location: /?status=error');
        exit;
    }

    $insert = $pdo->prepare('INSERT INTO gaestebuch (name, nachricht) VALUES (:name, :nachricht)');
    $insert->execute([
        ':name' => $name,
        ':nachricht' => $nachricht,
    ]);

    header('Location: /?status=success');
    exit;
}

// Einträge neueste zuerst laden.
$entriesStmt = $pdo->query('SELECT id, name, nachricht, created_at FROM gaestebuch ORDER BY created_at DESC, id DESC');
$entries = $entriesStmt->fetchAll();

$status = $_GET['status'] ?? null;
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gästebuch PoC</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-slate-200 text-slate-800">
  <main class="max-w-4xl mx-auto px-4 py-10">
    <header class="mb-8 text-center">
      <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900">📘 Retro Gästebuch</h1>
      <p class="mt-3 text-lg text-slate-600">Modernes Look & Feel mit klassischem Gästebuch-Charme.</p>
    </header>

    <?php if ($status === 'error'): ?>
      <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 shadow-sm">
        Bitte fülle beide Felder aus.
      </div>
    <?php elseif ($status === 'success'): ?>
      <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-700 shadow-sm">
        Eintrag gespeichert 😄
      </div>
    <?php endif; ?>

    <section class="rounded-3xl bg-white/90 backdrop-blur p-6 md:p-8 shadow-xl border border-slate-200 mb-10">
      <h2 class="text-2xl font-semibold mb-5 text-slate-900">Neuen Eintrag hinterlassen</h2>

      <form method="post" class="space-y-4">
        <div>
          <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
          <input
            id="name"
            name="name"
            type="text"
            maxlength="60"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
            placeholder="Dein Name"
          >
        </div>

        <div>
          <label for="nachricht" class="block text-sm font-medium text-slate-700 mb-1">Nachricht</label>
          <textarea
            id="nachricht"
            name="nachricht"
            rows="5"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
            placeholder="Schreibe eine Nachricht..."
          ></textarea>
        </div>

        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-sky-500 px-6 py-3 text-white font-semibold text-lg shadow hover:from-blue-700 hover:to-sky-600 transition"
        >
          Eintrag speichern
        </button>
      </form>
    </section>

    <section>
      <h2 class="text-2xl font-semibold mb-4 text-slate-900">Einträge</h2>

      <?php if (count($entries) === 0): ?>
        <div class="rounded-2xl bg-white border border-slate-200 p-5 text-slate-600 shadow-sm">
          Noch keine Einträge vorhanden.
        </div>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($entries as $entry): ?>
            <article class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <h3 class="text-xl font-semibold text-slate-900"><?= $entry['name'] ?></h3>
                <time class="text-sm text-slate-500"><?= $entry['created_at'] ?></time>
              </div>
              <p class="text-lg leading-relaxed text-slate-700 whitespace-pre-wrap"><?= $entry['nachricht'] ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <footer class="mt-10 text-center text-slate-600">
      Läuft in Docker · phpMyAdmin: <a class="text-blue-700 underline hover:text-blue-900" href="http://localhost:8081" target="_blank" rel="noreferrer">http://localhost:8081</a>
    </footer>
  </main>
</body>
</html>
