<?php
require 'includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$t = ['game_id' => 0, 'name' => '', 'start_date' => '', 'end_date' => '', 'prize_pool' => '0', 'max_teams' => 8, 'status' => 'upcoming'];
if ($id) {
    $s = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
    $s->execute([$id]);
    $t = $s->fetch();
    if (!$t) { flash('Tournament not found.', 'danger'); redirect('tournaments.php'); }
}
$games = $pdo->query("SELECT * FROM games ORDER BY game_name")->fetchAll();
if (!$games) { flash('Add a game first.', 'warning'); redirect('games.php'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = ['game_id' => (int)$_POST['game_id'], 'name' => post('name'), 'start_date' => post('start_date'), 'end_date' => post('end_date'),
          'prize_pool' => post('prize_pool'), 'max_teams' => (int)$_POST['max_teams'], 'status' => post('status')];
    if (strlen($t['name']) < 3) $errors[] = 'Name must be at least 3 characters.';
    if (!$t['start_date'] || !$t['end_date']) $errors[] = 'Start and end dates are required.';
    elseif ($t['end_date'] < $t['start_date']) $errors[] = 'End date cannot be before start date.';
    if (!is_numeric($t['prize_pool']) || $t['prize_pool'] < 0) $errors[] = 'Prize pool must be a number (0 or more).';
    if ($t['max_teams'] < 2 || $t['max_teams'] > 64) $errors[] = 'Max teams must be between 2 and 64.';
    if (!in_array($t['status'], ['upcoming', 'ongoing', 'completed'])) $errors[] = 'Invalid status.';
    if (!$errors) {
        $v = [$t['game_id'], $t['name'], $t['start_date'], $t['end_date'], $t['prize_pool'], $t['max_teams'], $t['status']];
        if ($id) { $v[] = $id; $pdo->prepare("UPDATE tournaments SET game_id=?, name=?, start_date=?, end_date=?, prize_pool=?, max_teams=?, status=? WHERE tournament_id=?")->execute($v); }
        else $pdo->prepare("INSERT INTO tournaments (game_id, name, start_date, end_date, prize_pool, max_teams, status) VALUES (?,?,?,?,?,?,?)")->execute($v);
        flash($id ? 'Tournament updated.' : 'Tournament created.');
        redirect('tournaments.php');
    }
}
$pageTitle = $id ? 'Edit Tournament' : 'New Tournament';
include 'includes/header.php';
?>
<div class="auth-box panel" style="max-width:600px">
  <h3 class="mb-3"><?= $pageTitle ?></h3>
  <?php show_errors($errors); ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Tournament name</label><input name="name" class="form-control" value="<?= e($t['name']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Game</label><select name="game_id" class="form-select">
      <?php foreach ($games as $g): ?><option value="<?= $g['game_id'] ?>" <?= $t['game_id'] == $g['game_id'] ? 'selected' : '' ?>><?= e($g['game_name']) ?></option><?php endforeach; ?></select></div>
    <div class="row">
      <div class="col-6 mb-3"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="<?= e($t['start_date']) ?>" required></div>
      <div class="col-6 mb-3"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="<?= e($t['end_date']) ?>" required></div>
      <div class="col-6 mb-3"><label class="form-label">Prize pool ($)</label><input type="number" step="0.01" min="0" name="prize_pool" class="form-control" value="<?= e($t['prize_pool']) ?>"></div>
      <div class="col-6 mb-3"><label class="form-label">Max teams</label><input type="number" min="2" max="64" name="max_teams" class="form-control" value="<?= e($t['max_teams']) ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select">
      <?php foreach (['upcoming', 'ongoing', 'completed'] as $st): ?><option value="<?= $st ?>" <?= $t['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-neon">Save</button> <a href="tournaments.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
