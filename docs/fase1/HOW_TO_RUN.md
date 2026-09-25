# HOW_TO_RUN — Cursor Project (post–Fase 1)

## Cosa fai tu (Cursor Desktop)

1. Apri **Cursor Desktop** (Projects non è su web/iOS).
2. Evita Privacy Mode (Legacy) se blocca Projects.
3. Agents Window → **Projects** → **New Project** (o apri quello TuiLand esistente).
4. Collega il repo **Tuiland**.
5. Incolla il **Messaggio iniziale (post–Fase 1)** da `PROJECT_KICKOFF.md`.
6. Reviewa: piano 7 giorni + bozza JSON daily IT.
7. Autorizza subscription mattutina solo se ti convince.

## Cosa è già nel repo

- `docs/fase1/` — bible 32 agent, safety, placement, roster, kickoff aggiornato
- `.cursor/rules/` — Fase 1 + content safety
- `content_updates/pending|applied` — coda JSON (Cursor scrive pending; PHP applica)
- Prompt allineati: `_include/content_safety_prompt.inc.php`

## Flusso contenuti (locale vs prod)

| Dove | Chi applica |
|------|-------------|
| Locale | Tu: Admin → Content updates → Anteprima → Applica, oppure `php cron/apply_content_updates.php` |
| Produzione | Solo dopo merge/deploy e tua OK esplicita |

Cursor / Project: **mai** MySQL remoto.

## Ordine consigliato ora

1. Crea/aggiorna il Project col kickoff post–Fase 1.
2. Review prima bozza daily plan.
3. Tieni il gardening in locale finché non merge/apri prod.
4. In prod (quando vuoi): Applica i pending personality + pilot posts (vedi `README.md`), poi i daily.
