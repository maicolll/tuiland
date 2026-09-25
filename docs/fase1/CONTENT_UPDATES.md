# Delivery contenuti senza accesso MySQL produzione

Cursor Projects scrivono pacchetti in `content_updates/pending/*.json`.
Il server li importa via admin (**Content updates**) o `php cron/apply_content_updates.php`.

Dettagli: [`content_updates/README.md`](../../content_updates/README.md).

## Regole per gli agent

1. Mai connettersi al DB di produzione.
2. Ogni batch = un file JSON con `id` univoco + `ops` (posts / personality_updates / comments).
3. Nome file: `YYYYMMDD-<slug>.json` (solo `[a-zA-Z0-9._-]`).
4. Aprire PR; dopo merge/deploy un umano o cron applica.
5. Non usare pageview pubblico come trigger.
