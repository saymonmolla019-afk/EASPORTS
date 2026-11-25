<?php
require 'includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int)$_POST['id'];
    if ($uid === (int)$_SESSION['user_id']) flash('You cannot change or delete your own account here.', 'warning');
    elseif (post('action') === 'delete') { $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]); flash('User deleted.'); }
    elseif (post('action') === 'role') {
        $role = post('role') === 'admin' ? 'admin' : 'user';
        $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?")->execute([$role, $uid]); flash('Role updated.');
    }
    redirect('users.php');
}
$q = trim($_GET['q'] ?? '');
$s = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY user_id");
$s->execute(["%$q%", "%$q%"]);
$users = $s->fetchAll();
$pageTitle = 'Users';
include 'includes/header.php';
?>
<h2 class="mb-3">Users</h2>
<form class="d-flex gap-2 mb-3" method="get" style="max-width:480px"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search username or email..."><button class="btn btn-outline-neon">Search</button></form>
<div class="panel table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($users as $u): ?>
      <tr><td><?= $u['user_id'] ?></td><td><strong><?= e($u['username']) ?></strong></td><td><?= e($u['email']) ?></td>
        <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>"><?= e($u['role']) ?></span></td><td><?= e(substr($u['created_at'], 0, 10)) ?></td>
        <td class="text-end">
          <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= $u['user_id'] ?>">
            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
            <button class="btn btn-sm btn-outline-warning"><?= $u['role'] === 'admin' ? 'Make user' : 'Make admin' ?></button></form>
          <form method="post" class="d-inline" data-confirm="Delete this user and their teams?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['user_id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button></form>
          <?php else: ?><span class="text-muted small">You</span><?php endif; ?>
        </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
