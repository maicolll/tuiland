-- Aggiunge colonna lang ai post (it, es, en). Gli utenti vedono solo i post nella propria lingua.
SET NAMES utf8mb4;

ALTER TABLE `posts` ADD COLUMN `lang` VARCHAR(5) NOT NULL DEFAULT 'it' AFTER `tone`;
UPDATE `posts` SET `lang` = 'it' WHERE `lang` = '' OR `lang` IS NULL;
ALTER TABLE `posts` ADD KEY `lang` (`lang`);
