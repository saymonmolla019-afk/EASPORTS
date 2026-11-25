<?php
require 'includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$m = ['tournament_id' => (int)($_GET['tournament_id'] ?? 0), 'team1_id' => 0, 'team2_id' => 0, 'match_date' => '', 'round_name' => 'Group Stage', 'status' => 'scheduled'];
$result = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM matches WHERE match_id = ?");
    $s->execute([$id]);
    $m = $s->fetch();
    if (!$m) { flash('Match not found.', 'danger'); redirect('matches.php'); }
    $s = $pdo->prepare("SELECT * FROM match_results WHERE match_id = ?");
    $s->execute([$id]);
    $result = $s->fetch() ?: null;
}
$tournaments = $pdo->query("SELECT tournament_id, name FROM tournaments ORDER BY name")->fetchAll();
$entries = $pdo->query("SELECT tt.tournament_id, t.team_id, t.team_name FROM tournament_teams tt JOIN teams t ON t.team_id = tt.team_id ORDER BY t.team_name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'save_match') {
        $m = ['tournament_id' => (int)$_POST['tournament_id'], 'team1_id' => (int)$_POST['team1_id'], 'team2_id' => (int)$_POST['team2_id'],
              'match_date' => str_replace('T', ' ', post('match_date')), 'round_name' => post('round_name') ?: 'Group Stage', 'status' => $m['status']];
        $reg = [];
        foreach ($entries as $en) if ($en['tournament_id'] == $m['tournament_id']) $reg[] = $en['team_id'];
        if (!$m['tournament_id']) $errors[] = 'Choose a tournament.';
        if ($m['team1_id'] === $m['team2_id']) $errors[] = 'A team cannot play against itself.';
        if (!in_array($m['team1_id'], $reg) || !in_array($m['team2_id'], $reg)) $errors[] = 'Both teams must be registered in the selected tournament.';
        if (!strtotime($m['match_date'])) $errors[] = 'Enter a valid date and time.';
        if (!$errors) {
            $v = [$m['tournament_id'], $m['team1_id'], $m['team2_id'], date('Y-m-d H:i:s', strtotime($m['match_date'])), $m['round_name']];
            if ($id) {
                $v[] = $id;
                $pdo->prepare("UPDATE matches SET tournament_id=?, team1_id=?, team2_id=?, match_date=?, round_name=? WHERE match_id=?")->execute($v);
                // keep the winner correct if the teams changed
                if ($result) $pdo->prepare("UPDATE match_results SET winner_team_id=? WHERE match_id=?")
                    ->execute([winner_of($m['team1_id'], $m['team2_id'], $result['team1_score'], $result['team2_score']), $id]);
            } else $pdo->prepare("INSERT INTO matches (tournament_id, team1_id, team2_id, match_date, round_name) VALUES (?,?,?,?,?)")->execute($v);
            flash($id ? 'Match updated.' : 'Match scheduled.');
            redirect('matches.php');
        }
    } elseif ($id && $action === 'save_result') {
        $s1 = $_POST['team1_score'] ?? ''; $s2 = $_POST['team2_score'] ?? '';
        if (!ctype_digit($s1) || !ctype_digit($s2)) $errors[] = 'Scores must be whole numbers (0 or more).';
        else {
            $w = winner_of($m['team1_id'], $m['team2_id'], (int)$s1, (int)$s2);
            $pdo->prepare("INSERT INTO match_results (match_id, team1_score, team2_score, winner_team_id) VALUES (?,?,?,?)
                           ON DUPLICATE KEY UPDATE team1_score=VALUES(team1_score), team2_score=VALUES(team2_score), winner_team_id=VALUES(winner_team_id)")
                ->execute([$id, (int)$s1, (int)$s2, $w]);
            $pdo->prepare("UPDATE matches SET status='completed' WHERE match_id=?")->execute([$id]);
            flash('Result saved. Leaderboard updated automatically.');
            redirect('matches.php');
        }
    } elseif ($id && $action === 'clear_result') {
        $pdo->prepare("DELETE FROM match_results WHERE match_id=?")->execute([$id]);
        $pdo->prepare("UPDATE matches SET status='scheduled' WHERE match_id=?")->execute([$id]);
        flash('Result cleared.');
        redirect('matches.php');
    }
}
$dateValue = $m['match_date'] ? date('Y-m-d\TH:i', strtotime($m['match_date'])) : '';
$pageTitle = $id ? 'Edit Match' : 'Schedule Match';
include 'includes/header.php';
?>
<?php show_errors($errors); ?>
<div class="row g-4 justify-content-center">
  <div class="col-lg-6">
    <div class="panel">
      <h3 class="mb-3"><?= $pageTitle ?></h3>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="save_match">
        <div class="mb-3"><label class="form-label">Tournament</label>
          <select name="tournament_id" id="tournament_id" class="form-select" required><option value="">-- choose --</option>
            <?php foreach ($tournaments as $t): ?><option value="<?= $t['tournament_id'] ?>" <?= $m['tournament_id'] == $t['tournament_id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
        <?php foreach ([1, 2] as $n): ?>
          <div class="mb-3"><label class="form-label">Team <?= $n ?></label>
            <select name="team<?= $n ?>_id" class="form-select team-select" required><option value="">-- choose --</option>
              <?php foreach ($entries as $en): ?><option value="<?= $en['team_id'] ?>" data-tournament="<?= $en['tournament_id'] ?>" <?= $m["team{$n}_id"] == $en['team_id'] && $m['tournament_id'] == $en['tournament_id'] ? 'selected' : '' ?>><?= e($en['team_name']) ?></option><?php endforeach; ?></select></div>
        <?php endforeach; ?>
        <div class="row">
          <div class="col-sm-7 mb-3"><label class="form-label">Date &amp; time</label><input type="datetime-local" name="match_date" class="form-control" value="<?= e($dateValue) ?>" required></div>
          <div class="col-sm-5 mb-3"><label class="form-label">Round</label><input name="round_name" class="form-control" value="<?= e($m['round_name']) ?>"></div>
        </div>
        <button class="btn btn-neon">Save match</button> <a href="matches.php" class="btn btn-outline-secondary">Cancel</a>
      </form>
    </div>
  </div>
  <?php if ($id): ?>
  <div class="col-lg-4">
    <div class="panel">
      <h5 class="mb-3">Match result</h5>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="save_result">
        <div class="mb-3"><label class="form-label">Team 1 score</label><input type="number" min="0" name="team1_score" class="form-control" value="<?= e($result['team1_score'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">Team 2 score</label><input type="number" min="0" name="team2_score" class="form-control" value="<?= e($result['team2_score'] ?? '') ?>" required></div>
        <button class="btn btn-neon">Save result</button>
      </form>
      <?php if ($result): ?>
        <form method="post" class="mt-2" data-confirm="Clear this result?"><?= csrf_field() ?><input type="hidden" name="action" value="clear_result"><button class="btn btn-sm btn-outline-danger">Clear result</button></form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
