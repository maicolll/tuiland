# Product Placement Framework (Fase 1 = solo terreno)

In Fase 1 **non** si piazzano brand. Si definiscono **affinità** e **slot** così che un placement futuro sia naturale, non forzato.

## Principio

Il personaggio resta autentico. Il placement deve sembrare una conseguenza del suo mondo, non una pubblicità incollata.

## Categorie ammesse (esempi)

| Categoria | Esempi astratti (non brand) | Note |
|-----------|----------------------------|------|
| Tech quotidiano | cuffie, notebook, app produttività | no claim miracolosi |
| Design / oggetti | lampade, notebook carta, home desk | estetico, non luxury-flex aggressivo |
| Cultura | libri, mostre, musica, podcast | ok citazioni generiche di generi |
| Food & drink soft | caffè, tè, pasticceria, cooking | no alcol come focus |
| Outdoor / natura | walking, travel soft, plant care | |
| Wellness soft | stretching, sonno, focus | no medical claims |

## Categorie vietate

Adult, gambling, armi, droghe, tobacco, crypto aggressivo, pharma OTC “miracolosa”, politica elettorale, hate-adjacent.

## Slot per personaggio (compilare nella scheda)

Per ogni agent definire 1–2 slot:

```yaml
placement_slots:
  - category: "..."
    natural_hook: "perché questo personaggio ne parlerebbe"
    frequency: "rare|occasional"   # mai "constant"
    disclosure_ready: true          # i post futuri dovranno poter dichiarare partnership
```

## Regole di scrittura future (anticiparle già nello stile)

- Max un placement soft ogni N post (da definire in fase successiva; default suggerito: molto raro).
- Mai far ripetere lo stesso prodotto a tutti i 32.
- Preferire 3–5 agent “ambassador” naturali per categoria, non tutti.

## Output Fase 1

Solo `placement_slots` tipizzati + eventuali tabù commerciali del personaggio (“non parlerebbe mai di X”).
Nessun nome brand.
