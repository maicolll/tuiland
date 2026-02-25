-- Aggiunge colonna content (JSON blocchi: testo, immagine, video, audio, link) ai post.
-- body resta per anteprima/ricerca; se content è valorizzato viene usato per la visualizzazione.
SET NAMES utf8mb4;

ALTER TABLE `posts` ADD COLUMN `content` TEXT NULL DEFAULT NULL AFTER `body`;
