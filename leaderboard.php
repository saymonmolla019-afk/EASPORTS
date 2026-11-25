<?php
require 'includes/functions.php';
$tid = (int)($_GET['tournament_id'] ?? 0);
$tournaments = $pdo->query("SELECT tournament_id, name FROM tournaments ORDER BY start_date DESC")->fetchAll();
$rows = leaderboard($pdo, $tid ?: null);
$pageTitle = 'Leaderboard';
include 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h2 class="mb-0">Leaderboard</h2>
  <form method="get" class="d-flex gap-2">
    <select name="tournament_id" class="form-select" onchange="this.form.submit()">
      <option value="0">All tournaments</option>
      <?php foreach ($tournaments as $t): ?><option value="<?= $t['tournament_id'] ?>" <?= $tid == $t['tournament_id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-outline-neon">Show</button></noscript>
  </form>
</div>
<div class="panel"><?php render_leaderboard($rows); ?></div>
<p class="text-muted small mt-3">Updates automatically from match results. Win = 3 points, draw = 1, loss = 0. Ties are broken by goal/score difference, then scores for.</p>
<?php include 'includes/footer.php'; ?>
