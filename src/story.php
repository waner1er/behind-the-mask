<?php

/**
 * Le scénario : l'intro avant le niveau 1, la fin après le Docteur Mask, et le générique.
 *
 * Chaque étape = un plan ("scene") mis en scène par js/game.js + un texte qui s'écrit
 * lettre par lettre dans la boîte de dialogue. START / ESPACE pour passer à la suite.
 *
 * Dans un texte, [PAUSE 3] fige la machine à écrire 3 secondes (pour un effet comique, par exemple).
 *
 * Options d'une étape : 'shout' (bulle au-dessus de Pete), 'action' => 'skate' (il part en skate),
 * 'duration' (passe tout seul au bout de N images).
 *
 * Plans disponibles :
 *   intro : pete, villains, mask, go
 *   fin   : boom (le Docteur Mask explose), peace (la ville renaît), credits (le générique)
 */

return [
    // numéros de piste de l'album
    'introTrack' => 9,
    'endingTrack' => 5,

    // Ton "Marvel" : phrases courtes, narrateur qui en fait des tonnes, répliques, punchline finale.
    // Deux étapes de suite sur le même plan = l'animation continue (elle ne recommence pas).
    'intro' => [
        ['scene' => 'pete', 'text' => "2026. LA VILLE DORT LES YEUX OUVERTS, COLLÉE À SES ÉCRANS. MAIS UN HOMME, LUI, NE DORT JAMAIS."],
        ['scene' => 'pete', 'text' => "PETE. CEINTURE NOIRE D'AÏKIDO. ENDURANT COMME UN MARATHON. TOLÉRANT COMME UN.......[PAUSE 3] ENFIN, PRESQUE TOLÉRANT."],
        ['scene' => 'villains', 'text' => "LES RACISTES. LES MASCULINISTES. LES PRÉDATEURS DU CAPITAL. ILS POUSSENT PARTOUT, COMME DE LA MAUVAISE HERBE."],
        ['scene' => 'villains', 'text' => "PETE : « VOUS AVEZ DE LA CHANCE. AUJOURD'HUI, JE SUIS DE BONNE HUMEUR. » (IL NE L'ÉTAIT PAS.)"],
        ['scene' => 'mask', 'text' => "PENDANT CE TEMPS, TOUT EN HAUT DE SON DATA CENTER..."],
        ['scene' => 'mask', 'text' => "DOCTEUR MASK : « MON IA VOIT TOUT, VEND TOUT, DÉCIDE DE TOUT. BIENTÔT, LE MONDE ENTIER SERA... EN PROMO ! HA HA HA ! »"],
        ['scene' => 'go', 'text' => "UN AÏKIDOKA. UN KATANA. UN SKATE. NEUF MORCEAUX DE PUNK HARDCORE."],
        // shout = bulle au-dessus de Pete, action = ce qu'il fait à ce moment-là
        ['scene' => 'go', 'text' => "PETE : « BRÛLONS LES DATA CENTERS ! »", 'shout' => 'BRÛLONS LES DATA CENTERS !', 'action' => 'skate'],
        ['scene' => 'go', 'text' => "IL EST TEMPS DE DÉBRANCHER LE MONDE. VIGILANTE : BEHIND THE MASK."],
    ],

    'ending' => [
        ['scene' => 'boom', 'text' => "NOOOON ! MON IA !! MES ACTIONNAIRES !!!", 'duration' => 240],
        ['scene' => 'peace', 'text' => "LE DATA CENTER A EXPLOSÉ. L'IA S'EST ÉTEINTE. LES ÉCRANS AUSSI."],
        ['scene' => 'peace', 'text' => "DANS LES RUES, LES ARBRES REPOUSSENT, LES FLEURS SORTENT DU BITUME... ET LES GENS SOURIENT."],
        ['scene' => 'peace', 'text' => "PETE RENGAINE SON KATANA. QUI SE CACHAIT DERRIÈRE LE MASQUE ? PEU IMPORTE : LA VILLE EST LIBRE."],
        ['scene' => 'credits', 'text' => ''],
    ],

    // Le générique : noms inventés, métiers qui n'existent pas (encore)
    'credits' => [
        ['VIGILANTE', 'BEHIND THE MASK'],
        ['PETE', 'AÏKIDOKA TOLÉRANT (MAIS PAS TROP)'],
        ['JEAN-MICHEL COLLECTIF', 'DÉMONTEUR DE PANNEAUX PUBLICITAIRES'],
        ['SANDRA DÉCROISSANCE', 'CHEFFE DE LA SIESTE COLLECTIVE'],
        ['KEVIN AUTOGESTION', 'RESPONSABLE DES POTAGERS SUR LES TOITS'],
        ['FATOU GRATUITÉ', 'DIRECTRICE DU SERVICE PUBLIC DU BONHEUR'],
        ['MOMO MUTUELLE', "DÉBRANCHEUR D'ALGORITHMES"],
        ['GISÈLE PARTAGE', 'CONSERVATRICE DU MUSÉE DU CAPITALISME'],
        ['RAOUL ZÉRO-PROFIT', 'EXPERT-COMPTABLE EN CÂLINS'],
        ['NADIA CUEILLETTE', 'INGÉNIEURE EN CABANES'],
        ['BERNARD SOLIDAIRE', 'CHAUFFEUR DE VÉLOS-CARGOS GRATUITS'],
        ['LUCETTE COOPÉRATIVE', 'ARCHITECTE DE SQUATS FLEURIS'],
        ['TONY SANS-PATRON', 'DÉLÉGUÉ À LA SEMAINE DE 4 HEURES'],
        ['YASMINE RECYCLAGE', 'PILOTE DE COMPOST DE COMBAT'],
        ['DIDIER DÉMONÉTISÉ', 'LIQUIDATEUR DE BANQUES'],
        ['CLAUDE ENTRAIDE', "DRESSEUR DE PIGEONS VOYAGEURS (REMPLACE L'IA)"],
        ['JOSIANE BIEN-COMMUN', 'TRADUCTRICE DE NOVLANGUE MANAGÉRIALE'],
        ['MUSIQUE', 'VIGILANTE'],
        ['', 'AUCUN ACTIONNAIRE N\'A ÉTÉ BLESSÉ PENDANT CE JEU.'],
        ['', '... ENFIN SI, UN PEU.'],
    ],
];
