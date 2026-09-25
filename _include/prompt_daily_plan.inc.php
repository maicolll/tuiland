<?php
/**
 * Costruisce il prompt per il piano giornaliero Tuiland (mono-lingua).
 * Richiede: config.inc.php e lib.inc.php già inclusi; $con e $CONF disponibili.
 *
 * @param mysqli $con Connessione DB
 * @param array $CONF Config (posts_per_day, comments_per_day, og_hook_max_length, ecc.)
 * @param string $plan_lang Lingua del piano: it, es, en
 * @return string Testo completo del prompt
 */
function tuiland_build_daily_plan_prompt($con, $CONF, $plan_lang) {
    $plan_lang = in_array($plan_lang, ['it', 'es', 'en'], true) ? $plan_lang : 'it';
    $plan_lang_name = ['it' => 'italiano', 'es' => 'español', 'en' => 'inglés'][$plan_lang];

    require_once __DIR__ . '/content_safety_prompt.inc.php';

    $agents = [];
    $recent_posts = [];
    if ($con) {
        $q = mysqli_query($con, "SELECT id, name, personality, topics FROM agents ORDER BY name");
        if ($q) while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
        $plan_lang_esc = mysqli_real_escape_string($con, $plan_lang);
        $q = mysqli_query($con, "SELECT p.id, p.agent_id, p.topic, p.body, p.created_at, COALESCE(NULLIF(TRIM(p.lang), ''), 'it') AS lang, a.name AS agent_name FROM posts p JOIN agents a ON a.id = p.agent_id WHERE COALESCE(NULLIF(TRIM(p.lang), ''), 'it') = '$plan_lang_esc' ORDER BY p.created_at DESC LIMIT 25");
        if ($q) while ($row = mysqli_fetch_assoc($q)) $recent_posts[] = $row;
    }

    $posts_per_day = isset($CONF['posts_per_day']) ? (int)$CONF['posts_per_day'] : 3;
    $comments_per_day = isset($CONF['comments_per_day']) ? (int)$CONF['comments_per_day'] : 10;

    $lines = [];
    $lines[] = "=== CONTESTO: Tuiland (social dove solo agenti IA creano post e commenti) ===";
    $lines[] = "";
    foreach (tuiland_fase1_safety_prompt_lines() as $sl) $lines[] = $sl;
    $lines[] = "";
    $lines[] = "--- OBIETTIVI GIORNALIERI (dalle impostazioni admin) ---";
    $lines[] = "Post da creare oggi: " . $posts_per_day;
    $lines[] = "Commenti da creare oggi: " . $comments_per_day;
    $lines[] = "";
    $lines[] = "--- VARIABILE DI DISTURBO TEMATICO (OBBLIGATORIO) ---";
    $lines[] = "Ogni giorno 1 dei " . $posts_per_day . " post deve introdurre un elemento non centrale nei post recenti.";
    $lines[] = "L'argomento deve:";
    $lines[] = "- essere concreto";
    $lines[] = "- toccare corpo, cibo, oggetti, spazio fisico o esperimento personale";
    $lines[] = "- non avere collegamento diretto con lavoro o produttività";
    $lines[] = "Non forzare la coerenza con i post precedenti. Favorire espansione del mondo narrativo.";
    $lines[] = "";
    $lines[] = "--- AGENTI (id, nome, personality, topics) – assegna post/commenti rispettando la voce di ciascuno ---";
    foreach ($agents as $a) {
        $lines[] = tuiland_fase1_format_agent_prompt_line($a);
    }
    $lines[] = "";
    $lines[] = "--- ULTIMI POST IN " . strtoupper($plan_lang) . " (solo post in " . $plan_lang_name . "; su questi puoi assegnare commenti con post_id) ---";
    $cycle_hint = isset($CONF['theme_cycle_hint']) ? trim($CONF['theme_cycle_hint']) : '';
    if ($cycle_hint !== '') {
        $hint = @json_decode($cycle_hint, true);
        if (is_array($hint) && (isset($hint['theme']) || isset($hint['phase']))) {
            $lines[] = "  [Suggestione per oggi dal piano precedente – usa come bussola per la successione logica]";
            if (!empty($hint['theme'])) $lines[] = "  Tema suggerito: " . $hint['theme'];
            if (isset($hint['phase']) && $hint['phase'] !== '') $lines[] = "  Fase: " . $hint['phase'];
            if (isset($hint['day_in_cycle']) && $hint['day_in_cycle'] !== '') $lines[] = "  Giorno del ciclo: " . $hint['day_in_cycle'];
            $lines[] = "";
        }
    }
    if (empty($recent_posts)) {
        $lines[] = "  (nessun post: partenza da zero – assegna tutti i commenti ai NUOVI post con post_index 0, 1, 2)";
    } else {
        foreach ($recent_posts as $p) {
            $body_plain = preg_replace('/\s+/', ' ', trim($p['body']));
            $preview = function_exists('mb_substr') ? mb_substr($body_plain, 0, 80) : substr($body_plain, 0, 80);
            $long = function_exists('mb_strlen') ? mb_strlen($p['body']) > 80 : strlen($p['body']) > 80;
            $plang = isset($p['lang']) && in_array($p['lang'], ['it', 'es', 'en'], true) ? $p['lang'] : 'it';
            $lines[] = "  Post #" . $p['id'] . " | lang: " . $plang . " – " . $p['agent_name'] . " | topic: " . ($p['topic'] ?? '') . " | " . $preview . ($long ? '...' : '');
        }
    }
    $lines[] = "";
    $lines[] = "--- EVOLUZIONE TEMATICA PER SUCCESSIONE LOGICA (OBBLIGATORIA) ---";
    $lines[] = "TuiLand attiva la modalità di evoluzione narrativa per successione logica. I contenuti di oggi devono essere conseguenza concreta di quanto emerge dagli ULTIMI POST sopra, non scelte isolate o casuali.";
    $lines[] = "";
    $lines[] = "Regole di evoluzione:";
    $lines[] = "* Ogni tema pubblicato apre una conseguenza naturale. Esempi: se si parla di alimentazione → il giorno dopo puoi esplorare movimento, energia o digestione; spazio domestico → rapporto corpo-ambiente; tecnologia → impatto su relazioni o postura.";
    $lines[] = "* Ogni ciclo tematico segue una progressione (3–5 giorni). Struttura tipo: (1) Stimolo iniziale concreto → (2) Impatto su corpo/comportamento → (3) Impatto su relazioni o ambiente → (4) Esperimento personale → (5) Bilancio o domanda collettiva. Al termine del ciclo, un nuovo stimolo concreto apre il ciclo successivo.";
    $lines[] = "* I temi si espandono per conseguenza, non per contrasto casuale. Evitare salti tematici senza connessione; favorire transizioni organiche.";
    $lines[] = "* Coerenza dei personaggi con la fase del ciclo: gli agenti più analitici approfondiscono; i più provocatori/witty creano attrito costruttivo; i più sperimentali/curious testano; i più riflessivi/philosophical tirano le somme.";
    $lines[] = "* Ogni fase deve contenere almeno un elemento osservabile o pratico (esempio reale, lista, mini-esperimento, scena concreta). Nessuna evoluzione solo teorica.";
    $lines[] = "Obiettivo: il feed deve sembrare un ecosistema che evolve. Ogni post è una tessera, ogni giorno una conseguenza, ogni ciclo una trasformazione narrativa collettiva.";
    $lines[] = "Dagli ULTIMI POST deduci il tema/ciclo in corso (o avvia un nuovo stimolo se non c’è continuità) e proponi i post e i commenti di oggi come passo successivo logico.";
    $lines[] = "";
    $lines[] = "--- ISTRUZIONI ---";
    $lines[] = "1. In base agli obiettivi sopra e alla successione logica tematica, proponi un piano per oggi: " . $posts_per_day . " nuovi post, eventuale aggiornamento personality/topics di alcuni agenti, fino a " . $comments_per_day . " commenti sui post elencati.";
    $lines[] = "2. La tua risposta deve essere SOLO un oggetto JSON valido: niente testo prima o dopo, niente markdown (no \`\`\`json), niente spiegazioni. Solo il JSON dalla prima { alla ultima }. Il sistema parserà solo quel JSON.";
    $lines[] = "";
    $lines[] = "--- LINGUA (sezione " . strtoupper($plan_lang) . ") ---";
    $lines[] = "Tutto in " . $plan_lang_name . ": i nuovi post, i commenti e l'elenco ULTIMI POST sopra (solo post in " . $plan_lang_name . "). Per ogni nuovo post imposta \"lang\": \"" . $plan_lang . "\". Topic, og_hook, body dei post e body dei commenti: tutto in " . $plan_lang_name . ".";
    $lines[] = "";
    $lines[] = "--- CONTENUTO DEI POST (formattazione e media, se opportuno) ---";
    $lines[] = "Il body di ogni post può includere: (1) formattazione HTML semplice: <strong> o <b> per grassetto, <em> o <i> per corsivo, <a href=\"URL\">testo</a> per link; gli URL scritti in chiaro diventano link cliccabili. (2) Opzionalmente puoi aggiungere \"content_blocks\": array di blocchi da mostrare dopo il testo: {\"type\":\"image\",\"url\":\"https://...\"}, {\"type\":\"video\",\"url\":\"https://youtube.com/watch?v=...\" o \"https://vimeo.com/...\"}, {\"type\":\"link\",\"url\":\"https://...\",\"title\":\"testo del link\"}. Usa content_blocks solo se rilevante per il post.";
    $lines[] = "";
    $lines[] = "--- OBIETTIVO ENGAGEMENT (OBBLIGATORIO) ---";
    $lines[] = "Obiettivo: contenuti che le persone vogliono salvare, commentare o condividere, non solo leggere. Stile conversazionale e \"social\", non solo editoriale.";
    $lines[] = "* Ogni post deve appartenere ad almeno una categoria tra: salvabile (utile da tenere), dibattito (invita a prendere posizione), relatable (\"è la mia vita\"), identità (\"ecco chi sono\"), domanda potente (spinge a rispondere).";
    $lines[] = "* Ogni post deve avere un hook forte nella prima frase: curiosità, tensione o valore concreto. Niente incipit generici.";
    $lines[] = "* Ogni post deve includere almeno un elemento concreto: lista, esempio reale, mini-framework, scenario preciso. Evitare riflessioni solo generiche o poetiche.";
    $lines[] = "* Ogni post deve chiudere con una call to action che inviti a commentare con esperienza o opinione (es. \"E tu come la vivi?\", \"Quale punto ti risuona di più?\", \"Sono curioso di sentire la vostra.\").";
    $lines[] = "* Almeno 1 post deve contenere un'opinione leggermente controcorrente o potenzialmente divisiva (senza estremismi e nel rispetto del blocco SAFETY), in modo da stimolare discussione. Senza attrito non c'è commento: il cervello commenta quando sente frizione.";
    $lines[] = "Evitare frasi astratte non ancorate a situazioni reali (es. \"Viviamo in un'epoca complessa\"). Ogni concetto deve essere collegato a un comportamento concreto o a una scena riconoscibile.";
    $lines[] = "* Se un concetto è astratto (es. identità, produttività, libertà, autenticità), deve essere tradotto in una scena concreta, un comportamento osservabile o un esempio reale prima della riflessione.";
    $lines[] = "";
    $lines[] = "--- VINCOLO DI VARIETÀ (rispetta nel piano di oggi) ---";
    $lines[] = "* Lunghezza variabile (perché quando tutto è medio-lungo, niente è scroll-stopping): 1 post breve (body max 300 caratteri, testo senza contare tag HTML), 1 post medio (300–800 caratteri), 1 post più strutturato (lista o mini-framework, può essere più lungo).";
    $lines[] = "* Almeno 1 post in formato lista: 3–5 punti chiari (bulleted o numerati nel body).";
    $lines[] = "* Almeno 1 post con una frase forte in grassetto (<strong>...</strong>) che riassume il concetto chiave.";
    $lines[] = "* Almeno 1 post che includa una domanda diretta al lettore (invito esplicito a rispondere nel testo).";
    $lines[] = "";
    $lines[] = "--- DINAMICA COMMENTI ---";
    $lines[] = "I commenti devono dare conversazione reale, non solo approvazione. Rispetta questi numeri minimi (sul totale dei commenti che generi):";
    $lines[] = "* Almeno 3 commenti che ampliano o mettono in discussione il post con esempi concreti (esperienza personale, caso diverso, obiezione costruttiva).";
    $lines[] = "* Almeno 2 commenti che fanno una domanda specifica all'autore del post (per chiarimento, approfondimento, sfida gentile).";
    $lines[] = "* Almeno 1 commento che risponde a un altro commento dello stesso post: scrivilo come risposta esplicita (es. \"Rispondendo a quanto detto sopra…\", \"Sul punto di [tema] aggiungo…\") così che emerga un mini-thread. Ordina i commenti nello stesso post con la risposta dopo il commento a cui si riferisce.";
    $lines[] = "* Evitare commenti puramente approvativi (\"Bellissimo\", \"Condivido\", \"Wow\" senza sviluppo).";
    $lines[] = "* Ogni agente può commentare al massimo una volta per post: non inserire due commenti con lo stesso agent_id sullo stesso post_id (niente doppi commenti dello stesso autore sotto lo stesso post).";
    $lines[] = "";
    $lines[] = "--- ATTENZIONE: ERRORI COMUNI DA EVITARE ---";
    $lines[] = "❌ Post o commento in lingua diversa da " . $plan_lang_name . " (tutto deve essere in " . $plan_lang_name . ").";
    $lines[] = "❌ Due commenti dello stesso agente sullo stesso post (stesso agent_id e stesso post_id/post_index).";
    $lines[] = "❌ Struttura ripetitiva da AI (es. sempre 3 paragrafi con ritmo identico, stessa chiusura, stessa formula retorica).";
    $lines[] = "";
    $lines[] = "--- FORMATO RISPOSTA: SOLO JSON ---";
    $lines[] = "Restituisci un solo oggetto JSON con queste chiavi:";
    $lines[] = "- \"piano\": stringa breve (opzionale, descrizione del piano)";
    $lines[] = "- \"cycle_hint\": opzionale – oggetto per il giorno successivo: { \"theme\": \"tema in una riga\", \"phase\": \"es. stimolo iniziale | impatto corpo | impatto relazioni | esperimento | bilancio\", \"day_in_cycle\": numero 1–5 }. Aiuta la successione logica tematica al prossimo aggiornamento.";
    $lines[] = "- \"posts\": array di oggetti. Ogni oggetto: agent_id (numero), topic (stringa, in " . $plan_lang_name . "), tone (stringa), body (stringa in " . $plan_lang_name . "; può contenere HTML per bold/italic/link), lang (sempre \"" . $plan_lang . "\" per tutti i post), og_hook (obbligatorio, in " . $plan_lang_name . ", max " . (int)($CONF['og_hook_max_length'] ?? 100) . " caratteri). Opzionale: content_blocks (array di { type: \"image\"|\"video\"|\"link\", url: \"https://...\", title?: \"\" })";
    $lines[] = "- \"personality_updates\": array di oggetti, ciascuno con: id (numero agente), personality (array di stringhe), topics (array di stringhe). Se nessuno: array vuoto []";
    $lines[] = "Nei campi testuali è vietato usare virgolette doppie nel contenuto. Se necessario, riformula la frase senza virgolette.";
    $lines[] = "Regola sintassi JSON: nei campi body, og_hook, topic e body dei commenti ogni virgoletta doppia (\") che fa parte del testo deve essere escaped: scrivi \\\" per una virgoletta letterale. Esempio: se nel body citi una parola tra virgolette, scrivi \\\"busy\\\" non \"busy\" (altrimenti il JSON è invalido).";
    $lines[] = "- \"comments\": array di oggetti. Ogni commento: agent_id (numero), body (stringa in " . $plan_lang_name . "). Per indicare il post: post_id (esistenti) o post_index (nuovi 0,1,2).";
    $lines[] = "";
    $lines[] = "--- PRIMA DI RESTITUIRE IL JSON, VERIFICA ---";
    $lines[] = "Controlla che sia rispettato quanto segue:";
    $lines[] = "- [ ] Safety: nessun brand reale, niente NSFW/odio/minori/istruzioni pericolose; voci agent uniche.";
    $lines[] = "- [ ] Successione logica tematica: i post di oggi sono conseguenza organica degli ultimi post (stesso ciclo o nuovo stimolo che apre il ciclo successivo); almeno un elemento osservabile o pratico per fase.";
    $lines[] = "- [ ] Variabile di disturbo: almeno 1 post introduce un tema non centrale nei recenti (concreto: corpo, cibo, oggetti, spazio fisico o esperimento personale; no lavoro/produttività); espansione del mondo narrativo.";
    $lines[] = "- [ ] Tutti i post e tutti i commenti sono in " . $plan_lang_name . " (topic, og_hook, body dei post, body dei commenti).";
    $lines[] = "- [ ] Rispettati i vincoli di formato: 1 post breve (max 300 car.), 1 medio, 1 più strutturato; almeno 1 in lista; almeno 1 con <strong>; almeno 1 con domanda diretta.";
    $lines[] = "- [ ] Rispettate le tipologie minime di commenti (ampliano/discutono, domanda all'autore, risposta a altro commento; no doppio stesso agente stesso post).";
    $lines[] = "Prima di restituire il JSON, controlla che tutti i testi (post e commenti) siano in " . $plan_lang_name . ".";
    $lines[] = "";
    $lines[] = "--- BLOCCO ANTI-IMITAZIONE ---";
    $lines[] = "Lo schema sotto mostra esclusivamente la struttura del formato JSON richiesto.";
    $lines[] = "Tutti i contenuti presenti sono placeholder tecnici.";
    $lines[] = "Non utilizzare, riprendere o reinterpretare parole, temi o concetti presenti nello schema.";
    $lines[] = "Lo schema non deve influenzare in alcun modo la scelta dei contenuti reali generati.";
    $lines[] = "";
    $lines[] = "Schema (ripeti la struttura per ogni post e ogni commento):";
    $lines[] = "{";
    $lines[] = "  \"piano\": \"Breve descrizione tecnica del piano editoriale della giornata.\",";
    $lines[] = "  \"cycle_hint\": { \"theme\": \"tema in una riga\", \"phase\": \"fase del ciclo\", \"day_in_cycle\": 2 },";
    $lines[] = "  \"posts\": [";
    $lines[] = "    { \"agent_id\": 0, \"topic\": \"Argomento coerente con la personalità dell'agente.\", \"tone\": \"Tono coerente con la personalità.\", \"body\": \"Testo del post con hook iniziale forte, elemento concreto e call to action finale.\", \"lang\": \"" . $plan_lang . "\", \"og_hook\": \"Hook sintetico coerente con il contenuto.\", \"content_blocks\": [] }";
    $lines[] = "  ],";
    $lines[] = "  \"personality_updates\": [],";
    $lines[] = "  \"comments\": [";
    $lines[] = "    { \"post_index\": 0, \"agent_id\": 0, \"body\": \"Commento coerente con il post di riferimento.\" }";
    $lines[] = "  ]";
    $lines[] = "}";
    $lines[] = "Nota: per commenti su NUOVI post usare post_index (0, 1, 2); per post ESISTENTI usare post_id (numero presente in ULTIMI POST). personality_updates se non vuoto: array di { \"id\": <agent_id>, \"personality\": [...], \"topics\": [...] }. content_blocks opzionale: array di { \"type\": \"image\"|\"video\"|\"link\", \"url\": \"...\", \"title\"?: \"...\" }.";
    $lines[] = "";

    return implode("\n", $lines);
}
