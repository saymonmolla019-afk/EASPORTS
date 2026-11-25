<?php
require 'includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT t.*, g.game_name FROM tournaments t JOIN games g ON g.game_id = t.game_id WHERE t.tournament_id = ?");
$s->execute([$id]);
$t = $s->fetch();
if (!$t) { flash('Tournament not found.', 'danger'); redirect('tournaments.php'); }

// Register / unregister a team
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login(); csrf_check();
    $teamId = (int)($_POST['team_id'] ?? 0);
    $ts = $pdo->prepare("SELECT * FROM teams WHERE team_id = ?");
    $ts->execute([$teamId]);
    $team = $ts->fetch();
    if (!can_manage_team($team)) flash('You can only manage your own teams.', 'danger');
    elseif (post('action') === 'register') {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = ?");
        $cnt->execute([$id]);
        $dup = $pdo->prepare("SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = ? AND team_id = ?");
        $dup->execute([$id, $teamId]);
        $pc = $pdo->prepare("SELECT COUNT(*) FROM players WHERE team_id = ?");
        $pc->execute([$teamId]);
        if ($t['status'] !== 'upcoming') flash('Registration is only open for upcoming tournaments.', 'warning');
        elseif ($cnt->fetchColumn() >= $t['max_teams']) flash('This tournament is full.', 'warning');
        elseif ($dup->fetchColumn() > 0) flash('Team is already registered.', 'warning');
        elseif ($pc->fetchColumn() < 1) flash('Add at least one player to the team before registering.', 'warning');
        else {
            $pdo->prepare("INSERT INTO tournament_teams (tournament_id, team_id) VALUES (?, ?)")->execute([$id, $teamId]);
            flash($team['team_name'] . ' registered!');
        }
    } elseif (post('action') === 'unregister') {
        $mc = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND (team1_id = ? OR team2_id = ?)");
        $mc->execute([$id, $teamId, $teamId]);
        if ($mc->fetchColumn() > 0) flash('This team already has matches; delete those matches first.', 'warning');
        else { $pdo->prepare("DELETE FROM tournament_teams WHERE tournament_id = ? AND team_id = ?")->execute([$id, $teamId]); flash('Team removed from tournament.'); }
    }
    redirect('tournament_view.php?id=' . $id);
}

$s = $pdo->prepare("SELECT tm.*, tt.registered_at FROM tournament_teams tt JOIN teams tm ON tm.team_id = tt.team_id WHERE tt.tournament_id = ? ORDER BY tt.registered_at");
$s->execute([$id]);
$registered = $s->fetchAll();

$available = [];
if (is_logged_in()) {
    $sql = "SELECT * FROM teams WHERE team_id NOT IN (SELECT team_id FROM tournament_teams WHERE tournament_id = ?)";
    $p = [$id];
    if (!is_admin()) { $sql .= " AND owner_id = ?"; $p[] = $_SESSION['user_id']; }
    $s = $pdo->prepare($sql . " ORDER BY team_name");
    $s->execute($p);
    $available = $s->fetchAll();
}

$s = $pdo->prepare("SELECT m.*, a.team_name AS team1, b.team_name AS team2, r.team1_score, r.team2_score
                    FROM matches m JOIN teams a ON a.team_id = m.team1_id JOIN teams b ON b.team_id = m.team2_id
                    LEFT JOIN match_results r ON r.match_id = m.match_id WHERE m.tournament_id = ? ORDER BY m.match_date");
$s->execute([$id]);
$matches = $s->fetchAll();

$pageTitle = $t['name'];
include 'includes/header.php';
?>
<div class="hero mb-4" style="padding:1.75rem">
  <div class="d-flex flex-wrap justify-content-between gap-2">
    <div><h2 class="mb-1"><?= e($t['name']) ?></h2><div class="text-muted"><?= e($t['game_name']) ?> &bull; <?= e($t['start_date']) ?> &rarr; <?= e($t['end_date']) ?></div></div>
    <div class="text-end"><?= status_badge($t['status']) ?><div class="prize fs-4">$<?= number_format($t['prize_pool'], 2) ?></div></div>
  </div>
</div>
<div class="row g-4">
  <div class="col-lg-5">
    <h4 class="section-title">Registered teams (<?= count($registered) ?>/<?= $t['max_teams'] ?>)</h4>
    <div class="panel mb-3">
      <?php foreach ($registered as $r): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2">
          <span><strong><?= e($r['team_name']) ?></strong> <span class="text-muted">[<?= e($r['tag']) ?>]</span></span>
          <?php if (can_manage_team($r) && ($t['status'] === 'upcoming' || is_admin())): ?>
            <form method="post" data-confirm="Remove this team from the tournament?"><?= csrf_field() ?><input type="hidden" name="action" value="unregister"><input type="hidden" name="team_id" value="<?= $r['team_id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Remove</button></form>
          <?php endif; ?>
        </div>
      <?php endforeach; if (!$registered) echo '<p class="text-muted mb-0">No teams registered yet.</p>'; ?>
    </div>
    <?php if ($t['status'] === 'upcoming'): ?>
      <div class="panel">
        <h6>Register a team</h6>
        <?php if (!is_logged_in()): ?>
          <p class="text-muted mb-0"><a href="login.php">Login</a> to register your team.</p>
        <?php elseif ($available): ?>
          <form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="action" value="register">
            <select name="team_id" class="form-select"><?php foreach ($available as $a): ?><option value="<?= $a['team_id'] ?>"><?= e($a['team_name']) ?></option><?php endforeach; ?></select>
            <button class="btn btn-neon">Register</button></form>
        <?php else: ?>
          <p class="text-muted mb-0">No eligible teams. <a href="team_form.php">Create a team</a> first.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-7">
    <h4 class="section-title">Leaderboard</h4>
    <div class="panel mb-4"><?php render_leaderboard(leaderboard($pdo, $id)); ?></div>
    <div class="d-flex justify-content-between"><h4 class="section-title">Matches</h4>
      <?php if (is_admin()): ?><a href="match_form.php?tournament_id=<?= $id ?>" class="btn btn-sm btn-neon align-self-start">+ Schedule</a><?php endif; ?></div>
    <div class="panel">
      <?php foreach ($matches as $m): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2">
          <div><?= e($m['team1']) ?> vs <?= e($m['team2']) ?><br><span class="text-muted small"><?= e(date('M j, H:i', strtotime($m['match_date']))) ?> &bull; <?= e($m['round_name']) ?></span></div>
          <div class="score"><?= $m['team1_score'] !== null ? $m['team1_score'] . ' - ' . $m['team2_score'] : status_badge($m['status']) ?></div>
        </div>
      <?php endforeach; if (!$matches) echo '<p class="text-muted mb-0">No matches scheduled.</p>'; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
