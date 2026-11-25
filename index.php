<?php
require 'includes/functions.php';
$stats = [
    'Teams' => $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
    'Players' => $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn(),
    'Tournaments' => $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn(),
    'Matches' => $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn(),
];
$tournaments = $pdo->query("SELECT t.*, g.game_name FROM tournaments t JOIN games g ON g.game_id = t.game_id
                            WHERE t.status <> 'completed' ORDER BY t.start_date LIMIT 3")->fetchAll();
$recent = $pdo->query("SELECT m.match_date, a.team_name AS team1, b.team_name AS team2, r.team1_score, r.team2_score
                       FROM matches m JOIN teams a ON a.team_id = m.team1_id JOIN teams b ON b.team_id = m.team2_id
                       JOIN match_results r ON r.match_id = m.match_id ORDER BY m.match_date DESC LIMIT 5")->fetchAll();
$pageTitle = 'Home';
include 'includes/header.php';
?>
<div class="hero mb-4">
  <h1>Compete. Win. <span>Rise to the top.</span></h1>
  <p class="text-muted mb-4">Manage teams, join tournaments, follow matches and watch the leaderboard update automatically.</p>
  <?php if (!is_logged_in()): ?>
    <a href="register.php" class="btn btn-neon me-2">Join now</a><a href="tournaments.php" class="btn btn-outline-neon">View tournaments</a>
  <?php else: ?>
    <a href="tournaments.php" class="btn btn-neon me-2">Tournaments</a><a href="dashboard.php" class="btn btn-outline-neon">My dashboard</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $n): ?>
    <div class="col-6 col-md-3"><div class="panel stat"><div class="num"><?= $n ?></div><div class="lbl"><?= $label ?></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <h4 class="section-title">Active &amp; Upcoming Tournaments</h4>
    <?php foreach ($tournaments as $t): ?>
      <a href="tournament_view.php?id=<?= $t['tournament_id'] ?>" class="text-decoration-none">
        <div class="card hoverable p-3 mb-3">
          <div class="d-flex justify-content-between"><strong class="text-white"><?= e($t['name']) ?></strong><?= status_badge($t['status']) ?></div>
          <div class="text-muted small"><?= e($t['game_name']) ?> &bull; <?= e($t['start_date']) ?> to <?= e($t['end_date']) ?></div>
          <div class="prize">$<?= number_format($t['prize_pool'], 2) ?> prize pool</div>
        </div>
      </a>
    <?php endforeach; if (!$tournaments) echo '<p class="text-muted">No tournaments yet.</p>'; ?>
  </div>
  <div class="col-lg-5">
    <h4 class="section-title">Top Teams</h4>
    <div class="panel mb-4"><?php render_leaderboard(array_slice(leaderboard($pdo), 0, 5)); ?></div>
    <h4 class="section-title">Recent Results</h4>
    <div class="panel">
      <?php foreach ($recent as $r): ?>
        <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
          <span><?= e($r['team1']) ?> vs <?= e($r['team2']) ?></span><span class="score"><?= $r['team1_score'] ?> - <?= $r['team2_score'] ?></span>
        </div>
      <?php endforeach; if (!$recent) echo '<p class="text-muted mb-0">No results yet.</p>'; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
