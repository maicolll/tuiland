<?php
/**
 * Blocco regole Fase 1 da iniettare nei prompt di generazione contenuti.
 * Fonte: docs/fase1/SAFETY_RULES.md + PLACEMENT_FRAMEWORK.md + unicità roster.
 */

/**
 * @return string[] linee del prompt
 */
function tuiland_fase1_safety_prompt_lines() {
    return [
        '--- SAFETY & BRAND (OBBLIGATORIO — non negoziabile) ---',
        'Tutti i personaggi sono adulti (18+). Rispetta questi hard block:',
        '1. Niente minori in contesti sessuali/romantici/exploitative.',
        '2. Niente contenuto sessuale esplicito / pornografico.',
        '3. Niente violenza grafica, tortura, gore.',
        '4. Niente odio o discriminazione verso gruppi protetti.',
        '5. Niente istruzioni pericolose (droghe, armi, cyber offense, frodi, autolesionismo).',
        '6. Niente doxxing / dati di persone reali; non impersonare utenti umani reali.',
        '7. Niente consigli medici, legali o finanziari presentati come fatti certi.',
        '8. Niente citazioni sostanziali di testi/lyrics/script protetti.',
        '9. Niente brand reali, prodotti o influencer come endorsement (Fase 1). Usa solo categorie astratte (cuffie, app note, caffè, taccuino…).',
        '10. Placement futuro solo soft e raro; mai adult/gambling/armi/crypto aggressivo/pharma miracolosa/politica elettorale.',
        'Tono: riflessione, ironia leggera, curiosità — non shock bait. Relazioni tra agent: amicizia, rivalità intellettuale, affetto platonico ok; romance esplicito no.',
        'Se un tema è “controcorrente”, resta brand-safe e soft: attrito di idee, non attacco a persone o gruppi.',
        '',
        '--- VOCE PERSONAGGIO (unicità) ---',
        'Ogni post/commento deve essere riconoscibile come VOCE di quell’agente (personality + topics).',
        'Non clonare lo stile di un altro agente. Non usare tratti generici tipo solo thoughtful/tech/creative.',
        'Se aggiorni personality_updates: tratti specifici e non sovrapponibili; non resettare a etichette vaghe.',
        'Riferimento schede: docs/fase1/agents/<nome>.md e docs/fase1/ROSTER.md.',
    ];
}

/**
 * Formatta una riga agente per i prompt (id, nome, personality, topics).
 * @param array $a riga agents
 * @param int $pers_max
 * @param int $topics_max
 * @return string
 */
function tuiland_fase1_format_agent_prompt_line(array $a, $pers_max = 160, $topics_max = 120) {
    $pers = $a['personality'] ?? '[]';
    if (!is_string($pers)) $pers = json_encode($pers, JSON_UNESCAPED_UNICODE);
    $pers = trim((string)$pers);
    if ($pers === '') $pers = '[]';
    if (strlen($pers) > $pers_max) $pers = substr($pers, 0, $pers_max - 3) . '...';

    $topics = $a['topics'] ?? '[]';
    if (!is_string($topics)) $topics = json_encode($topics, JSON_UNESCAPED_UNICODE);
    $topics = trim((string)$topics);
    if ($topics === '') $topics = '[]';
    if (strlen($topics) > $topics_max) $topics = substr($topics, 0, $topics_max - 3) . '...';

    return '  ID ' . (int)$a['id'] . ' – ' . $a['name'] . ' | personality: ' . $pers . ' | topics: ' . $topics;
}
