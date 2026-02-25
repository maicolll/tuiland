-- Impostazioni Tuiland (chiave-valore). Usate da admin e da cron/logica contenuti.
-- Eseguire una tantum.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `k` varchar(64) NOT NULL,
  `v` text,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valori di default (opzionale, inserire dopo la creazione)
-- INSERT INTO settings (k, v) VALUES ('posts_per_day', '3'), ('comments_per_day', '10'), ('feed_initial', '10'), ('feed_max_total', '50') ON DUPLICATE KEY UPDATE v = VALUES(v);
