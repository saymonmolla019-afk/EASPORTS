<?php
require 'includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$errors = [];
$username = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = post('username'); $email = post('email');
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['confirm'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) $errors[] = 'Username must be 3-30 letters, numbers or underscores.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($pw) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $c->execute([$username, $email]);
        if ($c->fetchColumn() > 0) $errors[] = 'Username or email is already taken.';
    }
    if (!$errors) {
        $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')")
            ->execute([$username, $email, password_hash($pw, PASSWORD_DEFAULT)]);
        flash('Account created! You can log in now.');
        redirect('login.php');
    }
}
$pageTitle = 'Register';
include 'includes/header.php';
?>
<div class="auth-box panel">
  <h3 class="mb-3">Create account</h3>
  <?php show_errors($errors); ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= e($username) ?>" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($email) ?>" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="confirm" class="form-control" required></div>
    <button class="btn btn-neon w-100">Register</button>
  </form>
  <p class="text-muted small mt-3 mb-0">Already registered? <a href="login.php">Login</a></p>
</div>
<?php include 'includes/footer.php'; ?>
