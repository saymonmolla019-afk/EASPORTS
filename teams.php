<?php
require 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    require_login(); csrf_check();
    $s = $pdo->prepare("SELECT * FROM teams WHERE team_id = ?");
    $s->execute([(int)$_POST['id']]);
    if (can_manage_team($s->fetch())) {
        $pdo->prepare("DELETE FROM teams WHERE team_id = ?")->execute([(int)$_POST['id']]);
        flash('Team deleted.');
    } else flash('Not allowed.', 'danger');
    redirect('teams.php');
}
$q = trim($_GET['q'] ?? '');
$s = $pdo->prepare("SELECT t.*, u.username, (SELECT COUNT(*) FROM players p WHERE p.team_id = t.team_id) AS pc
                    FROM teams t JOIN users u ON u.user_id = t.owner_id
                    WHERE t.team_name LIKE ? OR t.tag LIKE ? OR t.country LIKE ? ORDER BY t.team_name");
$s->execute(["%$q%", "%$q%", "%$q%"]);
$teams = $s->fetchAll();
$pageTitle = 'Teams';
include 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h2 class="mb-0">Teams</h2>
  <?php if (is_logged_in()): ?><a href="team_form.php" class="btn btn-neon">+ New team</a><?php endif; ?>
</div>
<form class="row g-2 mb-3" method="get">
  <div class="col-sm-8 col-md-6"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search by name, tag or country..."></div>
  <div class="col-auto"><button class="btn btn-outline-neon">Search</button> <a href="teams.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="panel table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>Team</th><th>Tag</th><th>Country</th><th>Owner</th><th>Players</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($teams as $t): ?>
      <tr>
        <td><strong><?= e($t['team_name']) ?></strong></td><td><?= e($t['tag']) ?></td><td><?= e($t['country']) ?></td>
        <td><?= e($t['username']) ?></td><td><?= $t['pc'] ?></td>
        <td class="text-end">
          <a href="players.php?team_id=<?= $t['team_id'] ?>" class="btn btn-sm btn-outline-neon">Players</a>
          <?php if (can_manage_team($t)): ?>
            <a href="team_form.php?id=<?= $t['team_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
            <form method="post" class="d-inline" data-confirm="Delete this team and its players?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['team_id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; if (!$teams) echo '<tr><td colspan="6" class="text-muted">No teams found.</td></tr>'; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
