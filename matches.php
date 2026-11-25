<?php
require 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    require_admin(); csrf_check();
    $pdo->prepare("DELETE FROM matches WHERE match_id = ?")->execute([(int)$_POST['id']]);
    flash('Match deleted.');
    redirect('matches.php');
}
$q = trim($_GET['q'] ?? '');
$tid = (int)($_GET['tournament_id'] ?? 0);
$status = $_GET['status'] ?? '';
$sql = "SELECT m.*, t.name AS tournament_name, a.team_name AS team1, b.team_name AS team2, r.team1_score, r.team2_score, r.winner_team_id
        FROM matches m JOIN tournaments t ON t.tournament_id = m.tournament_id
        JOIN teams a ON a.team_id = m.team1_id JOIN teams b ON b.team_id = m.team2_id
        LEFT JOIN match_results r ON r.match_id = m.match_id
        WHERE (a.team_name LIKE ? OR b.team_name LIKE ?)";
$params = ["%$q%", "%$q%"];
if ($tid) { $sql .= " AND m.tournament_id = ?"; $params[] = $tid; }
if (in_array($status, ['scheduled', 'completed'])) { $sql .= " AND m.status = ?"; $params[] = $status; }
$s = $pdo->prepare($sql . " ORDER BY m.match_date DESC");
$s->execute($params);
$matches = $s->fetchAll();
$tournaments = $pdo->query("SELECT tournament_id, name FROM tournaments ORDER BY name")->fetchAll();
$pageTitle = 'Matches';
include 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h2 class="mb-0">Matches</h2>
  <?php if (is_admin()): ?><a href="match_form.php" class="btn btn-neon">+ Schedule match</a><?php endif; ?>
</div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-4"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search by team..."></div>
  <div class="col-md-3"><select name="tournament_id" class="form-select"><option value="0">All tournaments</option>
    <?php foreach ($tournaments as $t): ?><option value="<?= $t['tournament_id'] ?>" <?= $tid == $t['tournament_id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="status" class="form-select"><option value="">Any status</option>
    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option><option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option></select></div>
  <div class="col-auto"><button class="btn btn-outline-neon">Filter</button> <a href="matches.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="panel table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Date</th><th>Tournament</th><th>Match</th><th>Score</th><th>Round</th><th>Status</th><?php if (is_admin()): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($matches as $m): ?>
      <tr>
        <td class="text-nowrap"><?= e(date('M j, H:i', strtotime($m['match_date']))) ?></td><td><?= e($m['tournament_name']) ?></td>
        <td><strong class="<?= $m['winner_team_id'] == $m['team1_id'] ? 'text-info' : '' ?>"><?= e($m['team1']) ?></strong> vs
            <strong class="<?= $m['winner_team_id'] == $m['team2_id'] ? 'text-info' : '' ?>"><?= e($m['team2']) ?></strong></td>
        <td class="score"><?= $m['team1_score'] !== null ? $m['team1_score'] . ' - ' . $m['team2_score'] : '&mdash;' ?></td>
        <td><?= e($m['round_name']) ?></td><td><?= status_badge($m['status']) ?></td>
        <?php if (is_admin()): ?><td class="text-end text-nowrap">
          <a href="match_form.php?id=<?= $m['match_id'] ?>" class="btn btn-sm btn-outline-warning">Edit / Result</a>
          <form method="post" class="d-inline" data-confirm="Delete this match?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['match_id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
        </td><?php endif; ?>
      </tr>
    <?php endforeach; if (!$matches) echo '<tr><td colspan="7" class="text-muted">No matches found.</td></tr>'; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
