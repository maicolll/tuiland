# HOW_TO_RUN — passi umani vs agent

## Cosa puoi fare solo tu (Cursor Desktop)

1. Apri **Cursor Desktop** (Projects non è su web/iOS).
2. Verifica di **non** essere su Privacy Mode (Legacy) né piano Enterprise-only senza Projects.
3. Apri **Agents Window** → nella nav sinistra **Projects** → **New Project**.
4. Collega il repo **Tuiland** (questo).
5. Incolla il messaggio in `PROJECT_KICKOFF.md`.
6. Reviewa i primi output; rispondi al coordinator con correzioni (soprattutto safety e unicità).
7. Quando i piloti vanno bene: autorizza i batch successivi.

## Cosa ho preparato io nel repo

- Cartella `docs/fase1/` con brief, safety, placement, template, roster, kickoff.
- Regole Cursor in `.cursor/rules/` (Fase 1 + safety always-on).
- Coda `content_updates/` + importer admin/cron (nessun accesso MySQL da Cursor).
- Istruzioni per far partire il Project senza reinventare il contesto.

## Cosa posso fare io in seguito (Cloud Agent / chat)

Su tua richiesta esplicita:

- Scrivere le 3 schede pilota (Neo, Cleo, Ben) e poi i batch.
- Creare JSON in `content_updates/pending/` + aggiornare seed SQL dalle schede approved.
- Allineare i prompt in `_admin567__/inc/prompt_cont.php` alle safety + voice.
- Aprire PR per ogni batch.

## Ordine consigliato oggi

1. Merge di questo PR di scaffolding.
2. In produzione: `php _install/run_schema_content_update_log.php` (o primo apply admin).
3. Tu: crea il Project e incolla il kickoff **oppure** dimmi “scrivi i 3 piloti qui”.
4. Tu: stringi `SAFETY_RULES.md` se hai limiti più severi del draft.
5. Dopo approve schede → JSON pending → deploy → Admin **Content updates** → Applica.
