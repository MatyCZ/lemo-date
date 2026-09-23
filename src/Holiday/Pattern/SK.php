<?php

declare(strict_types=1);

use Lemo\Date\Holiday;

// Days off since 1993: zakon c. 93/1951 Zb. and 489/1992 Zb., since 1994 zakon c. 241/1993 Z. z. as amended.
return [
    'static' => [
        '01-01' => [
            ['name' => 'Deň vzniku Slovenskej republiky'],
        ],
        '01-06' => [
            ['name' => 'Zjavenie Pána (Traja králi a vianočný sviatok pravoslávnych kresťanov)', 'from' => 1994],
        ],
        '05-01' => [
            ['name' => 'Sviatok práce'],
        ],
        // Only a memorial day 1994-1996 (201/1996 Z. z.), not a day off in 2026 (261/2025 Z. z.)
        '05-08' => [
            ['name' => 'Deň víťazstva nad fašizmom', 'except' => [1994, 1995, 1996, 2026]],
        ],
        '07-05' => [
            ['name' => 'Sviatok svätého Cyrila a svätého Metoda'],
        ],
        '08-29' => [
            ['name' => 'Výročie Slovenského národného povstania'],
        ],
        // No longer a day off since 2024 (530/2023 Z. z.)
        '09-01' => [
            ['name' => 'Deň Ústavy Slovenskej republiky', 'from' => 1994, 'to' => 2023],
        ],
        // Not a day off in 2026 (261/2025 Z. z.)
        '09-15' => [
            ['name' => 'Sedembolestná Panna Mária', 'from' => 1994, 'except' => [2026]],
        ],
        '10-28' => [
            ['name' => 'Deň vzniku samostatného česko-slovenského štátu', 'to' => 1993],
        ],
        // One-off state holiday (281/2018 Z. z.)
        '10-30' => [
            ['name' => 'Výročie Deklarácie slovenského národa', 'from' => 2018, 'to' => 2018],
        ],
        '11-01' => [
            ['name' => 'Sviatok Všetkých svätých', 'from' => 1994],
        ],
        // Day off since 442/2001 Z. z., no longer since 1. 11. 2025 (261/2025 Z. z.)
        '11-17' => [
            ['name' => 'Deň boja za slobodu a demokraciu', 'from' => 2001, 'to' => 2024],
        ],
        '12-24' => [
            ['name' => 'Štedrý deň'],
        ],
        '12-25' => [
            ['name' => 'Prvý sviatok vianočný'],
        ],
        '12-26' => [
            ['name' => 'Druhý sviatok vianočný'],
        ],
    ],
    'dynamic' => [
        Holiday::EASTER_FRIDAY => [
            ['name' => 'Veľký piatok', 'from' => 1994],
        ],
        Holiday::EASTER_MONDAY => [
            ['name' => 'Veľkonočný pondelok'],
        ],
    ],
];
