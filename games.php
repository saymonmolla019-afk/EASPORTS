<?php
require 'includes/functions.php';
require_admin();
$editId = (int)($_GET['edit'] ?? 0);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action'); $gid = (int)($_POST['id'] ?? 0);
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM games WHERE game_id = ?")->execute([$gid]);
        flash('Game deleted (its tournaments were removed too).'); redirect('games.php');
    }
    $name = post('game_name'); $genre = post('genre');
    if ($name === '' || $genre === '') $errors[] = 'Game name and genre are required.';
    else {
        $c = $pdo->prepare("SELECT COUNT(*) FROM games WHERE game_name = ? AND game_id <> ?");
        $c->execute([$name, $gid]);
        if ($c->fetchColumn() > 0) $errors[] = 'This game already exists.';
    }
    if (!$errors) {
        if ($gid) $pdo->prepare("UPDATE games SET game_name=?, genre=? WHERE game_id=?")->execute([$name, $genre, $gid]);
        else $pdo->prepare("INSERT INTO games (game_name, genre) VALUES (?,?)")->execute([$name, $genre]);
        flash('Game saved.'); redirect('games.php');
    }
    $editId = $gid;
}
$q = trim($_GET['q'] ?? '');
$s = $pdo->prepare("SELECT * FROM games WHERE game_name LIKE ? OR genre LIKE ? ORDER BY game_name");
$s->execute(["%$q%", "%$q%"]);
$games = $s->fetchAll();
$edit = ['game_id' => 0, 'game_name' => '', 'genre' => ''];
if ($editId) { $s = $pdo->prepare("SELECT * FROM games WHERE game_id = ?"); $s->execute([$editId]); $edit = $s->fetch() ?: $edit; }
$pageTitle = 'Games';
include 'includes/header.php';
?>
<h2 class="mb-3">Games</h2>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel">
      <h5><?= $edit['game_id'] ? 'Edit game' : 'Add game' ?></h5>
      <?php show_errors($errors); ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit['game_id'] ?>">
        <div class="mb-3"><label class="form-label">Game name</label><input name="game_name" class="form-control" value="<?= e($edit['game_name']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Genre</label><input name="genre" class="form-control" value="<?= e($edit['genre']) ?>" required></div>
        <button class="btn btn-neon">Save</button> <?php if ($edit['game_id']): ?><a href="games.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <form class="d-flex gap-2 mb-3" method="get"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search games..."><button class="btn btn-outline-neon">Search</button></form>
    <div class="panel table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Game</th><th>Genre</th><th class="text-end">Actions</th></tr></thead><tbody>
        <?php foreach ($games as $g): ?>
          <tr><td><strong><?= e($g['game_name']) ?></strong></td><td><?= e($g['genre']) ?></td>
            <td class="text-end"><a href="games.php?edit=<?= $g['game_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
              <form method="post" class="d-inline" data-confirm="Delete this game and its tournaments?"><?= csrf_field() ?>
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['game_id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
        <?php endforeach; if (!$games) echo '<tr><td colspan="3" class="text-muted">No games found.</td></tr>'; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
