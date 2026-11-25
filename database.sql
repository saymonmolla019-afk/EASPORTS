-- E-Sports Tournament and Team Management System
-- Import once in phpMyAdmin (Import tab) on a fresh server.
CREATE DATABASE IF NOT EXISTS esports_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE esports_db;

-- 1. USERS
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. GAMES
CREATE TABLE IF NOT EXISTS games (
  game_id INT AUTO_INCREMENT PRIMARY KEY,
  game_name VARCHAR(60) NOT NULL UNIQUE,
  genre VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- 3. TEAMS (owner = user who created the team)
CREATE TABLE IF NOT EXISTS teams (
  team_id INT AUTO_INCREMENT PRIMARY KEY,
  team_name VARCHAR(60) NOT NULL UNIQUE,
  tag VARCHAR(6) NOT NULL,
  country VARCHAR(50) NOT NULL,
  owner_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. PLAYERS (belong to a team; user_id optional link to an account)
CREATE TABLE IF NOT EXISTS players (
  player_id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  user_id INT NULL,
  gamer_tag VARCHAR(40) NOT NULL,
  full_name VARCHAR(80) NOT NULL,
  role_in_team VARCHAR(40) NOT NULL,
  country VARCHAR(50) NOT NULL,
  FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. TOURNAMENTS
CREATE TABLE IF NOT EXISTS tournaments (
  tournament_id INT AUTO_INCREMENT PRIMARY KEY,
  game_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  prize_pool DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_teams INT NOT NULL DEFAULT 8,
  status ENUM('upcoming','ongoing','completed') NOT NULL DEFAULT 'upcoming',
  FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. TOURNAMENT_TEAMS (team registrations)
CREATE TABLE IF NOT EXISTS tournament_teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  team_id INT NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tournament_team (tournament_id, team_id),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE,
  FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. MATCHES
CREATE TABLE IF NOT EXISTS matches (
  match_id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  team1_id INT NOT NULL,
  team2_id INT NOT NULL,
  match_date DATETIME NOT NULL,
  round_name VARCHAR(40) NOT NULL DEFAULT 'Group Stage',
  status ENUM('scheduled','completed') NOT NULL DEFAULT 'scheduled',
  FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE,
  FOREIGN KEY (team1_id) REFERENCES teams(team_id) ON DELETE CASCADE,
  FOREIGN KEY (team2_id) REFERENCES teams(team_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. MATCH_RESULTS (one result per match; winner NULL = draw)
CREATE TABLE IF NOT EXISTS match_results (
  result_id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NOT NULL UNIQUE,
  team1_score INT NOT NULL DEFAULT 0,
  team2_score INT NOT NULL DEFAULT 0,
  winner_team_id INT NULL,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (match_id) REFERENCES matches(match_id) ON DELETE CASCADE,
  FOREIGN KEY (winner_team_id) REFERENCES teams(team_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ================= SAMPLE DATA =================
-- admin@esports.local / admin123   |   other users / user123
INSERT INTO users (user_id, username, email, password, role) VALUES
(1,'admin','admin@esports.local','$2y$12$.mnTaw47DJqvIwHBghDwueWXBA8LQ5/yT.UHtvhARkjtDPd7wgYqC','admin'),
(2,'alpha_lead','alpha@esports.local','$2y$12$yEDhPyQl.o8rIX5FEST.R.U.7dUUW48D5n.6lTPP3EDpj7TwLBQS2','user'),
(3,'bravo_lead','bravo@esports.local','$2y$12$yEDhPyQl.o8rIX5FEST.R.U.7dUUW48D5n.6lTPP3EDpj7TwLBQS2','user'),
(4,'charlie_lead','charlie@esports.local','$2y$12$yEDhPyQl.o8rIX5FEST.R.U.7dUUW48D5n.6lTPP3EDpj7TwLBQS2','user'),
(5,'delta_lead','delta@esports.local','$2y$12$yEDhPyQl.o8rIX5FEST.R.U.7dUUW48D5n.6lTPP3EDpj7TwLBQS2','user');

INSERT INTO games (game_id, game_name, genre) VALUES
(1,'Valorant','FPS'),(2,'League of Legends','MOBA'),(3,'Counter-Strike 2','FPS'),(4,'Rocket League','Sports');

INSERT INTO teams (team_id, team_name, tag, country, owner_id) VALUES
(1,'Phoenix Rising','PHX','Bangladesh',2),
(2,'Shadow Wolves','SWF','India',3),
(3,'Neon Vipers','NVP','Japan',4),
(4,'Iron Titans','ITN','Germany',5),
(5,'Storm Breakers','STB','Brazil',2),
(6,'Crimson Dragons','CRD','South Korea',3);

INSERT INTO players (team_id, user_id, gamer_tag, full_name, role_in_team, country) VALUES
(1,2,'AlphaX','Rahim Uddin','Captain','Bangladesh'),
(1,NULL,'BlazeKid','Tanvir Hasan','Duelist','Bangladesh'),
(1,NULL,'Mystic','Nabila Rahman','Support','Bangladesh'),
(2,3,'WolfPack','Arjun Mehta','Captain','India'),
(2,NULL,'Fang','Rohan Das','Sniper','India'),
(2,NULL,'Howl','Priya Nair','Support','India'),
(3,4,'ViperOne','Kenji Sato','Captain','Japan'),
(3,NULL,'NeonBlade','Yuki Tanaka','Duelist','Japan'),
(3,NULL,'Ghost','Haru Mori','Controller','Japan'),
(4,5,'TitanZ','Lukas Weber','Captain','Germany'),
(4,NULL,'IronFist','Jonas Klein','Tank','Germany'),
(4,NULL,'Steel','Emma Fischer','Support','Germany'),
(5,NULL,'Tempest','Lucas Silva','Captain','Brazil'),
(5,NULL,'Bolt','Ana Costa','Rifler','Brazil'),
(5,NULL,'Gale','Pedro Alves','Support','Brazil'),
(6,NULL,'DragonEye','Min-jun Kim','Captain','South Korea'),
(6,NULL,'Ember','Seo-yeon Park','Rifler','South Korea'),
(6,NULL,'Scorch','Ji-ho Lee','Support','South Korea');

INSERT INTO tournaments (tournament_id, game_id, name, start_date, end_date, prize_pool, max_teams, status) VALUES
(1,1,'Winter Valorant Cup','2026-11-01','2026-11-20',5000.00,8,'ongoing'),
(2,3,'CS2 Showdown','2026-12-05','2026-12-15',3000.00,4,'upcoming'),
(3,4,'Rocket League Open','2026-10-01','2026-10-10',1500.00,8,'completed');

INSERT INTO tournament_teams (tournament_id, team_id) VALUES
(1,1),(1,2),(1,3),(1,4),
(2,5),(2,6),
(3,4),(3,5),(3,6);

INSERT INTO matches (match_id, tournament_id, team1_id, team2_id, match_date, round_name, status) VALUES
(1,1,1,2,'2026-11-02 18:00:00','Group Stage','completed'),
(2,1,3,4,'2026-11-02 20:00:00','Group Stage','completed'),
(3,1,1,3,'2026-11-05 18:00:00','Group Stage','completed'),
(4,1,2,4,'2026-11-06 18:00:00','Group Stage','completed'),
(5,1,1,4,'2026-11-14 18:00:00','Group Stage','scheduled'),
(6,1,2,3,'2026-11-15 18:00:00','Group Stage','scheduled'),
(7,3,4,5,'2026-10-02 17:00:00','Group Stage','completed'),
(8,3,5,6,'2026-10-04 17:00:00','Group Stage','completed'),
(9,3,4,6,'2026-10-06 17:00:00','Group Stage','completed');

INSERT INTO match_results (match_id, team1_score, team2_score, winner_team_id) VALUES
(1,2,1,1),(2,0,2,4),(3,1,1,NULL),(4,2,0,2),
(7,3,1,4),(8,2,3,6),(9,1,3,6);
