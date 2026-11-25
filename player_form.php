<?php
require 'includes/functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$player = ['team_id' => (int)($_GET['team_id'] ?? 0), 'gamer_tag' => '', 'full_name' => '', 'role_in_team' => '', 'country' => ''];
if ($id) {
    $s = $pdo->prepare("SELECT p.*, t.owner_id FROM players p JOIN teams t ON t.team_id = p.team_id WHERE p.player_id = ?");
    $s->execute([$id]);
    $player = $s->fetch();
    if (!can_manage_team($player)) { flash('Not allowed.', 'danger'); redirect('players.php'); }
}
// Teams this user may add players to
if (is_admin()) $myTeams = $pdo->query("SELECT team_id, team_name, owner_id FROM teams ORDER BY team_name")->fetchAll();
else { $s = $pdo->prepare("SELECT team_id, team_name, owner_id FROM teams WHERE owner_id = ? ORDER BY team_name"); $s->execute([$_SESSION['user_id']]); $myTeams = $s->fetchAll(); }
if (!$myTeams) { flash('Create a team first, then add players.', 'warning'); redirect('team_form.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = ['team_id' => (int)$_POST['team_id'], 'gamer_tag' => post('gamer_tag'), 'full_name' => post('full_name'),
          'role_in_team' => post('role_in_team'), 'country' => post('country')];
    $okTeam = false;
    foreach ($myTeams as $t) if ($t['team_id'] == $d['team_id']) $okTeam = true;
    if (!$okTeam) $errors[] = 'Choose one of your teams.';
    foreach (['gamer_tag' => 'Gamer tag', 'full_name' => 'Full name', 'role_in_team' => 'Role', 'country' => 'Country'] as $k => $label)
        if ($d[$k] === '') $errors[] = "$label is required.";
    if (!$errors) {
        if ($id) $pdo->prepare("UPDATE players SET team_id=?, gamer_tag=?, full_name=?, role_in_team=?, country=? WHERE player_id=?")
                     ->execute([$d['team_id'], $d['gamer_tag'], $d['full_name'], $d['role_in_team'], $d['country'], $id]);
        else $pdo->prepare("INSERT INTO players (team_id, gamer_tag, full_name, role_in_team, country) VALUES (?,?,?,?,?)")
                 ->execute([$d['team_id'], $d['gamer_tag'], $d['full_name'], $d['role_in_team'], $d['country']]);
        flash($id ? 'Player updated.' : 'Player added.');
        redirect('players.php?team_id=' . $d['team_id']);
    }
    $player = $d;
}
$pageTitle = $id ? 'Edit Player' : 'New Player';
include 'includes/header.php';
?>
<div class="auth-box panel" style="max-width:520px">
  <h3 class="mb-3"><?= $pageTitle ?></h3>
  <?php show_errors($errors); ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Team</label><select name="team_id" class="form-select" required>
      <?php foreach ($myTeams as $t): ?><option value="<?= $t['team_id'] ?>" <?= $player['team_id'] == $t['team_id'] ? 'selected' : '' ?>><?= e($t['team_name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-3"><label class="form-label">Gamer tag</label><input name="gamer_tag" class="form-control" value="<?= e($player['gamer_tag']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($player['full_name']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Role in team (e.g. Captain, Support)</label><input name="role_in_team" class="form-control" value="<?= e($player['role_in_team']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Country</label><input name="country" class="form-control" value="<?= e($player['country']) ?>" required></div>
    <button class="btn btn-neon">Save</button> <a href="players.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
