<?php
require 'includes/functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$team = ['team_name' => '', 'tag' => '', 'country' => ''];
if ($id) {
    $s = $pdo->prepare("SELECT * FROM teams WHERE team_id = ?");
    $s->execute([$id]);
    $team = $s->fetch();
    if (!can_manage_team($team)) { flash('Not allowed.', 'danger'); redirect('teams.php'); }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('team_name'); $tag = strtoupper(post('tag')); $country = post('country');
    if (strlen($name) < 2 || strlen($name) > 60) $errors[] = 'Team name must be 2-60 characters.';
    if (!preg_match('/^[A-Z0-9]{2,6}$/', $tag)) $errors[] = 'Tag must be 2-6 letters or numbers.';
    if ($country === '') $errors[] = 'Country is required.';
    if (!$errors) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ? AND team_id <> ?");
        $c->execute([$name, $id]);
        if ($c->fetchColumn() > 0) $errors[] = 'A team with this name already exists.';
    }
    if (!$errors) {
        if ($id) $pdo->prepare("UPDATE teams SET team_name=?, tag=?, country=? WHERE team_id=?")->execute([$name, $tag, $country, $id]);
        else $pdo->prepare("INSERT INTO teams (team_name, tag, country, owner_id) VALUES (?,?,?,?)")->execute([$name, $tag, $country, $_SESSION['user_id']]);
        flash($id ? 'Team updated.' : 'Team created.');
        redirect('teams.php');
    }
    $team = ['team_name' => $name, 'tag' => $tag, 'country' => $country];
}
$pageTitle = $id ? 'Edit Team' : 'New Team';
include 'includes/header.php';
?>
<div class="auth-box panel" style="max-width:520px">
  <h3 class="mb-3"><?= $pageTitle ?></h3>
  <?php show_errors($errors); ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Team name</label><input name="team_name" class="form-control" value="<?= e($team['team_name']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Tag (2-6 characters)</label><input name="tag" class="form-control" maxlength="6" value="<?= e($team['tag']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Country</label><input name="country" class="form-control" value="<?= e($team['country']) ?>" required></div>
    <button class="btn btn-neon">Save</button> <a href="teams.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
