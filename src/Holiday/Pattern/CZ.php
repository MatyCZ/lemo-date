<?php

declare(strict_types=1);

use Lemo\Date\Holiday;

// Days off since 1993: zakon c. 93/1951 Sb., since 9. 8. 2000 zakon c. 245/2000 Sb. as amended.
// A name change applies from the first occurrence after the amendment took effect.
return [
    'static' => [
        '01-01' => [
            ['name' => 'Nový rok', 'to' => 2000],
            ['name' => 'Den obnovy samostatného českého státu', 'from' => 2001],
        ],
        '05-01' => [
            ['name' => 'Svátek práce'],
        ],
        '05-08' => [
            ['name' => 'Den osvobození od fašismu', 'to' => 2000],
            ['name' => 'Den osvobození', 'from' => 2001, 'to' => 2003],
            ['name' => 'Den vítězství', 'from' => 2004],
        ],
        '07-05' => [
            ['name' => 'Den slovanských věrozvěstů Cyrila a Metoděje'],
        ],
        '07-06' => [
            ['name' => 'Den upálení mistra Jana Husa'],
        ],
        '09-28' => [
            ['name' => 'Den české státnosti', 'from' => 2000],
        ],
        '10-28' => [
            ['name' => 'Den vzniku samostatného československého státu'],
        ],
        '11-17' => [
            ['name' => 'Den boje za svobodu a demokracii', 'from' => 2000, 'to' => 2018],
            ['name' => 'Den boje za svobodu a demokracii a Mezinárodní den studentstva', 'from' => 2019],
        ],
        '12-24' => [
            ['name' => 'Štědrý den'],
        ],
        '12-25' => [
            ['name' => '1. svátek vánoční'],
        ],
        '12-26' => [
            ['name' => '2. svátek vánoční'],
        ],
    ],
    'dynamic' => [
        // zakon c. 359/2015 Sb.
        Holiday::EASTER_FRIDAY => [
            ['name' => 'Velký pátek', 'from' => 2016],
        ],
        Holiday::EASTER_MONDAY => [
            ['name' => 'Velikonoční pondělí'],
        ],
    ],
];
