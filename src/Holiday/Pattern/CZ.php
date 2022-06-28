<?php

use Lemo\Date\Holiday;

return [
    'static' => [
        '01-01' => 'Den obnovy samostatného českého státu',
        '05-01' => 'Svátek práce',
        '05-08' => 'Den vítězství',
        '07-05' => 'Den slovanských věrozvěstů Cyrila a Metoděje',
        '07-06' => 'Den upálení mistra Jana Husa',
        '09-28' => 'Den české státnosti',
        '10-28' => 'Den vzniku samostatného československého státu',
        '11-17' => 'Den boje za svobodu a demokracii',
        '12-24' => 'Štědrý den',
        '12-25' => '1. svátek vánoční',
        '12-26' => '2. svátek vánoční',
    ],
    'dynamic' => [
        Holiday::EASTERFRIDAY => 'Velký pátek',
        Holiday::EASTERMONDAY => 'Velikonoční pondělí',
    ]
];