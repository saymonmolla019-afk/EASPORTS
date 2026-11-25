<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header("Location: $url"); exit; }
function post($key) { return trim($_POST[$key] ?? ''); }

// One-time messages shown at the top of the next page
function flash($msg = null, $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = ['m' => $msg, 't' => $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// Login / roles
function is_logged_in() { return isset($_SESSION['user_id']); }
function is_admin() { return ($_SESSION['role'] ?? '') === 'admin'; }
function require_login() {
    if (!is_logged_in()) { flash('Please log in first.', 'warning'); redirect('login.php'); }
}
function require_admin() {
    require_login();
    if (!is_admin()) { flash('Admins only.', 'danger'); redirect('dashboard.php'); }
}
// Admin or the team owner may manage a team
function can_manage_team($team) {
    return $team && is_logged_in() && (is_admin() || $team['owner_id'] == $_SESSION['user_id']);
}

// CSRF protection for forms
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        die('Invalid form token. Go back, refresh the page and try again.');
    }
}

function status_badge($s) {
    $map = ['upcoming' => 'info', 'ongoing' => 'success', 'completed' => 'secondary', 'scheduled' => 'info'];
    return '<span class="badge text-bg-' . ($map[$s] ?? 'secondary') . '">' . e(ucfirst($s)) . '</span>';
}

// Winner of a match: team id, or null for a draw
function winner_of($t1, $t2, $s1, $s2) {
    if ($s1 > $s2) return $t1;
    if ($s2 > $s1) return $t2;
    return null;
}

/*
 * AUTOMATIC LEADERBOARD
 * Calculated from match_results every time (win = 3 pts, draw = 1, loss = 0).
 * Pass a tournament id for one tournament, or null for all tournaments.
 */
function leaderboard($pdo, $tid = null) {
    $stats = [];
    if ($tid) {
        $s = $pdo->prepare("SELECT t.team_id, t.team_name, t.tag FROM tournament_teams tt
                            JOIN teams t ON t.team_id = tt.team_id WHERE tt.tournament_id = ?");
        $s->execute([$tid]);
    } else {
        $s = $pdo->query("SELECT team_id, team_name, tag FROM teams");
    }
    foreach ($s as $r) {
        $stats[$r['team_id']] = ['team_id' => $r['team_id'], 'name' => $r['team_name'], 'tag' => $r['tag'],
            'p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
    }
    $sql = "SELECT m.team1_id, m.team2_id, r.team1_score, r.team2_score
            FROM matches m JOIN match_results r ON r.match_id = m.match_id";
    if ($tid) $sql .= " WHERE m.tournament_id = ?";
    $q = $pdo->prepare($sql);
    $q->execute($tid ? [$tid] : []);
    foreach ($q as $m) {
        if (!isset($stats[$m['team1_id']]) || !isset($stats[$m['team2_id']])) continue;
        $sides = [[$m['team1_id'], $m['team1_score'], $m['team2_score']],
                  [$m['team2_id'], $m['team2_score'], $m['team1_score']]];
        foreach ($sides as [$id, $gf, $ga]) {
            $stats[$id]['p']++;
            $stats[$id]['gf'] += $gf;
            $stats[$id]['ga'] += $ga;
            if ($gf > $ga)      { $stats[$id]['w']++; $stats[$id]['pts'] += 3; }
            elseif ($gf == $ga) { $stats[$id]['d']++; $stats[$id]['pts'] += 1; }
            else                { $stats[$id]['l']++; }
        }
    }
    if (!$tid) $stats = array_filter($stats, fn($t) => $t['p'] > 0);
    usort($stats, function ($a, $b) {
        return [$b['pts'], $b['gf'] - $b['ga'], $b['gf'], $a['name']]
           <=> [$a['pts'], $a['gf'] - $a['ga'], $a['gf'], $b['name']];
    });
    return $stats;
}

// Prints the leaderboard table (used on several pages)
function render_leaderboard($rows) {
    if (!$rows) { echo '<p class="text-muted mb-0">No results yet.</p>'; return; }
    echo '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr>
          <th>#</th><th>Team</th><th>P</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th>
          </tr></thead><tbody>';
    foreach ($rows as $i => $t) {
        $gd = $t['gf'] - $t['ga'];
        echo '<tr><td class="rank rank-' . ($i + 1) . '">' . ($i + 1) . '</td>
              <td><strong>' . e($t['name']) . '</strong> <span class="text-muted">[' . e($t['tag']) . ']</span></td>
              <td>' . $t['p'] . '</td><td>' . $t['w'] . '</td><td>' . $t['d'] . '</td><td>' . $t['l'] . '</td>
              <td>' . $t['gf'] . '</td><td>' . $t['ga'] . '</td><td>' . ($gd > 0 ? '+' : '') . $gd . '</td>
              <td class="pts">' . $t['pts'] . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function show_errors($errors) {
    foreach ($errors as $er) echo '<div class="alert alert-danger py-2">' . e($er) . '</div>';
}
