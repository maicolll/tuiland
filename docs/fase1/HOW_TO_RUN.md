# HOW_TO_RUN — passi umani vs agent

## Cosa puoi fare solo tu (Cursor Desktop)

1. Apri **Cursor Desktop** (Projects non è su web/iOS).
2. Verifica di **non** essere su Privacy Mode (Legacy) né piano Enterprise-only senza Projects.
3. Apri **Agents Window** → nella nav sinistra **Projects** → **New Project**.
4. Collega il repo **Tuiland** (questo).
5. Incolla il messaggio in `PROJECT_KICKOFF.md`.
6. Reviewa i primi output; rispondi al coordinator con correzioni (soprattutto safety e unicità).
7. Quando i piloti vanno bene: autorizza i batch successivi.

## Cosa ho preparato io nel repo (questo PR)

- Cartella `docs/fase1/` con brief, safety, placement, template, roster, kickoff.
- Regole Cursor in `.cursor/rules/` (Fase 1 + safety always-on).
- Istruzioni per far partire il Project senza reinventare il contesto.

## Cosa posso fare io in seguito (Cloud Agent / chat)

Su tua richiesta esplicita:

- Scrivere le 3 schede pilota (Neo, Cleo, Ben) e poi i batch.
- Aggiornare `_install/seed_agents.sql` con `personality`/`topics` dalle schede approved.
- Allineare i prompt in `_admin567__/inc/prompt_cont.php` alle safety + voice.
- Aprire PR per ogni batch.

## Ordine consigliato oggi

1. Merge di questo PR di scaffolding (o lavora sul branch).
2. Tu: crea il Project e incolla il kickoff **oppure** dimmi “scrivi i 3 piloti qui” e li faccio io senza Project UI.
3. Tu: stringi `SAFETY_RULES.md` se hai limiti più severi del draft.
4. Poi scala ai 32.
