<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

trait PeppolUneceRec2011eTrait1
{

    protected function getUNECERec2011eChunk1(): array
    {
        return [
            0 => [
                'Id' => '10',
                'Name' => 'group',
                'Description' => 'A unit of count defining the number of groups (group: set of items classified
            together).',
            ],
            1 => [
                'Id' => '11',
                'Name' => 'outfit',
                'Description' => 'A unit of count defining the number of outfits (outfit: a complete set of
            equipment / materials / objects used for a specific purpose).',
            ],
            2 => [
                'Id' => '13',
                'Name' => 'ration',
                'Description' => 'A unit of count defining the number of rations (ration: a single portion of
            provisions).',
            ],
            3 => [
                'Id' => '14',
                'Name' => 'shot',
                'Description' => 'A unit of liquid measure, especially related to spirits.',
            ],
            4 => [
                'Id' => '15',
                'Name' => 'stick, military',
                'Description' => 'A unit of count defining the number of military sticks (military stick: bombs
            or paratroops released in rapid succession from an aircraft).',
            ],
            5 => [
                'Id' => '20',
                'Name' => 'twenty foot container',
                'Description' => 'A unit of count defining the number of shipping containers that measure 20 foot
            in length.',
            ],
            6 => [
                'Id' => '21',
                'Name' => 'forty foot container',
                'Description' => 'A unit of count defining the number of shipping containers that measure 40 foot
            in length.',
            ],
            7 => [
                'Id' => '22',
                'Name' => 'decilitre per gram',
                'Description' => '',
            ],
            8 => [
                'Id' => '23',
                'Name' => 'gram per cubic centimetre',
                'Description' => '',
            ],
            9 => [
                'Id' => '24',
                'Name' => 'theoretical pound',
                'Description' => 'A unit of mass defining the expected mass of material expressed as the number
            of pounds.',
            ],
            10 => [
                'Id' => '25',
                'Name' => 'gram per square centimetre',
                'Description' => '',
            ],
            11 => [
                'Id' => '27',
                'Name' => 'theoretical ton',
                'Description' => 'A unit of mass defining the expected mass of material, expressed as the number
            of tons.',
            ],
            12 => [
                'Id' => '28',
                'Name' => 'kilogram per square metre',
                'Description' => '',
            ],
            13 => [
                'Id' => '33',
                'Name' => 'kilopascal square metre per gram',
                'Description' => '',
            ],
            14 => [
                'Id' => '34',
                'Name' => 'kilopascal per millimetre',
                'Description' => '',
            ],
            15 => [
                'Id' => '35',
                'Name' => 'millilitre per square centimetre second',
                'Description' => '',
            ],
            16 => [
                'Id' => '37',
                'Name' => 'ounce per square foot',
                'Description' => '',
            ],
            17 => [
                'Id' => '38',
                'Name' => 'ounce per square foot per 0,01inch',
                'Description' => '',
            ],
            18 => [
                'Id' => '40',
                'Name' => 'millilitre per second',
                'Description' => '',
            ],
            19 => [
                'Id' => '41',
                'Name' => 'millilitre per minute',
                'Description' => '',
            ],
            20 => [
                'Id' => '56',
                'Name' => 'sitas',
                'Description' => 'A unit of area for tin plate equal to a surface area of 100 square
            metres.',
            ],
            21 => [
                'Id' => '57',
                'Name' => 'mesh',
                'Description' => 'A unit of count defining the number of strands per inch as a measure of the
            fineness of a woven product.',
            ],
            22 => [
                'Id' => '58',
                'Name' => 'net kilogram',
                'Description' => 'A unit of mass defining the total number of kilograms after
            deductions.',
            ],
            23 => [
                'Id' => '59',
                'Name' => 'part per million',
                'Description' => 'A unit of proportion equal to 10⁻⁶.',
            ],
        ];
    }

    protected function getUNECERec2011eChunk2(): array
    {
        return [
            24 => [
                'Id' => '60',
                'Name' => 'percent weight',
                'Description' => 'A unit of proportion equal to 10⁻².',
            ],
            25 => [
                'Id' => '61',
                'Name' => 'part per billion (US)',
                'Description' => 'A unit of proportion equal to 10⁻⁹.',
            ],
            26 => [
                'Id' => '74',
                'Name' => 'millipascal',
                'Description' => '',
            ],
            27 => [
                'Id' => '77',
                'Name' => 'milli-inch',
                'Description' => '',
            ],
            28 => [
                'Id' => '80',
                'Name' => 'pound per square inch absolute',
                'Description' => '',
            ],
            29 => [
                'Id' => '81',
                'Name' => 'henry',
                'Description' => '',
            ],
            30 => [
                'Id' => '85',
                'Name' => 'foot pound-force',
                'Description' => '',
            ],
            31 => [
                'Id' => '87',
                'Name' => 'pound per cubic foot',
                'Description' => '',
            ],
            32 => [
                'Id' => '89',
                'Name' => 'poise',
                'Description' => '',
            ],
            33 => [
                'Id' => '91',
                'Name' => 'stokes',
                'Description' => '',
            ],
            34 => [
                'Id' => '1I',
                'Name' => 'fixed rate',
                'Description' => 'A unit of quantity expressed as a predetermined or set rate for usage of a
            facility or service.',
            ],
            35 => [
                'Id' => '2A',
                'Name' => 'radian per second',
                'Description' => 'Refer ISO/TC12 SI Guide',
            ],
            36 => [
                'Id' => '2B',
                'Name' => 'radian per second squared',
                'Description' => 'Refer ISO/TC12 SI Guide',
            ],
            37 => [
                'Id' => '2C',
                'Name' => 'roentgen',
                'Description' => '',
            ],
            38 => [
                'Id' => '2G',
                'Name' => 'volt AC',
                'Description' => 'A unit of electric potential in relation to alternating current
            (AC).',
            ],
            39 => [
                'Id' => '2H',
                'Name' => 'volt DC',
                'Description' => 'A unit of electric potential in relation to direct current (DC).',
            ],
            40 => [
                'Id' => '2I',
                'Name' => 'British thermal unit (international table) per hour',
                'Description' => '',
            ],
            41 => [
                'Id' => '2J',
                'Name' => 'cubic centimetre per second',
                'Description' => '',
            ],
            42 => [
                'Id' => '2K',
                'Name' => 'cubic foot per hour',
                'Description' => '',
            ],
            43 => [
                'Id' => '2L',
                'Name' => 'cubic foot per minute',
                'Description' => '',
            ],
            44 => [
                'Id' => '2M',
                'Name' => 'centimetre per second',
                'Description' => '',
            ],
            45 => [
                'Id' => '2N',
                'Name' => 'decibel',
                'Description' => '',
            ],
            46 => [
                'Id' => '2P',
                'Name' => 'kilobyte',
                'Description' => 'A unit of information equal to 10³ (1000) bytes.',
            ],
            47 => [
                'Id' => '2Q',
                'Name' => 'kilobecquerel',
                'Description' => '',
            ],
            48 => [
                'Id' => '2R',
                'Name' => 'kilocurie',
                'Description' => '',
            ],
            49 => [
                'Id' => '2U',
                'Name' => 'megagram',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk3(): array
    {
        return [
            50 => [
                'Id' => '2X',
                'Name' => 'metre per minute',
                'Description' => '',
            ],
            51 => [
                'Id' => '2Y',
                'Name' => 'milliroentgen',
                'Description' => '',
            ],
            52 => [
                'Id' => '2Z',
                'Name' => 'millivolt',
                'Description' => '',
            ],
            53 => [
                'Id' => '3B',
                'Name' => 'megajoule',
                'Description' => '',
            ],
            54 => [
                'Id' => '3C',
                'Name' => 'manmonth',
                'Description' => 'A unit of count defining the number of months for a person or persons to
            perform an undertaking.',
            ],
            55 => [
                'Id' => '4C',
                'Name' => 'centistokes',
                'Description' => '',
            ],
            56 => [
                'Id' => '4G',
                'Name' => 'microlitre',
                'Description' => '',
            ],
            57 => [
                'Id' => '4H',
                'Name' => 'micrometre (micron)',
                'Description' => '',
            ],
            58 => [
                'Id' => '4K',
                'Name' => 'milliampere',
                'Description' => '',
            ],
            59 => [
                'Id' => '4L',
                'Name' => 'megabyte',
                'Description' => 'A unit of information equal to 10⁶ (1000000) bytes.',
            ],
            60 => [
                'Id' => '4M',
                'Name' => 'milligram per hour',
                'Description' => '',
            ],
            61 => [
                'Id' => '4N',
                'Name' => 'megabecquerel',
                'Description' => '',
            ],
            62 => [
                'Id' => '4O',
                'Name' => 'microfarad',
                'Description' => '',
            ],
            63 => [
                'Id' => '4P',
                'Name' => 'newton per metre',
                'Description' => '',
            ],
            64 => [
                'Id' => '4Q',
                'Name' => 'ounce inch',
                'Description' => '',
            ],
            65 => [
                'Id' => '4R',
                'Name' => 'ounce foot',
                'Description' => '',
            ],
            66 => [
                'Id' => '4T',
                'Name' => 'picofarad',
                'Description' => '',
            ],
            67 => [
                'Id' => '4U',
                'Name' => 'pound per hour',
                'Description' => '',
            ],
            68 => [
                'Id' => '4W',
                'Name' => 'ton (US) per hour',
                'Description' => '',
            ],
            69 => [
                'Id' => '4X',
                'Name' => 'kilolitre per hour',
                'Description' => '',
            ],
            70 => [
                'Id' => '5A',
                'Name' => 'barrel (US) per minute',
                'Description' => '',
            ],
            71 => [
                'Id' => '5B',
                'Name' => 'batch',
                'Description' => 'A unit of count defining the number of batches (batch: quantity of material
            produced in one operation or number of animals or persons coming at once).',
            ],
            72 => [
                'Id' => '5E',
                'Name' => 'MMSCF/day',
                'Description' => 'A unit of volume equal to one million (1000000) cubic feet of gas per
            day.',
            ],
            73 => [
                'Id' => '5J',
                'Name' => 'hydraulic horse power',
                'Description' => 'A unit of power defining the hydraulic horse power delivered by a fluid pump
            depending on the viscosity of the fluid.',
            ],
            74 => [
                'Id' => 'A10',
                'Name' => 'ampere square metre per joule second',
                'Description' => '',
            ],
            75 => [
                'Id' => 'A11',
                'Name' => 'angstrom',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk4(): array
    {
        return [
            76 => [
                'Id' => 'A12',
                'Name' => 'astronomical unit',
                'Description' => '',
            ],
            77 => [
                'Id' => 'A13',
                'Name' => 'attojoule',
                'Description' => '',
            ],
            78 => [
                'Id' => 'A14',
                'Name' => 'barn',
                'Description' => '',
            ],
            79 => [
                'Id' => 'A15',
                'Name' => 'barn per electronvolt',
                'Description' => '',
            ],
            80 => [
                'Id' => 'A16',
                'Name' => 'barn per steradian electronvolt',
                'Description' => '',
            ],
            81 => [
                'Id' => 'A17',
                'Name' => 'barn per steradian',
                'Description' => '',
            ],
            82 => [
                'Id' => 'A18',
                'Name' => 'becquerel per kilogram',
                'Description' => '',
            ],
            83 => [
                'Id' => 'A19',
                'Name' => 'becquerel per cubic metre',
                'Description' => '',
            ],
            84 => [
                'Id' => 'A2',
                'Name' => 'ampere per centimetre',
                'Description' => '',
            ],
            85 => [
                'Id' => 'A20',
                'Name' => 'British thermal unit (international table) per second square foot degree
            Rankine',
                'Description' => '',
            ],
            86 => [
                'Id' => 'A21',
                'Name' => 'British thermal unit (international table) per pound degree Rankine',
                'Description' => '',
            ],
            87 => [
                'Id' => 'A22',
                'Name' => 'British thermal unit (international table) per second foot degree Rankine',
                'Description' => '',
            ],
            88 => [
                'Id' => 'A23',
                'Name' => 'British thermal unit (international table) per hour square foot degree Rankine',
                'Description' => '',
            ],
            89 => [
                'Id' => 'A24',
                'Name' => 'candela per square metre',
                'Description' => '',
            ],
            90 => [
                'Id' => 'A26',
                'Name' => 'coulomb metre',
                'Description' => '',
            ],
            91 => [
                'Id' => 'A27',
                'Name' => 'coulomb metre squared per volt',
                'Description' => '',
            ],
            92 => [
                'Id' => 'A28',
                'Name' => 'coulomb per cubic centimetre',
                'Description' => '',
            ],
            93 => [
                'Id' => 'A29',
                'Name' => 'coulomb per cubic metre',
                'Description' => '',
            ],
            94 => [
                'Id' => 'A3',
                'Name' => 'ampere per millimetre',
                'Description' => '',
            ],
            95 => [
                'Id' => 'A30',
                'Name' => 'coulomb per cubic millimetre',
                'Description' => '',
            ],
            96 => [
                'Id' => 'A31',
                'Name' => 'coulomb per kilogram second',
                'Description' => '',
            ],
            97 => [
                'Id' => 'A32',
                'Name' => 'coulomb per mole',
                'Description' => '',
            ],
            98 => [
                'Id' => 'A33',
                'Name' => 'coulomb per square centimetre',
                'Description' => '',
            ],
            99 => [
                'Id' => 'A34',
                'Name' => 'coulomb per square metre',
                'Description' => '',
            ],
            100 => [
                'Id' => 'A35',
                'Name' => 'coulomb per square millimetre',
                'Description' => '',
            ],
            101 => [
                'Id' => 'A36',
                'Name' => 'cubic centimetre per mole',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk5(): array
    {
        return [
            102 => [
                'Id' => 'A37',
                'Name' => 'cubic decimetre per mole',
                'Description' => '',
            ],
            103 => [
                'Id' => 'A38',
                'Name' => 'cubic metre per coulomb',
                'Description' => '',
            ],
            104 => [
                'Id' => 'A39',
                'Name' => 'cubic metre per kilogram',
                'Description' => '',
            ],
            105 => [
                'Id' => 'A4',
                'Name' => 'ampere per square centimetre',
                'Description' => '',
            ],
            106 => [
                'Id' => 'A40',
                'Name' => 'cubic metre per mole',
                'Description' => '',
            ],
            107 => [
                'Id' => 'A41',
                'Name' => 'ampere per square metre',
                'Description' => '',
            ],
            108 => [
                'Id' => 'A42',
                'Name' => 'curie per kilogram',
                'Description' => '',
            ],
            109 => [
                'Id' => 'A43',
                'Name' => 'deadweight tonnage',
                'Description' => 'A unit of mass defining the difference between the weight of a ship when
            completely empty and its weight when completely loaded, expressed as the number of
            tons.',
            ],
            110 => [
                'Id' => 'A44',
                'Name' => 'decalitre',
                'Description' => '',
            ],
            111 => [
                'Id' => 'A45',
                'Name' => 'decametre',
                'Description' => '',
            ],
            112 => [
                'Id' => 'A47',
                'Name' => 'decitex',
                'Description' => 'A unit of yarn density. One decitex equals a mass of 1 gram per 10 kilometres
            of length.',
            ],
            113 => [
                'Id' => 'A48',
                'Name' => 'degree Rankine',
                'Description' => 'Refer ISO 80000-5 (Quantities and units — Part 5: Thermodynamics)',
            ],
            114 => [
                'Id' => 'A49',
                'Name' => 'denier',
                'Description' => 'A unit of yarn density. One denier equals a mass of 1 gram per 9 kilometres of
            length.',
            ],
            115 => [
                'Id' => 'A5',
                'Name' => 'ampere square metre',
                'Description' => '',
            ],
            116 => [
                'Id' => 'A53',
                'Name' => 'electronvolt',
                'Description' => '',
            ],
            117 => [
                'Id' => 'A54',
                'Name' => 'electronvolt per metre',
                'Description' => '',
            ],
            118 => [
                'Id' => 'A55',
                'Name' => 'electronvolt square metre',
                'Description' => '',
            ],
            119 => [
                'Id' => 'A56',
                'Name' => 'electronvolt square metre per kilogram',
                'Description' => '',
            ],
            120 => [
                'Id' => 'A59',
                'Name' => '8-part cloud cover',
                'Description' => 'A unit of count defining the number of eighth-parts as a measure of the
            celestial dome cloud coverage. Synonym: OKTA , OCTA',
            ],
            121 => [
                'Id' => 'A6',
                'Name' => 'ampere per square metre kelvin squared',
                'Description' => '',
            ],
            122 => [
                'Id' => 'A68',
                'Name' => 'exajoule',
                'Description' => '',
            ],
            123 => [
                'Id' => 'A69',
                'Name' => 'farad per metre',
                'Description' => '',
            ],
            124 => [
                'Id' => 'A7',
                'Name' => 'ampere per square millimetre',
                'Description' => '',
            ],
            125 => [
                'Id' => 'A70',
                'Name' => 'femtojoule',
                'Description' => '',
            ],
            126 => [
                'Id' => 'A71',
                'Name' => 'femtometre',
                'Description' => '',
            ],
            127 => [
                'Id' => 'A73',
                'Name' => 'foot per second squared',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk6(): array
    {
        return [
            128 => [
                'Id' => 'A74',
                'Name' => 'foot pound-force per second',
                'Description' => '',
            ],
            129 => [
                'Id' => 'A75',
                'Name' => 'freight ton',
                'Description' => 'A unit of information typically used for billing purposes, defined as either
            the number of metric tons or the number of cubic metres, whichever is the
            larger.',
            ],
            130 => [
                'Id' => 'A76',
                'Name' => 'gal',
                'Description' => '',
            ],
            131 => [
                'Id' => 'A8',
                'Name' => 'ampere second',
                'Description' => '',
            ],
            132 => [
                'Id' => 'A84',
                'Name' => 'gigacoulomb per cubic metre',
                'Description' => '',
            ],
            133 => [
                'Id' => 'A85',
                'Name' => 'gigaelectronvolt',
                'Description' => '',
            ],
            134 => [
                'Id' => 'A86',
                'Name' => 'gigahertz',
                'Description' => '',
            ],
            135 => [
                'Id' => 'A87',
                'Name' => 'gigaohm',
                'Description' => '',
            ],
            136 => [
                'Id' => 'A88',
                'Name' => 'gigaohm metre',
                'Description' => '',
            ],
            137 => [
                'Id' => 'A89',
                'Name' => 'gigapascal',
                'Description' => '',
            ],
            138 => [
                'Id' => 'A9',
                'Name' => 'rate',
                'Description' => 'A unit of quantity expressed as a rate for usage of a facility or
            service.',
            ],
            139 => [
                'Id' => 'A90',
                'Name' => 'gigawatt',
                'Description' => '',
            ],
            140 => [
                'Id' => 'A91',
                'Name' => 'gon',
                'Description' => 'Synonym: grade',
            ],
            141 => [
                'Id' => 'A93',
                'Name' => 'gram per cubic metre',
                'Description' => '',
            ],
            142 => [
                'Id' => 'A94',
                'Name' => 'gram per mole',
                'Description' => '',
            ],
            143 => [
                'Id' => 'A95',
                'Name' => 'gray',
                'Description' => '',
            ],
            144 => [
                'Id' => 'A96',
                'Name' => 'gray per second',
                'Description' => '',
            ],
            145 => [
                'Id' => 'A97',
                'Name' => 'hectopascal',
                'Description' => '',
            ],
            146 => [
                'Id' => 'A98',
                'Name' => 'henry per metre',
                'Description' => '',
            ],
            147 => [
                'Id' => 'A99',
                'Name' => 'bit',
                'Description' => 'A unit of information equal to one binary digit.',
            ],
            148 => [
                'Id' => 'AA',
                'Name' => 'ball',
                'Description' => 'A unit of count defining the number of balls (ball: object formed in the shape
            of sphere).',
            ],
            149 => [
                'Id' => 'AB',
                'Name' => 'bulk pack',
                'Description' => 'A unit of count defining the number of items per bulk pack.',
            ],
            150 => [
                'Id' => 'ACR',
                'Name' => 'acre',
                'Description' => '',
            ],
            151 => [
                'Id' => 'ACT',
                'Name' => 'activity',
                'Description' => 'A unit of count defining the number of activities (activity: a unit of work or
            action).',
            ],
            152 => [
                'Id' => 'AD',
                'Name' => 'byte',
                'Description' => 'A unit of information equal to 8 bits.',
            ],
            153 => [
                'Id' => 'AE',
                'Name' => 'ampere per metre',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk7(): array
    {
        return [
            154 => [
                'Id' => 'AH',
                'Name' => 'additional minute',
                'Description' => 'A unit of time defining the number of minutes in addition to the referenced
            minutes.',
            ],
            155 => [
                'Id' => 'AI',
                'Name' => 'average minute per call',
                'Description' => 'A unit of count defining the number of minutes for the average interval of a
            call.',
            ],
            156 => [
                'Id' => 'AK',
                'Name' => 'fathom',
                'Description' => '',
            ],
            157 => [
                'Id' => 'AL',
                'Name' => 'access line',
                'Description' => 'A unit of count defining the number of telephone access lines.',
            ],
            158 => [
                'Id' => 'AMH',
                'Name' => 'ampere hour',
                'Description' => 'A unit of electric charge defining the amount of charge accumulated by a steady
            flow of one ampere for one hour.',
            ],
            159 => [
                'Id' => 'AMP',
                'Name' => 'ampere',
                'Description' => '',
            ],
            160 => [
                'Id' => 'ANN',
                'Name' => 'year',
                'Description' => 'Unit of time equal to 365,25 days. Synonym: Julian year',
            ],
            161 => [
                'Id' => 'APZ',
                'Name' => 'troy ounce or apothecary ounce',
                'Description' => '',
            ],
            162 => [
                'Id' => 'AQ',
                'Name' => 'anti-hemophilic factor (AHF) unit',
                'Description' => 'A unit of measure for blood potency (US).',
            ],
            163 => [
                'Id' => 'AS',
                'Name' => 'assortment',
                'Description' => 'A unit of count defining the number of assortments (assortment: set of items
            grouped in a mixed collection).',
            ],
            164 => [
                'Id' => 'ASM',
                'Name' => 'alcoholic strength by mass',
                'Description' => 'A unit of mass defining the alcoholic strength of a liquid.',
            ],
            165 => [
                'Id' => 'ASU',
                'Name' => 'alcoholic strength by volume',
                'Description' => 'A unit of volume defining the alcoholic strength of a liquid (e.g. spirit,
            wine, beer, etc), often at a specific temperature.',
            ],
            166 => [
                'Id' => 'ATM',
                'Name' => 'standard atmosphere',
                'Description' => '',
            ],
            167 => [
                'Id' => 'AWG',
                'Name' => 'american wire gauge',
                'Description' => 'A unit of distance used for measuring the diameter of small tubes or wires such
            as the outer diameter of hypotermic or suture needles.',
            ],
            168 => [
                'Id' => 'AY',
                'Name' => 'assembly',
                'Description' => 'A unit of count defining the number of assemblies (assembly: items that consist
            of component parts).',
            ],
            169 => [
                'Id' => 'AZ',
                'Name' => 'British thermal unit (international table) per pound',
                'Description' => '',
            ],
            170 => [
                'Id' => 'B1',
                'Name' => 'barrel (US) per day',
                'Description' => '',
            ],
            171 => [
                'Id' => 'B10',
                'Name' => 'bit per second',
                'Description' => 'A unit of information equal to one binary digit per second.',
            ],
            172 => [
                'Id' => 'B11',
                'Name' => 'joule per kilogram kelvin',
                'Description' => '',
            ],
            173 => [
                'Id' => 'B12',
                'Name' => 'joule per metre',
                'Description' => '',
            ],
            174 => [
                'Id' => 'B13',
                'Name' => 'joule per square metre',
                'Description' => 'Synonym: joule per metre squared',
            ],
            175 => [
                'Id' => 'B14',
                'Name' => 'joule per metre to the fourth power',
                'Description' => '',
            ],
            176 => [
                'Id' => 'B15',
                'Name' => 'joule per mole',
                'Description' => '',
            ],
            177 => [
                'Id' => 'B16',
                'Name' => 'joule per mole kelvin',
                'Description' => '',
            ],
            178 => [
                'Id' => 'B17',
                'Name' => 'credit',
                'Description' => 'A unit of count defining the number of entries made to the credit side of an
            account.',
            ],
        ];
    }

    protected function getUNECERec2011eChunk8(): array
    {
        return [
            179 => [
                'Id' => 'B18',
                'Name' => 'joule second',
                'Description' => '',
            ],
            180 => [
                'Id' => 'B19',
                'Name' => 'digit',
                'Description' => 'A unit of information defining the quantity of numerals used to form a
            number.',
            ],
            181 => [
                'Id' => 'B20',
                'Name' => 'joule square metre per kilogram',
                'Description' => '',
            ],
            182 => [
                'Id' => 'B21',
                'Name' => 'kelvin per watt',
                'Description' => '',
            ],
            183 => [
                'Id' => 'B22',
                'Name' => 'kiloampere',
                'Description' => '',
            ],
            184 => [
                'Id' => 'B23',
                'Name' => 'kiloampere per square metre',
                'Description' => '',
            ],
            185 => [
                'Id' => 'B24',
                'Name' => 'kiloampere per metre',
                'Description' => '',
            ],
            186 => [
                'Id' => 'B25',
                'Name' => 'kilobecquerel per kilogram',
                'Description' => '',
            ],
            187 => [
                'Id' => 'B26',
                'Name' => 'kilocoulomb',
                'Description' => '',
            ],
            188 => [
                'Id' => 'B27',
                'Name' => 'kilocoulomb per cubic metre',
                'Description' => '',
            ],
            189 => [
                'Id' => 'B28',
                'Name' => 'kilocoulomb per square metre',
                'Description' => '',
            ],
            190 => [
                'Id' => 'B29',
                'Name' => 'kiloelectronvolt',
                'Description' => '',
            ],
            191 => [
                'Id' => 'B3',
                'Name' => 'batting pound',
                'Description' => 'A unit of mass defining the number of pounds of wadded fibre.',
            ],
            192 => [
                'Id' => 'B30',
                'Name' => 'gibibit',
                'Description' => 'A unit of information equal to 2³⁰ bits (binary digits).',
            ],
            193 => [
                'Id' => 'B31',
                'Name' => 'kilogram metre per second',
                'Description' => '',
            ],
            194 => [
                'Id' => 'B32',
                'Name' => 'kilogram metre squared',
                'Description' => '',
            ],
            195 => [
                'Id' => 'B33',
                'Name' => 'kilogram metre squared per second',
                'Description' => '',
            ],
            196 => [
                'Id' => 'B34',
                'Name' => 'kilogram per cubic decimetre',
                'Description' => '',
            ],
            197 => [
                'Id' => 'B35',
                'Name' => 'kilogram per litre',
                'Description' => '',
            ],
            198 => [
                'Id' => 'B4',
                'Name' => 'barrel, imperial',
                'Description' => 'A unit of volume used to measure beer. One beer barrel equals 36 imperial
            gallons.',
            ],
            199 => [
                'Id' => 'B41',
                'Name' => 'kilojoule per kelvin',
                'Description' => '',
            ],
            200 => [
                'Id' => 'B42',
                'Name' => 'kilojoule per kilogram',
                'Description' => '',
            ],
            201 => [
                'Id' => 'B43',
                'Name' => 'kilojoule per kilogram kelvin',
                'Description' => '',
            ],
            202 => [
                'Id' => 'B44',
                'Name' => 'kilojoule per mole',
                'Description' => '',
            ],
            203 => [
                'Id' => 'B45',
                'Name' => 'kilomole',
                'Description' => '',
            ],
            204 => [
                'Id' => 'B46',
                'Name' => 'kilomole per cubic metre',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk9(): array
    {
        return [
            205 => [
                'Id' => 'B47',
                'Name' => 'kilonewton',
                'Description' => '',
            ],
            206 => [
                'Id' => 'B48',
                'Name' => 'kilonewton metre',
                'Description' => '',
            ],
            207 => [
                'Id' => 'B49',
                'Name' => 'kiloohm',
                'Description' => '',
            ],
            208 => [
                'Id' => 'B50',
                'Name' => 'kiloohm metre',
                'Description' => '',
            ],
            209 => [
                'Id' => 'B52',
                'Name' => 'kilosecond',
                'Description' => '',
            ],
            210 => [
                'Id' => 'B53',
                'Name' => 'kilosiemens',
                'Description' => '',
            ],
            211 => [
                'Id' => 'B54',
                'Name' => 'kilosiemens per metre',
                'Description' => '',
            ],
            212 => [
                'Id' => 'B55',
                'Name' => 'kilovolt per metre',
                'Description' => '',
            ],
            213 => [
                'Id' => 'B56',
                'Name' => 'kiloweber per metre',
                'Description' => '',
            ],
            214 => [
                'Id' => 'B57',
                'Name' => 'light year',
                'Description' => 'A unit of length defining the distance that light travels in a vacuum in one
            year.',
            ],
            215 => [
                'Id' => 'B58',
                'Name' => 'litre per mole',
                'Description' => '',
            ],
            216 => [
                'Id' => 'B59',
                'Name' => 'lumen hour',
                'Description' => '',
            ],
            217 => [
                'Id' => 'B60',
                'Name' => 'lumen per square metre',
                'Description' => '',
            ],
            218 => [
                'Id' => 'B61',
                'Name' => 'lumen per watt',
                'Description' => '',
            ],
            219 => [
                'Id' => 'B62',
                'Name' => 'lumen second',
                'Description' => '',
            ],
            220 => [
                'Id' => 'B63',
                'Name' => 'lux hour',
                'Description' => '',
            ],
            221 => [
                'Id' => 'B64',
                'Name' => 'lux second',
                'Description' => '',
            ],
            222 => [
                'Id' => 'B66',
                'Name' => 'megaampere per square metre',
                'Description' => '',
            ],
            223 => [
                'Id' => 'B67',
                'Name' => 'megabecquerel per kilogram',
                'Description' => '',
            ],
            224 => [
                'Id' => 'B68',
                'Name' => 'gigabit',
                'Description' => 'A unit of information equal to 10⁹ bits (binary digits).',
            ],
            225 => [
                'Id' => 'B69',
                'Name' => 'megacoulomb per cubic metre',
                'Description' => '',
            ],
            226 => [
                'Id' => 'B7',
                'Name' => 'cycle',
                'Description' => 'A unit of count defining the number of cycles (cycle: a recurrent period of
            definite duration).',
            ],
            227 => [
                'Id' => 'B70',
                'Name' => 'megacoulomb per square metre',
                'Description' => '',
            ],
            228 => [
                'Id' => 'B71',
                'Name' => 'megaelectronvolt',
                'Description' => '',
            ],
            229 => [
                'Id' => 'B72',
                'Name' => 'megagram per cubic metre',
                'Description' => '',
            ],
            230 => [
                'Id' => 'B73',
                'Name' => 'meganewton',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk10(): array
    {
        return [
            231 => [
                'Id' => 'B74',
                'Name' => 'meganewton metre',
                'Description' => '',
            ],
            232 => [
                'Id' => 'B75',
                'Name' => 'megaohm',
                'Description' => '',
            ],
            233 => [
                'Id' => 'B76',
                'Name' => 'megaohm metre',
                'Description' => '',
            ],
            234 => [
                'Id' => 'B77',
                'Name' => 'megasiemens per metre',
                'Description' => '',
            ],
            235 => [
                'Id' => 'B78',
                'Name' => 'megavolt',
                'Description' => '',
            ],
            236 => [
                'Id' => 'B79',
                'Name' => 'megavolt per metre',
                'Description' => '',
            ],
            237 => [
                'Id' => 'B8',
                'Name' => 'joule per cubic metre',
                'Description' => '',
            ],
            238 => [
                'Id' => 'B80',
                'Name' => 'gigabit per second',
                'Description' => 'A unit of information equal to 10⁹ bits (binary digits) per
            second.',
            ],
            239 => [
                'Id' => 'B81',
                'Name' => 'reciprocal metre squared reciprocal second',
                'Description' => '',
            ],
            240 => [
                'Id' => 'B82',
                'Name' => 'inch per linear foot',
                'Description' => 'A unit of length defining the number of inches per linear foot.',
            ],
            241 => [
                'Id' => 'B83',
                'Name' => 'metre to the fourth power',
                'Description' => '',
            ],
            242 => [
                'Id' => 'B84',
                'Name' => 'microampere',
                'Description' => '',
            ],
            243 => [
                'Id' => 'B85',
                'Name' => 'microbar',
                'Description' => '',
            ],
            244 => [
                'Id' => 'B86',
                'Name' => 'microcoulomb',
                'Description' => '',
            ],
            245 => [
                'Id' => 'B87',
                'Name' => 'microcoulomb per cubic metre',
                'Description' => '',
            ],
            246 => [
                'Id' => 'B88',
                'Name' => 'microcoulomb per square metre',
                'Description' => '',
            ],
            247 => [
                'Id' => 'B89',
                'Name' => 'microfarad per metre',
                'Description' => '',
            ],
            248 => [
                'Id' => 'B90',
                'Name' => 'microhenry',
                'Description' => '',
            ],
            249 => [
                'Id' => 'B91',
                'Name' => 'microhenry per metre',
                'Description' => '',
            ],
            250 => [
                'Id' => 'B92',
                'Name' => 'micronewton',
                'Description' => '',
            ],
            251 => [
                'Id' => 'B93',
                'Name' => 'micronewton metre',
                'Description' => '',
            ],
            252 => [
                'Id' => 'B94',
                'Name' => 'microohm',
                'Description' => '',
            ],
            253 => [
                'Id' => 'B95',
                'Name' => 'microohm metre',
                'Description' => '',
            ],
            254 => [
                'Id' => 'B96',
                'Name' => 'micropascal',
                'Description' => '',
            ],
            255 => [
                'Id' => 'B97',
                'Name' => 'microradian',
                'Description' => '',
            ],
            256 => [
                'Id' => 'B98',
                'Name' => 'microsecond',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk11(): array
    {
        return [
            257 => [
                'Id' => 'B99',
                'Name' => 'microsiemens',
                'Description' => '',
            ],
            258 => [
                'Id' => 'BAR',
                'Name' => 'bar [unit of pressure]',
                'Description' => '',
            ],
            259 => [
                'Id' => 'BB',
                'Name' => 'base box',
                'Description' => 'A unit of area of 112 sheets of tin mil products (tin plate, tin free steel or
            black plate) 14 by 20 inches, or 31,360 square inches.',
            ],
            260 => [
                'Id' => 'BFT',
                'Name' => 'board foot',
                'Description' => 'A unit of volume defining the number of cords (cord: a stack of firewood of 128
            cubic feet).',
            ],
            261 => [
                'Id' => 'BHP',
                'Name' => 'brake horse power',
                'Description' => '',
            ],
            262 => [
                'Id' => 'BIL',
                'Name' => 'billion (EUR)',
                'Description' => 'Synonym: trillion (US)',
            ],
            263 => [
                'Id' => 'BLD',
                'Name' => 'dry barrel (US)',
                'Description' => '',
            ],
            264 => [
                'Id' => 'BLL',
                'Name' => 'barrel (US)',
                'Description' => '',
            ],
            265 => [
                'Id' => 'BP',
                'Name' => 'hundred board foot',
                'Description' => 'A unit of volume equal to one hundred board foot.',
            ],
            266 => [
                'Id' => 'BPM',
                'Name' => 'beats per minute',
                'Description' => 'The number of beats per minute.',
            ],
            267 => [
                'Id' => 'BQL',
                'Name' => 'becquerel',
                'Description' => '',
            ],
            268 => [
                'Id' => 'BTU',
                'Name' => 'British thermal unit (international table)',
                'Description' => '',
            ],
            269 => [
                'Id' => 'BUA',
                'Name' => 'bushel (US)',
                'Description' => '',
            ],
            270 => [
                'Id' => 'BUI',
                'Name' => 'bushel (UK)',
                'Description' => '',
            ],
            271 => [
                'Id' => 'C0',
                'Name' => 'call',
                'Description' => 'A unit of count defining the number of calls (call: communication session or
            visitation).',
            ],
            272 => [
                'Id' => 'C10',
                'Name' => 'millifarad',
                'Description' => '',
            ],
            273 => [
                'Id' => 'C11',
                'Name' => 'milligal',
                'Description' => '',
            ],
            274 => [
                'Id' => 'C12',
                'Name' => 'milligram per metre',
                'Description' => '',
            ],
            275 => [
                'Id' => 'C13',
                'Name' => 'milligray',
                'Description' => '',
            ],
            276 => [
                'Id' => 'C14',
                'Name' => 'millihenry',
                'Description' => '',
            ],
            277 => [
                'Id' => 'C15',
                'Name' => 'millijoule',
                'Description' => '',
            ],
            278 => [
                'Id' => 'C16',
                'Name' => 'millimetre per second',
                'Description' => '',
            ],
            279 => [
                'Id' => 'C17',
                'Name' => 'millimetre squared per second',
                'Description' => '',
            ],
            280 => [
                'Id' => 'C18',
                'Name' => 'millimole',
                'Description' => '',
            ],
            281 => [
                'Id' => 'C19',
                'Name' => 'mole per kilogram',
                'Description' => '',
            ],
            282 => [
                'Id' => 'C20',
                'Name' => 'millinewton',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk12(): array
    {
        return [
            283 => [
                'Id' => 'C21',
                'Name' => 'kibibit',
                'Description' => 'A unit of information equal to 2¹⁰ (1024) bits (binary digits).',
            ],
            284 => [
                'Id' => 'C22',
                'Name' => 'millinewton per metre',
                'Description' => '',
            ],
            285 => [
                'Id' => 'C23',
                'Name' => 'milliohm metre',
                'Description' => '',
            ],
            286 => [
                'Id' => 'C24',
                'Name' => 'millipascal second',
                'Description' => '',
            ],
            287 => [
                'Id' => 'C25',
                'Name' => 'milliradian',
                'Description' => '',
            ],
            288 => [
                'Id' => 'C26',
                'Name' => 'millisecond',
                'Description' => '',
            ],
            289 => [
                'Id' => 'C27',
                'Name' => 'millisiemens',
                'Description' => '',
            ],
            290 => [
                'Id' => 'C28',
                'Name' => 'millisievert',
                'Description' => '',
            ],
            291 => [
                'Id' => 'C29',
                'Name' => 'millitesla',
                'Description' => '',
            ],
            292 => [
                'Id' => 'C3',
                'Name' => 'microvolt per metre',
                'Description' => '',
            ],
            293 => [
                'Id' => 'C30',
                'Name' => 'millivolt per metre',
                'Description' => '',
            ],
            294 => [
                'Id' => 'C31',
                'Name' => 'milliwatt',
                'Description' => '',
            ],
            295 => [
                'Id' => 'C32',
                'Name' => 'milliwatt per square metre',
                'Description' => '',
            ],
            296 => [
                'Id' => 'C33',
                'Name' => 'milliweber',
                'Description' => '',
            ],
            297 => [
                'Id' => 'C34',
                'Name' => 'mole',
                'Description' => '',
            ],
            298 => [
                'Id' => 'C35',
                'Name' => 'mole per cubic decimetre',
                'Description' => '',
            ],
            299 => [
                'Id' => 'C36',
                'Name' => 'mole per cubic metre',
                'Description' => '',
            ],
            300 => [
                'Id' => 'C37',
                'Name' => 'kilobit',
                'Description' => 'A unit of information equal to 10³ (1000) bits (binary digits).',
            ],
            301 => [
                'Id' => 'C38',
                'Name' => 'mole per litre',
                'Description' => '',
            ],
            302 => [
                'Id' => 'C39',
                'Name' => 'nanoampere',
                'Description' => '',
            ],
            303 => [
                'Id' => 'C40',
                'Name' => 'nanocoulomb',
                'Description' => '',
            ],
            304 => [
                'Id' => 'C41',
                'Name' => 'nanofarad',
                'Description' => '',
            ],
            305 => [
                'Id' => 'C42',
                'Name' => 'nanofarad per metre',
                'Description' => '',
            ],
            306 => [
                'Id' => 'C43',
                'Name' => 'nanohenry',
                'Description' => '',
            ],
            307 => [
                'Id' => 'C44',
                'Name' => 'nanohenry per metre',
                'Description' => '',
            ],
            308 => [
                'Id' => 'C45',
                'Name' => 'nanometre',
                'Description' => '',
            ],
            309 => [
                'Id' => 'C46',
                'Name' => 'nanoohm metre',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk13(): array
    {
        return [
            310 => [
                'Id' => 'C47',
                'Name' => 'nanosecond',
                'Description' => '',
            ],
            311 => [
                'Id' => 'C48',
                'Name' => 'nanotesla',
                'Description' => '',
            ],
            312 => [
                'Id' => 'C49',
                'Name' => 'nanowatt',
                'Description' => '',
            ],
            313 => [
                'Id' => 'C50',
                'Name' => 'neper',
                'Description' => '',
            ],
            314 => [
                'Id' => 'C51',
                'Name' => 'neper per second',
                'Description' => '',
            ],
            315 => [
                'Id' => 'C52',
                'Name' => 'picometre',
                'Description' => '',
            ],
            316 => [
                'Id' => 'C53',
                'Name' => 'newton metre second',
                'Description' => '',
            ],
            317 => [
                'Id' => 'C54',
                'Name' => 'newton metre squared per kilogram squared',
                'Description' => '',
            ],
            318 => [
                'Id' => 'C55',
                'Name' => 'newton per square metre',
                'Description' => '',
            ],
            319 => [
                'Id' => 'C56',
                'Name' => 'newton per square millimetre',
                'Description' => '',
            ],
            320 => [
                'Id' => 'C57',
                'Name' => 'newton second',
                'Description' => '',
            ],
            321 => [
                'Id' => 'C58',
                'Name' => 'newton second per metre',
                'Description' => '',
            ],
            322 => [
                'Id' => 'C59',
                'Name' => 'octave',
                'Description' => 'A unit used in music to describe the ratio in frequency between
            notes.',
            ],
            323 => [
                'Id' => 'C60',
                'Name' => 'ohm centimetre',
                'Description' => '',
            ],
            324 => [
                'Id' => 'C61',
                'Name' => 'ohm metre',
                'Description' => '',
            ],
            325 => [
                'Id' => 'C62',
                'Name' => 'one',
                'Description' => 'Synonym: unit',
            ],
            326 => [
                'Id' => 'C63',
                'Name' => 'parsec',
                'Description' => '',
            ],
            327 => [
                'Id' => 'C64',
                'Name' => 'pascal per kelvin',
                'Description' => '',
            ],
            328 => [
                'Id' => 'C65',
                'Name' => 'pascal second',
                'Description' => '',
            ],
            329 => [
                'Id' => 'C66',
                'Name' => 'pascal second per cubic metre',
                'Description' => '',
            ],
            330 => [
                'Id' => 'C67',
                'Name' => 'pascal second per metre',
                'Description' => '',
            ],
            331 => [
                'Id' => 'C68',
                'Name' => 'petajoule',
                'Description' => '',
            ],
            332 => [
                'Id' => 'C69',
                'Name' => 'phon',
                'Description' => 'A unit of subjective sound loudness. A sound has loudness p phons if it seems
            to the listener to be equal in loudness to the sound of a pure tone of frequency 1
            kilohertz and strength p decibels.',
            ],
            333 => [
                'Id' => 'C7',
                'Name' => 'centipoise',
                'Description' => '',
            ],
            334 => [
                'Id' => 'C70',
                'Name' => 'picoampere',
                'Description' => '',
            ],
            335 => [
                'Id' => 'C71',
                'Name' => 'picocoulomb',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk14(): array
    {
        return [
            336 => [
                'Id' => 'C72',
                'Name' => 'picofarad per metre',
                'Description' => '',
            ],
            337 => [
                'Id' => 'C73',
                'Name' => 'picohenry',
                'Description' => '',
            ],
            338 => [
                'Id' => 'C74',
                'Name' => 'kilobit per second',
                'Description' => 'A unit of information equal to 10³ (1000) bits (binary digits) per
            second.',
            ],
            339 => [
                'Id' => 'C75',
                'Name' => 'picowatt',
                'Description' => '',
            ],
            340 => [
                'Id' => 'C76',
                'Name' => 'picowatt per square metre',
                'Description' => '',
            ],
            341 => [
                'Id' => 'C78',
                'Name' => 'pound-force',
                'Description' => '',
            ],
            342 => [
                'Id' => 'C79',
                'Name' => 'kilovolt ampere hour',
                'Description' => 'A unit of accumulated energy of 1000 volt amperes over a period of one
            hour.',
            ],
            343 => [
                'Id' => 'C8',
                'Name' => 'millicoulomb per kilogram',
                'Description' => '',
            ],
            344 => [
                'Id' => 'C80',
                'Name' => 'rad',
                'Description' => '',
            ],
            345 => [
                'Id' => 'C81',
                'Name' => 'radian',
                'Description' => '',
            ],
            346 => [
                'Id' => 'C82',
                'Name' => 'radian square metre per mole',
                'Description' => '',
            ],
            347 => [
                'Id' => 'C83',
                'Name' => 'radian square metre per kilogram',
                'Description' => '',
            ],
            348 => [
                'Id' => 'C84',
                'Name' => 'radian per metre',
                'Description' => '',
            ],
            349 => [
                'Id' => 'C85',
                'Name' => 'reciprocal angstrom',
                'Description' => '',
            ],
            350 => [
                'Id' => 'C86',
                'Name' => 'reciprocal cubic metre',
                'Description' => '',
            ],
            351 => [
                'Id' => 'C87',
                'Name' => 'reciprocal cubic metre per second',
                'Description' => 'Synonym: reciprocal second per cubic metre',
            ],
            352 => [
                'Id' => 'C88',
                'Name' => 'reciprocal electron volt per cubic metre',
                'Description' => '',
            ],
            353 => [
                'Id' => 'C89',
                'Name' => 'reciprocal henry',
                'Description' => '',
            ],
            354 => [
                'Id' => 'C9',
                'Name' => 'coil group',
                'Description' => 'A unit of count defining the number of coil groups (coil group: groups of items
            arranged by lengths of those items placed in a joined sequence of concentric
            circles).',
            ],
            355 => [
                'Id' => 'C90',
                'Name' => 'reciprocal joule per cubic metre',
                'Description' => '',
            ],
            356 => [
                'Id' => 'C91',
                'Name' => 'reciprocal kelvin or kelvin to the power minus one',
                'Description' => '',
            ],
            357 => [
                'Id' => 'C92',
                'Name' => 'reciprocal metre',
                'Description' => '',
            ],
            358 => [
                'Id' => 'C93',
                'Name' => 'reciprocal square metre',
                'Description' => 'Synonym: reciprocal metre squared',
            ],
            359 => [
                'Id' => 'C94',
                'Name' => 'reciprocal minute',
                'Description' => '',
            ],
            360 => [
                'Id' => 'C95',
                'Name' => 'reciprocal mole',
                'Description' => '',
            ],
            361 => [
                'Id' => 'C96',
                'Name' => 'reciprocal pascal or pascal to the power minus one',
                'Description' => '',
            ],
        ];
    }

    protected function getUNECERec2011eChunk15(): array
    {
        return [
            362 => [
                'Id' => 'C97',
                'Name' => 'reciprocal second',
                'Description' => '',
            ],
            363 => [
                'Id' => 'C99',
                'Name' => 'reciprocal second per metre squared',
                'Description' => '',
            ],
            364 => [
                'Id' => 'CCT',
                'Name' => 'carrying capacity in metric ton',
                'Description' => 'A unit of mass defining the carrying capacity, expressed as the number of
            metric tons.',
            ],
            365 => [
                'Id' => 'CDL',
                'Name' => 'candela',
                'Description' => '',
            ],
            366 => [
                'Id' => 'CEL',
                'Name' => 'degree Celsius',
                'Description' => 'Refer ISO 80000-5 (Quantities and units — Part 5: Thermodynamics)',
            ],
            367 => [
                'Id' => 'CEN',
                'Name' => 'hundred',
                'Description' => 'A unit of count defining the number of units in multiples of 100.',
            ],
            368 => [
                'Id' => 'CG',
                'Name' => 'card',
                'Description' => 'A unit of count defining the number of units of card (card: thick stiff paper
            or cardboard).',
            ],
            369 => [
                'Id' => 'CGM',
                'Name' => 'centigram',
                'Description' => '',
            ],
            370 => [
                'Id' => 'CKG',
                'Name' => 'coulomb per kilogram',
                'Description' => '',
            ],
            371 => [
                'Id' => 'CLF',
                'Name' => 'hundred leave',
                'Description' => 'A unit of count defining the number of leaves, expressed in units of one
            hundred leaves.',
            ],
            372 => [
                'Id' => 'CLT',
                'Name' => 'centilitre',
                'Description' => '',
            ],
            373 => [
                'Id' => 'CMK',
                'Name' => 'square centimetre',
                'Description' => '',
            ],
            374 => [
                'Id' => 'CMQ',
                'Name' => 'cubic centimetre',
                'Description' => '',
            ],
            375 => [
                'Id' => 'CMT',
                'Name' => 'centimetre',
                'Description' => '',
            ],
            376 => [
                'Id' => 'CNP',
                'Name' => 'hundred pack',
                'Description' => 'A unit of count defining the number of hundred-packs (hundred-pack: set of one
            hundred items packaged together).',
            ],
            377 => [
                'Id' => 'CNT',
                'Name' => 'cental (UK)',
                'Description' => 'A unit of mass equal to one hundred weight (US).',
            ],
            378 => [
                'Id' => 'COU',
                'Name' => 'coulomb',
                'Description' => '',
            ],
            379 => [
                'Id' => 'CTG',
                'Name' => 'content gram',
                'Description' => 'A unit of mass defining the number of grams of a named item in a
            product.',
            ],
            380 => [
                'Id' => 'CTM',
                'Name' => 'metric carat',
                'Description' => '',
            ],
            381 => [
                'Id' => 'CTN',
                'Name' => 'content ton (metric)',
                'Description' => 'A unit of mass defining the number of metric tons of a named item in a
            product.',
            ],
            382 => [
                'Id' => 'CUR',
                'Name' => 'curie',
                'Description' => '',
            ],
            383 => [
                'Id' => 'CWA',
                'Name' => 'hundred pound (cwt) / hundred weight (US)',
                'Description' => '',
            ],
            384 => [
                'Id' => 'CWI',
                'Name' => 'hundred weight (UK)',
                'Description' => '',
            ],
            385 => [
                'Id' => 'D03',
                'Name' => 'kilowatt hour per hour',
                'Description' => 'A unit of accumulated energy of a thousand watts over a period of one
            hour.',
            ],
            386 => [
                'Id' => 'D04',
                'Name' => 'lot [unit of weight]',
                'Description' => 'A unit of weight equal to about 1/2 ounce or 15 grams.',
            ],
        ];
    }

    protected function getUNECERec2011eChunk16(): array
    {
        return [
            387 => [
                'Id' => 'D1',
                'Name' => 'reciprocal second per steradian',
                'Description' => '',
            ],
            388 => [
                'Id' => 'D10',
                'Name' => 'siemens per metre',
                'Description' => '',
            ],
            389 => [
                'Id' => 'D11',
                'Name' => 'mebibit',
                'Description' => 'A unit of information equal to 2²⁰ (1048576) bits (binary
            digits).',
            ],
            390 => [
                'Id' => 'D12',
                'Name' => 'siemens square metre per mole',
                'Description' => '',
            ],
            391 => [
                'Id' => 'D13',
                'Name' => 'sievert',
                'Description' => '',
            ],
            392 => [
                'Id' => 'D15',
                'Name' => 'sone',
                'Description' => 'A unit of subjective sound loudness. One sone is the loudness of a pure tone of
            frequency one kilohertz and strength 40 decibels.',
            ],
            393 => [
                'Id' => 'D16',
                'Name' => 'square centimetre per erg',
                'Description' => '',
            ],
            394 => [
                'Id' => 'D17',
                'Name' => 'square centimetre per steradian erg',
                'Description' => '',
            ],
            395 => [
                'Id' => 'D18',
                'Name' => 'metre kelvin',
                'Description' => '',
            ],
            396 => [
                'Id' => 'D19',
                'Name' => 'square metre kelvin per watt',
                'Description' => '',
            ],
            397 => [
                'Id' => 'D2',
                'Name' => 'reciprocal second per steradian metre squared',
                'Description' => '',
            ],
            398 => [
                'Id' => 'D20',
                'Name' => 'square metre per joule',
                'Description' => '',
            ],
            399 => [
                'Id' => 'D21',
                'Name' => 'square metre per kilogram',
                'Description' => '',
            ],
            400 => [
                'Id' => 'D22',
                'Name' => 'square metre per mole',
                'Description' => '',
            ],
            401 => [
                'Id' => 'D23',
                'Name' => 'pen gram (protein)',
                'Description' => 'A unit of count defining the number of grams of amino acid prescribed for
            parenteral/enteral therapy.',
            ],
            402 => [
                'Id' => 'D24',
                'Name' => 'square metre per steradian',
                'Description' => '',
            ],
            403 => [
                'Id' => 'D25',
                'Name' => 'square metre per steradian joule',
                'Description' => '',
            ],
            404 => [
                'Id' => 'D26',
                'Name' => 'square metre per volt second',
                'Description' => '',
            ],
            405 => [
                'Id' => 'D27',
                'Name' => 'steradian',
                'Description' => '',
            ],
            406 => [
                'Id' => 'D29',
                'Name' => 'terahertz',
                'Description' => '',
            ],
            407 => [
                'Id' => 'D30',
                'Name' => 'terajoule',
                'Description' => '',
            ],
            408 => [
                'Id' => 'D31',
                'Name' => 'terawatt',
                'Description' => '',
            ],
            409 => [
                'Id' => 'D32',
                'Name' => 'terawatt hour',
                'Description' => '',
            ],
            410 => [
                'Id' => 'D33',
                'Name' => 'tesla',
                'Description' => '',
            ],
            411 => [
                'Id' => 'D34',
                'Name' => 'tex',
                'Description' => 'A unit of yarn density. One decitex equals a mass of 1 gram per 1 kilometre of
            length.',
            ],
            412 => [
                'Id' => 'D36',
                'Name' => 'megabit',
                'Description' => 'A unit of information equal to 10⁶ (1000000) bits (binary
            digits).',
            ],
        ];
    }

    protected function getUNECERec2011eChunk17(): array
    {
        return [
            413 => [
                'Id' => 'D41',
                'Name' => 'tonne per cubic metre',
                'Description' => '',
            ],
            414 => [
                'Id' => 'D42',
                'Name' => 'tropical year',
                'Description' => '',
            ],
            415 => [
                'Id' => 'D43',
                'Name' => 'unified atomic mass unit',
                'Description' => '',
            ],
            416 => [
                'Id' => 'D44',
                'Name' => 'var',
                'Description' => 'The name of the unit is an acronym for volt-ampere-reactive.',
            ],
            417 => [
                'Id' => 'D45',
                'Name' => 'volt squared per kelvin squared',
                'Description' => '',
            ],
            418 => [
                'Id' => 'D46',
                'Name' => 'volt - ampere',
                'Description' => '',
            ],
            419 => [
                'Id' => 'D47',
                'Name' => 'volt per centimetre',
                'Description' => '',
            ],
            420 => [
                'Id' => 'D48',
                'Name' => 'volt per kelvin',
                'Description' => '',
            ],
            421 => [
                'Id' => 'D49',
                'Name' => 'millivolt per kelvin',
                'Description' => '',
            ],
            422 => [
                'Id' => 'D5',
                'Name' => 'kilogram per square centimetre',
                'Description' => '',
            ],
            423 => [
                'Id' => 'D50',
                'Name' => 'volt per metre',
                'Description' => '',
            ],
            424 => [
                'Id' => 'D51',
                'Name' => 'volt per millimetre',
                'Description' => '',
            ],
            425 => [
                'Id' => 'D52',
                'Name' => 'watt per kelvin',
                'Description' => '',
            ],
            426 => [
                'Id' => 'D53',
                'Name' => 'watt per metre kelvin',
                'Description' => '',
            ],
            427 => [
                'Id' => 'D54',
                'Name' => 'watt per square metre',
                'Description' => '',
            ],
            428 => [
                'Id' => 'D55',
                'Name' => 'watt per square metre kelvin',
                'Description' => '',
            ],
            429 => [
                'Id' => 'D56',
                'Name' => 'watt per square metre kelvin to the fourth power',
                'Description' => '',
            ],
            430 => [
                'Id' => 'D57',
                'Name' => 'watt per steradian',
                'Description' => '',
            ],
            431 => [
                'Id' => 'D58',
                'Name' => 'watt per steradian square metre',
                'Description' => '',
            ],
            432 => [
                'Id' => 'D59',
                'Name' => 'weber per metre',
                'Description' => '',
            ],
            433 => [
                'Id' => 'D6',
                'Name' => 'roentgen per second',
                'Description' => '',
            ],
            434 => [
                'Id' => 'D60',
                'Name' => 'weber per millimetre',
                'Description' => '',
            ],
            435 => [
                'Id' => 'D61',
                'Name' => 'minute [unit of angle]',
                'Description' => '',
            ],
            436 => [
                'Id' => 'D62',
                'Name' => 'second [unit of angle]',
                'Description' => '',
            ],
            437 => [
                'Id' => 'D63',
                'Name' => 'book',
                'Description' => 'A unit of count defining the number of books (book: set of items bound together
            or written document of a material whole).',
            ],
            438 => [
                'Id' => 'D65',
                'Name' => 'round',
                'Description' => 'A unit of count defining the number of rounds (round: A circular or cylindrical
            object).',
            ],
        ];
    }
}
