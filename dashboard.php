<?php
require 'includes/functions.php';
require_login();
$uid = $_SESSION['user_id'];
$pageTitle = 'Dashboard';
include 'includes/header.php';

if (is_admin()):
    $counts = [
        'Users' => 'users', 'Games' => 'games', 'Teams' => 'teams', 'Players' => 'players',
        'Tournaments' => 'tournaments', 'Registrations' => 'tournament_teams', 'Matches' => 'matches', 'Results' => 'match_results',
    ];
    $upcoming = $pdo->query("SELECT m.match_id, m.match_date, t.name, a.team_name AS team1, b.team_name AS team2
                             FROM matches m JOIN tournaments t ON t.tournament_id = m.tournament_id
                             JOIN teams a ON a.team_id = m.team1_id JOIN teams b ON b.team_id = m.team2_id
                             WHERE m.status = 'scheduled' ORDER BY m.match_date LIMIT 6")->fetchAll();
?>
<h2 class="mb-1">Admin Dashboard</h2>
<p class="text-muted">Overview of the whole system.</p>
<div class="row g-3 mb-4">
  <?php foreach ($counts as $label => $table): ?>
    <div class="col-6 col-md-3"><div class="panel stat"><div class="num"><?= $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn() ?></div><div class="lbl"><?= $label ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4">
  <div class="col-lg-5">
    <h4 class="section-title">Quick actions</h4>
    <div class="panel d-grid gap-2">
      <a class="btn btn-neon" href="tournament_form.php">+ New tournament</a>
      <a class="btn btn-outline-neon" href="match_form.php">+ Schedule match</a>
      <a class="btn btn-outline-neon" href="team_form.php">+ New team</a>
      <a class="btn btn-outline-neon" href="player_form.php">+ New player</a>
      <a class="btn btn-outline-neon" href="games.php">Manage games</a>
      <a class="btn btn-outline-neon" href="users.php">Manage users</a>
    </div>
  </div>
  <div class="col-lg-7">
    <h4 class="section-title">Scheduled matches (enter results here)</h4>
    <div class="panel">
      <?php foreach ($upcoming as $m): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2">
          <div><strong><?= e($m['team1']) ?> vs <?= e($m['team2']) ?></strong><br><span class="text-muted small"><?= e($m['name']) ?> &bull; <?= e(date('M j, H:i', strtotime($m['match_date']))) ?></span></div>
          <a class="btn btn-sm btn-outline-neon" href="match_form.php?id=<?= $m['match_id'] ?>">Result</a>
        </div>
      <?php endforeach; if (!$upcoming) echo '<p class="text-muted mb-0">No scheduled matches.</p>'; ?>
    </div>
  </div>
</div>
<?php else:
    $teams = $pdo->prepare("SELECT t.*, (SELECT COUNT(*) FROM players p WHERE p.team_id = t.team_id) AS pc FROM teams t WHERE owner_id = ? ORDER BY team_name");
    $teams->execute([$uid]); $teams = $teams->fetchAll();
    $regs = $pdo->prepare("SELECT tr.tournament_id, tr.name, tr.status, tm.team_name FROM tournament_teams tt
                           JOIN teams tm ON tm.team_id = tt.team_id JOIN tournaments tr ON tr.tournament_id = tt.tournament_id
                           WHERE tm.owner_id = ? ORDER BY tr.start_date DESC");
    $regs->execute([$uid]); $regs = $regs->fetchAll();
?>
<h2 class="mb-1">Welcome, <?= e($_SESSION['username']) ?></h2>
<p class="text-muted">Create a team, add players, then register it in a tournament.</p>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="d-flex justify-content-between"><h4 class="section-title">My teams</h4><a class="btn btn-sm btn-neon align-self-start" href="team_form.php">+ New team</a></div>
    <div class="panel">
      <?php foreach ($teams as $t): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2">
          <div><strong><?= e($t['team_name']) ?></strong> <span class="text-muted">[<?= e($t['tag']) ?>]</span><br><span class="text-muted small"><?= $t['pc'] ?> players</span></div>
          <div><a class="btn btn-sm btn-outline-neon" href="players.php?team_id=<?= $t['team_id'] ?>">Players</a>
               <a class="btn btn-sm btn-outline-neon" href="player_form.php?team_id=<?= $t['team_id'] ?>">+ Player</a></div>
        </div>
      <?php endforeach; if (!$teams) echo '<p class="text-muted mb-0">You have no teams yet.</p>'; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <h4 class="section-title">My tournament registrations</h4>
    <div class="panel">
      <?php foreach ($regs as $r): ?>
        <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
          <span><a href="tournament_view.php?id=<?= $r['tournament_id'] ?>"><?= e($r['name']) ?></a> <span class="text-muted">&mdash; <?= e($r['team_name']) ?></span></span><?= status_badge($r['status']) ?>
        </div>
      <?php endforeach; if (!$regs) echo '<p class="text-muted mb-0">Not registered anywhere yet. <a href="tournaments.php">Browse tournaments</a></p>'; ?>
    </div>
  </div>
</div>
<?php endif; include 'includes/footer.php'; ?>
