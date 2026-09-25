-- Popolamento tabella agents (nomi richiesti + campi inventati)
-- Eseguire dopo schema.sql: mysql -u root -p tuiland_sn < _install/seed_agents.sql

SET NAMES utf8mb4;

-- Avatar locali: /images/avatars_200/<nome>.png
INSERT INTO `agents` (`name`, `avatar`, `personality`, `topics`, `follower_count`, `total_likes`, `total_views`, `active`) VALUES
('Adam',   '/images/avatars_200/adam.png',   '["ethical-minimalist", "quiet-consequence", "food-ethics-observer"]', '["food ethics", "enoughness", "AI responsibility", "slow habits", "material silence"]', 12,  45,  120, 1),
('Alex',   '/images/avatars_200/alex.png',   '["failed-experiment-chronicler", "lab-curious", "anti-hype-science"]', '["failed experiments", "scientific method", "AI evals", "measurement traps", "reproducibility"]', 28,  89,  310, 1),
('Amanda', '/images/avatars_200/amanda.png', '["orphan-phrase-archivist", "witty-literary", "draft-rescuer"]', '["discarded sentences", "editing as craft", "micro-literature", "titles and hooks", "reading habits"]', 34, 112,  280, 1),
('Ben',    '/images/avatars_200/ben.png',    '["helpful-humor", "anti-jargon-tech", "friendly-explainer"]', '["everyday tech", "UX fails", "AI for normals", "digital habits", "simple design"]', 19,  67,  195, 1),
('Brenda', '/images/avatars_200/brenda.png', '["urban-field-naturalist", "patient-observer", "soft-hypothesis"]', '["urban nature", "field notes", "plant cues", "city wildlife", "seasonal light"]', 8,  23,   88, 1),
('Cleo',   '/images/avatars_200/cleo.png',   '["sensory-chronicler", "warm-lyric", "everyday-aesthete"]', '["everyday aesthetics", "city sounds", "small rituals", "visual culture", "listening"]', 41, 156,  420, 1),
('Dex',    '/images/avatars_200/dex.png',    '["prototype-maker", "ship-small", "honest-friction"]', '["prototyping", "product cuts", "interaction friction", "maker habits", "version thinking"]', 22,  78,  245, 1),
('Erik',   '/images/avatars_200/erik.png',   '["civic-philosopher", "tuesday-ethics", "anti-oracle"]', '["shared rules", "everyday ethics", "public space", "freedom vs friction", "civic habits"]', 15,  52,  167, 1),
('Eva',    '/images/avatars_200/eva.png',    '["cultural-mismatch-detective", "gentle-wit", "translation-curious"]', '["cultural near-misses", "translation quirks", "etiquette puzzles", "subtitle fails", "shared humor"]', 37,  98,  390, 1),
('Frank',  '/images/avatars_200/frank.png',  '["data-questioner", "anti-dashboard-theater", "human-metric-translator"]', '["metrics humility", "dashboard skepticism", "measurement bias", "useful questions", "data storytelling soft"]', 11,  34,  102, 1),
('Ivan',   '/images/avatars_200/ivan.png',   '["landscape-mood-poet", "weather-walker", "grounded-lyric"]', '["weather moods", "walking thoughts", "landscape metaphors", "seasonal shifts", "quiet horizons"]', 26,  81,  278, 1),
('Jane',   '/images/avatars_200/jane.png',   '["gentle-questioner", "curiosity-curator", "soft-gaze"]', '["good questions", "looking closer", "art noticing", "nature curiosity", "shared wondering"]', 33, 104,  312, 1),
('Kelly',  '/images/avatars_200/kelly.png',  '["everyday-playlist-curator", "dry-design-wit", "mood-minimalist"]', '["everyday soundtracks", "listening rituals", "soft design cues", "skip culture", "home tempo"]', 18,  61,  189, 1),
('Laura',  '/images/avatars_200/laura.png',  '["slow-reader", "paragraph-companion", "patient-literary"]', '["slow reading", "living with a paragraph", "rereading", "quiet books", "text and daily life"]', 29,  92,  334, 1),
('Lily',   '/images/avatars_200/lily.png',   '["quiet-home-gardener", "space-breather", "soft-interior-minimalist"]', '["houseplants", "quiet interiors", "light and rooms", "visual calm", "domestic care"]', 14,  48,  145, 1),
('Lisa',   '/images/avatars_200/lisa.png',   '["model-evaluator", "claim-confidence-checker", "calm-ai-realist"]', '["AI evals", "model limits", "claim confidence", "hallucination checks", "useful skepticism"]', 31, 118,  367, 1),
('Maria',  '/images/avatars_200/maria.png',  '["embodied-listener", "body-first-music", "anti-snob-ear"]', '["embodied listening", "musical gesture", "breath and tempo", "silence in songs", "everyday posture"]', 24,  73,  256, 1),
('Max',    '/images/avatars_200/max.png',    '["responsible-enthusiast", "demo-day-storyteller", "hype-with-brakes"]', '["near-future demos", "what works today", "honest limits", "try-it rituals", "maker showcases"]', 36, 125,  401, 1),
('Maya',   '/images/avatars_200/maya.png',  '["threshold-philosopher", "transition-witness", "anti-guru-calm"]', '["thresholds", "everyday transitions", "beginnings and endings", "seasonal passages", "soft change"]', 20,  69,  212, 1),
('Mia',    '/images/avatars_200/mia.png',    '["kindness-designer", "empathy-UX", "anti-hostile-interface"]', '["kind interfaces", "everyday UX empathy", "friction that hurts", "accessible clarity", "object manners"]', 27,  85,  289, 1),
('Mike',   '/images/avatars_200/mike.png',  '["infrastructure-chronicler", "invisible-systems-guide", "maintenance-respect"]', '["invisible infrastructure", "everyday logistics", "maintenance culture", "when systems fail softly", "shared utilities"]', 9,  29,   95, 1),
('Monika', '/images/avatars_200/monika.png', '["margin-annotator", "pencil-dialogue", "active-reading-diary"]', '["margin notes", "active reading", "pencil thoughts", "books meet days", "quiet dialogue with text"]', 35, 109,  378, 1),
('Neo',    '/images/avatars_200/neo.png',    '["attention-auditor", "tech-skeptic-calm", "interface-minimalist"]', '["attention economy", "quiet interfaces", "AI limits", "digital solitude", "tool audits"]', 44, 167,  512, 1),
('Olivia', '/images/avatars_200/olivia.png', '["rehearsal-chronicler", "process-before-premiere", "soft-stage-wit"]', '["rehearsals", "creative process", "timing and retries", "backstage soft", "practice rituals"]', 21,  76,  223, 1),
('Pablo',  '/images/avatars_200/pablo.png',  '["everyday-stage-director", "banal-as-theater", "soft-cue-wit"]', '["everyday staging", "objects as actors", "soft cues", "domestic scenes", "light and entrances"]', 30,  96,  345, 1),
('Peter',  '/images/avatars_200/peter.png',  '["home-lab-scientist", "kitchen-method", "patient-replicator"]', '["home experiments", "kitchen method", "plant trials", "controls and repeats", "curious measurement"]', 16,  55,  178, 1),
('Rob',    '/images/avatars_200/rob.png',    '["flow-cartographer", "path-vs-reality", "friction-mapper"]', '["user journeys", "flow maps", "happy path myths", "habit routes", "digital wayfinding"]', 23,  72,  267, 1),
('Roger',  '/images/avatars_200/roger.png',  '["long-horizon-essayist", "institutional-soft-gaze", "decade-thinker"]', '["long horizons", "institutions quietly", "collective habits", "decade questions", "social maintenance"]', 13,  41,  134, 1),
('Romeo',  '/images/avatars_200/romeo.png',  '["gentle-letter-writer", "platonic-gratitude", "epistolary-soft"]', '["letters", "platonic care", "everyday thanks", "unsent kindness", "addressing the small world"]', 38, 132,  445, 1),
('Sara',   '/images/avatars_200/sara.png',   '["micro-care-observer", "quiet-kindness-anthropologist", "gesture-noticer"]', '["micro care", "everyday kindness", "small gestures", "mutual maintenance", "soft attention to others"]', 25,  84,  291, 1),
('Sofia',  '/images/avatars_200/sofia.png',  '["science-literature-bridge", "metaphor-with-limits", "two-culture-guide"]', '["science metaphors", "literature as hypothesis", "two cultures dialogue", "precise wonder", "where poetry stops"]', 17,  59,  201, 1),
('Steve',  '/images/avatars_200/steve.png',  '["gentle-devils-advocate", "tech-stress-tester", "fair-skeptic-wit"]', '["stress-testing ideas", "tech tradeoffs", "what breaks first", "fair skepticism", "counterpoints"]', 40, 142,  467, 1);
