<?php
require 'includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $s->execute([post('email')]);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['user_id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['role'] = $u['role'];
        flash('Welcome back, ' . $u['username'] . '!');
        redirect('dashboard.php');
    }
    $error = 'Invalid email or password.';
}
$pageTitle = 'Login';
include 'includes/header.php';
?>
<div class="auth-box panel">
  <h3 class="mb-3">Login</h3>
  <?php if ($error) show_errors([$error]); ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-neon w-100">Login</button>
  </form>
  <p class="text-muted small mt-3 mb-0">No account? <a href="register.php">Register</a><br>Demo admin: admin@esports.local / admin123</p>
</div>
<?php include 'includes/footer.php'; ?>
