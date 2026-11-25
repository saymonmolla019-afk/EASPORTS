<?php
require 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    require_login(); csrf_check();
    $s = $pdo->prepare("SELECT t.owner_id FROM players p JOIN teams t ON t.team_id = p.team_id WHERE p.player_id = ?");
    $s->execute([(int)$_POST['id']]);
    if (can_manage_team($s->fetch())) {
        $pdo->prepare("DELETE FROM players WHERE player_id = ?")->execute([(int)$_POST['id']]);
        flash('Player deleted.');
    } else flash('Not allowed.', 'danger');
    redirect('players.php' . (!empty($_POST['team_id']) ? '?team_id=' . (int)$_POST['team_id'] : ''));
}
$q = trim($_GET['q'] ?? '');
$teamId = (int)($_GET['team_id'] ?? 0);
$sql = "SELECT p.*, t.team_name, t.owner_id FROM players p JOIN teams t ON t.team_id = p.team_id
        WHERE (p.gamer_tag LIKE ? OR p.full_name LIKE ? OR p.role_in_team LIKE ? OR p.country LIKE ?)";
$params = array_fill(0, 4, "%$q%");
if ($teamId) { $sql .= " AND p.team_id = ?"; $params[] = $teamId; }
$s = $pdo->prepare($sql . " ORDER BY t.team_name, p.gamer_tag");
$s->execute($params);
$players = $s->fetchAll();
$allTeams = $pdo->query("SELECT team_id, team_name FROM teams ORDER BY team_name")->fetchAll();
$pageTitle = 'Players';
include 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h2 class="mb-0">Players</h2>
  <?php if (is_logged_in()): ?><a href="player_form.php<?= $teamId ? '?team_id=' . $teamId : '' ?>" class="btn btn-neon">+ New player</a><?php endif; ?>
</div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-5"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search gamer tag, name, role, country..."></div>
  <div class="col-md-3"><select name="team_id" class="form-select"><option value="0">All teams</option>
    <?php foreach ($allTeams as $t): ?><option value="<?= $t['team_id'] ?>" <?= $teamId == $t['team_id'] ? 'selected' : '' ?>><?= e($t['team_name']) ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-auto"><button class="btn btn-outline-neon">Search</button> <a href="players.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="panel table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Gamer tag</th><th>Name</th><th>Team</th><th>Role</th><th>Country</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($players as $p): ?>
      <tr>
        <td><strong class="text-info"><?= e($p['gamer_tag']) ?></strong></td><td><?= e($p['full_name']) ?></td>
        <td><?= e($p['team_name']) ?></td><td><?= e($p['role_in_team']) ?></td><td><?= e($p['country']) ?></td>
        <td class="text-end">
          <?php if (can_manage_team($p)): ?>
            <a href="player_form.php?id=<?= $p['player_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
            <form method="post" class="d-inline" data-confirm="Delete this player?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['player_id'] ?>"><input type="hidden" name="team_id" value="<?= $teamId ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; if (!$players) echo '<tr><td colspan="6" class="text-muted">No players found.</td></tr>'; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
