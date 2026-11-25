<?php
require 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    require_admin(); csrf_check();
    $pdo->prepare("DELETE FROM tournaments WHERE tournament_id = ?")->execute([(int)$_POST['id']]);
    flash('Tournament deleted.');
    redirect('tournaments.php');
}
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$sql = "SELECT t.*, g.game_name, (SELECT COUNT(*) FROM tournament_teams tt WHERE tt.tournament_id = t.tournament_id) AS reg
        FROM tournaments t JOIN games g ON g.game_id = t.game_id WHERE (t.name LIKE ? OR g.game_name LIKE ?)";
$params = ["%$q%", "%$q%"];
if (in_array($status, ['upcoming', 'ongoing', 'completed'])) { $sql .= " AND t.status = ?"; $params[] = $status; }
$s = $pdo->prepare($sql . " ORDER BY t.start_date DESC");
$s->execute($params);
$list = $s->fetchAll();
$pageTitle = 'Tournaments';
include 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h2 class="mb-0">Tournaments</h2>
  <?php if (is_admin()): ?><a href="tournament_form.php" class="btn btn-neon">+ New tournament</a><?php endif; ?>
</div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-5"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search tournament or game..."></div>
  <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>
    <?php foreach (['upcoming', 'ongoing', 'completed'] as $st): ?><option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-auto"><button class="btn btn-outline-neon">Search</button> <a href="tournaments.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="row g-3">
  <?php foreach ($list as $t): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card hoverable h-100 p-3">
        <div class="d-flex justify-content-between mb-1"><h5 class="mb-0"><?= e($t['name']) ?></h5><?= status_badge($t['status']) ?></div>
        <div class="text-muted small mb-2"><?= e($t['game_name']) ?></div>
        <div class="small">Dates: <?= e($t['start_date']) ?> &rarr; <?= e($t['end_date']) ?></div>
        <div class="small">Teams: <?= $t['reg'] ?> / <?= $t['max_teams'] ?></div>
        <div class="prize mb-3">$<?= number_format($t['prize_pool'], 2) ?></div>
        <div class="mt-auto">
          <a href="tournament_view.php?id=<?= $t['tournament_id'] ?>" class="btn btn-sm btn-neon">View</a>
          <?php if (is_admin()): ?>
            <a href="tournament_form.php?id=<?= $t['tournament_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
            <form method="post" class="d-inline" data-confirm="Delete this tournament, its registrations and matches?"><?= csrf_field() ?>
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['tournament_id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; if (!$list) echo '<p class="text-muted">No tournaments found.</p>'; ?>
</div>
<?php include 'includes/footer.php'; ?>
