<?php

/*
 * Feedback naast elk analyseresultaat, in het Nederlands.
 *
 * Huisstijl (zelfde als en/analysis.php): één zin die zegt wat er gevonden
 * is, één zin die zegt wat eraan te doen. Een goed resultaat krijgt alleen de
 * eerste zin.
 *
 * De sleutelstructuur is identiek aan de Engelse baseline; een sleutel die
 * hier ontbreekt valt via TranslatorMessageRenderer terug op het Engels.
 */

return [
    'introduction_keyword' => [
        'good' => 'Het zoekwoord staat in de eerste alinea, in één stuk.',
        'spread' => 'De woorden van het zoekwoord staan allemaal in de eerste alinea, maar niet bij elkaar. Zet de zoekterm zelf in één zin, zodat de opening meteen zegt waar de pagina over gaat.',
        'none' => 'Het zoekwoord komt niet voor in de eerste alinea. Benoem in de openingszinnen waar de pagina over gaat, in de woorden van de lezer.',
    ],

    'keyphrase_length' => [
        'missing' => 'Er is geen zoekwoord ingesteld, dus het grootste deel van deze analyse heeft niets om tegen te meten. Vul de zoekterm in waarop deze pagina gevonden moet worden.',
        'good' => 'Het zoekwoord is :count inhoudswoorden lang — precies de lengte waarmee mensen echt zoeken.',
        'slightly_long' => 'Het zoekwoord is :count inhoudswoorden lang, iets boven de aanbevolen :recommendedMax. Kortere zoektermen sluiten aan bij meer zoekopdrachten.',
        'too_long' => 'Het zoekwoord is :count inhoudswoorden lang. Zo specifiek zoekt niemand — breng het terug naar ongeveer :recommendedMax woorden.',
    ],

    'meta_description_keyword' => [
        'missing_description' => 'Er is geen metabeschrijving waar het zoekwoord in kan staan. Schrijf er een en verwerk de zoekterm erin.',
        'good' => 'Het zoekwoord staat :count keer in de metabeschrijving, waar een zoeker het vetgedrukt terugziet.',
        'none' => 'Het zoekwoord komt niet voor in de metabeschrijving. Verwerk het erin, zodat het zoekresultaat de zoeker zijn eigen woorden toont.',
        'too_many' => 'Het zoekwoord staat :count keer in de metabeschrijving. Eén keer is genoeg — gebruik de ruimte om te vertellen waarom de lezer moet klikken.',
    ],

    'meta_description_length' => [
        'missing' => 'Er is geen metabeschrijving ingesteld. Schrijf er een van maximaal :max tekens, zodat zoekmachines jouw samenvatting tonen in plaats van zelf te gokken.',
        'too_short' => 'De metabeschrijving is :length tekens, ruim onder de :max die beschikbaar is. Breid haar uit zodat het zoekresultaat de hele ruimte benut.',
        'good' => 'De metabeschrijving is :length tekens en past precies in de ruimte die een zoekresultaat biedt.',
        'too_long' => 'De metabeschrijving is :length tekens en wordt na ongeveer :max afgekapt. Kort haar in zodat de hele samenvatting zichtbaar blijft.',
    ],

    'text_length' => [
        'good' => 'De tekst is :words woorden lang, op of boven het aanbevolen minimum van :recommended.',
        'slightly_short' => 'De tekst is :words woorden lang, net onder de aanbevolen :recommended. Voeg wat meer detail toe.',
        'short' => 'De tekst is :words woorden lang, ruim onder de aanbevolen :recommended. Behandel het onderwerp uitgebreider.',
        'very_short' => 'De tekst is :words woorden lang, ver onder de aanbevolen :recommended. Schrijf aanzienlijk meer voordat je publiceert.',
        'far_too_short' => 'De tekst is :words woorden lang — te weinig voor een zoekmachine om te beoordelen. Schrijf minstens :recommended woorden.',
    ],

    'keyword_density' => [
        'none' => 'Het zoekwoord komt helemaal niet voor in de tekst. Verwerk het waar het past, tot ongeveer :recommendedMax keer.',
        'under' => 'Het zoekwoord komt :count keer voor, een dichtheid van :density procent. Gebruik het nog een paar keer, tot ongeveer :recommendedMax.',
        'good' => 'Het zoekwoord komt :count keer voor, een dichtheid van :density procent — passend bij deze tekstlengte.',
        'over' => 'Het zoekwoord komt :count keer voor, een dichtheid van :density procent. Dat is vaker dan natuurlijk leest — mik op ongeveer :recommendedMax keer.',
        'way_over' => 'Het zoekwoord komt :count keer voor, een dichtheid van :density procent. Dat is veel te vaak — herschrijf het merendeel en mik op ongeveer :recommendedMax keer.',
    ],

    'text_competing_links' => [
        'good' => 'Geen enkele link in de tekst concurreert met deze pagina om het zoekwoord.',
        'competing' => ':count link in de tekst gebruikt het zoekwoord als ankertekst. Die pagina\'s concurreren nu met deze om dezelfde zoekopdracht — herformuleer het anker of verwijder de link.',
    ],

    'image_keyphrase' => [
        'missing_input' => 'Voeg een afbeelding toe en stel een zoekwoord in; deze check kijkt dan of de alt-tekst het beschrijft.',
        'good' => ':count van de :total afbeeldingen beschrijven het zoekwoord in hun alt-tekst.',
        'too_few' => 'Slechts :count van de :total afbeeldingen noemen het zoekwoord in hun alt-tekst. Beschrijf er een paar meer in de woorden waar de pagina over gaat.',
        'too_many' => ':count van de :total afbeeldingen herhalen het zoekwoord in hun alt-tekst. Alt-tekst beschrijft eerst het beeld — varieer waar de afbeelding iets anders toont.',
        'none_match' => 'Geen van de :total afbeeldingen noemt het zoekwoord in de alt-tekst. Waar een afbeelding het onderwerp echt toont, benoem dat dan ook.',
        'no_alts' => 'Geen van de :total afbeeldingen heeft alt-tekst. Beschrijf ze allemaal, voor zoekmachines én voor lezers die het beeld niet kunnen zien.',
    ],

    'keyphrase_in_seo_title' => [
        'missing_input' => 'Stel zowel een SEO-titel als een zoekwoord in; deze check kijkt dan of die twee overeenkomen.',
        'good_start' => 'De SEO-titel opent met het zoekwoord — precies waar een zoeker als eerste leest.',
        'good_not_start' => 'De SEO-titel bevat het zoekwoord, maar niet vooraan. Eerder in de titel scant het resultaat makkelijker.',
        'all_words' => 'De woorden van het zoekwoord staan allemaal in de SEO-titel, maar niet als de zoekterm zelf. Gebruik de term zoals geschreven waar de formulering het toelaat.',
        'not_found' => 'Het zoekwoord komt niet voor in de SEO-titel. Zet het erin, zo dicht mogelijk bij het begin.',
    ],

    'subheadings_keyword' => [
        'missing_input' => 'Stel een zoekwoord in en schrijf tekst; deze check kijkt dan of je tussenkoppen erbij passen.',
        'none_long_text' => 'Een tekst van deze lengte heeft geen tussenkoppen. Verdeel hem in secties met een naam, en verwerk het zoekwoord in een deel daarvan.',
        'none_short_text' => 'De tekst is kort genoeg om zonder tussenkoppen te lezen.',
        'good' => ':count van de :total tussenkoppen bevatten het zoekwoord — ongeveer het juiste aandeel.',
        'too_few' => 'Slechts :count van de :total tussenkoppen bevatten het zoekwoord. Verwerk het in een paar meer, zodat de opbouw van de pagina het onderwerp weerspiegelt.',
        'too_many' => ':count van de :total tussenkoppen bevatten het zoekwoord. In bijna elke kop leest het als geschreven voor een zoekmachine — varieer de formulering.',
        'none' => 'Geen van de :total tussenkoppen bevat het zoekwoord. Verwerk het in een paar ervan, zodat de opbouw van de pagina het onderwerp weerspiegelt.',
    ],

    'images' => [
        'none' => 'De tekst bevat geen afbeeldingen. Voeg minstens één relevante afbeelding of video toe, zodat de pagina geen muur van tekst is.',
        'good' => 'De tekst is geïllustreerd met minstens één afbeelding.',
    ],

    'single_h1' => [
        'good' => 'De tekst gebruikt hooguit één H1-kop.',
        'multiple' => 'De tekst bevat :count H1-koppen. Houd één H1 voor de paginatitel en maak van de rest H2 of lager.',
    ],

    'title_width' => [
        'missing' => 'Er is geen SEO-titel ingesteld. Schrijf er een, zodat zoekmachines jouw formulering tonen in plaats van zelf iets te kiezen.',
        'good' => 'De SEO-titel is ongeveer :width pixels breed en past binnen de :max pixels die een zoekresultaat toont.',
        'too_wide' => 'De SEO-titel is ongeveer :width pixels breed en wordt na :max afgekapt. Kort hem in zodat de hele titel zichtbaar blijft.',
    ],

    'slug_keyword' => [
        'missing_input' => 'Stel een slug en een zoekwoord in; deze check kijkt dan of de URL bij het onderwerp past.',
        'good' => 'De slug bevat het zoekwoord — de URL zelf zegt al waar de pagina over gaat.',
        'some' => 'De slug bevat :count van de :total zoekwoordwoorden. Verwerk de rest, zolang de URL leesbaar blijft.',
    ],

    'function_words_in_keyphrase' => [
        'only_function_words' => 'Het zoekwoord bestaat volledig uit algemene woorden en kan deze pagina dus niet onderscheiden. Voeg het woord toe voor waar de pagina echt over gaat.',
    ],

    'previously_used_keyphrase' => [
        'missing_keyphrase' => 'Er is geen zoekwoord ingesteld, dus er valt niets te vergelijken met de rest van de site.',
        'unique' => 'Geen andere pagina richt zich op dit zoekwoord.',
        'used_once' => 'Eén andere pagina richt zich al op dit zoekwoord. Twee pagina\'s achter dezelfde zoekopdracht zitten elkaar in de weg — overweeg er één aan te scherpen.',
        'used_multiple' => 'Dit zoekwoord wordt al op :count andere pagina\'s gebruikt. Ze gaan met elkaar concurreren in de resultaten — geef elke pagina een eigen zoekterm.',
    ],

    'sentence_length' => [
        'good' => ':percentage procent van de zinnen is langer dan :limit woorden — binnen de comfortabele marge.',
        'some_long' => ':percentage procent van de zinnen is langer dan :limit woorden. Kort er een paar in om de vaart erin te houden.',
        'too_many_long' => ':percentage procent van de zinnen is langer dan :limit woorden. Knip de langste op, zodat een lezer ze in één keer kan volgen.',
    ],

    'paragraph_too_long' => [
        'good' => 'De langste alinea is :words woorden, binnen de :max die prettig leest.',
        'slightly_long' => 'De langste alinea is :words woorden, net boven de :max die prettig leest. Overweeg hem te splitsen.',
        'too_long' => 'De langste alinea is :words woorden, ruim boven de :max die prettig leest. Knip hem op bij de wendingen in het verhaal.',
    ],

    'subheadings_too_long' => [
        'short_text' => 'De tekst is kort genoeg om zonder tussenkoppen te lezen.',
        'none' => 'Een tekst van :words woorden heeft helemaal geen tussenkoppen. Voeg ze toe, zodat een lezer het deel vindt waarvoor hij kwam.',
        'good' => 'Het langste stuk tussen twee tussenkoppen is :words woorden, binnen de :max die een lezer volhoudt.',
        'long_section' => 'Het langste stuk tussen twee tussenkoppen is :words woorden, net boven de :max die een lezer volhoudt. Overweeg een extra tussenkop.',
        'too_long_section' => 'Het langste stuk tussen twee tussenkoppen is :words woorden, boven de :max die een lezer volhoudt. Plaats een tussenkop waar het onderwerp draait.',
    ],

    'sentence_beginnings' => [
        'varied' => 'De zinnen beginnen niet allemaal hetzelfde.',
        'repeated' => ':count zinnen op rij beginnen met ":word". Varieer de openingen, zodat de tekst niet als een opsomming leest.',
    ],

    'transition_words' => [
        'short_text' => 'De tekst is kort genoeg om zonder signaalwoorden te volgen.',
        'good' => ':percentage procent van de zinnen gebruikt een signaalwoord — genoeg om de redenering te volgen.',
        'some' => ':percentage procent van de zinnen gebruikt een signaalwoord. Met een paar meer wordt de rode draad makkelijker te volgen.',
        'few' => 'Slechts :percentage procent van de zinnen gebruikt een signaalwoord. Maak duidelijk hoe de ene zin uit de andere volgt, zodat de tekst als een betoog leest en niet als een lijst.',
    ],

    'passive_voice' => [
        'good' => ':percentage procent van de zinnen staat in de lijdende vorm — binnen de normale marge.',
        'some' => ':percentage procent van de zinnen staat in de lijdende vorm. Een paar ervan actief herschrijven leest directer.',
        'too_many' => ':percentage procent van de zinnen staat in de lijdende vorm. Benoem in elk geval in een deel ervan wie wat doet — actieve zinnen zijn korter en makkelijker te volgen.',
    ],

    'text_presence' => [
        'too_little' => 'Er staat te weinig tekst op deze pagina om de leesbaarheid te beoordelen. Schrijf een paar alinea\'s en de leesbaarheidsanalyse heeft iets om mee te werken.',
    ],

    'internal_links' => [
        'none' => 'De tekst linkt naar geen enkele andere pagina van je site. Voeg interne links toe, zodat lezers en zoekmachines gerelateerde content kunnen bereiken.',
        'all_nofollow' => 'Elke interne link in de tekst is nofollow. Haal nofollow weg bij de links die waarde aan je eigen pagina\'s moeten doorgeven.',
        'some_nofollow' => ':nofollow van de :total interne links zijn nofollow. Controleer of dat bij elk daarvan de bedoeling is.',
        'good' => 'De tekst linkt naar je eigen pagina\'s, en geen van die links is nofollow.',
    ],

    'external_links' => [
        'none' => 'De tekst linkt naar geen enkele andere site. Link naar een bron of referentie waar dat de lezer helpt.',
        'all_nofollow' => 'Elke externe link in de tekst is nofollow. Haal nofollow weg waar je de bron wél wilt aanbevelen.',
        'some_nofollow' => ':nofollow van de :total externe links zijn nofollow. Controleer of dat bij elk daarvan de bedoeling is.',
        'good' => 'De tekst linkt naar andere sites, en geen van die links is nofollow.',
    ],
];
