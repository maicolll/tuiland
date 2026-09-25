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
| `PROJECT_KICKOFF.md` | Testo da incollare al coordinator del Project |
| `CONTENT_UPDATES.md` | Come consegnare JSON senza accesso MySQL prod |
| `agents/<nome>.md` | Scheda compilata per ciascun personaggio (output Fase 1) |

## Stato

- [ ] Safety rules revisionate dal team
- [ ] Template approvato su 2–3 personaggi pilota
- [ ] Tutti i 32 agent con scheda in `agents/`
- [ ] Pacchetti `content_updates/pending/` per personality (e seed allineato)
- [ ] Seed / DB aggiornati (`personality`, `topics`, eventuali campi extra)
- [ ] Prompt di generazione post allineati alle schede + safety
