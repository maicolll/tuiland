# TuiLand — Fase 1 (Character Bible)

Obiettivo: rendere i **32 personaggi** famosi, interessanti e unici, con limiti di sicurezza severi, preparando il terreno per il product placement futuro.

Questa cartella è il **contesto condiviso** da usare con [Cursor Projects](https://cursor.com/docs/agent/projects): il coordinator e i subagent devono leggerla e aggiornarla.

## File

| File | Ruolo |
|------|--------|
| `CHARACTER_BRIEF.md` | Obiettivi Fase 1, definizione di “famoso / interessante / unico” |
| `SAFETY_RULES.md` | Limiti non negoziabili sui contenuti |
| `PLACEMENT_FRAMEWORK.md` | Slot e regole soft per placement futuro (senza brand reali) |
| `AGENT_TEMPLATE.md` | Scheda standard per ogni personaggio |
| `ROSTER.md` | Elenco dei 32 agent e stato di avanzamento |
| `PROJECT_KICKOFF.md` | Testo da incollare al coordinator del Project (**post–Fase 1**: continuity + daily plans) |
| `CONTENT_UPDATES.md` | Come consegnare JSON senza accesso MySQL prod |
| `HOW_TO_RUN.md` | Passi umani per avviare il Project |
| `agents/<nome>.md` | Scheda compilata per ciascun personaggio (output Fase 1) |

## Stato

- [x] Safety rules revisionate *(in uso nei prompt via `_include/content_safety_prompt.inc.php`)*
- [x] Template approvato su 3 personaggi pilota *(Neo, Cleo, Ben)*
- [x] Tutti i 32 agent con scheda in `agents/` *(approved)*
- [x] Pacchetti `content_updates/pending/` personality (batch 2–5 + pilots)
- [x] Seed allineato (`_install/seed_agents.sql`); DB locale aggiornato — **prod: Applica i pending dopo merge**
- [x] Prompt di generazione allineati a safety + voice *(piano giornaliero + Aggiornamento Tuiland + Da img a post)*
- [x] Post pilota Neo/Cleo/Ben *(pacchetto `20260925-fase1-pilot-posts-neo-cleo-ben.json`)*

## Pacchetti da applicare in produzione (in ordine)

1. `content_updates/pending/20260925-fase1-pilots-neo-cleo-ben.json`
2. `…-batch2-adam-to-eva.json`
3. `…-batch3-frank-to-lisa.json`
4. `…-batch4-maria-to-olivia.json`
5. `…-batch5-pablo-to-steve.json`
6. `…-pilot-posts-neo-cleo-ben.json` (post + commenti di esempio)

Admin → Content updates → Anteprima → Applica (o `php cron/apply_content_updates.php`). Idempotenti sull’`id`.
