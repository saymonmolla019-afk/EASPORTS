# EsportsHub – E-Sports Tournament and Team Management System

A simple, beginner-friendly PHP + MySQL project with a dark gaming theme.
Stack: PHP 8 (PDO), MySQL, HTML, CSS, JavaScript, Bootstrap 5 (CDN). Runs on XAMPP.

## Setup (XAMPP)
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy the `esports-system` folder into `C:\xampp\htdocs\`.
3. Open http://localhost/phpmyadmin → **Import** → choose `database.sql` → **Go**.
   (This creates the `esports_db` database, all 8 tables and sample data. Import it once on a fresh setup.)
4. Open **http://localhost/esports-system/**
5. If your MySQL has a password, edit `config/db.php`.

> Bootstrap loads from a CDN, so you need an internet connection for the styling.

## Demo accounts
| Role  | Email                  | Password |
|-------|------------------------|----------|
| Admin | admin@esports.local    | admin123 |
| User  | alpha@esports.local    | user123  |
| User  | bravo@esports.local    | user123  |

## Database (8 tables)
`users` → `teams` (owner_id) → `players` (team_id)
`games` → `tournaments` (game_id)
`tournament_teams` links tournaments ↔ teams (registrations, unique pair)
`matches` (tournament, team1, team2) → `match_results` (scores + winner, one per match)

All relationships use foreign keys; deleting a parent removes its children (CASCADE).
A draw is stored as `winner_team_id = NULL`.

## Features
- **Login / register** – hashed passwords, sessions, CSRF tokens, prepared statements.
- **Admin dashboard** – system counts, quick actions, matches waiting for results.
- **Users dashboard** – my teams and my tournament registrations.
- **Teams & players** – CRUD + search. Any user can create a team and add its players; only the owner or an admin can edit/delete.
- **Tournaments** – admin CRUD (with games); search/filter by status; team registration (upcoming only, max-team limit, no duplicates).
- **Matches** – admin schedules matches between registered teams, then enters the result.
- **Leaderboard** – calculated automatically from results (win 3, draw 1, loss 0; tie-break by score difference). Filter by tournament.
- **Search** on teams, players, tournaments, matches, games and users.

## Roles
- **admin** – can do everything (manage games, users, tournaments, matches, results).
- **user** – manages own teams/players and registers own teams.
To make another admin: log in as admin → **Users** → *Make admin*.

## Folder structure
```
config/db.php          database connection
includes/              header, footer, shared functions (auth, CSRF, leaderboard)
assets/css, assets/js  dark theme and small scripts
*.php                  one file per page
database.sql           tables + sample data
```

## Notes
- The ER diagram image was not readable in this session, so the 8 tables follow the project brief. The 8th table is `games`; adjust names in `database.sql` if your diagram differs.
- Passwords in the sample data are for demo only. Change them before any real use.
