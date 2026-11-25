<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Home';
$flash = flash();
$cur = basename($_SERVER['PHP_SELF']);
function nav_link($file, $label, $cur, $extra = []) {
    $active = ($cur === $file || in_array($cur, $extra)) ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . $file . '">' . $label . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | EsportsHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">ESPORTS<span>HUB</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php
        nav_link('index.php', 'Home', $cur);
        nav_link('tournaments.php', 'Tournaments', $cur, ['tournament_view.php', 'tournament_form.php']);
        nav_link('teams.php', 'Teams', $cur, ['team_form.php']);
        nav_link('players.php', 'Players', $cur, ['player_form.php']);
        nav_link('matches.php', 'Matches', $cur, ['match_form.php']);
        nav_link('leaderboard.php', 'Leaderboard', $cur);
        if (is_admin()) { nav_link('games.php', 'Games', $cur); nav_link('users.php', 'Users', $cur); }
        ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (is_logged_in()): ?>
          <?php nav_link('dashboard.php', 'Dashboard (' . e($_SESSION['username']) . ')', $cur); ?>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <?php nav_link('login.php', 'Login', $cur); nav_link('register.php', 'Register', $cur); ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['t']) ?> alert-dismissible fade show"><?= e($flash['m']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
