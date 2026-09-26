# Content updates (Cursor → PHP → MySQL)

Cursor (e i Projects) **non** accedono al MySQL di produzione.
Scrivono pacchetti JSON in `pending/`; il server PHP li importa in modo controllato.

## Cartelle

| Path | Ruolo |
|------|--------|
| `pending/` | Pacchetti da applicare (versionati in git) |
| `applied/` | Spostati dopo apply riuscito (stato locale server; idempotenza anche su DB) |
| `failed/` | JSON invalido o apply fallito |
| `examples/` | Esempi di schema |

Accesso HTTP negato via `.htaccess`.

## Schema pacchetto

Vedi `examples/example-personality-update.json`. Campi:

- `id` (obbligatorio per idempotenza; se assente si usa il nome file)
- `schema_version` (attualmente `1`)
- `source` (es. `cursor-project-fase1`)
- `ops.posts` / `ops.personality_updates` / `ops.comments` — stesso contratto del piano admin

È accettato anche il JSON legacy del piano (ops in root senza wrapper).

## Come applicare (in produzione)

1. **Admin (consigliato Fase 1)**  
   `_admin567__` → **Content updates** → Applica tutti / Applica singolo.

2. **Cron**  
   `php cron/apply_content_updates.php`  
   Opzioni: `--dry-run`, `--force`, `--file=nome.json`

3. **Post-deploy**  
   Dopo `git pull`, lanciare lo stesso script cron.

Non applicare su pageview pubblico.

## Flusso tipico con Cursor Project

1. Project crea `content_updates/pending/YYYYMMDD-batch-….json` + PR  
2. Review umana + merge + deploy  
3. Admin o cron applica  
4. `content_update_log` registra l’`id` → riesecuzioni = skip  

## Bootstrap DB

```bash
php _install/run_schema_content_update_log.php
```

La tabella viene creata anche al primo apply.
