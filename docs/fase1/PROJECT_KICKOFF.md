# Kickoff — incolla questo al coordinator del Cursor Project

Usa il testo sotto quando crei il Project in Cursor (Agents Window → Projects → nuovo Project sul repo Tuiland).

---

## Titolo Project suggerito

`TuiLand Fase 1 — Character Bible & Safety`

## Messaggio iniziale

```
Sei il coordinator del Project "TuiLand Fase 1".

Contesto obbligatorio (leggi tutto prima di delegare):
- docs/fase1/README.md
- docs/fase1/CHARACTER_BRIEF.md
- docs/fase1/SAFETY_RULES.md
- docs/fase1/PLACEMENT_FRAMEWORK.md
- docs/fase1/AGENT_TEMPLATE.md
- docs/fase1/ROSTER.md
- .cursor/rules/tuiland-fase1.mdc
- .cursor/rules/tuiland-content-safety.mdc

Obiettivo: rendere i 32 personaggi famosi, interessanti e unici; preparare slot product placement SENZA brand reali; rispettare safety hard.

Piano di lavoro:
1. Conferma di aver letto safety + template.
2. Produci PR piccole: prima 3 piloti (Neo, Cleo, Ben) come schede in docs/fase1/agents/*.md e aggiorna ROSTER.md.
3. Aspetta mia review sui 3 piloti. Non scalare agli altri 29 finché non dico "approved".
4. Dopo approvazione, batch da 4–8 agent in parallelo (subagent), una PR per batch, aggiorna personality/topics nel seed SQL solo dopo schede approved.
5. Se un output viola SAFETY_RULES.md, rigettalo e rifai.

Non inventare brand. Non contenuto NSFW. Non minori. Non odio. Non istruzioni pericolose.

Inizia con: (a) un piano in 5 bullet, (b) bozza delle 3 schede pilota in draft.
```

## Dopo il kickoff (tuoi passi)

1. Reviewa `SAFETY_RULES.md` e dimmi cosa stringere/allentare.
2. Reviewa le 3 schede pilota.
3. Scrivi al coordinator: `approved i piloti; procedi batch successivo`.
4. Opzionale: chiedi subscription “ogni mattina rivedi coerenza roster vs safety”.
