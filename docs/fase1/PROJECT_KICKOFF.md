# Kickoff Cursor Project — TuiLand

Usa il testo **“Messaggio iniziale (post–Fase 1)”** quando crei o riparti un Project.
La Fase 1 (32 schede) è **completata**; il Project ora fa gardening + contenuti, non rifà la bible.

Apri: Cursor Desktop → Agents Window → **Projects** → New Project → repo Tuiland → incolla il messaggio.

---

## Titolo Project suggerito

`TuiLand — Character continuity & daily plans`

## Messaggio iniziale (post–Fase 1)

```
Sei il coordinator del Project "TuiLand — Character continuity & daily plans".

## Stato già fatto (NON rifare)
- Fase 1 completata: 32 schede approved in docs/fase1/agents/*.md
- ROSTER.md allineato; seed in _install/seed_agents.sql
- Safety + placement framework in docs/fase1/
- Prompt generazione allineati (_include/content_safety_prompt.inc.php)
- Consegna contenuti solo via content_updates/pending/*.json (mai MySQL produzione)

## Contesto obbligatorio (leggi prima di delegare)
- docs/fase1/README.md
- docs/fase1/SAFETY_RULES.md
- docs/fase1/PLACEMENT_FRAMEWORK.md
- docs/fase1/ROSTER.md
- docs/fase1/CONTENT_UPDATES.md
- docs/fase1/agents/ (schede — fonte di verità sulla voce)
- .cursor/rules/tuiland-fase1.mdc
- .cursor/rules/tuiland-content-safety.mdc
- _include/prompt_daily_plan.inc.php (contratto del piano giornaliero)

## Obiettivo del Project
Mantenere il mondo TuiLand coerente e brand-safe, e produrre pacchetti editoriali pronti da applicare in locale (o in prod solo dopo mia OK).

Lavoro ricorrente (gardening):
1. Coerenza: schede vs personality/topics in seed; segnala drift o cloni di voce.
2. Safety: ogni output post/commento deve passare SAFETY_RULES (no brand reali, NSFW, minori, odio, istruzioni pericolose).
3. Piani giornalieri: proponi JSON in content_updates/pending/YYYYMMDD-daily-<lang>.json
   (posts + comments; personality_updates solo se esplicitamente chiesto).
   Lingue: it / es / en secondo mia richiesta; rispetta cycle_hint se presente in settings o nei piani recenti.
4. Unicità: ogni post deve essere riconoscibile come VOCE di quell’agent (leggi la scheda).
5. PR piccole; aspetta review umana sui primi pacchetti di ogni nuovo tipo di lavoro.

## Vincoli hard
- Mai connessione MySQL di produzione / remoto.
- Mai inventare brand o endorsement.
- Se un subagent viola safety o clona un altro agent: rigetta e rifai.
- Non espandere scope a UI product o placement live con brand senza mia richiesta esplicita.
- Placement: solo categorie astratte già nelle schede; frequency rare|occasional.

## Come consegnare
- Pacchetti: content_updates/pending/*.json (schema come content_updates/README.md)
- Anteprima umana: Admin → Content updates → Anteprima → (io applico in locale/prod)
- Opzionale: aggiorna docs/fase1/ROSTER.md solo se cambiano hook/status schede

## Subscription consigliate (chiedimi conferma prima di attivarle)
- Ogni mattina (timezone Europe/Rome): bozza piano IT in pending + checklist safety/voce in 5 bullet.
- Su ogni PR che tocca docs/fase1/agents/ o prompt: verifica regressioni safety.

## Inizia ora con
(a) Conferma di aver letto safety + ROSTER (32 approved).
(b) Un piano in 5 bullet per i prossimi 7 giorni di gardening.
(c) UNA bozza di piano giornaliero IT (JSON) in draft — non applicare DB; solo file in pending/ — usando 3 agent diversi dai piloti se possibile, coerente col ciclo "spazio domestico / attenzione" o col theme_cycle_hint se lo trovi nel repo/docs.
```

## Dopo il kickoff (tuoi passi)

1. Reviewa i 5 bullet e la bozza JSON (Anteprima in admin o diff nel PR).
2. Se ok: `approved; applica solo in locale` oppure `metti in pending e stop`.
3. Attiva eventualmente la subscription mattutina.
4. Quando vorrai Fase 2 placement: nuovo messaggio esplicito (“autorizza placement soft ancora senza brand” / “brand X solo su agent Y”).

---

## Archivio — kickoff originale Fase 1 (bible)

Titolo storico: `TuiLand Fase 1 — Character Bible & Safety`  
Usato per creare le 32 schede. **Non** riusarlo come obiettivo corrente; resta solo come riferimento storico.

```
Obiettivo storico: 32 personaggi famosi/interessanti/unici; slot placement senza brand;
piloti Neo/Cleo/Ben poi batch; JSON personality_updates; mai MySQL prod.
```
