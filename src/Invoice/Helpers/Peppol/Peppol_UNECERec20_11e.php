<?php
declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

Class Peppol_UNECERec20_11e
{
    
public function getUNECERec20_11e() : array 
{
    //'CodeList' 
    //'Title' => 'Recommendation 20, including Recommendation 21 codes - prefixed with X (UN/ECE)',
    //'Identifier' => 'UNECERec20',
    //'Version' => 'Revision 11e',
    //'Agency' => 'UN/ECE',
   $array = [
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
      439 => [
        'Id' => 'D68',
        'Name' => 'number of words',
        'Description' => 'A unit of count defining the number of words.',
      ],
      440 => [
        'Id' => 'D69',
        'Name' => 'inch to the fourth power',
        'Description' => '',
      ],
      441 => [
        'Id' => 'D73',
        'Name' => 'joule square metre',
        'Description' => '',
      ],
      442 => [
        'Id' => 'D74',
        'Name' => 'kilogram per mole',
        'Description' => '',
      ],
      443 => [
        'Id' => 'D77',
        'Name' => 'megacoulomb',
        'Description' => '',
      ],
      444 => [
        'Id' => 'D78',
        'Name' => 'megajoule per second',
        'Description' => 'A unit of accumulated energy equal to one million joules per
            second.',
      ],
      445 => [
        'Id' => 'D80',
        'Name' => 'microwatt',
        'Description' => '',
      ],
      446 => [
        'Id' => 'D81',
        'Name' => 'microtesla',
        'Description' => '',
      ],
      447 => [
        'Id' => 'D82',
        'Name' => 'microvolt',
        'Description' => '',
      ],
      448 => [
        'Id' => 'D83',
        'Name' => 'millinewton metre',
        'Description' => '',
      ],
      449 => [
        'Id' => 'D85',
        'Name' => 'microwatt per square metre',
        'Description' => '',
      ],
      450 => [
        'Id' => 'D86',
        'Name' => 'millicoulomb',
        'Description' => '',
      ],
      451 => [
        'Id' => 'D87',
        'Name' => 'millimole per kilogram',
        'Description' => '',
      ],
      452 => [
        'Id' => 'D88',
        'Name' => 'millicoulomb per cubic metre',
        'Description' => '',
      ],
      453 => [
        'Id' => 'D89',
        'Name' => 'millicoulomb per square metre',
        'Description' => '',
      ],
      454 => [
        'Id' => 'D91',
        'Name' => 'rem',
        'Description' => '',
      ],
      455 => [
        'Id' => 'D93',
        'Name' => 'second per cubic metre',
        'Description' => '',
      ],
      456 => [
        'Id' => 'D94',
        'Name' => 'second per cubic metre radian',
        'Description' => '',
      ],
      457 => [
        'Id' => 'D95',
        'Name' => 'joule per gram',
        'Description' => '',
      ],
      458 => [
        'Id' => 'DAA',
        'Name' => 'decare',
        'Description' => '',
      ],
      459 => [
        'Id' => 'DAD',
        'Name' => 'ten day',
        'Description' => 'A unit of time defining the number of days in multiples of 10.',
      ],
      460 => [
        'Id' => 'DAY',
        'Name' => 'day',
        'Description' => '',
      ],
      461 => [
        'Id' => 'DB',
        'Name' => 'dry pound',
        'Description' => 'A unit of mass defining the number of pounds of a product, disregarding the
            water content of the product.',
      ],
      462 => [
        'Id' => 'DBM',
        'Name' => 'Decibel-milliwatts',
      ],
      463 => [
        'Id' => 'DBW',
        'Name' => 'Decibel watt',
      ],
      464 => [
        'Id' => 'DD',
        'Name' => 'degree [unit of angle]',
        'Description' => '',
      ],
      465 => [
        'Id' => 'DEC',
        'Name' => 'decade',
        'Description' => 'A unit of count defining the number of decades (decade: quantity equal to 10 or
            time equal to 10 years).',
      ],
      466 => [
        'Id' => 'DG',
        'Name' => 'decigram',
        'Description' => '',
      ],
      467 => [
        'Id' => 'DJ',
        'Name' => 'decagram',
        'Description' => '',
      ],
      468 => [
        'Id' => 'DLT',
        'Name' => 'decilitre',
        'Description' => '',
      ],
      469 => [
        'Id' => 'DMA',
        'Name' => 'cubic decametre',
        'Description' => '',
      ],
      470 => [
        'Id' => 'DMK',
        'Name' => 'square decimetre',
        'Description' => '',
      ],
      471 => [
        'Id' => 'DMO',
        'Name' => 'standard kilolitre',
        'Description' => 'A unit of volume defining the number of kilolitres of a product at a
            temperature of 15 degrees Celsius, especially in relation to hydrocarbon
            oils.',
      ],
      472 => [
        'Id' => 'DMQ',
        'Name' => 'cubic decimetre',
        'Description' => '',
      ],
      473 => [
        'Id' => 'DMT',
        'Name' => 'decimetre',
        'Description' => '',
      ],
      474 => [
        'Id' => 'DN',
        'Name' => 'decinewton metre',
        'Description' => '',
      ],
      475 => [
        'Id' => 'DPC',
        'Name' => 'dozen piece',
        'Description' => 'A unit of count defining the number of pieces in multiples of 12 (piece: a
            single item, article or exemplar).',
      ],
      476 => [
        'Id' => 'DPR',
        'Name' => 'dozen pair',
        'Description' => 'A unit of count defining the number of pairs in multiples of 12 (pair: item
            described by two\'s).',
      ],
      477 => [
        'Id' => 'DPT',
        'Name' => 'displacement tonnage',
        'Description' => 'A unit of mass defining the volume of sea water a ship displaces, expressed as
            the number of tons.',
      ],
      478 => [
        'Id' => 'DRA',
        'Name' => 'dram (US)',
        'Description' => 'Synonym: drachm (UK), troy dram',
      ],
      479 => [
        'Id' => 'DRI',
        'Name' => 'dram (UK)',
        'Description' => 'Synonym: avoirdupois dram',
      ],
      480 => [
        'Id' => 'DRL',
        'Name' => 'dozen roll',
        'Description' => 'A unit of count defining the number of rolls, expressed in twelve roll
            units.',
      ],
      481 => [
        'Id' => 'DT',
        'Name' => 'dry ton',
        'Description' => 'A unit of mass defining the number of tons of a product, disregarding the water
            content of the product.',
      ],
      482 => [
        'Id' => 'DTN',
        'Name' => 'decitonne',
        'Description' => 'Synonym: centner, metric 100 kg; quintal, metric 100 kg',
      ],
      483 => [
        'Id' => 'DWT',
        'Name' => 'pennyweight',
        'Description' => '',
      ],
      484 => [
        'Id' => 'DZN',
        'Name' => 'dozen',
        'Description' => 'A unit of count defining the number of units in multiples of 12.',
      ],
      485 => [
        'Id' => 'DZP',
        'Name' => 'dozen pack',
        'Description' => 'A unit of count defining the number of packs in multiples of 12 (pack: standard
            packaging unit).',
      ],
      486 => [
        'Id' => 'E01',
        'Name' => 'newton per square centimetre',
        'Description' => 'A measure of pressure expressed in newtons per square centimetre.',
      ],
      487 => [
        'Id' => 'E07',
        'Name' => 'megawatt hour per hour',
        'Description' => 'A unit of accumulated energy of a million watts over a period of one
            hour.',
      ],
      488 => [
        'Id' => 'E08',
        'Name' => 'megawatt per hertz',
        'Description' => 'A unit of energy expressed as the load change in million watts that will cause
            a frequency shift of one hertz.',
      ],
      489 => [
        'Id' => 'E09',
        'Name' => 'milliampere hour',
        'Description' => 'A unit of power load delivered at the rate of one thousandth of an ampere over
            a period of one hour.',
      ],
      490 => [
        'Id' => 'E10',
        'Name' => 'degree day',
        'Description' => 'A unit of measure used in meteorology and engineering to measure the demand for
            heating or cooling over a given period of days.',
      ],
      491 => [
        'Id' => 'E12',
        'Name' => 'mille',
        'Description' => 'A unit of count defining the number of cigarettes in units of
            1000.',
      ],
      492 => [
        'Id' => 'E14',
        'Name' => 'kilocalorie (international table)',
        'Description' => 'A unit of heat energy equal to one thousand calories.',
      ],
      493 => [
        'Id' => 'E15',
        'Name' => 'kilocalorie (thermochemical) per hour',
        'Description' => 'A unit of energy equal to one thousand calories per hour.',
      ],
      494 => [
        'Id' => 'E16',
        'Name' => 'million Btu(IT) per hour',
        'Description' => 'A unit of power equal to one million British thermal units per
            hour.',
      ],
      495 => [
        'Id' => 'E17',
        'Name' => 'cubic foot per second',
        'Description' => 'A unit of volume equal to one cubic foot passing a given point in a period of
            one second.',
      ],
      496 => [
        'Id' => 'E18',
        'Name' => 'tonne per hour',
        'Description' => 'A unit of weight or mass equal to one tonne per hour.',
      ],
      497 => [
        'Id' => 'E19',
        'Name' => 'ping',
        'Description' => 'A unit of area equal to 3.3 square metres.',
      ],
      498 => [
        'Id' => 'E20',
        'Name' => 'megabit per second',
        'Description' => 'A unit of information equal to 10⁶ (1000000) bits (binary digits) per
            second.',
      ],
      499 => [
        'Id' => 'E21',
        'Name' => 'shares',
        'Description' => 'A unit of count defining the number of shares (share: a total or portion of the
            parts into which a business entity’s capital is divided).',
      ],
      500 => [
        'Id' => 'E22',
        'Name' => 'TEU',
        'Description' => 'A unit of count defining the number of twenty-foot equivalent units (TEUs) as a
            measure of containerized cargo capacity.',
      ],
      501 => [
        'Id' => 'E23',
        'Name' => 'tyre',
        'Description' => 'A unit of count defining the number of tyres (a solid or air-filled covering
            placed around a wheel rim to form a soft contact with the road, absorb shock and provide
            traction).',
      ],
      502 => [
        'Id' => 'E25',
        'Name' => 'active unit',
        'Description' => 'A unit of count defining the number of active units within a
            substance.',
      ],
      503 => [
        'Id' => 'E27',
        'Name' => 'dose',
        'Description' => 'A unit of count defining the number of doses (dose: a definite quantity of a
            medicine or drug).',
      ],
      504 => [
        'Id' => 'E28',
        'Name' => 'air dry ton',
        'Description' => 'A unit of mass defining the number of tons of a product, disregarding the water
            content of the product.',
      ],
      505 => [
        'Id' => 'E30',
        'Name' => 'strand',
        'Description' => 'A unit of count defining the number of strands (strand: long, thin, flexible,
            single thread, strip of fibre, constituent filament or multiples of the same, twisted
            together).',
      ],
      506 => [
        'Id' => 'E31',
        'Name' => 'square metre per litre',
        'Description' => 'A unit of count defining the number of square metres per litre.',
      ],
      507 => [
        'Id' => 'E32',
        'Name' => 'litre per hour',
        'Description' => 'A unit of count defining the number of litres per hour.',
      ],
      508 => [
        'Id' => 'E33',
        'Name' => 'foot per thousand',
        'Description' => 'A unit of count defining the number of feet per thousand units.',
      ],
      509 => [
        'Id' => 'E34',
        'Name' => 'gigabyte',
        'Description' => 'A unit of information equal to 10⁹ bytes.',
      ],
      510 => [
        'Id' => 'E35',
        'Name' => 'terabyte',
        'Description' => 'A unit of information equal to 10¹² bytes.',
      ],
      511 => [
        'Id' => 'E36',
        'Name' => 'petabyte',
        'Description' => 'A unit of information equal to 10¹⁵ bytes.',
      ],
      512 => [
        'Id' => 'E37',
        'Name' => 'pixel',
        'Description' => 'A unit of count defining the number of pixels (pixel: picture
            element).',
      ],
      513 => [
        'Id' => 'E38',
        'Name' => 'megapixel',
        'Description' => 'A unit of count equal to 10⁶ (1000000) pixels (picture elements).',
      ],
      514 => [
        'Id' => 'E39',
        'Name' => 'dots per inch',
        'Description' => 'A unit of information defining the number of dots per linear inch as a measure
            of the resolution or sharpness of a graphic image.',
      ],
      515 => [
        'Id' => 'E4',
        'Name' => 'gross kilogram',
        'Description' => 'A unit of mass defining the total number of kilograms before
            deductions.',
      ],
      516 => [
        'Id' => 'E40',
        'Name' => 'part per hundred thousand',
        'Description' => 'A unit of proportion equal to 10⁻⁵.',
      ],
      517 => [
        'Id' => 'E41',
        'Name' => 'kilogram-force per square millimetre',
        'Description' => 'A unit of pressure defining the number of kilograms force per square
            millimetre.',
      ],
      518 => [
        'Id' => 'E42',
        'Name' => 'kilogram-force per square centimetre',
        'Description' => 'A unit of pressure defining the number of kilograms force per square
            centimetre.',
      ],
      519 => [
        'Id' => 'E43',
        'Name' => 'joule per square centimetre',
        'Description' => 'A unit of energy defining the number of joules per square
            centimetre.',
      ],
      520 => [
        'Id' => 'E44',
        'Name' => 'kilogram-force metre per square centimetre',
        'Description' => 'A unit of torsion defining the torque kilogram-force metre per square
            centimetre.',
      ],
      521 => [
        'Id' => 'E45',
        'Name' => 'milliohm',
        'Description' => '',
      ],
      522 => [
        'Id' => 'E46',
        'Name' => 'kilowatt hour per cubic metre',
        'Description' => 'A unit of energy consumption expressed as kilowatt hour per cubic
            metre.',
      ],
      523 => [
        'Id' => 'E47',
        'Name' => 'kilowatt hour per kelvin',
        'Description' => 'A unit of energy consumption expressed as kilowatt hour per
            kelvin.',
      ],
      524 => [
        'Id' => 'E48',
        'Name' => 'service unit',
        'Description' => 'A unit of count defining the number of service units (service unit: defined
            period / property / facility / utility of supply).',
      ],
      525 => [
        'Id' => 'E49',
        'Name' => 'working day',
        'Description' => 'A unit of count defining the number of working days (working day: a day on
            which work is ordinarily performed).',
      ],
      526 => [
        'Id' => 'E50',
        'Name' => 'accounting unit',
        'Description' => 'A unit of count defining the number of accounting units.',
      ],
      527 => [
        'Id' => 'E51',
        'Name' => 'job',
        'Description' => 'A unit of count defining the number of jobs.',
      ],
      528 => [
        'Id' => 'E52',
        'Name' => 'run foot',
        'Description' => 'A unit of count defining the number feet per run.',
      ],
      529 => [
        'Id' => 'E53',
        'Name' => 'test',
        'Description' => 'A unit of count defining the number of tests.',
      ],
      530 => [
        'Id' => 'E54',
        'Name' => 'trip',
        'Description' => 'A unit of count defining the number of trips.',
      ],
      531 => [
        'Id' => 'E55',
        'Name' => 'use',
        'Description' => 'A unit of count defining the number of times an object is used.',
      ],
      532 => [
        'Id' => 'E56',
        'Name' => 'well',
        'Description' => 'A unit of count defining the number of wells.',
      ],
      533 => [
        'Id' => 'E57',
        'Name' => 'zone',
        'Description' => 'A unit of count defining the number of zones.',
      ],
      534 => [
        'Id' => 'E58',
        'Name' => 'exabit per second',
        'Description' => 'A unit of information equal to 10¹⁸ bits (binary digits) per
            second.',
      ],
      535 => [
        'Id' => 'E59',
        'Name' => 'exbibyte',
        'Description' => 'A unit of information equal to 2⁶⁰ bytes.',
      ],
      536 => [
        'Id' => 'E60',
        'Name' => 'pebibyte',
        'Description' => 'A unit of information equal to 2⁵⁰ bytes.',
      ],
      537 => [
        'Id' => 'E61',
        'Name' => 'tebibyte',
        'Description' => 'A unit of information equal to 2⁴⁰ bytes.',
      ],
      538 => [
        'Id' => 'E62',
        'Name' => 'gibibyte',
        'Description' => 'A unit of information equal to 2³⁰ bytes.',
      ],
      539 => [
        'Id' => 'E63',
        'Name' => 'mebibyte',
        'Description' => 'A unit of information equal to 2²⁰ bytes.',
      ],
      540 => [
        'Id' => 'E64',
        'Name' => 'kibibyte',
        'Description' => 'A unit of information equal to 2¹⁰ bytes.',
      ],
      541 => [
        'Id' => 'E65',
        'Name' => 'exbibit per metre',
        'Description' => 'A unit of information equal to 2⁶⁰ bits (binary digits) per
            metre.',
      ],
      542 => [
        'Id' => 'E66',
        'Name' => 'exbibit per square metre',
        'Description' => 'A unit of information equal to 2⁶⁰ bits (binary digits) per square
            metre.',
      ],
      543 => [
        'Id' => 'E67',
        'Name' => 'exbibit per cubic metre',
        'Description' => 'A unit of information equal to 2⁶⁰ bits (binary digits) per cubic
            metre.',
      ],
      544 => [
        'Id' => 'E68',
        'Name' => 'gigabyte per second',
        'Description' => 'A unit of information equal to 10⁹ bytes per second.',
      ],
      545 => [
        'Id' => 'E69',
        'Name' => 'gibibit per metre',
        'Description' => 'A unit of information equal to 2³⁰ bits (binary digits) per
            metre.',
      ],
      546 => [
        'Id' => 'E70',
        'Name' => 'gibibit per square metre',
        'Description' => 'A unit of information equal to 2³⁰ bits (binary digits) per square
            metre.',
      ],
      547 => [
        'Id' => 'E71',
        'Name' => 'gibibit per cubic metre',
        'Description' => 'A unit of information equal to 2³⁰ bits (binary digits) per cubic
            metre.',
      ],
      548 => [
        'Id' => 'E72',
        'Name' => 'kibibit per metre',
        'Description' => 'A unit of information equal to 2¹⁰ bits (binary digits) per
            metre.',
      ],
      549 => [
        'Id' => 'E73',
        'Name' => 'kibibit per square metre',
        'Description' => 'A unit of information equal to 2¹⁰ bits (binary digits) per square
            metre.',
      ],
      550 => [
        'Id' => 'E74',
        'Name' => 'kibibit per cubic metre',
        'Description' => 'A unit of information equal to 2¹⁰ bits (binary digits) per cubic
            metre.',
      ],
      551 => [
        'Id' => 'E75',
        'Name' => 'mebibit per metre',
        'Description' => 'A unit of information equal to 2²⁰ bits (binary digits) per
            metre.',
      ],
      552 => [
        'Id' => 'E76',
        'Name' => 'mebibit per square metre',
        'Description' => 'A unit of information equal to 2²⁰ bits (binary digits) per square
            metre.',
      ],
      553 => [
        'Id' => 'E77',
        'Name' => 'mebibit per cubic metre',
        'Description' => 'A unit of information equal to 2²⁰ bits (binary digits) per cubic
            metre.',
      ],
      554 => [
        'Id' => 'E78',
        'Name' => 'petabit',
        'Description' => 'A unit of information equal to 10¹⁵ bits (binary digits).',
      ],
      555 => [
        'Id' => 'E79',
        'Name' => 'petabit per second',
        'Description' => 'A unit of information equal to 10¹⁵ bits (binary digits) per
            second.',
      ],
      556 => [
        'Id' => 'E80',
        'Name' => 'pebibit per metre',
        'Description' => 'A unit of information equal to 2⁵⁰ bits (binary digits) per
            metre.',
      ],
      557 => [
        'Id' => 'E81',
        'Name' => 'pebibit per square metre',
        'Description' => 'A unit of information equal to 2⁵⁰ bits (binary digits) per square
            metre.',
      ],
      558 => [
        'Id' => 'E82',
        'Name' => 'pebibit per cubic metre',
        'Description' => 'A unit of information equal to 2⁵⁰ bits (binary digits) per cubic
            metre.',
      ],
      559 => [
        'Id' => 'E83',
        'Name' => 'terabit',
        'Description' => 'A unit of information equal to 10¹² bits (binary digits).',
      ],
      560 => [
        'Id' => 'E84',
        'Name' => 'terabit per second',
        'Description' => 'A unit of information equal to 10¹² bits (binary digits) per
            second.',
      ],
      561 => [
        'Id' => 'E85',
        'Name' => 'tebibit per metre',
        'Description' => 'A unit of information equal to 2⁴⁰ bits (binary digits) per
            metre.',
      ],
      562 => [
        'Id' => 'E86',
        'Name' => 'tebibit per cubic metre',
        'Description' => 'A unit of information equal to 2⁴⁰ bits (binary digits) per cubic
            metre.',
      ],
      563 => [
        'Id' => 'E87',
        'Name' => 'tebibit per square metre',
        'Description' => 'A unit of information equal to 2⁴⁰ bits (binary digits) per square
            metre.',
      ],
      564 => [
        'Id' => 'E88',
        'Name' => 'bit per metre',
        'Description' => 'A unit of information equal to 1 bit (binary digit) per metre.',
      ],
      565 => [
        'Id' => 'E89',
        'Name' => 'bit per square metre',
        'Description' => 'A unit of information equal to 1 bit (binary digit) per square
            metre.',
      ],
      566 => [
        'Id' => 'E90',
        'Name' => 'reciprocal centimetre',
        'Description' => '',
      ],
      567 => [
        'Id' => 'E91',
        'Name' => 'reciprocal day',
        'Description' => '',
      ],
      568 => [
        'Id' => 'E92',
        'Name' => 'cubic decimetre per hour',
        'Description' => '',
      ],
      569 => [
        'Id' => 'E93',
        'Name' => 'kilogram per hour',
        'Description' => '',
      ],
      570 => [
        'Id' => 'E94',
        'Name' => 'kilomole per second',
        'Description' => '',
      ],
      571 => [
        'Id' => 'E95',
        'Name' => 'mole per second',
        'Description' => '',
      ],
      572 => [
        'Id' => 'E96',
        'Name' => 'degree per second',
        'Description' => '',
      ],
      573 => [
        'Id' => 'E97',
        'Name' => 'millimetre per degree Celcius metre',
        'Description' => '',
      ],
      574 => [
        'Id' => 'E98',
        'Name' => 'degree Celsius per kelvin',
        'Description' => '',
      ],
      575 => [
        'Id' => 'E99',
        'Name' => 'hectopascal per bar',
        'Description' => '',
      ],
      576 => [
        'Id' => 'EA',
        'Name' => 'each',
        'Description' => 'A unit of count defining the number of items regarded as separate
            units.',
      ],
      577 => [
        'Id' => 'EB',
        'Name' => 'electronic mail box',
        'Description' => 'A unit of count defining the number of electronic mail boxes.',
      ],
      578 => [
        'Id' => 'EQ',
        'Name' => 'equivalent gallon',
        'Description' => 'A unit of volume defining the number of gallons of product produced from
            concentrate.',
      ],
      579 => [
        'Id' => 'F01',
        'Name' => 'bit per cubic metre',
        'Description' => 'A unit of information equal to 1 bit (binary digit) per cubic
            metre.',
      ],
      580 => [
        'Id' => 'F02',
        'Name' => 'kelvin per kelvin',
        'Description' => '',
      ],
      581 => [
        'Id' => 'F03',
        'Name' => 'kilopascal per bar',
        'Description' => '',
      ],
      582 => [
        'Id' => 'F04',
        'Name' => 'millibar per bar',
        'Description' => '',
      ],
      583 => [
        'Id' => 'F05',
        'Name' => 'megapascal per bar',
        'Description' => '',
      ],
      584 => [
        'Id' => 'F06',
        'Name' => 'poise per bar',
        'Description' => '',
      ],
      585 => [
        'Id' => 'F07',
        'Name' => 'pascal per bar',
        'Description' => '',
      ],
      586 => [
        'Id' => 'F08',
        'Name' => 'milliampere per inch',
        'Description' => '',
      ],
      587 => [
        'Id' => 'F10',
        'Name' => 'kelvin per hour',
        'Description' => '',
      ],
      588 => [
        'Id' => 'F11',
        'Name' => 'kelvin per minute',
        'Description' => '',
      ],
      589 => [
        'Id' => 'F12',
        'Name' => 'kelvin per second',
        'Description' => '',
      ],
      590 => [
        'Id' => 'F13',
        'Name' => 'slug',
        'Description' => 'A unit of mass. One slug is the mass accelerated at 1 foot per second per
            second by a force of 1 pound.',
      ],
      591 => [
        'Id' => 'F14',
        'Name' => 'gram per kelvin',
        'Description' => '',
      ],
      592 => [
        'Id' => 'F15',
        'Name' => 'kilogram per kelvin',
        'Description' => '',
      ],
      593 => [
        'Id' => 'F16',
        'Name' => 'milligram per kelvin',
        'Description' => '',
      ],
      594 => [
        'Id' => 'F17',
        'Name' => 'pound-force per foot',
        'Description' => '',
      ],
      595 => [
        'Id' => 'F18',
        'Name' => 'kilogram square centimetre',
        'Description' => '',
      ],
      596 => [
        'Id' => 'F19',
        'Name' => 'kilogram square millimetre',
        'Description' => '',
      ],
      597 => [
        'Id' => 'F20',
        'Name' => 'pound inch squared',
        'Description' => '',
      ],
      598 => [
        'Id' => 'F21',
        'Name' => 'pound-force inch',
        'Description' => '',
      ],
      599 => [
        'Id' => 'F22',
        'Name' => 'pound-force foot per ampere',
        'Description' => '',
      ],
      600 => [
        'Id' => 'F23',
        'Name' => 'gram per cubic decimetre',
        'Description' => '',
      ],
      601 => [
        'Id' => 'F24',
        'Name' => 'kilogram per kilomol',
        'Description' => '',
      ],
      602 => [
        'Id' => 'F25',
        'Name' => 'gram per hertz',
        'Description' => '',
      ],
      603 => [
        'Id' => 'F26',
        'Name' => 'gram per day',
        'Description' => '',
      ],
      604 => [
        'Id' => 'F27',
        'Name' => 'gram per hour',
        'Description' => '',
      ],
      605 => [
        'Id' => 'F28',
        'Name' => 'gram per minute',
        'Description' => '',
      ],
      606 => [
        'Id' => 'F29',
        'Name' => 'gram per second',
        'Description' => '',
      ],
      607 => [
        'Id' => 'F30',
        'Name' => 'kilogram per day',
        'Description' => '',
      ],
      608 => [
        'Id' => 'F31',
        'Name' => 'kilogram per minute',
        'Description' => '',
      ],
      609 => [
        'Id' => 'F32',
        'Name' => 'milligram per day',
        'Description' => '',
      ],
      610 => [
        'Id' => 'F33',
        'Name' => 'milligram per minute',
        'Description' => '',
      ],
      611 => [
        'Id' => 'F34',
        'Name' => 'milligram per second',
        'Description' => '',
      ],
      612 => [
        'Id' => 'F35',
        'Name' => 'gram per day kelvin',
        'Description' => '',
      ],
      613 => [
        'Id' => 'F36',
        'Name' => 'gram per hour kelvin',
        'Description' => '',
      ],
      614 => [
        'Id' => 'F37',
        'Name' => 'gram per minute kelvin',
        'Description' => '',
      ],
      615 => [
        'Id' => 'F38',
        'Name' => 'gram per second kelvin',
        'Description' => '',
      ],
      616 => [
        'Id' => 'F39',
        'Name' => 'kilogram per day kelvin',
        'Description' => '',
      ],
      617 => [
        'Id' => 'F40',
        'Name' => 'kilogram per hour kelvin',
        'Description' => '',
      ],
      618 => [
        'Id' => 'F41',
        'Name' => 'kilogram per minute kelvin',
        'Description' => '',
      ],
      619 => [
        'Id' => 'F42',
        'Name' => 'kilogram per second kelvin',
        'Description' => '',
      ],
      620 => [
        'Id' => 'F43',
        'Name' => 'milligram per day kelvin',
        'Description' => '',
      ],
      621 => [
        'Id' => 'F44',
        'Name' => 'milligram per hour kelvin',
        'Description' => '',
      ],
      622 => [
        'Id' => 'F45',
        'Name' => 'milligram per minute kelvin',
        'Description' => '',
      ],
      623 => [
        'Id' => 'F46',
        'Name' => 'milligram per second kelvin',
        'Description' => '',
      ],
      624 => [
        'Id' => 'F47',
        'Name' => 'newton per millimetre',
        'Description' => '',
      ],
      625 => [
        'Id' => 'F48',
        'Name' => 'pound-force per inch',
        'Description' => '',
      ],
      626 => [
        'Id' => 'F49',
        'Name' => 'rod [unit of distance]',
        'Description' => 'A unit of distance equal to 5.5 yards (16 feet 6 inches).',
      ],
      627 => [
        'Id' => 'F50',
        'Name' => 'micrometre per kelvin',
        'Description' => '',
      ],
      628 => [
        'Id' => 'F51',
        'Name' => 'centimetre per kelvin',
        'Description' => '',
      ],
      629 => [
        'Id' => 'F52',
        'Name' => 'metre per kelvin',
        'Description' => '',
      ],
      630 => [
        'Id' => 'F53',
        'Name' => 'millimetre per kelvin',
        'Description' => '',
      ],
      631 => [
        'Id' => 'F54',
        'Name' => 'milliohm per metre',
        'Description' => '',
      ],
      632 => [
        'Id' => 'F55',
        'Name' => 'ohm per mile (statute mile)',
        'Description' => '',
      ],
      633 => [
        'Id' => 'F56',
        'Name' => 'ohm per kilometre',
        'Description' => '',
      ],
      634 => [
        'Id' => 'F57',
        'Name' => 'milliampere per pound-force per square inch',
        'Description' => '',
      ],
      635 => [
        'Id' => 'F58',
        'Name' => 'reciprocal bar',
        'Description' => '',
      ],
      636 => [
        'Id' => 'F59',
        'Name' => 'milliampere per bar',
        'Description' => '',
      ],
      637 => [
        'Id' => 'F60',
        'Name' => 'degree Celsius per bar',
        'Description' => '',
      ],
      638 => [
        'Id' => 'F61',
        'Name' => 'kelvin per bar',
        'Description' => '',
      ],
      639 => [
        'Id' => 'F62',
        'Name' => 'gram per day bar',
        'Description' => '',
      ],
      640 => [
        'Id' => 'F63',
        'Name' => 'gram per hour bar',
        'Description' => '',
      ],
      641 => [
        'Id' => 'F64',
        'Name' => 'gram per minute bar',
        'Description' => '',
      ],
      642 => [
        'Id' => 'F65',
        'Name' => 'gram per second bar',
        'Description' => '',
      ],
      643 => [
        'Id' => 'F66',
        'Name' => 'kilogram per day bar',
        'Description' => '',
      ],
      644 => [
        'Id' => 'F67',
        'Name' => 'kilogram per hour bar',
        'Description' => '',
      ],
      645 => [
        'Id' => 'F68',
        'Name' => 'kilogram per minute bar',
        'Description' => '',
      ],
      646 => [
        'Id' => 'F69',
        'Name' => 'kilogram per second bar',
        'Description' => '',
      ],
      647 => [
        'Id' => 'F70',
        'Name' => 'milligram per day bar',
        'Description' => '',
      ],
      648 => [
        'Id' => 'F71',
        'Name' => 'milligram per hour bar',
        'Description' => '',
      ],
      649 => [
        'Id' => 'F72',
        'Name' => 'milligram per minute bar',
        'Description' => '',
      ],
      650 => [
        'Id' => 'F73',
        'Name' => 'milligram per second bar',
        'Description' => '',
      ],
      651 => [
        'Id' => 'F74',
        'Name' => 'gram per bar',
        'Description' => '',
      ],
      652 => [
        'Id' => 'F75',
        'Name' => 'milligram per bar',
        'Description' => '',
      ],
      653 => [
        'Id' => 'F76',
        'Name' => 'milliampere per millimetre',
        'Description' => '',
      ],
      654 => [
        'Id' => 'F77',
        'Name' => 'pascal second per kelvin',
        'Description' => '',
      ],
      655 => [
        'Id' => 'F78',
        'Name' => 'inch of water',
        'Description' => '',
      ],
      656 => [
        'Id' => 'F79',
        'Name' => 'inch of mercury',
        'Description' => '',
      ],
      657 => [
        'Id' => 'F80',
        'Name' => 'water horse power',
        'Description' => 'A unit of power defining the amount of power required to move a given volume of
            water against acceleration of gravity to a specified elevation (pressure
            head).',
      ],
      658 => [
        'Id' => 'F81',
        'Name' => 'bar per kelvin',
        'Description' => '',
      ],
      659 => [
        'Id' => 'F82',
        'Name' => 'hectopascal per kelvin',
        'Description' => '',
      ],
      660 => [
        'Id' => 'F83',
        'Name' => 'kilopascal per kelvin',
        'Description' => '',
      ],
      661 => [
        'Id' => 'F84',
        'Name' => 'millibar per kelvin',
        'Description' => '',
      ],
      662 => [
        'Id' => 'F85',
        'Name' => 'megapascal per kelvin',
        'Description' => '',
      ],
      663 => [
        'Id' => 'F86',
        'Name' => 'poise per kelvin',
        'Description' => '',
      ],
      664 => [
        'Id' => 'F87',
        'Name' => 'volt per litre minute',
        'Description' => '',
      ],
      665 => [
        'Id' => 'F88',
        'Name' => 'newton centimetre',
        'Description' => '',
      ],
      666 => [
        'Id' => 'F89',
        'Name' => 'newton metre per degree',
        'Description' => '',
      ],
      667 => [
        'Id' => 'F90',
        'Name' => 'newton metre per ampere',
        'Description' => '',
      ],
      668 => [
        'Id' => 'F91',
        'Name' => 'bar litre per second',
        'Description' => '',
      ],
      669 => [
        'Id' => 'F92',
        'Name' => 'bar cubic metre per second',
        'Description' => '',
      ],
      670 => [
        'Id' => 'F93',
        'Name' => 'hectopascal litre per second',
        'Description' => '',
      ],
      671 => [
        'Id' => 'F94',
        'Name' => 'hectopascal cubic metre per second',
        'Description' => '',
      ],
      672 => [
        'Id' => 'F95',
        'Name' => 'millibar litre per second',
        'Description' => '',
      ],
      673 => [
        'Id' => 'F96',
        'Name' => 'millibar cubic metre per second',
        'Description' => '',
      ],
      674 => [
        'Id' => 'F97',
        'Name' => 'megapascal litre per second',
        'Description' => '',
      ],
      675 => [
        'Id' => 'F98',
        'Name' => 'megapascal cubic metre per second',
        'Description' => '',
      ],
      676 => [
        'Id' => 'F99',
        'Name' => 'pascal litre per second',
        'Description' => '',
      ],
      677 => [
        'Id' => 'FAH',
        'Name' => 'degree Fahrenheit',
        'Description' => 'Refer ISO 80000-5 (Quantities and units — Part 5: Thermodynamics)',
      ],
      678 => [
        'Id' => 'FAR',
        'Name' => 'farad',
        'Description' => '',
      ],
      679 => [
        'Id' => 'FBM',
        'Name' => 'fibre metre',
        'Description' => 'A unit of length defining the number of metres of individual
            fibre.',
      ],
      680 => [
        'Id' => 'FC',
        'Name' => 'thousand cubic foot',
        'Description' => 'A unit of volume equal to one thousand cubic foot.',
      ],
      681 => [
        'Id' => 'FF',
        'Name' => 'hundred cubic metre',
        'Description' => 'A unit of volume equal to one hundred cubic metres.',
      ],
      682 => [
        'Id' => 'FH',
        'Name' => 'micromole',
        'Description' => '',
      ],
      683 => [
        'Id' => 'FIT',
        'Name' => 'failures in time',
        'Description' => 'A unit of count defining the number of failures that can be expected over a
            specified time interval. Failure rates of semiconductor components are often specified
            as FIT (failures in time unit) where 1 FIT = 10⁻⁹ /h.',
      ],
      684 => [
        'Id' => 'FL',
        'Name' => 'flake ton',
        'Description' => 'A unit of mass defining the number of tons of a flaked substance (flake: a
            small flattish fragment).',
      ],
      685 => [
        'Id' => 'FNU',
        'Name' => 'Formazin nephelometric unit',
      ],
      686 => [
        'Id' => 'FOT',
        'Name' => 'foot',
        'Description' => '',
      ],
      687 => [
        'Id' => 'FP',
        'Name' => 'pound per square foot',
        'Description' => '',
      ],
      688 => [
        'Id' => 'FR',
        'Name' => 'foot per minute',
        'Description' => '',
      ],
      689 => [
        'Id' => 'FS',
        'Name' => 'foot per second',
        'Description' => '',
      ],
      690 => [
        'Id' => 'FTK',
        'Name' => 'square foot',
        'Description' => '',
      ],
      691 => [
        'Id' => 'FTQ',
        'Name' => 'cubic foot',
        'Description' => '',
      ],
      692 => [
        'Id' => 'G01',
        'Name' => 'pascal cubic metre per second',
        'Description' => '',
      ],
      693 => [
        'Id' => 'G04',
        'Name' => 'centimetre per bar',
        'Description' => '',
      ],
      694 => [
        'Id' => 'G05',
        'Name' => 'metre per bar',
        'Description' => '',
      ],
      695 => [
        'Id' => 'G06',
        'Name' => 'millimetre per bar',
        'Description' => '',
      ],
      696 => [
        'Id' => 'G08',
        'Name' => 'square inch per second',
        'Description' => '',
      ],
      697 => [
        'Id' => 'G09',
        'Name' => 'square metre per second kelvin',
        'Description' => '',
      ],
      698 => [
        'Id' => 'G10',
        'Name' => 'stokes per kelvin',
        'Description' => '',
      ],
      699 => [
        'Id' => 'G11',
        'Name' => 'gram per cubic centimetre bar',
        'Description' => '',
      ],
      700 => [
        'Id' => 'G12',
        'Name' => 'gram per cubic decimetre bar',
        'Description' => '',
      ],
      701 => [
        'Id' => 'G13',
        'Name' => 'gram per litre bar',
        'Description' => '',
      ],
      702 => [
        'Id' => 'G14',
        'Name' => 'gram per cubic metre bar',
        'Description' => '',
      ],
      703 => [
        'Id' => 'G15',
        'Name' => 'gram per millilitre bar',
        'Description' => '',
      ],
      704 => [
        'Id' => 'G16',
        'Name' => 'kilogram per cubic centimetre bar',
        'Description' => '',
      ],
      705 => [
        'Id' => 'G17',
        'Name' => 'kilogram per litre bar',
        'Description' => '',
      ],
      706 => [
        'Id' => 'G18',
        'Name' => 'kilogram per cubic metre bar',
        'Description' => '',
      ],
      707 => [
        'Id' => 'G19',
        'Name' => 'newton metre per kilogram',
        'Description' => '',
      ],
      708 => [
        'Id' => 'G2',
        'Name' => 'US gallon per minute',
        'Description' => '',
      ],
      709 => [
        'Id' => 'G20',
        'Name' => 'pound-force foot per pound',
        'Description' => '',
      ],
      710 => [
        'Id' => 'G21',
        'Name' => 'cup [unit of volume]',
        'Description' => '',
      ],
      711 => [
        'Id' => 'G23',
        'Name' => 'peck',
        'Description' => '',
      ],
      712 => [
        'Id' => 'G24',
        'Name' => 'tablespoon (US)',
        'Description' => '',
      ],
      713 => [
        'Id' => 'G25',
        'Name' => 'teaspoon (US)',
        'Description' => '',
      ],
      714 => [
        'Id' => 'G26',
        'Name' => 'stere',
        'Description' => '',
      ],
      715 => [
        'Id' => 'G27',
        'Name' => 'cubic centimetre per kelvin',
        'Description' => '',
      ],
      716 => [
        'Id' => 'G28',
        'Name' => 'litre per kelvin',
        'Description' => '',
      ],
      717 => [
        'Id' => 'G29',
        'Name' => 'cubic metre per kelvin',
        'Description' => '',
      ],
      718 => [
        'Id' => 'G3',
        'Name' => 'Imperial gallon per minute',
        'Description' => '',
      ],
      719 => [
        'Id' => 'G30',
        'Name' => 'millilitre per kelvin',
        'Description' => '',
      ],
      720 => [
        'Id' => 'G31',
        'Name' => 'kilogram per cubic centimetre',
        'Description' => '',
      ],
      721 => [
        'Id' => 'G32',
        'Name' => 'ounce (avoirdupois) per cubic yard',
        'Description' => '',
      ],
      722 => [
        'Id' => 'G33',
        'Name' => 'gram per cubic centimetre kelvin',
        'Description' => '',
      ],
      723 => [
        'Id' => 'G34',
        'Name' => 'gram per cubic decimetre kelvin',
        'Description' => '',
      ],
      724 => [
        'Id' => 'G35',
        'Name' => 'gram per litre kelvin',
        'Description' => '',
      ],
      725 => [
        'Id' => 'G36',
        'Name' => 'gram per cubic metre kelvin',
        'Description' => '',
      ],
      726 => [
        'Id' => 'G37',
        'Name' => 'gram per millilitre kelvin',
        'Description' => '',
      ],
      727 => [
        'Id' => 'G38',
        'Name' => 'kilogram per cubic centimetre kelvin',
        'Description' => '',
      ],
      728 => [
        'Id' => 'G39',
        'Name' => 'kilogram per litre kelvin',
        'Description' => '',
      ],
      729 => [
        'Id' => 'G40',
        'Name' => 'kilogram per cubic metre kelvin',
        'Description' => '',
      ],
      730 => [
        'Id' => 'G41',
        'Name' => 'square metre per second bar',
        'Description' => '',
      ],
      731 => [
        'Id' => 'G42',
        'Name' => 'microsiemens per centimetre',
        'Description' => '',
      ],
      732 => [
        'Id' => 'G43',
        'Name' => 'microsiemens per metre',
        'Description' => '',
      ],
      733 => [
        'Id' => 'G44',
        'Name' => 'nanosiemens per centimetre',
        'Description' => '',
      ],
      734 => [
        'Id' => 'G45',
        'Name' => 'nanosiemens per metre',
        'Description' => '',
      ],
      735 => [
        'Id' => 'G46',
        'Name' => 'stokes per bar',
        'Description' => '',
      ],
      736 => [
        'Id' => 'G47',
        'Name' => 'cubic centimetre per day',
        'Description' => '',
      ],
      737 => [
        'Id' => 'G48',
        'Name' => 'cubic centimetre per hour',
        'Description' => '',
      ],
      738 => [
        'Id' => 'G49',
        'Name' => 'cubic centimetre per minute',
        'Description' => '',
      ],
      739 => [
        'Id' => 'G50',
        'Name' => 'gallon (US) per hour',
        'Description' => '',
      ],
      740 => [
        'Id' => 'G51',
        'Name' => 'litre per second',
        'Description' => '',
      ],
      741 => [
        'Id' => 'G52',
        'Name' => 'cubic metre per day',
        'Description' => '',
      ],
      742 => [
        'Id' => 'G53',
        'Name' => 'cubic metre per minute',
        'Description' => '',
      ],
      743 => [
        'Id' => 'G54',
        'Name' => 'millilitre per day',
        'Description' => '',
      ],
      744 => [
        'Id' => 'G55',
        'Name' => 'millilitre per hour',
        'Description' => '',
      ],
      745 => [
        'Id' => 'G56',
        'Name' => 'cubic inch per hour',
        'Description' => '',
      ],
      746 => [
        'Id' => 'G57',
        'Name' => 'cubic inch per minute',
        'Description' => '',
      ],
      747 => [
        'Id' => 'G58',
        'Name' => 'cubic inch per second',
        'Description' => '',
      ],
      748 => [
        'Id' => 'G59',
        'Name' => 'milliampere per litre minute',
        'Description' => '',
      ],
      749 => [
        'Id' => 'G60',
        'Name' => 'volt per bar',
        'Description' => '',
      ],
      750 => [
        'Id' => 'G61',
        'Name' => 'cubic centimetre per day kelvin',
        'Description' => '',
      ],
      751 => [
        'Id' => 'G62',
        'Name' => 'cubic centimetre per hour kelvin',
        'Description' => '',
      ],
      752 => [
        'Id' => 'G63',
        'Name' => 'cubic centimetre per minute kelvin',
        'Description' => '',
      ],
      753 => [
        'Id' => 'G64',
        'Name' => 'cubic centimetre per second kelvin',
        'Description' => '',
      ],
      754 => [
        'Id' => 'G65',
        'Name' => 'litre per day kelvin',
        'Description' => '',
      ],
      755 => [
        'Id' => 'G66',
        'Name' => 'litre per hour kelvin',
        'Description' => '',
      ],
      756 => [
        'Id' => 'G67',
        'Name' => 'litre per minute kelvin',
        'Description' => '',
      ],
      757 => [
        'Id' => 'G68',
        'Name' => 'litre per second kelvin',
        'Description' => '',
      ],
      758 => [
        'Id' => 'G69',
        'Name' => 'cubic metre per day kelvin',
        'Description' => '',
      ],
      759 => [
        'Id' => 'G70',
        'Name' => 'cubic metre per hour kelvin',
        'Description' => '',
      ],
      760 => [
        'Id' => 'G71',
        'Name' => 'cubic metre per minute kelvin',
        'Description' => '',
      ],
      761 => [
        'Id' => 'G72',
        'Name' => 'cubic metre per second kelvin',
        'Description' => '',
      ],
      762 => [
        'Id' => 'G73',
        'Name' => 'millilitre per day kelvin',
        'Description' => '',
      ],
      763 => [
        'Id' => 'G74',
        'Name' => 'millilitre per hour kelvin',
        'Description' => '',
      ],
      764 => [
        'Id' => 'G75',
        'Name' => 'millilitre per minute kelvin',
        'Description' => '',
      ],
      765 => [
        'Id' => 'G76',
        'Name' => 'millilitre per second kelvin',
        'Description' => '',
      ],
      766 => [
        'Id' => 'G77',
        'Name' => 'millimetre to the fourth power',
        'Description' => '',
      ],
      767 => [
        'Id' => 'G78',
        'Name' => 'cubic centimetre per day bar',
        'Description' => '',
      ],
      768 => [
        'Id' => 'G79',
        'Name' => 'cubic centimetre per hour bar',
        'Description' => '',
      ],
      769 => [
        'Id' => 'G80',
        'Name' => 'cubic centimetre per minute bar',
        'Description' => '',
      ],
      770 => [
        'Id' => 'G81',
        'Name' => 'cubic centimetre per second bar',
        'Description' => '',
      ],
      771 => [
        'Id' => 'G82',
        'Name' => 'litre per day bar',
        'Description' => '',
      ],
      772 => [
        'Id' => 'G83',
        'Name' => 'litre per hour bar',
        'Description' => '',
      ],
      773 => [
        'Id' => 'G84',
        'Name' => 'litre per minute bar',
        'Description' => '',
      ],
      774 => [
        'Id' => 'G85',
        'Name' => 'litre per second bar',
        'Description' => '',
      ],
      775 => [
        'Id' => 'G86',
        'Name' => 'cubic metre per day bar',
        'Description' => '',
      ],
      776 => [
        'Id' => 'G87',
        'Name' => 'cubic metre per hour bar',
        'Description' => '',
      ],
      777 => [
        'Id' => 'G88',
        'Name' => 'cubic metre per minute bar',
        'Description' => '',
      ],
      778 => [
        'Id' => 'G89',
        'Name' => 'cubic metre per second bar',
        'Description' => '',
      ],
      779 => [
        'Id' => 'G90',
        'Name' => 'millilitre per day bar',
        'Description' => '',
      ],
      780 => [
        'Id' => 'G91',
        'Name' => 'millilitre per hour bar',
        'Description' => '',
      ],
      781 => [
        'Id' => 'G92',
        'Name' => 'millilitre per minute bar',
        'Description' => '',
      ],
      782 => [
        'Id' => 'G93',
        'Name' => 'millilitre per second bar',
        'Description' => '',
      ],
      783 => [
        'Id' => 'G94',
        'Name' => 'cubic centimetre per bar',
        'Description' => '',
      ],
      784 => [
        'Id' => 'G95',
        'Name' => 'litre per bar',
        'Description' => '',
      ],
      785 => [
        'Id' => 'G96',
        'Name' => 'cubic metre per bar',
        'Description' => '',
      ],
      786 => [
        'Id' => 'G97',
        'Name' => 'millilitre per bar',
        'Description' => '',
      ],
      787 => [
        'Id' => 'G98',
        'Name' => 'microhenry per kiloohm',
        'Description' => '',
      ],
      788 => [
        'Id' => 'G99',
        'Name' => 'microhenry per ohm',
        'Description' => '',
      ],
      789 => [
        'Id' => 'GB',
        'Name' => 'gallon (US) per day',
        'Description' => '',
      ],
      790 => [
        'Id' => 'GBQ',
        'Name' => 'gigabecquerel',
        'Description' => '',
      ],
      791 => [
        'Id' => 'GDW',
        'Name' => 'gram, dry weight',
        'Description' => 'A unit of mass defining the number of grams of a product, disregarding the
            water content of the product.',
      ],
      792 => [
        'Id' => 'GE',
        'Name' => 'pound per gallon (US)',
        'Description' => '',
      ],
      793 => [
        'Id' => 'GF',
        'Name' => 'gram per metre (gram per 100 centimetres)',
        'Description' => '',
      ],
      794 => [
        'Id' => 'GFI',
        'Name' => 'gram of fissile isotope',
        'Description' => 'A unit of mass defining the number of grams of a fissile isotope (fissile
            isotope: an isotope whose nucleus is able to be split when irradiated with low energy
            neutrons).',
      ],
      795 => [
        'Id' => 'GGR',
        'Name' => 'great gross',
        'Description' => 'A unit of count defining the number of units in multiples of 1728 (12 x 12 x
            12).',
      ],
      796 => [
        'Id' => 'GIA',
        'Name' => 'gill (US)',
        'Description' => '',
      ],
      797 => [
        'Id' => 'GIC',
        'Name' => 'gram, including container',
        'Description' => 'A unit of mass defining the number of grams of a product, including its
            container.',
      ],
      798 => [
        'Id' => 'GII',
        'Name' => 'gill (UK)',
        'Description' => '',
      ],
      799 => [
        'Id' => 'GIP',
        'Name' => 'gram, including inner packaging',
        'Description' => 'A unit of mass defining the number of grams of a product, including its inner
            packaging materials.',
      ],
      800 => [
        'Id' => 'GJ',
        'Name' => 'gram per millilitre',
        'Description' => '',
      ],
      801 => [
        'Id' => 'GL',
        'Name' => 'gram per litre',
        'Description' => '',
      ],
      802 => [
        'Id' => 'GLD',
        'Name' => 'dry gallon (US)',
        'Description' => '',
      ],
      803 => [
        'Id' => 'GLI',
        'Name' => 'gallon (UK)',
        'Description' => '',
      ],
      804 => [
        'Id' => 'GLL',
        'Name' => 'gallon (US)',
        'Description' => '',
      ],
      805 => [
        'Id' => 'GM',
        'Name' => 'gram per square metre',
        'Description' => '',
      ],
      806 => [
        'Id' => 'GO',
        'Name' => 'milligram per square metre',
        'Description' => '',
      ],
      807 => [
        'Id' => 'GP',
        'Name' => 'milligram per cubic metre',
        'Description' => '',
      ],
      808 => [
        'Id' => 'GQ',
        'Name' => 'microgram per cubic metre',
        'Description' => '',
      ],
      809 => [
        'Id' => 'GRM',
        'Name' => 'gram',
        'Description' => '',
      ],
      810 => [
        'Id' => 'GRN',
        'Name' => 'grain',
        'Description' => '',
      ],
      811 => [
        'Id' => 'GRO',
        'Name' => 'gross',
        'Description' => 'A unit of count defining the number of units in multiples of 144 (12 x
            12).',
      ],
      812 => [
        'Id' => 'GV',
        'Name' => 'gigajoule',
        'Description' => '',
      ],
      813 => [
        'Id' => 'GWH',
        'Name' => 'gigawatt hour',
        'Description' => '',
      ],
      814 => [
        'Id' => 'H03',
        'Name' => 'henry per kiloohm',
        'Description' => '',
      ],
      815 => [
        'Id' => 'H04',
        'Name' => 'henry per ohm',
        'Description' => '',
      ],
      816 => [
        'Id' => 'H05',
        'Name' => 'millihenry per kiloohm',
        'Description' => '',
      ],
      817 => [
        'Id' => 'H06',
        'Name' => 'millihenry per ohm',
        'Description' => '',
      ],
      818 => [
        'Id' => 'H07',
        'Name' => 'pascal second per bar',
        'Description' => '',
      ],
      819 => [
        'Id' => 'H08',
        'Name' => 'microbecquerel',
        'Description' => '',
      ],
      820 => [
        'Id' => 'H09',
        'Name' => 'reciprocal year',
        'Description' => '',
      ],
      821 => [
        'Id' => 'H10',
        'Name' => 'reciprocal hour',
        'Description' => '',
      ],
      822 => [
        'Id' => 'H11',
        'Name' => 'reciprocal month',
        'Description' => '',
      ],
      823 => [
        'Id' => 'H12',
        'Name' => 'degree Celsius per hour',
        'Description' => '',
      ],
      824 => [
        'Id' => 'H13',
        'Name' => 'degree Celsius per minute',
        'Description' => '',
      ],
      825 => [
        'Id' => 'H14',
        'Name' => 'degree Celsius per second',
        'Description' => '',
      ],
      826 => [
        'Id' => 'H15',
        'Name' => 'square centimetre per gram',
        'Description' => '',
      ],
      827 => [
        'Id' => 'H16',
        'Name' => 'square decametre',
        'Description' => 'Synonym: are',
      ],
      828 => [
        'Id' => 'H18',
        'Name' => 'square hectometre',
        'Description' => 'Synonym: hectare',
      ],
      829 => [
        'Id' => 'H19',
        'Name' => 'cubic hectometre',
        'Description' => '',
      ],
      830 => [
        'Id' => 'H20',
        'Name' => 'cubic kilometre',
        'Description' => '',
      ],
      831 => [
        'Id' => 'H21',
        'Name' => 'blank',
        'Description' => 'A unit of count defining the number of blanks.',
      ],
      832 => [
        'Id' => 'H22',
        'Name' => 'volt square inch per pound-force',
        'Description' => '',
      ],
      833 => [
        'Id' => 'H23',
        'Name' => 'volt per inch',
        'Description' => '',
      ],
      834 => [
        'Id' => 'H24',
        'Name' => 'volt per microsecond',
        'Description' => '',
      ],
      835 => [
        'Id' => 'H25',
        'Name' => 'percent per kelvin',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to the SI base unit
            Kelvin.',
      ],
      836 => [
        'Id' => 'H26',
        'Name' => 'ohm per metre',
        'Description' => '',
      ],
      837 => [
        'Id' => 'H27',
        'Name' => 'degree per metre',
        'Description' => '',
      ],
      838 => [
        'Id' => 'H28',
        'Name' => 'microfarad per kilometre',
        'Description' => '',
      ],
      839 => [
        'Id' => 'H29',
        'Name' => 'microgram per litre',
        'Description' => '',
      ],
      840 => [
        'Id' => 'H30',
        'Name' => 'square micrometre (square micron)',
        'Description' => '',
      ],
      841 => [
        'Id' => 'H31',
        'Name' => 'ampere per kilogram',
        'Description' => '',
      ],
      842 => [
        'Id' => 'H32',
        'Name' => 'ampere squared second',
        'Description' => '',
      ],
      843 => [
        'Id' => 'H33',
        'Name' => 'farad per kilometre',
        'Description' => '',
      ],
      844 => [
        'Id' => 'H34',
        'Name' => 'hertz metre',
        'Description' => '',
      ],
      845 => [
        'Id' => 'H35',
        'Name' => 'kelvin metre per watt',
        'Description' => '',
      ],
      846 => [
        'Id' => 'H36',
        'Name' => 'megaohm per kilometre',
        'Description' => '',
      ],
      847 => [
        'Id' => 'H37',
        'Name' => 'megaohm per metre',
        'Description' => '',
      ],
      848 => [
        'Id' => 'H38',
        'Name' => 'megaampere',
        'Description' => '',
      ],
      849 => [
        'Id' => 'H39',
        'Name' => 'megahertz kilometre',
        'Description' => '',
      ],
      850 => [
        'Id' => 'H40',
        'Name' => 'newton per ampere',
        'Description' => '',
      ],
      851 => [
        'Id' => 'H41',
        'Name' => 'newton metre watt to the power minus 0,5',
        'Description' => '',
      ],
      852 => [
        'Id' => 'H42',
        'Name' => 'pascal per metre',
        'Description' => '',
      ],
      853 => [
        'Id' => 'H43',
        'Name' => 'siemens per centimetre',
        'Description' => '',
      ],
      854 => [
        'Id' => 'H44',
        'Name' => 'teraohm',
        'Description' => '',
      ],
      855 => [
        'Id' => 'H45',
        'Name' => 'volt second per metre',
        'Description' => '',
      ],
      856 => [
        'Id' => 'H46',
        'Name' => 'volt per second',
        'Description' => '',
      ],
      857 => [
        'Id' => 'H47',
        'Name' => 'watt per cubic metre',
        'Description' => '',
      ],
      858 => [
        'Id' => 'H48',
        'Name' => 'attofarad',
        'Description' => '',
      ],
      859 => [
        'Id' => 'H49',
        'Name' => 'centimetre per hour',
        'Description' => '',
      ],
      860 => [
        'Id' => 'H50',
        'Name' => 'reciprocal cubic centimetre',
        'Description' => '',
      ],
      861 => [
        'Id' => 'H51',
        'Name' => 'decibel per kilometre',
        'Description' => '',
      ],
      862 => [
        'Id' => 'H52',
        'Name' => 'decibel per metre',
        'Description' => '',
      ],
      863 => [
        'Id' => 'H53',
        'Name' => 'kilogram per bar',
        'Description' => '',
      ],
      864 => [
        'Id' => 'H54',
        'Name' => 'kilogram per cubic decimetre kelvin',
        'Description' => '',
      ],
      865 => [
        'Id' => 'H55',
        'Name' => 'kilogram per cubic decimetre bar',
        'Description' => '',
      ],
      866 => [
        'Id' => 'H56',
        'Name' => 'kilogram per square metre second',
        'Description' => '',
      ],
      867 => [
        'Id' => 'H57',
        'Name' => 'inch per two pi radiant',
        'Description' => '',
      ],
      868 => [
        'Id' => 'H58',
        'Name' => 'metre per volt second',
        'Description' => '',
      ],
      869 => [
        'Id' => 'H59',
        'Name' => 'square metre per newton',
        'Description' => '',
      ],
      870 => [
        'Id' => 'H60',
        'Name' => 'cubic metre per cubic metre',
        'Description' => '',
      ],
      871 => [
        'Id' => 'H61',
        'Name' => 'millisiemens per centimetre',
        'Description' => '',
      ],
      872 => [
        'Id' => 'H62',
        'Name' => 'millivolt per minute',
        'Description' => '',
      ],
      873 => [
        'Id' => 'H63',
        'Name' => 'milligram per square centimetre',
        'Description' => '',
      ],
      874 => [
        'Id' => 'H64',
        'Name' => 'milligram per gram',
        'Description' => '',
      ],
      875 => [
        'Id' => 'H65',
        'Name' => 'millilitre per cubic metre',
        'Description' => '',
      ],
      876 => [
        'Id' => 'H66',
        'Name' => 'millimetre per year',
        'Description' => '',
      ],
      877 => [
        'Id' => 'H67',
        'Name' => 'millimetre per hour',
        'Description' => '',
      ],
      878 => [
        'Id' => 'H68',
        'Name' => 'millimole per gram',
        'Description' => '',
      ],
      879 => [
        'Id' => 'H69',
        'Name' => 'picopascal per kilometre',
        'Description' => '',
      ],
      880 => [
        'Id' => 'H70',
        'Name' => 'picosecond',
        'Description' => '',
      ],
      881 => [
        'Id' => 'H71',
        'Name' => 'percent per month',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to a month.',
      ],
      882 => [
        'Id' => 'H72',
        'Name' => 'percent per hectobar',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to 100-fold of the unit
            bar.',
      ],
      883 => [
        'Id' => 'H73',
        'Name' => 'percent per decakelvin',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to 10-fold of the SI base unit
            Kelvin.',
      ],
      884 => [
        'Id' => 'H74',
        'Name' => 'watt per metre',
        'Description' => '',
      ],
      885 => [
        'Id' => 'H75',
        'Name' => 'decapascal',
        'Description' => '',
      ],
      886 => [
        'Id' => 'H76',
        'Name' => 'gram per millimetre',
        'Description' => '',
      ],
      887 => [
        'Id' => 'H77',
        'Name' => 'module width',
        'Description' => 'A unit of measure used to describe the breadth of electronic assemblies as
            an installation standard or mounting dimension.',
      ],
      888 => [
        'Id' => 'H79',
        'Name' => 'French gauge',
        'Description' => 'A unit of distance used for measuring the diameter of small tubes such as
            urological instruments and catheters. Synonym: French, Charrière, Charrière
            gauge',
      ],
      889 => [
        'Id' => 'H80',
        'Name' => 'rack unit',
        'Description' => 'A unit of measure used to describe the height in rack units of equipment
            intended for mounting in a 19-inch rack or a 23-inch rack. One rack unit is 1.75 inches
            (44.45 mm) high.',
      ],
      890 => [
        'Id' => 'H81',
        'Name' => 'millimetre per minute',
        'Description' => '',
      ],
      891 => [
        'Id' => 'H82',
        'Name' => 'big point',
        'Description' => 'A unit of length defining the number of big points (big point: Adobe
            software(US) defines the big point to be exactly 1/72 inch (0.013 888 9 inch or 0.352
            777 8 millimeters))',
      ],
      892 => [
        'Id' => 'H83',
        'Name' => 'litre per kilogram',
        'Description' => '',
      ],
      893 => [
        'Id' => 'H84',
        'Name' => 'gram millimetre',
        'Description' => '',
      ],
      894 => [
        'Id' => 'H85',
        'Name' => 'reciprocal week',
        'Description' => '',
      ],
      895 => [
        'Id' => 'H87',
        'Name' => 'piece',
        'Description' => 'A unit of count defining the number of pieces (piece: a single item, article or
            exemplar).',
      ],
      896 => [
        'Id' => 'H88',
        'Name' => 'megaohm kilometre',
        'Description' => '',
      ],
      897 => [
        'Id' => 'H89',
        'Name' => 'percent per ohm',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to the SI derived unit
            ohm.',
      ],
      898 => [
        'Id' => 'H90',
        'Name' => 'percent per degree',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to an angle of one
            degree.',
      ],
      899 => [
        'Id' => 'H91',
        'Name' => 'percent per ten thousand',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to multiples of ten
            thousand.',
      ],
      900 => [
        'Id' => 'H92',
        'Name' => 'percent per one hundred thousand',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to multiples of one hundred
            thousand.',
      ],
      901 => [
        'Id' => 'H93',
        'Name' => 'percent per hundred',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to multiples of one
            hundred.',
      ],
      902 => [
        'Id' => 'H94',
        'Name' => 'percent per thousand',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to multiples of one
            thousand.',
      ],
      903 => [
        'Id' => 'H95',
        'Name' => 'percent per volt',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to the SI derived unit
            volt.',
      ],
      904 => [
        'Id' => 'H96',
        'Name' => 'percent per bar',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to an atmospheric pressure of
            one bar.',
      ],
      905 => [
        'Id' => 'H98',
        'Name' => 'percent per inch',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to an inch.',
      ],
      906 => [
        'Id' => 'H99',
        'Name' => 'percent per metre',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to a metre.',
      ],
      907 => [
        'Id' => 'HA',
        'Name' => 'hank',
        'Description' => 'A unit of length, typically for yarn.',
      ],
      908 => [
        'Id' => 'HAD',
        'Name' => 'Piece Day',
      ],
      909 => [
        'Id' => 'HBA',
        'Name' => 'hectobar',
        'Description' => '',
      ],
      910 => [
        'Id' => 'HBX',
        'Name' => 'hundred boxes',
        'Description' => 'A unit of count defining the number of boxes in multiples of one hundred box
            units.',
      ],
      911 => [
        'Id' => 'HC',
        'Name' => 'hundred count',
        'Description' => 'A unit of count defining the number of units counted in multiples of
            100.',
      ],
      912 => [
        'Id' => 'HDW',
        'Name' => 'hundred kilogram, dry weight',
        'Description' => 'A unit of mass defining the number of hundred kilograms of a product,
            disregarding the water content of the product.',
      ],
      913 => [
        'Id' => 'HEA',
        'Name' => 'head',
        'Description' => 'A unit of count defining the number of heads (head: a person or animal
            considered as one of a number).',
      ],
      914 => [
        'Id' => 'HGM',
        'Name' => 'hectogram',
        'Description' => '',
      ],
      915 => [
        'Id' => 'HH',
        'Name' => 'hundred cubic foot',
        'Description' => 'A unit of volume equal to one hundred cubic foot.',
      ],
      916 => [
        'Id' => 'HIU',
        'Name' => 'hundred international unit',
        'Description' => 'A unit of count defining the number of international units in multiples of
            100.',
      ],
      917 => [
        'Id' => 'HKM',
        'Name' => 'hundred kilogram, net mass',
        'Description' => 'A unit of mass defining the number of hundred kilograms of a product, after
            deductions.',
      ],
      918 => [
        'Id' => 'HLT',
        'Name' => 'hectolitre',
        'Description' => '',
      ],
      919 => [
        'Id' => 'HM',
        'Name' => 'mile per hour (statute mile)',
        'Description' => '',
      ],
      920 => [
        'Id' => 'HMO',
        'Name' => 'Piece Month',
      ],
      921 => [
        'Id' => 'HMQ',
        'Name' => 'million cubic metre',
        'Description' => 'A unit of volume equal to one million cubic metres.',
      ],
      922 => [
        'Id' => 'HMT',
        'Name' => 'hectometre',
        'Description' => '',
      ],
      923 => [
        'Id' => 'HPA',
        'Name' => 'hectolitre of pure alcohol',
        'Description' => 'A unit of volume equal to one hundred litres of pure alcohol.',
      ],
      924 => [
        'Id' => 'HTZ',
        'Name' => 'hertz',
        'Description' => '',
      ],
      925 => [
        'Id' => 'HUR',
        'Name' => 'hour',
        'Description' => '',
        'Code' => [
          'Id' => 'HWE',
          'Name' => 'Piece Week',
        ],
      ],
      926 => [
        'Id' => 'IA',
        'Name' => 'inch pound (pound inch)',
        'Description' => '',
      ],
      927 => [
        'Id' => 'IE',
        'Name' => 'person',
        'Description' => 'A unit of count defining the number of persons.',
      ],
      928 => [
        'Id' => 'INH',
        'Name' => 'inch',
        'Description' => '',
      ],
      929 => [
        'Id' => 'INK',
        'Name' => 'square inch',
        'Description' => '',
      ],
      930 => [
        'Id' => 'INQ',
        'Name' => 'cubic inch',
        'Description' => 'Synonym: inch cubed',
      ],
      931 => [
        'Id' => 'ISD',
        'Name' => 'international sugar degree',
        'Description' => 'A unit of measure defining the sugar content of a solution, expressed in
            degrees.',
      ],
      932 => [
        'Id' => 'IU',
        'Name' => 'inch per second',
        'Description' => '',
      ],
      933 => [
        'Id' => 'IUG',
        'Name' => 'international unit per gram',
      ],
      934 => [
        'Id' => 'IV',
        'Name' => 'inch per second squared',
        'Description' => '',
      ],
      935 => [
        'Id' => 'J10',
        'Name' => 'percent per millimetre',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to a millimetre.',
      ],
      936 => [
        'Id' => 'J12',
        'Name' => 'per mille per psi',
        'Description' => 'A unit of pressure equal to one thousandth of a psi (pound-force per square
            inch).',
      ],
      937 => [
        'Id' => 'J13',
        'Name' => 'degree API',
        'Description' => 'A unit of relative density as a measure of how heavy or light a petroleum
            liquid is compared to water (API: American Petroleum Institute).',
      ],
      938 => [
        'Id' => 'J14',
        'Name' => 'degree Baume (origin scale)',
        'Description' => 'A traditional unit of relative density for liquids. Named after Antoine
            Baumé.',
      ],
      939 => [
        'Id' => 'J15',
        'Name' => 'degree Baume (US heavy)',
        'Description' => 'A unit of relative density for liquids heavier than water.',
      ],
      940 => [
        'Id' => 'J16',
        'Name' => 'degree Baume (US light)',
        'Description' => 'A unit of relative density for liquids lighter than water.',
      ],
      941 => [
        'Id' => 'J17',
        'Name' => 'degree Balling',
        'Description' => 'A unit of density as a measure of sugar content, especially of beer wort. Named
            after Karl Balling.',
      ],
      942 => [
        'Id' => 'J18',
        'Name' => 'degree Brix',
        'Description' => 'A unit of proportion used in measuring the dissolved sugar-to-water mass ratio
            of a liquid. Named after Adolf Brix.',
      ],
      943 => [
        'Id' => 'J19',
        'Name' => 'degree Fahrenheit hour square foot per British thermal unit (thermochemical)',
        'Description' => '',
      ],
      944 => [
        'Id' => 'J2',
        'Name' => 'joule per kilogram',
        'Description' => '',
      ],
      945 => [
        'Id' => 'J20',
        'Name' => 'degree Fahrenheit per kelvin',
        'Description' => '',
      ],
      946 => [
        'Id' => 'J21',
        'Name' => 'degree Fahrenheit per bar',
        'Description' => '',
      ],
      947 => [
        'Id' => 'J22',
        'Name' => 'degree Fahrenheit hour square foot per British thermal unit (international
            table)',
        'Description' => '',
      ],
      948 => [
        'Id' => 'J23',
        'Name' => 'degree Fahrenheit per hour',
        'Description' => '',
      ],
      949 => [
        'Id' => 'J24',
        'Name' => 'degree Fahrenheit per minute',
        'Description' => '',
      ],
      950 => [
        'Id' => 'J25',
        'Name' => 'degree Fahrenheit per second',
        'Description' => '',
      ],
      951 => [
        'Id' => 'J26',
        'Name' => 'reciprocal degree Fahrenheit',
        'Description' => '',
      ],
      952 => [
        'Id' => 'J27',
        'Name' => 'degree Oechsle',
        'Description' => 'A unit of density as a measure of sugar content of must, the unfermented
            liqueur from which wine is made. Named after Ferdinand Oechsle.',
      ],
      953 => [
        'Id' => 'J28',
        'Name' => 'degree Rankine per hour',
        'Description' => '',
      ],
      954 => [
        'Id' => 'J29',
        'Name' => 'degree Rankine per minute',
        'Description' => '',
      ],
      955 => [
        'Id' => 'J30',
        'Name' => 'degree Rankine per second',
        'Description' => '',
      ],
      956 => [
        'Id' => 'J31',
        'Name' => 'degree Twaddell',
        'Description' => 'A unit of density for liquids that are heavier than water. 1 degree Twaddle
            represents a difference in specific gravity of 0.005.',
      ],
      957 => [
        'Id' => 'J32',
        'Name' => 'micropoise',
        'Description' => '',
      ],
      958 => [
        'Id' => 'J33',
        'Name' => 'microgram per kilogram',
        'Description' => '',
      ],
      959 => [
        'Id' => 'J34',
        'Name' => 'microgram per cubic metre kelvin',
        'Description' => '',
      ],
      960 => [
        'Id' => 'J35',
        'Name' => 'microgram per cubic metre bar',
        'Description' => '',
      ],
      961 => [
        'Id' => 'J36',
        'Name' => 'microlitre per litre',
        'Description' => '',
      ],
      962 => [
        'Id' => 'J38',
        'Name' => 'baud',
        'Description' => 'A unit of signal transmission speed equal to one signalling event per
            second.',
      ],
      963 => [
        'Id' => 'J39',
        'Name' => 'British thermal unit (mean)',
        'Description' => '',
      ],
      964 => [
        'Id' => 'J40',
        'Name' => 'British thermal unit (international table) foot per hour square foot degree
            Fahrenheit',
        'Description' => '',
      ],
      965 => [
        'Id' => 'J41',
        'Name' => 'British thermal unit (international table) inch per hour square foot degree
            Fahrenheit',
        'Description' => '',
      ],
      966 => [
        'Id' => 'J42',
        'Name' => 'British thermal unit (international table) inch per second square foot degree
            Fahrenheit',
        'Description' => '',
      ],
      967 => [
        'Id' => 'J43',
        'Name' => 'British thermal unit (international table) per pound degree Fahrenheit',
        'Description' => '',
      ],
      968 => [
        'Id' => 'J44',
        'Name' => 'British thermal unit (international table) per minute',
        'Description' => '',
      ],
      969 => [
        'Id' => 'J45',
        'Name' => 'British thermal unit (international table) per second',
        'Description' => '',
      ],
      970 => [
        'Id' => 'J46',
        'Name' => 'British thermal unit (thermochemical) foot per hour square foot degree
            Fahrenheit',
        'Description' => '',
      ],
      971 => [
        'Id' => 'J47',
        'Name' => 'British thermal unit (thermochemical) per hour',
        'Description' => '',
      ],
      972 => [
        'Id' => 'J48',
        'Name' => 'British thermal unit (thermochemical) inch per hour square foot degree
            Fahrenheit',
        'Description' => '',
      ],
      973 => [
        'Id' => 'J49',
        'Name' => 'British thermal unit (thermochemical) inch per second square foot degree
            Fahrenheit',
        'Description' => '',
      ],
      974 => [
        'Id' => 'J50',
        'Name' => 'British thermal unit (thermochemical) per pound degree Fahrenheit',
        'Description' => '',
      ],
      975 => [
        'Id' => 'J51',
        'Name' => 'British thermal unit (thermochemical) per minute',
        'Description' => '',
      ],
      976 => [
        'Id' => 'J52',
        'Name' => 'British thermal unit (thermochemical) per second',
        'Description' => '',
      ],
      977 => [
        'Id' => 'J53',
        'Name' => 'coulomb square metre per kilogram',
        'Description' => '',
      ],
      978 => [
        'Id' => 'J54',
        'Name' => 'megabaud',
        'Description' => 'A unit of signal transmission speed equal to 10⁶ (1000000) signaling events per
            second.',
      ],
      979 => [
        'Id' => 'J55',
        'Name' => 'watt second',
        'Description' => '',
      ],
      980 => [
        'Id' => 'J56',
        'Name' => 'bar per bar',
        'Description' => '',
      ],
      981 => [
        'Id' => 'J57',
        'Name' => 'barrel (UK petroleum)',
        'Description' => '',
      ],
      982 => [
        'Id' => 'J58',
        'Name' => 'barrel (UK petroleum) per minute',
        'Description' => '',
      ],
      983 => [
        'Id' => 'J59',
        'Name' => 'barrel (UK petroleum) per day',
        'Description' => '',
      ],
      984 => [
        'Id' => 'J60',
        'Name' => 'barrel (UK petroleum) per hour',
        'Description' => '',
      ],
      985 => [
        'Id' => 'J61',
        'Name' => 'barrel (UK petroleum) per second',
        'Description' => '',
      ],
      986 => [
        'Id' => 'J62',
        'Name' => 'barrel (US petroleum) per hour',
        'Description' => '',
      ],
      987 => [
        'Id' => 'J63',
        'Name' => 'barrel (US petroleum) per second',
        'Description' => '',
      ],
      988 => [
        'Id' => 'J64',
        'Name' => 'bushel (UK) per day',
        'Description' => '',
      ],
      989 => [
        'Id' => 'J65',
        'Name' => 'bushel (UK) per hour',
        'Description' => '',
      ],
      990 => [
        'Id' => 'J66',
        'Name' => 'bushel (UK) per minute',
        'Description' => '',
      ],
      991 => [
        'Id' => 'J67',
        'Name' => 'bushel (UK) per second',
        'Description' => '',
      ],
      992 => [
        'Id' => 'J68',
        'Name' => 'bushel (US dry) per day',
        'Description' => '',
      ],
      993 => [
        'Id' => 'J69',
        'Name' => 'bushel (US dry) per hour',
        'Description' => '',
      ],
      994 => [
        'Id' => 'J70',
        'Name' => 'bushel (US dry) per minute',
        'Description' => '',
      ],
      995 => [
        'Id' => 'J71',
        'Name' => 'bushel (US dry) per second',
        'Description' => '',
      ],
      996 => [
        'Id' => 'J72',
        'Name' => 'centinewton metre',
        'Description' => '',
      ],
      997 => [
        'Id' => 'J73',
        'Name' => 'centipoise per kelvin',
        'Description' => '',
      ],
      998 => [
        'Id' => 'J74',
        'Name' => 'centipoise per bar',
        'Description' => '',
      ],
      999 => [
        'Id' => 'J75',
        'Name' => 'calorie (mean)',
        'Description' => '',
      ],
      1000 => [
        'Id' => 'J76',
        'Name' => 'calorie (international table) per gram degree Celsius',
        'Description' => '',
      ],
      1001 => [
        'Id' => 'J78',
        'Name' => 'calorie (thermochemical) per centimetre second degree Celsius',
        'Description' => '',
      ],
      1002 => [
        'Id' => 'J79',
        'Name' => 'calorie (thermochemical) per gram degree Celsius',
        'Description' => '',
      ],
      1003 => [
        'Id' => 'J81',
        'Name' => 'calorie (thermochemical) per minute',
        'Description' => '',
      ],
      1004 => [
        'Id' => 'J82',
        'Name' => 'calorie (thermochemical) per second',
        'Description' => '',
      ],
      1005 => [
        'Id' => 'J83',
        'Name' => 'clo',
        'Description' => '',
      ],
      1006 => [
        'Id' => 'J84',
        'Name' => 'centimetre per second kelvin',
        'Description' => '',
      ],
      1007 => [
        'Id' => 'J85',
        'Name' => 'centimetre per second bar',
        'Description' => '',
      ],
      1008 => [
        'Id' => 'J87',
        'Name' => 'cubic centimetre per cubic metre',
        'Description' => '',
      ],
      1009 => [
        'Id' => 'J90',
        'Name' => 'cubic decimetre per day',
        'Description' => '',
      ],
      1010 => [
        'Id' => 'J91',
        'Name' => 'cubic decimetre per cubic metre',
        'Description' => '',
      ],
      1011 => [
        'Id' => 'J92',
        'Name' => 'cubic decimetre per minute',
        'Description' => '',
      ],
      1012 => [
        'Id' => 'J93',
        'Name' => 'cubic decimetre per second',
        'Description' => '',
      ],
      1013 => [
        'Id' => 'J95',
        'Name' => 'ounce (UK fluid) per day',
        'Description' => '',
      ],
      1014 => [
        'Id' => 'J96',
        'Name' => 'ounce (UK fluid) per hour',
        'Description' => '',
      ],
      1015 => [
        'Id' => 'J97',
        'Name' => 'ounce (UK fluid) per minute',
        'Description' => '',
      ],
      1016 => [
        'Id' => 'J98',
        'Name' => 'ounce (UK fluid) per second',
        'Description' => '',
      ],
      1017 => [
        'Id' => 'J99',
        'Name' => 'ounce (US fluid) per day',
        'Description' => '',
      ],
      1018 => [
        'Id' => 'JE',
        'Name' => 'joule per kelvin',
        'Description' => '',
      ],
      1019 => [
        'Id' => 'JK',
        'Name' => 'megajoule per kilogram',
        'Description' => '',
      ],
      1020 => [
        'Id' => 'JM',
        'Name' => 'megajoule per cubic metre',
        'Description' => '',
      ],
      1021 => [
        'Id' => 'JNT',
        'Name' => 'pipeline joint',
        'Description' => 'A count of the number of pipeline joints.',
      ],
      1022 => [
        'Id' => 'JOU',
        'Name' => 'joule',
        'Description' => '',
      ],
      1023 => [
        'Id' => 'JPS',
        'Name' => 'hundred metre',
        'Description' => 'A unit of count defining the number of 100 metre lengths.',
      ],
      1024 => [
        'Id' => 'JWL',
        'Name' => 'number of jewels',
        'Description' => 'A unit of count defining the number of jewels (jewel: precious
            stone).',
      ],
      1025 => [
        'Id' => 'K1',
        'Name' => 'kilowatt demand',
        'Description' => 'A unit of measure defining the power load measured at predetermined
            intervals.',
      ],
      1026 => [
        'Id' => 'K10',
        'Name' => 'ounce (US fluid) per hour',
        'Description' => '',
      ],
      1027 => [
        'Id' => 'K11',
        'Name' => 'ounce (US fluid) per minute',
        'Description' => '',
      ],
      1028 => [
        'Id' => 'K12',
        'Name' => 'ounce (US fluid) per second',
        'Description' => '',
      ],
      1029 => [
        'Id' => 'K13',
        'Name' => 'foot per degree Fahrenheit',
        'Description' => '',
      ],
      1030 => [
        'Id' => 'K14',
        'Name' => 'foot per hour',
        'Description' => '',
      ],
      1031 => [
        'Id' => 'K15',
        'Name' => 'foot pound-force per hour',
        'Description' => '',
      ],
      1032 => [
        'Id' => 'K16',
        'Name' => 'foot pound-force per minute',
        'Description' => '',
      ],
      1033 => [
        'Id' => 'K17',
        'Name' => 'foot per psi',
        'Description' => '',
      ],
      1034 => [
        'Id' => 'K18',
        'Name' => 'foot per second degree Fahrenheit',
        'Description' => '',
      ],
      1035 => [
        'Id' => 'K19',
        'Name' => 'foot per second psi',
        'Description' => '',
      ],
      1036 => [
        'Id' => 'K2',
        'Name' => 'kilovolt ampere reactive demand',
        'Description' => 'A unit of measure defining the reactive power demand equal to one kilovolt
            ampere of reactive power.',
      ],
      1037 => [
        'Id' => 'K20',
        'Name' => 'reciprocal cubic foot',
        'Description' => '',
      ],
      1038 => [
        'Id' => 'K21',
        'Name' => 'cubic foot per degree Fahrenheit',
        'Description' => '',
      ],
      1039 => [
        'Id' => 'K22',
        'Name' => 'cubic foot per day',
        'Description' => '',
      ],
      1040 => [
        'Id' => 'K23',
        'Name' => 'cubic foot per psi',
        'Description' => '',
      ],
      1041 => [
        'Id' => 'K26',
        'Name' => 'gallon (UK) per day',
        'Description' => '',
      ],
      1042 => [
        'Id' => 'K27',
        'Name' => 'gallon (UK) per hour',
        'Description' => '',
      ],
      1043 => [
        'Id' => 'K28',
        'Name' => 'gallon (UK) per second',
        'Description' => '',
      ],
      1044 => [
        'Id' => 'K3',
        'Name' => 'kilovolt ampere reactive hour',
        'Description' => 'A unit of measure defining the accumulated reactive energy equal to one
            kilovolt ampere of reactive power per hour.',
      ],
      1045 => [
        'Id' => 'K30',
        'Name' => 'gallon (US liquid) per second',
        'Description' => '',
      ],
      1046 => [
        'Id' => 'K31',
        'Name' => 'gram-force per square centimetre',
        'Description' => '',
      ],
      1047 => [
        'Id' => 'K32',
        'Name' => 'gill (UK) per day',
        'Description' => '',
      ],
      1048 => [
        'Id' => 'K33',
        'Name' => 'gill (UK) per hour',
        'Description' => '',
      ],
      1049 => [
        'Id' => 'K34',
        'Name' => 'gill (UK) per minute',
        'Description' => '',
      ],
      1050 => [
        'Id' => 'K35',
        'Name' => 'gill (UK) per second',
        'Description' => '',
      ],
      1051 => [
        'Id' => 'K36',
        'Name' => 'gill (US) per day',
        'Description' => '',
      ],
      1052 => [
        'Id' => 'K37',
        'Name' => 'gill (US) per hour',
        'Description' => '',
      ],
      1053 => [
        'Id' => 'K38',
        'Name' => 'gill (US) per minute',
        'Description' => '',
      ],
      1054 => [
        'Id' => 'K39',
        'Name' => 'gill (US) per second',
        'Description' => '',
      ],
      1055 => [
        'Id' => 'K40',
        'Name' => 'standard acceleration of free fall',
        'Description' => '',
      ],
      1056 => [
        'Id' => 'K41',
        'Name' => 'grain per gallon (US)',
        'Description' => '',
      ],
      1057 => [
        'Id' => 'K42',
        'Name' => 'horsepower (boiler)',
        'Description' => '',
      ],
      1058 => [
        'Id' => 'K43',
        'Name' => 'horsepower (electric)',
        'Description' => '',
      ],
      1059 => [
        'Id' => 'K45',
        'Name' => 'inch per degree Fahrenheit',
        'Description' => '',
      ],
      1060 => [
        'Id' => 'K46',
        'Name' => 'inch per psi',
        'Description' => '',
      ],
      1061 => [
        'Id' => 'K47',
        'Name' => 'inch per second degree Fahrenheit',
        'Description' => '',
      ],
      1062 => [
        'Id' => 'K48',
        'Name' => 'inch per second psi',
        'Description' => '',
      ],
      1063 => [
        'Id' => 'K49',
        'Name' => 'reciprocal cubic inch',
        'Description' => '',
      ],
      1064 => [
        'Id' => 'K50',
        'Name' => 'kilobaud',
        'Description' => 'A unit of signal transmission speed equal to 10³ (1000) signaling events per
            second.',
      ],
      1065 => [
        'Id' => 'K51',
        'Name' => 'kilocalorie (mean)',
        'Description' => '',
      ],
      1066 => [
        'Id' => 'K52',
        'Name' => 'kilocalorie (international table) per hour metre degree Celsius',
        'Description' => '',
      ],
      1067 => [
        'Id' => 'K53',
        'Name' => 'kilocalorie (thermochemical)',
        'Description' => '',
      ],
      1068 => [
        'Id' => 'K54',
        'Name' => 'kilocalorie (thermochemical) per minute',
        'Description' => '',
      ],
      1069 => [
        'Id' => 'K55',
        'Name' => 'kilocalorie (thermochemical) per second',
        'Description' => '',
      ],
      1070 => [
        'Id' => 'K58',
        'Name' => 'kilomole per hour',
        'Description' => '',
      ],
      1071 => [
        'Id' => 'K59',
        'Name' => 'kilomole per cubic metre kelvin',
        'Description' => '',
      ],
      1072 => [
        'Id' => 'K6',
        'Name' => 'kilolitre',
        'Description' => '',
      ],
      1073 => [
        'Id' => 'K60',
        'Name' => 'kilomole per cubic metre bar',
        'Description' => '',
      ],
      1074 => [
        'Id' => 'K61',
        'Name' => 'kilomole per minute',
        'Description' => '',
      ],
      1075 => [
        'Id' => 'K62',
        'Name' => 'litre per litre',
        'Description' => '',
      ],
      1076 => [
        'Id' => 'K63',
        'Name' => 'reciprocal litre',
        'Description' => '',
      ],
      1077 => [
        'Id' => 'K64',
        'Name' => 'pound (avoirdupois) per degree Fahrenheit',
        'Description' => '',
      ],
      1078 => [
        'Id' => 'K65',
        'Name' => 'pound (avoirdupois) square foot',
        'Description' => '',
      ],
      1079 => [
        'Id' => 'K66',
        'Name' => 'pound (avoirdupois) per day',
        'Description' => '',
      ],
      1080 => [
        'Id' => 'K67',
        'Name' => 'pound per foot hour',
        'Description' => '',
      ],
      1081 => [
        'Id' => 'K68',
        'Name' => 'pound per foot second',
        'Description' => '',
      ],
      1082 => [
        'Id' => 'K69',
        'Name' => 'pound (avoirdupois) per cubic foot degree Fahrenheit',
        'Description' => '',
      ],
      1083 => [
        'Id' => 'K70',
        'Name' => 'pound (avoirdupois) per cubic foot psi',
        'Description' => '',
      ],
      1084 => [
        'Id' => 'K71',
        'Name' => 'pound (avoirdupois) per gallon (UK)',
        'Description' => '',
      ],
      1085 => [
        'Id' => 'K73',
        'Name' => 'pound (avoirdupois) per hour degree Fahrenheit',
        'Description' => '',
      ],
      1086 => [
        'Id' => 'K74',
        'Name' => 'pound (avoirdupois) per hour psi',
        'Description' => '',
      ],
      1087 => [
        'Id' => 'K75',
        'Name' => 'pound (avoirdupois) per cubic inch degree Fahrenheit',
        'Description' => '',
      ],
      1088 => [
        'Id' => 'K76',
        'Name' => 'pound (avoirdupois) per cubic inch psi',
        'Description' => '',
      ],
      1089 => [
        'Id' => 'K77',
        'Name' => 'pound (avoirdupois) per psi',
        'Description' => '',
      ],
      1090 => [
        'Id' => 'K78',
        'Name' => 'pound (avoirdupois) per minute',
        'Description' => '',
      ],
      1091 => [
        'Id' => 'K79',
        'Name' => 'pound (avoirdupois) per minute degree Fahrenheit',
        'Description' => '',
      ],
      1092 => [
        'Id' => 'K80',
        'Name' => 'pound (avoirdupois) per minute psi',
        'Description' => '',
      ],
      1093 => [
        'Id' => 'K81',
        'Name' => 'pound (avoirdupois) per second',
        'Description' => '',
      ],
      1094 => [
        'Id' => 'K82',
        'Name' => 'pound (avoirdupois) per second degree Fahrenheit',
        'Description' => '',
      ],
      1095 => [
        'Id' => 'K83',
        'Name' => 'pound (avoirdupois) per second psi',
        'Description' => '',
      ],
      1096 => [
        'Id' => 'K84',
        'Name' => 'pound per cubic yard',
        'Description' => '',
      ],
      1097 => [
        'Id' => 'K85',
        'Name' => 'pound-force per square foot',
        'Description' => '',
      ],
      1098 => [
        'Id' => 'K86',
        'Name' => 'pound-force per square inch degree Fahrenheit',
        'Description' => '',
      ],
      1099 => [
        'Id' => 'K87',
        'Name' => 'psi cubic inch per second',
        'Description' => '',
      ],
      1100 => [
        'Id' => 'K88',
        'Name' => 'psi litre per second',
        'Description' => '',
      ],
      1101 => [
        'Id' => 'K89',
        'Name' => 'psi cubic metre per second',
        'Description' => '',
      ],
      1102 => [
        'Id' => 'K90',
        'Name' => 'psi cubic yard per second',
        'Description' => '',
      ],
      1103 => [
        'Id' => 'K91',
        'Name' => 'pound-force second per square foot',
        'Description' => '',
      ],
      1104 => [
        'Id' => 'K92',
        'Name' => 'pound-force second per square inch',
        'Description' => '',
      ],
      1105 => [
        'Id' => 'K93',
        'Name' => 'reciprocal psi',
        'Description' => '',
      ],
      1106 => [
        'Id' => 'K94',
        'Name' => 'quart (UK liquid) per day',
        'Description' => '',
      ],
      1107 => [
        'Id' => 'K95',
        'Name' => 'quart (UK liquid) per hour',
        'Description' => '',
      ],
      1108 => [
        'Id' => 'K96',
        'Name' => 'quart (UK liquid) per minute',
        'Description' => '',
      ],
      1109 => [
        'Id' => 'K97',
        'Name' => 'quart (UK liquid) per second',
        'Description' => '',
      ],
      1110 => [
        'Id' => 'K98',
        'Name' => 'quart (US liquid) per day',
        'Description' => '',
      ],
      1111 => [
        'Id' => 'K99',
        'Name' => 'quart (US liquid) per hour',
        'Description' => '',
      ],
      1112 => [
        'Id' => 'KA',
        'Name' => 'cake',
        'Description' => 'A unit of count defining the number of cakes (cake: object shaped into a flat,
            compact mass).',
      ],
      1113 => [
        'Id' => 'KAT',
        'Name' => 'katal',
        'Description' => 'A unit of catalytic activity defining the catalytic activity of enzymes and
            other catalysts.',
      ],
      1114 => [
        'Id' => 'KB',
        'Name' => 'kilocharacter',
        'Description' => 'A unit of information equal to 10³ (1000) characters.',
      ],
      1115 => [
        'Id' => 'KBA',
        'Name' => 'kilobar',
        'Description' => '',
      ],
      1116 => [
        'Id' => 'KCC',
        'Name' => 'kilogram of choline chloride',
        'Description' => 'A unit of mass equal to one thousand grams of choline chloride.',
      ],
      1117 => [
        'Id' => 'KDW',
        'Name' => 'kilogram drained net weight',
        'Description' => 'A unit of mass defining the net number of kilograms of a product, disregarding
            the liquid content of the product.',
      ],
      1118 => [
        'Id' => 'KEL',
        'Name' => 'kelvin',
        'Description' => 'Refer ISO 80000-5 (Quantities and units — Part 5: Thermodynamics)',
      ],
      1119 => [
        'Id' => 'KGM',
        'Name' => 'kilogram',
        'Description' => 'A unit of mass equal to one thousand grams.',
      ],
      1120 => [
        'Id' => 'KGS',
        'Name' => 'kilogram per second',
        'Description' => '',
      ],
      1121 => [
        'Id' => 'KHY',
        'Name' => 'kilogram of hydrogen peroxide',
        'Description' => 'A unit of mass equal to one thousand grams of hydrogen peroxide.',
      ],
      1122 => [
        'Id' => 'KHZ',
        'Name' => 'kilohertz',
        'Description' => '',
      ],
      1123 => [
        'Id' => 'KI',
        'Name' => 'kilogram per millimetre width',
        'Description' => '',
      ],
      1124 => [
        'Id' => 'KIC',
        'Name' => 'kilogram, including container',
        'Description' => 'A unit of mass defining the number of kilograms of a product, including its
            container.',
      ],
      1125 => [
        'Id' => 'KIP',
        'Name' => 'kilogram, including inner packaging',
        'Description' => 'A unit of mass defining the number of kilograms of a product, including its
            inner packaging materials.',
      ],
      1126 => [
        'Id' => 'KJ',
        'Name' => 'kilosegment',
        'Description' => 'A unit of information equal to 10³ (1000) segments.',
      ],
      1127 => [
        'Id' => 'KJO',
        'Name' => 'kilojoule',
        'Description' => '',
      ],
      1128 => [
        'Id' => 'KL',
        'Name' => 'kilogram per metre',
        'Description' => '',
      ],
      1129 => [
        'Id' => 'KLK',
        'Name' => 'lactic dry material percentage',
        'Description' => 'A unit of proportion defining the percentage of dry lactic material in a
            product.',
      ],
      1130 => [
        'Id' => 'KLX',
        'Name' => 'kilolux',
        'Description' => 'A unit of illuminance equal to one thousand lux.',
      ],
      1131 => [
        'Id' => 'KMA',
        'Name' => 'kilogram of methylamine',
        'Description' => 'A unit of mass equal to one thousand grams of methylamine.',
      ],
      1132 => [
        'Id' => 'KMH',
        'Name' => 'kilometre per hour',
        'Description' => '',
      ],
      1133 => [
        'Id' => 'KMK',
        'Name' => 'square kilometre',
        'Description' => '',
      ],
      1134 => [
        'Id' => 'KMQ',
        'Name' => 'kilogram per cubic metre',
        'Description' => 'A unit of weight expressed in kilograms of a substance that fills a volume of
            one cubic metre.',
      ],
      1135 => [
        'Id' => 'KMT',
        'Name' => 'kilometre',
        'Description' => '',
      ],
      1136 => [
        'Id' => 'KNI',
        'Name' => 'kilogram of nitrogen',
        'Description' => 'A unit of mass equal to one thousand grams of nitrogen.',
      ],
      1137 => [
        'Id' => 'KNM',
        'Name' => 'kilonewton per square metre',
        'Description' => 'Pressure expressed in kN/m2.',
      ],
      1138 => [
        'Id' => 'KNS',
        'Name' => 'kilogram named substance',
        'Description' => 'A unit of mass equal to one kilogram of a named substance.',
      ],
      1139 => [
        'Id' => 'KNT',
        'Name' => 'knot',
        'Description' => '',
      ],
      1140 => [
        'Id' => 'KO',
        'Name' => 'milliequivalence caustic potash per gram of product',
        'Description' => 'A unit of count defining the number of milligrams of potassium hydroxide per
            gram of product as a measure of the concentration of potassium hydroxide in the
            product.',
      ],
      1141 => [
        'Id' => 'KPA',
        'Name' => 'kilopascal',
        'Description' => '',
      ],
      1142 => [
        'Id' => 'KPH',
        'Name' => 'kilogram of potassium hydroxide (caustic potash)',
        'Description' => 'A unit of mass equal to one thousand grams of potassium hydroxide (caustic
            potash).',
      ],
      1143 => [
        'Id' => 'KPO',
        'Name' => 'kilogram of potassium oxide',
        'Description' => 'A unit of mass equal to one thousand grams of potassium oxide.',
      ],
      1144 => [
        'Id' => 'KPP',
        'Name' => 'kilogram of phosphorus pentoxide (phosphoric anhydride)',
        'Description' => 'A unit of mass equal to one thousand grams of phosphorus pentoxide phosphoric
            anhydride.',
      ],
      1145 => [
        'Id' => 'KR',
        'Name' => 'kiloroentgen',
        'Description' => '',
      ],
      1146 => [
        'Id' => 'KSD',
        'Name' => 'kilogram of substance 90 % dry',
        'Description' => 'A unit of mass equal to one thousand grams of a named substance that is 90%
            dry.',
      ],
      1147 => [
        'Id' => 'KSH',
        'Name' => 'kilogram of sodium hydroxide (caustic soda)',
        'Description' => 'A unit of mass equal to one thousand grams of sodium hydroxide (caustic
            soda).',
      ],
      1148 => [
        'Id' => 'KT',
        'Name' => 'kit',
        'Description' => 'A unit of count defining the number of kits (kit: tub, barrel or
            pail).',
      ],
      1149 => [
        'Id' => 'KTN',
        'Name' => 'kilotonne',
        'Description' => '',
      ],
      1150 => [
        'Id' => 'KUR',
        'Name' => 'kilogram of uranium',
        'Description' => 'A unit of mass equal to one thousand grams of uranium.',
      ],
      1151 => [
        'Id' => 'KVA',
        'Name' => 'kilovolt - ampere',
        'Description' => '',
      ],
      1152 => [
        'Id' => 'KVR',
        'Name' => 'kilovar',
        'Description' => '',
      ],
      1153 => [
        'Id' => 'KVT',
        'Name' => 'kilovolt',
        'Description' => '',
      ],
      1154 => [
        'Id' => 'KW',
        'Name' => 'kilogram per millimetre',
        'Description' => '',
      ],
      1155 => [
        'Id' => 'KWH',
        'Name' => 'kilowatt hour',
        'Description' => '',
      ],
      1156 => [
        'Id' => 'KWN',
        'Name' => 'Kilowatt hour per normalized cubic metre',
      ],
      1157 => [
        'Id' => 'KWO',
        'Name' => 'kilogram of tungsten trioxide',
        'Description' => 'A unit of mass equal to one thousand grams of tungsten trioxide.',
      ],
      1158 => [
        'Id' => 'KWS',
        'Name' => 'Kilowatt hour per standard cubic metre',
      ],
      1159 => [
        'Id' => 'KWT',
        'Name' => 'kilowatt',
        'Description' => '',
      ],
      1160 => [
        'Id' => 'KWY',
        'Name' => 'kilowatt year',
      ],
      1161 => [
        'Id' => 'KX',
        'Name' => 'millilitre per kilogram',
        'Description' => '',
      ],
      1162 => [
        'Id' => 'L10',
        'Name' => 'quart (US liquid) per minute',
        'Description' => '',
      ],
      1163 => [
        'Id' => 'L11',
        'Name' => 'quart (US liquid) per second',
        'Description' => '',
      ],
      1164 => [
        'Id' => 'L12',
        'Name' => 'metre per second kelvin',
        'Description' => '',
      ],
      1165 => [
        'Id' => 'L13',
        'Name' => 'metre per second bar',
        'Description' => '',
      ],
      1166 => [
        'Id' => 'L14',
        'Name' => 'square metre hour degree Celsius per kilocalorie (international table)',
        'Description' => '',
      ],
      1167 => [
        'Id' => 'L15',
        'Name' => 'millipascal second per kelvin',
        'Description' => '',
      ],
      1168 => [
        'Id' => 'L16',
        'Name' => 'millipascal second per bar',
        'Description' => '',
      ],
      1169 => [
        'Id' => 'L17',
        'Name' => 'milligram per cubic metre kelvin',
        'Description' => '',
      ],
      1170 => [
        'Id' => 'L18',
        'Name' => 'milligram per cubic metre bar',
        'Description' => '',
      ],
      1171 => [
        'Id' => 'L19',
        'Name' => 'millilitre per litre',
        'Description' => '',
      ],
      1172 => [
        'Id' => 'L2',
        'Name' => 'litre per minute',
        'Description' => '',
      ],
      1173 => [
        'Id' => 'L20',
        'Name' => 'reciprocal cubic millimetre',
        'Description' => '',
      ],
      1174 => [
        'Id' => 'L21',
        'Name' => 'cubic millimetre per cubic metre',
        'Description' => '',
      ],
      1175 => [
        'Id' => 'L23',
        'Name' => 'mole per hour',
        'Description' => '',
      ],
      1176 => [
        'Id' => 'L24',
        'Name' => 'mole per kilogram kelvin',
        'Description' => '',
      ],
      1177 => [
        'Id' => 'L25',
        'Name' => 'mole per kilogram bar',
        'Description' => '',
      ],
      1178 => [
        'Id' => 'L26',
        'Name' => 'mole per litre kelvin',
        'Description' => '',
      ],
      1179 => [
        'Id' => 'L27',
        'Name' => 'mole per litre bar',
        'Description' => '',
      ],
      1180 => [
        'Id' => 'L28',
        'Name' => 'mole per cubic metre kelvin',
        'Description' => '',
      ],
      1181 => [
        'Id' => 'L29',
        'Name' => 'mole per cubic metre bar',
        'Description' => '',
      ],
      1182 => [
        'Id' => 'L30',
        'Name' => 'mole per minute',
        'Description' => '',
      ],
      1183 => [
        'Id' => 'L31',
        'Name' => 'milliroentgen aequivalent men',
        'Description' => '',
      ],
      1184 => [
        'Id' => 'L32',
        'Name' => 'nanogram per kilogram',
        'Description' => '',
      ],
      1185 => [
        'Id' => 'L33',
        'Name' => 'ounce (avoirdupois) per day',
        'Description' => '',
      ],
      1186 => [
        'Id' => 'L34',
        'Name' => 'ounce (avoirdupois) per hour',
        'Description' => '',
      ],
      1187 => [
        'Id' => 'L35',
        'Name' => 'ounce (avoirdupois) per minute',
        'Description' => '',
      ],
      1188 => [
        'Id' => 'L36',
        'Name' => 'ounce (avoirdupois) per second',
        'Description' => '',
      ],
      1189 => [
        'Id' => 'L37',
        'Name' => 'ounce (avoirdupois) per gallon (UK)',
        'Description' => '',
      ],
      1190 => [
        'Id' => 'L38',
        'Name' => 'ounce (avoirdupois) per gallon (US)',
        'Description' => '',
      ],
      1191 => [
        'Id' => 'L39',
        'Name' => 'ounce (avoirdupois) per cubic inch',
        'Description' => '',
      ],
      1192 => [
        'Id' => 'L40',
        'Name' => 'ounce (avoirdupois)-force',
        'Description' => '',
      ],
      1193 => [
        'Id' => 'L41',
        'Name' => 'ounce (avoirdupois)-force inch',
        'Description' => '',
      ],
      1194 => [
        'Id' => 'L42',
        'Name' => 'picosiemens per metre',
        'Description' => '',
      ],
      1195 => [
        'Id' => 'L43',
        'Name' => 'peck (UK)',
        'Description' => '',
      ],
      1196 => [
        'Id' => 'L44',
        'Name' => 'peck (UK) per day',
        'Description' => '',
      ],
      1197 => [
        'Id' => 'L45',
        'Name' => 'peck (UK) per hour',
        'Description' => '',
      ],
      1198 => [
        'Id' => 'L46',
        'Name' => 'peck (UK) per minute',
        'Description' => '',
      ],
      1199 => [
        'Id' => 'L47',
        'Name' => 'peck (UK) per second',
        'Description' => '',
      ],
      1200 => [
        'Id' => 'L48',
        'Name' => 'peck (US dry) per day',
        'Description' => '',
      ],
      1201 => [
        'Id' => 'L49',
        'Name' => 'peck (US dry) per hour',
        'Description' => '',
      ],
      1202 => [
        'Id' => 'L50',
        'Name' => 'peck (US dry) per minute',
        'Description' => '',
      ],
      1203 => [
        'Id' => 'L51',
        'Name' => 'peck (US dry) per second',
        'Description' => '',
      ],
      1204 => [
        'Id' => 'L52',
        'Name' => 'psi per psi',
        'Description' => '',
      ],
      1205 => [
        'Id' => 'L53',
        'Name' => 'pint (UK) per day',
        'Description' => '',
      ],
      1206 => [
        'Id' => 'L54',
        'Name' => 'pint (UK) per hour',
        'Description' => '',
      ],
      1207 => [
        'Id' => 'L55',
        'Name' => 'pint (UK) per minute',
        'Description' => '',
      ],
      1208 => [
        'Id' => 'L56',
        'Name' => 'pint (UK) per second',
        'Description' => '',
      ],
      1209 => [
        'Id' => 'L57',
        'Name' => 'pint (US liquid) per day',
        'Description' => '',
      ],
      1210 => [
        'Id' => 'L58',
        'Name' => 'pint (US liquid) per hour',
        'Description' => '',
      ],
      1211 => [
        'Id' => 'L59',
        'Name' => 'pint (US liquid) per minute',
        'Description' => '',
      ],
      1212 => [
        'Id' => 'L60',
        'Name' => 'pint (US liquid) per second',
        'Description' => '',
      ],
      1213 => [
        'Id' => 'L63',
        'Name' => 'slug per day',
        'Description' => '',
      ],
      1214 => [
        'Id' => 'L64',
        'Name' => 'slug per foot second',
        'Description' => '',
      ],
      1215 => [
        'Id' => 'L65',
        'Name' => 'slug per cubic foot',
        'Description' => '',
      ],
      1216 => [
        'Id' => 'L66',
        'Name' => 'slug per hour',
        'Description' => '',
      ],
      1217 => [
        'Id' => 'L67',
        'Name' => 'slug per minute',
        'Description' => '',
      ],
      1218 => [
        'Id' => 'L68',
        'Name' => 'slug per second',
        'Description' => '',
      ],
      1219 => [
        'Id' => 'L69',
        'Name' => 'tonne per kelvin',
        'Description' => '',
      ],
      1220 => [
        'Id' => 'L70',
        'Name' => 'tonne per bar',
        'Description' => '',
      ],
      1221 => [
        'Id' => 'L71',
        'Name' => 'tonne per day',
        'Description' => '',
      ],
      1222 => [
        'Id' => 'L72',
        'Name' => 'tonne per day kelvin',
        'Description' => '',
      ],
      1223 => [
        'Id' => 'L73',
        'Name' => 'tonne per day bar',
        'Description' => '',
      ],
      1224 => [
        'Id' => 'L74',
        'Name' => 'tonne per hour kelvin',
        'Description' => '',
      ],
      1225 => [
        'Id' => 'L75',
        'Name' => 'tonne per hour bar',
        'Description' => '',
      ],
      1226 => [
        'Id' => 'L76',
        'Name' => 'tonne per cubic metre kelvin',
        'Description' => '',
      ],
      1227 => [
        'Id' => 'L77',
        'Name' => 'tonne per cubic metre bar',
        'Description' => '',
      ],
      1228 => [
        'Id' => 'L78',
        'Name' => 'tonne per minute',
        'Description' => '',
      ],
      1229 => [
        'Id' => 'L79',
        'Name' => 'tonne per minute kelvin',
        'Description' => '',
      ],
      1230 => [
        'Id' => 'L80',
        'Name' => 'tonne per minute bar',
        'Description' => '',
      ],
      1231 => [
        'Id' => 'L81',
        'Name' => 'tonne per second',
        'Description' => '',
      ],
      1232 => [
        'Id' => 'L82',
        'Name' => 'tonne per second kelvin',
        'Description' => '',
      ],
      1233 => [
        'Id' => 'L83',
        'Name' => 'tonne per second bar',
        'Description' => '',
      ],
      1234 => [
        'Id' => 'L84',
        'Name' => 'ton (UK shipping)',
        'Description' => '',
      ],
      1235 => [
        'Id' => 'L85',
        'Name' => 'ton long per day',
        'Description' => '',
      ],
      1236 => [
        'Id' => 'L86',
        'Name' => 'ton (US shipping)',
        'Description' => '',
      ],
      1237 => [
        'Id' => 'L87',
        'Name' => 'ton short per degree Fahrenheit',
        'Description' => '',
      ],
      1238 => [
        'Id' => 'L88',
        'Name' => 'ton short per day',
        'Description' => '',
      ],
      1239 => [
        'Id' => 'L89',
        'Name' => 'ton short per hour degree Fahrenheit',
        'Description' => '',
      ],
      1240 => [
        'Id' => 'L90',
        'Name' => 'ton short per hour psi',
        'Description' => '',
      ],
      1241 => [
        'Id' => 'L91',
        'Name' => 'ton short per psi',
        'Description' => '',
      ],
      1242 => [
        'Id' => 'L92',
        'Name' => 'ton (UK long) per cubic yard',
        'Description' => '',
      ],
      1243 => [
        'Id' => 'L93',
        'Name' => 'ton (US short) per cubic yard',
        'Description' => '',
      ],
      1244 => [
        'Id' => 'L94',
        'Name' => 'ton-force (US short)',
        'Description' => '',
      ],
      1245 => [
        'Id' => 'L95',
        'Name' => 'common year',
        'Description' => '',
      ],
      1246 => [
        'Id' => 'L96',
        'Name' => 'sidereal year',
        'Description' => '',
      ],
      1247 => [
        'Id' => 'L98',
        'Name' => 'yard per degree Fahrenheit',
        'Description' => '',
      ],
      1248 => [
        'Id' => 'L99',
        'Name' => 'yard per psi',
        'Description' => '',
      ],
      1249 => [
        'Id' => 'LA',
        'Name' => 'pound per cubic inch',
        'Description' => '',
      ],
      1250 => [
        'Id' => 'LAC',
        'Name' => 'lactose excess percentage',
        'Description' => 'A unit of proportion defining the percentage of lactose in a product that
            exceeds a defined percentage level.',
      ],
      1251 => [
        'Id' => 'LBR',
        'Name' => 'pound',
        'Description' => '',
      ],
      1252 => [
        'Id' => 'LBT',
        'Name' => 'troy pound (US)',
        'Description' => '',
      ],
      1253 => [
        'Id' => 'LD',
        'Name' => 'litre per day',
        'Description' => '',
      ],
      1254 => [
        'Id' => 'LEF',
        'Name' => 'leaf',
        'Description' => 'A unit of count defining the number of leaves.',
      ],
      1255 => [
        'Id' => 'LF',
        'Name' => 'linear foot',
        'Description' => 'A unit of count defining the number of feet (12-inch) in length of a uniform
            width object.',
      ],
      1256 => [
        'Id' => 'LH',
        'Name' => 'labour hour',
        'Description' => 'A unit of time defining the number of labour hours.',
      ],
      1257 => [
        'Id' => 'LK',
        'Name' => 'link',
        'Description' => 'A unit of distance equal to 0.01 chain.',
      ],
      1258 => [
        'Id' => 'LM',
        'Name' => 'linear metre',
        'Description' => 'A unit of count defining the number of metres in length of a uniform width
            object.',
      ],
      1259 => [
        'Id' => 'LN',
        'Name' => 'length',
        'Description' => 'A unit of distance defining the linear extent of an item measured from end to
            end.',
      ],
      1260 => [
        'Id' => 'LO',
        'Name' => 'lot [unit of procurement]',
        'Description' => 'A unit of count defining the number of lots (lot: a collection of associated
            items).',
      ],
      1261 => [
        'Id' => 'LP',
        'Name' => 'liquid pound',
        'Description' => 'A unit of mass defining the number of pounds of a liquid
            substance.',
      ],
      1262 => [
        'Id' => 'LPA',
        'Name' => 'litre of pure alcohol',
        'Description' => 'A unit of volume equal to one litre of pure alcohol.',
      ],
      1263 => [
        'Id' => 'LR',
        'Name' => 'layer',
        'Description' => 'A unit of count defining the number of layers.',
      ],
      1264 => [
        'Id' => 'LS',
        'Name' => 'lump sum',
        'Description' => 'A unit of count defining the number of whole or a complete monetary
            amounts.',
      ],
      1265 => [
        'Id' => 'LTN',
        'Name' => 'ton (UK) or long ton (US)',
        'Description' => 'Synonym: gross ton (2240 lb)',
      ],
      1266 => [
        'Id' => 'LTR',
        'Name' => 'litre',
        'Description' => '',
      ],
      1267 => [
        'Id' => 'LUB',
        'Name' => 'metric ton, lubricating oil',
        'Description' => 'A unit of mass defining the number of metric tons of lubricating
            oil.',
      ],
      1268 => [
        'Id' => 'LUM',
        'Name' => 'lumen',
        'Description' => '',
      ],
      1269 => [
        'Id' => 'LUX',
        'Name' => 'lux',
        'Description' => '',
      ],
      1270 => [
        'Id' => 'LY',
        'Name' => 'linear yard',
        'Description' => 'A unit of count defining the number of 36-inch units in length of a uniform
            width object.',
      ],
      1271 => [
        'Id' => 'M1',
        'Name' => 'milligram per litre',
        'Description' => '',
      ],
      1272 => [
        'Id' => 'M10',
        'Name' => 'reciprocal cubic yard',
        'Description' => '',
      ],
      1273 => [
        'Id' => 'M11',
        'Name' => 'cubic yard per degree Fahrenheit',
        'Description' => '',
      ],
      1274 => [
        'Id' => 'M12',
        'Name' => 'cubic yard per day',
        'Description' => '',
      ],
      1275 => [
        'Id' => 'M13',
        'Name' => 'cubic yard per hour',
        'Description' => '',
      ],
      1276 => [
        'Id' => 'M14',
        'Name' => 'cubic yard per psi',
        'Description' => '',
      ],
      1277 => [
        'Id' => 'M15',
        'Name' => 'cubic yard per minute',
        'Description' => '',
      ],
      1278 => [
        'Id' => 'M16',
        'Name' => 'cubic yard per second',
        'Description' => '',
      ],
      1279 => [
        'Id' => 'M17',
        'Name' => 'kilohertz metre',
        'Description' => '',
      ],
      1280 => [
        'Id' => 'M18',
        'Name' => 'gigahertz metre',
        'Description' => '',
      ],
      1281 => [
        'Id' => 'M19',
        'Name' => 'Beaufort',
        'Description' => 'An empirical measure for describing wind speed based mainly on observed sea
            conditions. The Beaufort scale indicates the wind speed by numbers that typically range
            from 0 for calm, to 12 for hurricane.',
      ],
      1282 => [
        'Id' => 'M20',
        'Name' => 'reciprocal megakelvin or megakelvin to the power minus one',
        'Description' => '',
      ],
      1283 => [
        'Id' => 'M21',
        'Name' => 'reciprocal kilovolt - ampere reciprocal hour',
        'Description' => '',
      ],
      1284 => [
        'Id' => 'M22',
        'Name' => 'millilitre per square centimetre minute',
        'Description' => '',
      ],
      1285 => [
        'Id' => 'M23',
        'Name' => 'newton per centimetre',
        'Description' => '',
      ],
      1286 => [
        'Id' => 'M24',
        'Name' => 'ohm kilometre',
        'Description' => '',
      ],
      1287 => [
        'Id' => 'M25',
        'Name' => 'percent per degree Celsius',
        'Description' => 'A unit of proportion, equal to 0.01, in relation to a temperature of one
            degree.',
      ],
      1288 => [
        'Id' => 'M26',
        'Name' => 'gigaohm per metre',
        'Description' => '',
      ],
      1289 => [
        'Id' => 'M27',
        'Name' => 'megahertz metre',
        'Description' => '',
      ],
      1290 => [
        'Id' => 'M29',
        'Name' => 'kilogram per kilogram',
        'Description' => '',
      ],
      1291 => [
        'Id' => 'M30',
        'Name' => 'reciprocal volt - ampere reciprocal second',
        'Description' => '',
      ],
      1292 => [
        'Id' => 'M31',
        'Name' => 'kilogram per kilometre',
        'Description' => '',
      ],
      1293 => [
        'Id' => 'M32',
        'Name' => 'pascal second per litre',
        'Description' => '',
      ],
      1294 => [
        'Id' => 'M33',
        'Name' => 'millimole per litre',
        'Description' => '',
      ],
      1295 => [
        'Id' => 'M34',
        'Name' => 'newton metre per square metre',
        'Description' => '',
      ],
      1296 => [
        'Id' => 'M35',
        'Name' => 'millivolt - ampere',
        'Description' => '',
      ],
      1297 => [
        'Id' => 'M36',
        'Name' => '30-day month',
        'Description' => 'A unit of count defining the number of months expressed in multiples of 30
            days, one day equals 24 hours.',
      ],
      1298 => [
        'Id' => 'M37',
        'Name' => 'actual/360',
        'Description' => 'A unit of count defining the number of years expressed in multiples of 360
            days, one day equals 24 hours.',
      ],
      1299 => [
        'Id' => 'M38',
        'Name' => 'kilometre per second squared',
        'Description' => '1000-fold of the SI base unit metre divided by the power of the SI base unit
            second by exponent 2.',
      ],
      1300 => [
        'Id' => 'M39',
        'Name' => 'centimetre per second squared',
        'Description' => '0,01-fold of the SI base unit metre divided by the power of the SI base unit
            second by exponent 2.',
      ],
      1301 => [
        'Id' => 'M4',
        'Name' => 'monetary value',
        'Description' => 'A unit of measure expressed as a monetary amount.',
      ],
      1302 => [
        'Id' => 'M40',
        'Name' => 'yard per second squared',
        'Description' => 'Unit of the length according to the Anglo-American and Imperial system of units
            divided by the power of the SI base unit second by exponent 2.',
      ],
      1303 => [
        'Id' => 'M41',
        'Name' => 'millimetre per second squared',
        'Description' => '0,001-fold of the SI base unit metre divided by the power of the SI base unit
            second by exponent 2.',
      ],
      1304 => [
        'Id' => 'M42',
        'Name' => 'mile (statute mile) per second squared',
        'Description' => 'Unit of the length according to the Imperial system of units divided by the
            power of the SI base unit second by exponent 2.',
      ],
      1305 => [
        'Id' => 'M43',
        'Name' => 'mil',
        'Description' => 'Unit to indicate an angle at military zone, equal to the 6400th part of the
            full circle of the 360° or 2·p·rad.',
      ],
      1306 => [
        'Id' => 'M44',
        'Name' => 'revolution',
        'Description' => 'Unit to identify an angle of the full circle of 360° or 2·p·rad (Refer ISO/TC12
            SI Guide).',
      ],
      1307 => [
        'Id' => 'M45',
        'Name' => 'degree [unit of angle] per second squared',
        'Description' => '360 part of a full circle divided by the power of the SI base unit second and
            the exponent 2.',
      ],
      1308 => [
        'Id' => 'M46',
        'Name' => 'revolution per minute',
        'Description' => 'Unit of the angular velocity.',
      ],
      1309 => [
        'Id' => 'M47',
        'Name' => 'circular mil',
        'Description' => 'Unit of an area, of which the size is given by a diameter of length of 1 mm
            (0,001 in) based on the formula: area = p·(diameter/2)².',
      ],
      1310 => [
        'Id' => 'M48',
        'Name' => 'square mile (based on U.S. survey foot)',
        'Description' => 'Unit of the area, which is mainly common in the agriculture and
            forestry.',
      ],
      1311 => [
        'Id' => 'M49',
        'Name' => 'chain (based on U.S. survey foot)',
        'Description' => 'Unit of the length according the Anglo-American system of units.',
      ],
      1312 => [
        'Id' => 'M5',
        'Name' => 'microcurie',
        'Description' => '',
      ],
      1313 => [
        'Id' => 'M50',
        'Name' => 'furlong',
        'Description' => 'Unit commonly used in Great Britain at rural distances: 1 furlong = 40 rods =
            10 chains (UK) = 1/8 mile = 1/10 furlong = 220 yards = 660 foot.',
      ],
      1314 => [
        'Id' => 'M51',
        'Name' => 'foot (U.S. survey)',
        'Description' => 'Unit commonly used in the United States for ordnance survey.',
      ],
      1315 => [
        'Id' => 'M52',
        'Name' => 'mile (based on U.S. survey foot)',
        'Description' => 'Unit commonly used in the United States for ordnance survey.',
      ],
      1316 => [
        'Id' => 'M53',
        'Name' => 'metre per pascal',
        'Description' => 'SI base unit metre divided by the derived SI unit pascal.',
      ],
      1317 => [
        'Id' => 'M55',
        'Name' => 'metre per radiant',
        'Description' => 'Unit of the translation factor for implementation from rotation to linear
            movement.',
      ],
      1318 => [
        'Id' => 'M56',
        'Name' => 'shake',
        'Description' => 'Unit for a very short period.',
      ],
      1319 => [
        'Id' => 'M57',
        'Name' => 'mile per minute',
        'Description' => 'Unit of velocity from the Imperial system of units.',
      ],
      1320 => [
        'Id' => 'M58',
        'Name' => 'mile per second',
        'Description' => 'Unit of the velocity from the Imperial system of units.',
      ],
      1321 => [
        'Id' => 'M59',
        'Name' => 'metre per second pascal',
        'Description' => 'SI base unit meter divided by the product of SI base unit second and the
            derived SI unit pascal.',
      ],
      1322 => [
        'Id' => 'M60',
        'Name' => 'metre per hour',
        'Description' => 'SI base unit metre divided by the unit hour.',
      ],
      1323 => [
        'Id' => 'M61',
        'Name' => 'inch per year',
        'Description' => 'Unit of the length according to the Anglo-American and Imperial system of units
            divided by the unit common year with 365 days.',
      ],
      1324 => [
        'Id' => 'M62',
        'Name' => 'kilometre per second',
        'Description' => '1000-fold of the SI base unit metre divided by the SI base unit
            second.',
      ],
      1325 => [
        'Id' => 'M63',
        'Name' => 'inch per minute',
        'Description' => 'Unit inch according to the Anglo-American and Imperial system of units divided
            by the unit minute.',
      ],
      1326 => [
        'Id' => 'M64',
        'Name' => 'yard per second',
        'Description' => 'Unit yard according to the Anglo-American and Imperial system of units divided
            by the SI base unit second.',
      ],
      1327 => [
        'Id' => 'M65',
        'Name' => 'yard per minute',
        'Description' => 'Unit yard according to the Anglo-American and Imperial system of units divided
            by the unit minute.',
      ],
      1328 => [
        'Id' => 'M66',
        'Name' => 'yard per hour',
        'Description' => 'Unit yard according to the Anglo-American and Imperial system of units divided
            by the unit hour.',
      ],
      1329 => [
        'Id' => 'M67',
        'Name' => 'acre-foot (based on U.S. survey foot)',
        'Description' => 'Unit of the volume, which is used in the United States to measure/gauge the
            capacity of reservoirs.',
      ],
      1330 => [
        'Id' => 'M68',
        'Name' => 'cord (128 ft3)',
        'Description' => 'Traditional unit of the volume of stacked firewood which has been measured with
            a cord.',
      ],
      1331 => [
        'Id' => 'M69',
        'Name' => 'cubic mile (UK statute)',
        'Description' => 'Unit of volume according to the Imperial system of units.',
      ],
      1332 => [
        'Id' => 'M7',
        'Name' => 'micro-inch',
        'Description' => '',
      ],
      1333 => [
        'Id' => 'M70',
        'Name' => 'ton, register',
        'Description' => 'Traditional unit of the cargo capacity.',
      ],
      1334 => [
        'Id' => 'M71',
        'Name' => 'cubic metre per pascal',
        'Description' => 'Power of the SI base unit meter by exponent 3 divided by the derived SI base
            unit pascal.',
      ],
      1335 => [
        'Id' => 'M72',
        'Name' => 'bel',
        'Description' => 'Logarithmic relationship to base 10.',
      ],
      1336 => [
        'Id' => 'M73',
        'Name' => 'kilogram per cubic metre pascal',
        'Description' => 'SI base unit kilogram divided by the product of the power of the SI base unit
            metre with exponent 3 and the derived SI unit pascal.',
      ],
      1337 => [
        'Id' => 'M74',
        'Name' => 'kilogram per pascal',
        'Description' => 'SI base unit kilogram divided by the derived SI unit pascal.',
      ],
      1338 => [
        'Id' => 'M75',
        'Name' => 'kilopound-force',
        'Description' => '1000-fold of the unit of the force pound-force (lbf) according to the
            Anglo-American system of units with the relationship.',
      ],
      1339 => [
        'Id' => 'M76',
        'Name' => 'poundal',
        'Description' => 'Non SI-conforming unit of the power, which corresponds to a mass of a pound
            multiplied with the acceleration of a foot per square second.',
      ],
      1340 => [
        'Id' => 'M77',
        'Name' => 'kilogram metre per second squared',
        'Description' => 'Product of the SI base unit kilogram and the SI base unit metre divided by the
            power of the SI base unit second by exponent 2.',
      ],
      1341 => [
        'Id' => 'M78',
        'Name' => 'pond',
        'Description' => '0,001-fold of the unit of the weight, defined as a mass of 1 kg which finds out
            about a weight strength from 1 kp by the gravitational force at sea level which
            corresponds to a strength of 9,806 65 newton.',
      ],
      1342 => [
        'Id' => 'M79',
        'Name' => 'square foot per hour',
        'Description' => 'Power of the unit foot according to the Anglo-American and Imperial system of
            units by exponent 2 divided by the unit of time hour.',
      ],
      1343 => [
        'Id' => 'M80',
        'Name' => 'stokes per pascal',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit stokes divided by the derived SI unit
            pascal.',
      ],
      1344 => [
        'Id' => 'M81',
        'Name' => 'square centimetre per second',
        'Description' => '0,000 1-fold of the power of the SI base unit metre by exponent 2 divided by
            the SI base unit second.',
      ],
      1345 => [
        'Id' => 'M82',
        'Name' => 'square metre per second pascal',
        'Description' => 'Power of the SI base unit metre with the exponent 2 divided by the SI base unit
            second and the derived SI unit pascal.',
      ],
      1346 => [
        'Id' => 'M83',
        'Name' => 'denier',
        'Description' => 'Traditional unit for the indication of the linear mass of textile fibers and
            yarns.',
      ],
      1347 => [
        'Id' => 'M84',
        'Name' => 'pound per yard',
        'Description' => 'Unit for linear mass according to avoirdupois system of units.',
      ],
      1348 => [
        'Id' => 'M85',
        'Name' => 'ton, assay',
        'Description' => 'Non SI-conforming unit of the mass used in the mineralogy to determine the
            concentration of precious metals in ore according to the mass of the precious metal in
            milligrams in a sample of the mass of an assay sound (number of troy ounces in a short
            ton (1 000 lb)).',
      ],
      1349 => [
        'Id' => 'M86',
        'Name' => 'pfund',
        'Description' => 'Outdated unit of the mass used in Germany.',
      ],
      1350 => [
        'Id' => 'M87',
        'Name' => 'kilogram per second pascal',
        'Description' => 'SI base unit kilogram divided by the product of the SI base unit second and the
            derived SI unit pascal.',
      ],
      1351 => [
        'Id' => 'M88',
        'Name' => 'tonne per month',
        'Description' => 'Unit tonne divided by the unit month.',
      ],
      1352 => [
        'Id' => 'M89',
        'Name' => 'tonne per year',
        'Description' => 'Unit tonne divided by the unit year with 365 days.',
      ],
      1353 => [
        'Id' => 'M9',
        'Name' => 'million Btu per 1000 cubic foot',
        'Description' => '',
      ],
      1354 => [
        'Id' => 'M90',
        'Name' => 'kilopound per hour',
        'Description' => '1000-fold of the unit of the mass avoirdupois pound according to the
            avoirdupois unit system divided by the unit hour.',
      ],
      1355 => [
        'Id' => 'M91',
        'Name' => 'pound per pound',
        'Description' => 'Proportion of the mass consisting of the avoirdupois pound according to the
            avoirdupois unit system divided by the avoirdupois pound according to the avoirdupois
            unit system.',
      ],
      1356 => [
        'Id' => 'M92',
        'Name' => 'pound-force foot',
        'Description' => 'Product of the unit pound-force according to the Anglo-American system of units
            and the unit foot according to the Anglo-American and the Imperial system of
            units.',
      ],
      1357 => [
        'Id' => 'M93',
        'Name' => 'newton metre per radian',
        'Description' => 'Product of the derived SI unit newton and the SI base unit metre divided by the
            unit radian.',
      ],
      1358 => [
        'Id' => 'M94',
        'Name' => 'kilogram metre',
        'Description' => 'Unit of imbalance as a product of the SI base unit kilogram and the SI base
            unit metre.',
      ],
      1359 => [
        'Id' => 'M95',
        'Name' => 'poundal foot',
        'Description' => 'Product of the non SI-conforming unit of the force poundal and the unit foot
            according to the Anglo-American and Imperial system of units .',
      ],
      1360 => [
        'Id' => 'M96',
        'Name' => 'poundal inch',
        'Description' => 'Product of the non SI-conforming unit of the force poundal and the unit inch
            according to the Anglo-American and Imperial system of units .',
      ],
      1361 => [
        'Id' => 'M97',
        'Name' => 'dyne metre',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of the rotational
            moment.',
      ],
      1362 => [
        'Id' => 'M98',
        'Name' => 'kilogram centimetre per second',
        'Description' => 'Product of the SI base unit kilogram and the 0,01-fold of the SI base unit
            metre divided by the SI base unit second.',
      ],
      1363 => [
        'Id' => 'M99',
        'Name' => 'gram centimetre per second',
        'Description' => 'Product of the 0,001-fold of the SI base unit kilogram and the 0,01-fold of the
            SI base unit metre divided by the SI base unit second.',
      ],
      1364 => [
        'Id' => 'MAH',
        'Name' => 'megavolt ampere reactive hour',
        'Description' => 'A unit of electrical reactive power defining the total amount of reactive power
            across a power system.',
      ],
      1365 => [
        'Id' => 'MAL',
        'Name' => 'megalitre',
        'Description' => '',
      ],
      1366 => [
        'Id' => 'MAM',
        'Name' => 'megametre',
        'Description' => '',
      ],
      1367 => [
        'Id' => 'MAR',
        'Name' => 'megavar',
        'Description' => 'A unit of electrical reactive power represented by a current of one thousand
            amperes flowing due a potential difference of one thousand volts where the sine of the
            phase angle between them is 1.',
      ],
      1368 => [
        'Id' => 'MAW',
        'Name' => 'megawatt',
        'Description' => 'A unit of power defining the rate of energy transferred or consumed when a
            current of 1000 amperes flows due to a potential of 1000 volts at unity power
            factor.',
      ],
      1369 => [
        'Id' => 'MBE',
        'Name' => 'thousand standard brick equivalent',
        'Description' => 'A unit of count defining the number of one thousand brick equivalent
            units.',
      ],
      1370 => [
        'Id' => 'MBF',
        'Name' => 'thousand board foot',
        'Description' => 'A unit of volume equal to one thousand board foot.',
      ],
      1371 => [
        'Id' => 'MBR',
        'Name' => 'millibar',
        'Description' => '',
      ],
      1372 => [
        'Id' => 'MC',
        'Name' => 'microgram',
        'Description' => '',
      ],
      1373 => [
        'Id' => 'MCU',
        'Name' => 'millicurie',
        'Description' => '',
      ],
      1374 => [
        'Id' => 'MD',
        'Name' => 'air dry metric ton',
        'Description' => 'A unit of count defining the number of metric tons of a product, disregarding
            the water content of the product.',
      ],
      1375 => [
        'Id' => 'MGM',
        'Name' => 'milligram',
        'Description' => '',
      ],
      1376 => [
        'Id' => 'MHZ',
        'Name' => 'megahertz',
        'Description' => '',
      ],
      1377 => [
        'Id' => 'MIK',
        'Name' => 'square mile (statute mile)',
        'Description' => '',
      ],
      1378 => [
        'Id' => 'MIL',
        'Name' => 'thousand',
        'Description' => '',
      ],
      1379 => [
        'Id' => 'MIN',
        'Name' => 'minute [unit of time]',
        'Description' => '',
      ],
      1380 => [
        'Id' => 'MIO',
        'Name' => 'million',
        'Description' => '',
      ],
      1381 => [
        'Id' => 'MIU',
        'Name' => 'million international unit',
        'Description' => 'A unit of count defining the number of international units in multiples of 10.',
      ],
      1382 => [
        'Id' => 'MKD',
        'Name' => 'Square Metre Day',
      ],
      1383 => [
        'Id' => 'MKM',
        'Name' => 'Square Metre Month',
      ],
      1384 => [
        'Id' => 'MKW',
        'Name' => 'Square Metre Week',
      ],
      1385 => [
        'Id' => 'MLD',
        'Name' => 'milliard',
        'Description' => 'Synonym: billion (US)',
      ],
      1386 => [
        'Id' => 'MLT',
        'Name' => 'millilitre',
        'Description' => '',
      ],
      1387 => [
        'Id' => 'MMK',
        'Name' => 'square millimetre',
        'Description' => '',
      ],
      1388 => [
        'Id' => 'MMQ',
        'Name' => 'cubic millimetre',
        'Description' => '',
      ],
      1389 => [
        'Id' => 'MMT',
        'Name' => 'millimetre',
        'Description' => '',
      ],
      1390 => [
        'Id' => 'MND',
        'Name' => 'kilogram, dry weight',
        'Description' => 'A unit of mass defining the number of kilograms of a product, disregarding the
            water content of the product.',
      ],
      1391 => [
        'Id' => 'MNJ',
        'Name' => 'Mega Joule per Normalised cubic Metre',
      ],
      1392 => [
        'Id' => 'MON',
        'Name' => 'month',
        'Description' => 'Unit of time equal to 1/12 of a year of 365,25 days.',
      ],
      1393 => [
        'Id' => 'MPA',
        'Name' => 'megapascal',
        'Description' => '',
      ],
      1394 => [
        'Id' => 'MQD',
        'Name' => 'Cubic Metre Day',
      ],
      1395 => [
        'Id' => 'MQH',
        'Name' => 'cubic metre per hour',
        'Description' => '',
      ],
      1396 => [
        'Id' => 'MQM',
        'Name' => 'Cubic Metre Month',
      ],
      1397 => [
        'Id' => 'MQS',
        'Name' => 'cubic metre per second',
        'Description' => '',
      ],
      1398 => [
        'Id' => 'MQW',
        'Name' => 'Cubic Metre Week',
      ],
      1399 => [
        'Id' => 'MRD',
        'Name' => 'Metre Day',
      ],
      1400 => [
        'Id' => 'MRM',
        'Name' => 'Metre Month',
      ],
      1401 => [
        'Id' => 'MRW',
        'Name' => 'Metre Week',
      ],
      1402 => [
        'Id' => 'MSK',
        'Name' => 'metre per second squared',
        'Description' => '',
      ],
      1403 => [
        'Id' => 'MTK',
        'Name' => 'square metre',
        'Description' => '',
      ],
      1404 => [
        'Id' => 'MTQ',
        'Name' => 'cubic metre',
        'Description' => 'Synonym: metre cubed',
      ],
      1405 => [
        'Id' => 'MTR',
        'Name' => 'metre',
        'Description' => '',
      ],
      1406 => [
        'Id' => 'MTS',
        'Name' => 'metre per second',
        'Description' => '',
      ],
      1407 => [
        'Id' => 'MTZ',
        'Name' => 'milihertz',
      ],
      1408 => [
        'Id' => 'MVA',
        'Name' => 'megavolt - ampere',
        'Description' => '',
      ],
      1409 => [
        'Id' => 'MWH',
        'Name' => 'megawatt hour (1000 kW.h)',
        'Description' => 'A unit of power defining the total amount of bulk energy transferred or
            consumed.',
      ],
      1410 => [
        'Id' => 'N1',
        'Name' => 'pen calorie',
        'Description' => 'A unit of count defining the number of calories prescribed daily for
            parenteral/enteral therapy.',
      ],
      1411 => [
        'Id' => 'N10',
        'Name' => 'pound foot per second',
        'Description' => 'Product of the avoirdupois pound according to the avoirdupois unit system and
            the unit foot according to the Anglo-American and Imperial system of units divided by
            the SI base unit second.',
      ],
      1412 => [
        'Id' => 'N11',
        'Name' => 'pound inch per second',
        'Description' => 'Product of the avoirdupois pound according to the avoirdupois unit system and
            the unit inch according to the Anglo-American and Imperial system of units divided by
            the SI base unit second.',
      ],
      1413 => [
        'Id' => 'N12',
        'Name' => 'Pferdestaerke',
        'Description' => 'Obsolete unit of the power relating to DIN 1301-3:1979: 1 PS = 735,498 75
            W.',
      ],
      1414 => [
        'Id' => 'N13',
        'Name' => 'centimetre of mercury (0 ºC)',
        'Description' => 'Non SI-conforming unit of pressure, at which a value of 1 cmHg meets the static
            pressure, which is generated by a mercury at a temperature of 0 °C with a height of 1
            centimetre .',
      ],
      1415 => [
        'Id' => 'N14',
        'Name' => 'centimetre of water (4 ºC)',
        'Description' => 'Non SI-conforming unit of pressure, at which a value of 1 cmH2O meets the
            static pressure, which is generated by a head of water at a temperature of 4 °C with a
            height of 1 centimetre .',
      ],
      1416 => [
        'Id' => 'N15',
        'Name' => 'foot of water (39.2 ºF)',
        'Description' => 'Non SI-conforming unit of pressure according to the Anglo-American and Imperial
            system for units, whereas the value of 1 ftH2O is equivalent to the static pressure,
            which is generated by a head of water at a temperature 39,2°F with a height of 1 foot
            .',
      ],
      1417 => [
        'Id' => 'N16',
        'Name' => 'inch of mercury (32 ºF)',
        'Description' => 'Non SI-conforming unit of pressure according to the Anglo-American and Imperial
            system for units, whereas the value of 1 inHg meets the static pressure, which is
            generated by a mercury at a temperature of 32°F with a height of 1 inch.',
      ],
      1418 => [
        'Id' => 'N17',
        'Name' => 'inch of mercury (60 ºF)',
        'Description' => 'Non SI-conforming unit of pressure according to the Anglo-American and Imperial
            system for units, whereas the value of 1 inHg meets the static pressure, which is
            generated by a mercury at a temperature of 60°F with a height of 1 inch.',
      ],
      1419 => [
        'Id' => 'N18',
        'Name' => 'inch of water (39.2 ºF)',
        'Description' => 'Non SI-conforming unit of pressure according to the Anglo-American and Imperial
            system for units, whereas the value of 1 inH2O meets the static pressure, which is
            generated by a head of water at a temperature of 39,2°F with a height of 1 inch
            .',
      ],
      1420 => [
        'Id' => 'N19',
        'Name' => 'inch of water (60 ºF)',
        'Description' => 'Non SI-conforming unit of pressure according to the Anglo-American and Imperial
            system for units, whereas the value of 1 inH2O meets the static pressure, which is
            generated by a head of water at a temperature of 60°F with a height of 1 inch
            .',
      ],
      1421 => [
        'Id' => 'N20',
        'Name' => 'kip per square inch',
        'Description' => 'Non SI-conforming unit of the pressure according to the Anglo-American system
            of units as the 1000-fold of the unit of the force pound-force divided by the power of
            the unit inch by exponent 2.',
      ],
      1422 => [
        'Id' => 'N21',
        'Name' => 'poundal per square foot',
        'Description' => 'Non SI-conforming unit of pressure by the Imperial system of units according to
            NIST: 1 pdl/ft² = 1,488 164 Pa.',
      ],
      1423 => [
        'Id' => 'N22',
        'Name' => 'ounce (avoirdupois) per square inch',
        'Description' => 'Unit of the surface specific mass (avoirdupois ounce according to the
            avoirdupois system of units according to the surface square inch according to the
            Anglo-American and Imperial system of units).',
      ],
      1424 => [
        'Id' => 'N23',
        'Name' => 'conventional metre of water',
        'Description' => 'Not SI-conforming unit of pressure, whereas a value of 1 mH2O is equivalent to
            the static pressure, which is produced by one metre high water column .',
      ],
      1425 => [
        'Id' => 'N24',
        'Name' => 'gram per square millimetre',
        'Description' => '0,001-fold of the SI base unit kilogram divided by the 0.000 001-fold of the
            power of the SI base unit meter by exponent 2.',
      ],
      1426 => [
        'Id' => 'N25',
        'Name' => 'pound per square yard',
        'Description' => 'Unit for areal-related mass as a unit pound according to the avoirdupois unit
            system divided by the power of the unit yard according to the Anglo-American and
            Imperial system of units with exponent 2.',
      ],
      1427 => [
        'Id' => 'N26',
        'Name' => 'poundal per square inch',
        'Description' => 'Non SI-conforming unit of the pressure according to the Imperial system of
            units (poundal by square inch).',
      ],
      1428 => [
        'Id' => 'N27',
        'Name' => 'foot to the fourth power',
        'Description' => 'Power of the unit foot according to the Anglo-American and Imperial system of
            units by exponent 4 according to NIST: 1 ft4 = 8,630 975 m4.',
      ],
      1429 => [
        'Id' => 'N28',
        'Name' => 'cubic decimetre per kilogram',
        'Description' => '0,001 fold of the power of the SI base unit meter by exponent 3 divided by the
            SI based unit kilogram.',
      ],
      1430 => [
        'Id' => 'N29',
        'Name' => 'cubic foot per pound',
        'Description' => 'Power of the unit foot according to the Anglo-American and Imperial system of
            units by exponent 3 divided by the unit avoirdupois pound according to the avoirdupois
            unit system.',
      ],
      1431 => [
        'Id' => 'N3',
        'Name' => 'print point',
        'Description' => '',
      ],
      1432 => [
        'Id' => 'N30',
        'Name' => 'cubic inch per pound',
        'Description' => 'Power of the unit inch according to the Anglo-American and Imperial system of
            units by exponent 3 divided by the avoirdupois pound according to the avoirdupois unit
            system .',
      ],
      1433 => [
        'Id' => 'N31',
        'Name' => 'kilonewton per metre',
        'Description' => '1000-fold of the derived SI unit newton divided by the SI base unit
            metre.',
      ],
      1434 => [
        'Id' => 'N32',
        'Name' => 'poundal per inch',
        'Description' => 'Non SI-conforming unit of the surface tension according to the Imperial unit
            system as quotient poundal by inch.',
      ],
      1435 => [
        'Id' => 'N33',
        'Name' => 'pound-force per yard',
        'Description' => 'Unit of force per unit length based on the Anglo-American system of
            units.',
      ],
      1436 => [
        'Id' => 'N34',
        'Name' => 'poundal second per square foot',
        'Description' => 'Non SI-conforming unit of viscosity.',
      ],
      1437 => [
        'Id' => 'N35',
        'Name' => 'poise per pascal',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit poise divided by the derived SI unit
            pascal.',
      ],
      1438 => [
        'Id' => 'N36',
        'Name' => 'newton second per square metre',
        'Description' => 'Unit of the dynamic viscosity as a product of unit of the pressure (newton by
            square metre) multiplied with the SI base unit second.',
      ],
      1439 => [
        'Id' => 'N37',
        'Name' => 'kilogram per metre second',
        'Description' => 'Unit of the dynamic viscosity as a quotient SI base unit kilogram divided by
            the SI base unit metre and by the SI base unit second.',
      ],
      1440 => [
        'Id' => 'N38',
        'Name' => 'kilogram per metre minute',
        'Description' => 'Unit of the dynamic viscosity as a quotient SI base unit kilogram divided by
            the SI base unit metre and by the unit minute.',
      ],
      1441 => [
        'Id' => 'N39',
        'Name' => 'kilogram per metre day',
        'Description' => 'Unit of the dynamic viscosity as a quotient SI base unit kilogram divided by
            the SI base unit metre and by the unit day.',
      ],
      1442 => [
        'Id' => 'N40',
        'Name' => 'kilogram per metre hour',
        'Description' => 'Unit of the dynamic viscosity as a quotient SI base unit kilogram divided by
            the SI base unit metre and by the unit hour.',
      ],
      1443 => [
        'Id' => 'N41',
        'Name' => 'gram per centimetre second',
        'Description' => 'Unit of the dynamic viscosity as a quotient of the 0,001-fold of the SI base
            unit kilogram divided by the 0,01-fold of the SI base unit metre and SI base unit
            second.',
      ],
      1444 => [
        'Id' => 'N42',
        'Name' => 'poundal second per square inch',
        'Description' => 'Non SI-conforming unit of dynamic viscosity according to the Imperial system of
            units as product unit of the pressure (poundal by square inch) multiplied by the SI base
            unit second.',
      ],
      1445 => [
        'Id' => 'N43',
        'Name' => 'pound per foot minute',
        'Description' => 'Unit of the dynamic viscosity according to the Anglo-American unit
            system.',
      ],
      1446 => [
        'Id' => 'N44',
        'Name' => 'pound per foot day',
        'Description' => 'Unit of the dynamic viscosity according to the Anglo-American unit
            system.',
      ],
      1447 => [
        'Id' => 'N45',
        'Name' => 'cubic metre per second pascal',
        'Description' => 'Power of the SI base unit meter by exponent 3 divided by the product of the SI
            base unit second and the derived SI base unit pascal.',
      ],
      1448 => [
        'Id' => 'N46',
        'Name' => 'foot poundal',
        'Description' => 'Unit of the work (force-path).',
      ],
      1449 => [
        'Id' => 'N47',
        'Name' => 'inch poundal',
        'Description' => 'Unit of work (force multiplied by path) according to the Imperial system of
            units as a product unit inch multiplied by poundal.',
      ],
      1450 => [
        'Id' => 'N48',
        'Name' => 'watt per square centimetre',
        'Description' => 'Derived SI unit watt divided by the power of the 0,01-fold the SI base unit
            metre by exponent 2.',
      ],
      1451 => [
        'Id' => 'N49',
        'Name' => 'watt per square inch',
        'Description' => 'Derived SI unit watt divided by the power of the unit inch according to the
            Anglo-American and Imperial system of units by exponent 2.',
      ],
      1452 => [
        'Id' => 'N50',
        'Name' => 'British thermal unit (international table) per square foot hour',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1453 => [
        'Id' => 'N51',
        'Name' => 'British thermal unit (thermochemical) per square foot hour',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1454 => [
        'Id' => 'N52',
        'Name' => 'British thermal unit (thermochemical) per square foot minute',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1455 => [
        'Id' => 'N53',
        'Name' => 'British thermal unit (international table) per square foot second',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1456 => [
        'Id' => 'N54',
        'Name' => 'British thermal unit (thermochemical) per square foot second',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1457 => [
        'Id' => 'N55',
        'Name' => 'British thermal unit (international table) per square inch second',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1458 => [
        'Id' => 'N56',
        'Name' => 'calorie (thermochemical) per square centimetre minute',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1459 => [
        'Id' => 'N57',
        'Name' => 'calorie (thermochemical) per square centimetre second',
        'Description' => 'Unit of the surface heat flux according to the Imperial system of
            units.',
      ],
      1460 => [
        'Id' => 'N58',
        'Name' => 'British thermal unit (international table) per cubic foot',
        'Description' => 'Unit of the energy density according to the Imperial system of
            units.',
      ],
      1461 => [
        'Id' => 'N59',
        'Name' => 'British thermal unit (thermochemical) per cubic foot',
        'Description' => 'Unit of the energy density according to the Imperial system of
            units.',
      ],
      1462 => [
        'Id' => 'N60',
        'Name' => 'British thermal unit (international table) per degree Fahrenheit',
        'Description' => 'Unit of the heat capacity according to the Imperial system of
            units.',
      ],
      1463 => [
        'Id' => 'N61',
        'Name' => 'British thermal unit (thermochemical) per degree Fahrenheit',
        'Description' => 'Unit of the heat capacity according to the Imperial system of
            units.',
      ],
      1464 => [
        'Id' => 'N62',
        'Name' => 'British thermal unit (international table) per degree Rankine',
        'Description' => 'Unit of the heat capacity according to the Imperial system of
            units.',
      ],
      1465 => [
        'Id' => 'N63',
        'Name' => 'British thermal unit (thermochemical) per degree Rankine',
        'Description' => 'Unit of the heat capacity according to the Imperial system of
            units.',
      ],
      1466 => [
        'Id' => 'N64',
        'Name' => 'British thermal unit (thermochemical) per pound degree Rankine',
        'Description' => 'Unit of the heat capacity (British thermal unit according to the international
            table according to the Rankine degree) according to the Imperial system of units divided
            by the unit avoirdupois pound according to the avoirdupois system of
            units.',
      ],
      1467 => [
        'Id' => 'N65',
        'Name' => 'kilocalorie (international table) per gram kelvin',
        'Description' => 'Unit of the mass-related heat capacity as quotient 1000-fold of the calorie
            (international table) divided by the product of the 0,001-fold of the SI base units
            kilogram and kelvin.',
      ],
      1468 => [
        'Id' => 'N66',
        'Name' => 'British thermal unit (39 ºF)',
        'Description' => 'Unit of heat energy according to the Imperial system of units in a reference
            temperature of 39 °F.',
      ],
      1469 => [
        'Id' => 'N67',
        'Name' => 'British thermal unit (59 ºF)',
        'Description' => 'Unit of heat energy according to the Imperial system of units in a reference
            temperature of 59 °F.',
      ],
      1470 => [
        'Id' => 'N68',
        'Name' => 'British thermal unit (60 ºF)',
        'Description' => 'Unit of head energy according to the Imperial system of units at a reference
            temperature of 60 °F.',
      ],
      1471 => [
        'Id' => 'N69',
        'Name' => 'calorie (20 ºC)',
        'Description' => 'Unit for quantity of heat, which is to be required for 1 g air free water at a
            constant pressure from 101,325 kPa, to warm up the pressure of standard atmosphere at
            sea level, from 19,5 °C on 20,5 °C.',
      ],
      1472 => [
        'Id' => 'N70',
        'Name' => 'quad (1015 BtuIT)',
        'Description' => 'Unit of heat energy according to the imperial system of units.',
      ],
      1473 => [
        'Id' => 'N71',
        'Name' => 'therm (EC)',
        'Description' => 'Unit of heat energy in commercial use, within the EU defined: 1 thm (EC) = 100
            000 BtuIT.',
      ],
      1474 => [
        'Id' => 'N72',
        'Name' => 'therm (U.S.)',
        'Description' => 'Unit of heat energy in commercial use.',
      ],
      1475 => [
        'Id' => 'N73',
        'Name' => 'British thermal unit (thermochemical) per pound',
        'Description' => 'Unit of the heat energy according to the Imperial system of units divided the
            unit avoirdupois pound according to the avoirdupois system of units.',
      ],
      1476 => [
        'Id' => 'N74',
        'Name' => 'British thermal unit (international table) per hour square foot degree
            Fahrenheit',
        'Description' => 'Unit of the heat transition coefficient according to the Imperial system of
            units.',
      ],
      1477 => [
        'Id' => 'N75',
        'Name' => 'British thermal unit (thermochemical) per hour square foot degree Fahrenheit',
        'Description' => 'Unit of the heat transition coefficient according to the imperial system of
            units.',
      ],
      1478 => [
        'Id' => 'N76',
        'Name' => 'British thermal unit (international table) per second square foot degree
            Fahrenheit',
        'Description' => 'Unit of the heat transition coefficient according to the imperial system of
            units.',
      ],
      1479 => [
        'Id' => 'N77',
        'Name' => 'British thermal unit (thermochemical) per second square foot degree Fahrenheit',
        'Description' => 'Unit of the heat transition coefficient according to the imperial system of
            units.',
      ],
      1480 => [
        'Id' => 'N78',
        'Name' => 'kilowatt per square metre kelvin',
        'Description' => '1000-fold of the derived SI unit watt divided by the product of the power of
            the SI base unit metre by exponent 2 and the SI base unit kelvin.',
      ],
      1481 => [
        'Id' => 'N79',
        'Name' => 'kelvin per pascal',
        'Description' => 'SI base unit kelvin divided by the derived SI unit pascal.',
      ],
      1482 => [
        'Id' => 'N80',
        'Name' => 'watt per metre degree Celsius',
        'Description' => 'Derived SI unit watt divided by the product of the SI base unit metre and the
            unit for temperature degree Celsius.',
      ],
      1483 => [
        'Id' => 'N81',
        'Name' => 'kilowatt per metre kelvin',
        'Description' => '1000-fold of the derived SI unit watt divided by the product of the SI base
            unit metre and the SI base unit kelvin.',
      ],
      1484 => [
        'Id' => 'N82',
        'Name' => 'kilowatt per metre degree Celsius',
        'Description' => '1000-fold of the derived SI unit watt divided by the product of the SI base
            unit metre and the unit for temperature degree Celsius.',
      ],
      1485 => [
        'Id' => 'N83',
        'Name' => 'metre per degree Celcius metre',
        'Description' => 'SI base unit metre divided by the product of the unit degree Celsius and the SI
            base unit metre.',
      ],
      1486 => [
        'Id' => 'N84',
        'Name' => 'degree Fahrenheit hour per British thermal unit (international table)',
        'Description' => 'Non SI-conforming unit of the thermal resistance according to the Imperial
            system of units.',
      ],
      1487 => [
        'Id' => 'N85',
        'Name' => 'degree Fahrenheit hour per British thermal unit (thermochemical)',
        'Description' => 'Non SI-conforming unit of the thermal resistance according to the Imperial
            system of units.',
      ],
      1488 => [
        'Id' => 'N86',
        'Name' => 'degree Fahrenheit second per British thermal unit (international table)',
        'Description' => 'Non SI-conforming unit of the thermal resistance according to the Imperial
            system of units.',
      ],
      1489 => [
        'Id' => 'N87',
        'Name' => 'degree Fahrenheit second per British thermal unit (thermochemical)',
        'Description' => 'Non SI-conforming unit of the thermal resistance according to the Imperial
            system of units.',
      ],
      1490 => [
        'Id' => 'N88',
        'Name' => 'degree Fahrenheit hour square foot per British thermal unit (international table)
            inch',
        'Description' => 'Unit of specific thermal resistance according to the Imperial system of
            units.',
      ],
      1491 => [
        'Id' => 'N89',
        'Name' => 'degree Fahrenheit hour square foot per British thermal unit (thermochemical)
            inch',
        'Description' => 'Unit of specific thermal resistance according to the Imperial system of
            units.',
      ],
      1492 => [
        'Id' => 'N90',
        'Name' => 'kilofarad',
        'Description' => '1000-fold of the derived SI unit farad.',
      ],
      1493 => [
        'Id' => 'N91',
        'Name' => 'reciprocal joule',
        'Description' => 'Reciprocal of the derived SI unit joule.',
      ],
      1494 => [
        'Id' => 'N92',
        'Name' => 'picosiemens',
        'Description' => '0,000 000 000 001-fold of the derived SI unit siemens.',
      ],
      1495 => [
        'Id' => 'N93',
        'Name' => 'ampere per pascal',
        'Description' => 'SI base unit ampere divided by the derived SI unit pascal.',
      ],
      1496 => [
        'Id' => 'N94',
        'Name' => 'franklin',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of the electrical charge, where the
            charge amounts to exactly 1 Fr where the force of 1 dyn on an equal load is performed at
            a distance of 1 cm.',
      ],
      1497 => [
        'Id' => 'N95',
        'Name' => 'ampere minute',
        'Description' => 'A unit of electric charge defining the amount of charge accumulated by a steady
            flow of one ampere for one minute..',
      ],
      1498 => [
        'Id' => 'N96',
        'Name' => 'biot',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of the electric power which is defined
            by a force of 2 dyn per cm between two parallel conductors of infinite length with
            negligible cross-section in the distance of 1 cm.',
      ],
      1499 => [
        'Id' => 'N97',
        'Name' => 'gilbert',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of the magnetomotive force, which is
            defined by the work to increase the magnetic potential of a positive common pol with 1
            erg.',
      ],
      1500 => [
        'Id' => 'N98',
        'Name' => 'volt per pascal',
        'Description' => 'Derived SI unit volt divided by the derived SI unit pascal.',
      ],
      1501 => [
        'Id' => 'N99',
        'Name' => 'picovolt',
        'Description' => '0,000 000 000 001-fold of the derived SI unit volt.',
      ],
      1502 => [
        'Id' => 'NA',
        'Name' => 'milligram per kilogram',
        'Description' => '',
      ],
      1503 => [
        'Id' => 'NAR',
        'Name' => 'number of articles',
        'Description' => 'A unit of count defining the number of articles (article: item).',
      ],
      1504 => [
        'Id' => 'NCL',
        'Name' => 'number of cells',
        'Description' => 'A unit of count defining the number of cells (cell: an enclosed or
            circumscribed space, cavity, or volume).',
      ],
      1505 => [
        'Id' => 'NEW',
        'Name' => 'newton',
        'Description' => '',
      ],
      1506 => [
        'Id' => 'NF',
        'Name' => 'message',
        'Description' => 'A unit of count defining the number of messages.',
      ],
      1507 => [
        'Id' => 'NIL',
        'Name' => 'nil',
        'Description' => 'A unit of count defining the number of instances of nothing.',
      ],
      1508 => [
        'Id' => 'NIU',
        'Name' => 'number of international units',
        'Description' => 'A unit of count defining the number of international units.',
      ],
      1509 => [
        'Id' => 'NL',
        'Name' => 'load',
        'Description' => 'A unit of volume defining the number of loads (load: a quantity of items
            carried or processed at one time).',
      ],
      1510 => [
        'Id' => 'NM3',
        'Name' => 'Normalised cubic metre',
        'Description' => 'Normalised cubic metre (temperature 0°C and pressure 101325 millibars
            )',
      ],
      1511 => [
        'Id' => 'NMI',
        'Name' => 'nautical mile',
        'Description' => '',
      ],
      1512 => [
        'Id' => 'NMP',
        'Name' => 'number of packs',
        'Description' => 'A unit of count defining the number of packs (pack: a collection of objects
            packaged together).',
      ],
      1513 => [
        'Id' => 'NPT',
        'Name' => 'number of parts',
        'Description' => 'A unit of count defining the number of parts (part: component of a larger
            entity).',
      ],
      1514 => [
        'Id' => 'NT',
        'Name' => 'net ton',
        'Description' => 'A unit of mass equal to 2000 pounds, see ton (US). Refer International
            Convention on tonnage measurement of Ships.',
      ],
      1515 => [
        'Id' => 'NTU',
        'Name' => 'Nephelometric turbidity unit',
      ],
      1516 => [
        'Id' => 'NU',
        'Name' => 'newton metre',
        'Description' => '',
      ],
      1517 => [
        'Id' => 'NX',
        'Name' => 'part per thousand',
        'Description' => 'A unit of proportion equal to 10⁻³. Synonym: per mille',
      ],
      1518 => [
        'Id' => 'OA',
        'Name' => 'panel',
        'Description' => 'A unit of count defining the number of panels (panel: a distinct, usually
            rectangular, section of a surface).',
      ],
      1519 => [
        'Id' => 'ODE',
        'Name' => 'ozone depletion equivalent',
        'Description' => 'A unit of mass defining the ozone depletion potential in kilograms of a product
            relative to the calculated depletion for the reference substance, Trichlorofluoromethane
            (CFC-11).',
      ],
      1520 => [
        'Id' => 'ODG',
        'Name' => 'ODS Grams',
      ],
      1521 => [
        'Id' => 'ODK',
        'Name' => 'ODS Kilograms',
      ],
      1522 => [
        'Id' => 'ODM',
        'Name' => 'ODS Milligrams',
      ],
      1523 => [
        'Id' => 'OHM',
        'Name' => 'ohm',
        'Description' => '',
      ],
      1524 => [
        'Id' => 'ON',
        'Name' => 'ounce per square yard',
        'Description' => '',
      ],
      1525 => [
        'Id' => 'ONZ',
        'Name' => 'ounce (avoirdupois)',
        'Description' => '',
      ],
      1526 => [
        'Id' => 'OPM',
        'Name' => 'oscillations per minute',
        'Description' => 'The number of oscillations per minute.',
      ],
      1527 => [
        'Id' => 'OT',
        'Name' => 'overtime hour',
        'Description' => 'A unit of time defining the number of overtime hours.',
      ],
      1528 => [
        'Id' => 'OZA',
        'Name' => 'fluid ounce (US)',
        'Description' => '',
      ],
      1529 => [
        'Id' => 'OZI',
        'Name' => 'fluid ounce (UK)',
        'Description' => '',
      ],
      1530 => [
        'Id' => 'P1',
        'Name' => 'percent',
        'Description' => 'A unit of proportion equal to 0.01.',
      ],
      1531 => [
        'Id' => 'P10',
        'Name' => 'coulomb per metre',
        'Description' => 'Derived SI unit coulomb divided by the SI base unit metre.',
      ],
      1532 => [
        'Id' => 'P11',
        'Name' => 'kiloweber',
        'Description' => '1000 fold of the derived SI unit weber.',
      ],
      1533 => [
        'Id' => 'P12',
        'Name' => 'gamma',
        'Description' => 'Unit of magnetic flow density.',
      ],
      1534 => [
        'Id' => 'P13',
        'Name' => 'kilotesla',
        'Description' => '1000-fold of the derived SI unit tesla.',
      ],
      1535 => [
        'Id' => 'P14',
        'Name' => 'joule per second',
        'Description' => 'Quotient of the derived SI unit joule divided by the SI base unit
            second.',
      ],
      1536 => [
        'Id' => 'P15',
        'Name' => 'joule per minute',
        'Description' => 'Quotient from the derived SI unit joule divided by the unit
            minute.',
      ],
      1537 => [
        'Id' => 'P16',
        'Name' => 'joule per hour',
        'Description' => 'Quotient from the derived SI unit joule divided by the unit hour.',
      ],
      1538 => [
        'Id' => 'P17',
        'Name' => 'joule per day',
        'Description' => 'Quotient from the derived SI unit joule divided by the unit day.',
      ],
      1539 => [
        'Id' => 'P18',
        'Name' => 'kilojoule per second',
        'Description' => 'Quotient from the 1000-fold of the derived SI unit joule divided by the SI base
            unit second.',
      ],
      1540 => [
        'Id' => 'P19',
        'Name' => 'kilojoule per minute',
        'Description' => 'Quotient from the 1000-fold of the derived SI unit joule divided by the unit
            minute.',
      ],
      1541 => [
        'Id' => 'P2',
        'Name' => 'pound per foot',
        'Description' => '',
      ],
      1542 => [
        'Id' => 'P20',
        'Name' => 'kilojoule per hour',
        'Description' => 'Quotient from the 1000-fold of the derived SI unit joule divided by the unit
            hour.',
      ],
      1543 => [
        'Id' => 'P21',
        'Name' => 'kilojoule per day',
        'Description' => 'Quotient from the 1000-fold of the derived SI unit joule divided by the unit
            day.',
      ],
      1544 => [
        'Id' => 'P22',
        'Name' => 'nanoohm',
        'Description' => '0,000 000 001-fold of the derived SI unit ohm.',
      ],
      1545 => [
        'Id' => 'P23',
        'Name' => 'ohm circular-mil per foot',
        'Description' => 'Unit of resistivity.',
      ],
      1546 => [
        'Id' => 'P24',
        'Name' => 'kilohenry',
        'Description' => '1000-fold of the derived SI unit henry.',
      ],
      1547 => [
        'Id' => 'P25',
        'Name' => 'lumen per square foot',
        'Description' => 'Derived SI unit lumen divided by the power of the unit foot according to the
            Anglo-American and Imperial system of units by exponent 2.',
      ],
      1548 => [
        'Id' => 'P26',
        'Name' => 'phot',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of luminance, defined as lumen by
            square centimetre.',
      ],
      1549 => [
        'Id' => 'P27',
        'Name' => 'footcandle',
        'Description' => 'Non SI conform traditional unit, defined as density of light which impinges on
            a surface which has a distance of one foot from a light source, which shines with an
            intensity of an international candle.',
      ],
      1550 => [
        'Id' => 'P28',
        'Name' => 'candela per square inch',
        'Description' => 'SI base unit candela divided by the power of unit inch according to the
            Anglo-American and Imperial system of units by exponent 2.',
      ],
      1551 => [
        'Id' => 'P29',
        'Name' => 'footlambert',
        'Description' => 'Unit of the luminance according to the Anglo-American system of units, defined
            as emitted or reflected luminance of a lm/ft².',
      ],
      1552 => [
        'Id' => 'P30',
        'Name' => 'lambert',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of luminance, defined as the emitted
            or reflected luminance by one lumen per square centimetre.',
      ],
      1553 => [
        'Id' => 'P31',
        'Name' => 'stilb',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of luminance, defined as emitted or
            reflected luminance by one lumen per square centimetre.',
      ],
      1554 => [
        'Id' => 'P32',
        'Name' => 'candela per square foot',
        'Description' => 'Base unit SI candela divided by the power of the unit foot according to the
            Anglo-American and Imperial system of units by exponent 2.',
      ],
      1555 => [
        'Id' => 'P33',
        'Name' => 'kilocandela',
        'Description' => '1000-fold of the SI base unit candela.',
      ],
      1556 => [
        'Id' => 'P34',
        'Name' => 'millicandela',
        'Description' => '0,001-fold of the SI base unit candela.',
      ],
      1557 => [
        'Id' => 'P35',
        'Name' => 'Hefner-Kerze',
        'Description' => 'Obsolete, non-legal unit of the power in Germany relating to DIN 1301-3:1979: 1
            HK = 0,903 cd.',
      ],
      1558 => [
        'Id' => 'P36',
        'Name' => 'international candle',
        'Description' => 'Obsolete, non-legal unit of the power in Germany relating to DIN 1301-3:1979: 1
            HK = 1,019 cd.',
      ],
      1559 => [
        'Id' => 'P37',
        'Name' => 'British thermal unit (international table) per square foot',
        'Description' => 'Unit of the areal-related energy transmission according to the Imperial system
            of units.',
      ],
      1560 => [
        'Id' => 'P38',
        'Name' => 'British thermal unit (thermochemical) per square foot',
        'Description' => 'Unit of the areal-related energy transmission according to the Imperial system
            of units.',
      ],
      1561 => [
        'Id' => 'P39',
        'Name' => 'calorie (thermochemical) per square centimetre',
        'Description' => 'Unit of the areal-related energy transmission according to the Imperial system
            of units.',
      ],
      1562 => [
        'Id' => 'P40',
        'Name' => 'langley',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of the areal-related energy
            transmission (as a measure of the incident quantity of heat of solar radiation on the
            earth\'s surface).',
      ],
      1563 => [
        'Id' => 'P41',
        'Name' => 'decade (logarithmic)',
        'Description' => '1 Dec := log2 10 ˜ 3,32 according to the logarithm for frequency range between
            f1 and f2, when f2/f1 = 10.',
      ],
      1564 => [
        'Id' => 'P42',
        'Name' => 'pascal squared second',
        'Description' => 'Unit of the set as a product of the power of derived SI unit pascal with
            exponent 2 and the SI base unit second.',
      ],
      1565 => [
        'Id' => 'P43',
        'Name' => 'bel per metre',
        'Description' => 'Unit bel divided by the SI base unit metre.',
      ],
      1566 => [
        'Id' => 'P44',
        'Name' => 'pound mole',
        'Description' => 'Non SI-conforming unit of quantity of a substance relating that one pound mole
            of a chemical composition corresponds to the same number of pounds as the molecular
            weight of one molecule of this composition in atomic mass units.',
      ],
      1567 => [
        'Id' => 'P45',
        'Name' => 'pound mole per second',
        'Description' => 'Non SI-conforming unit of the power of the amount of substance non-SI compliant
            unit of the molar flux relating that a pound mole of a chemical composition the same
            number of pound corresponds like the molecular weight of a molecule of this composition
            in atomic mass units.',
      ],
      1568 => [
        'Id' => 'P46',
        'Name' => 'pound mole per minute',
        'Description' => 'Non SI-conforming unit of the power of the amount of substance non-SI compliant
            unit of the molar flux relating that a pound mole of a chemical composition the same
            number of pound corresponds like the molecular weight of a molecule of this composition
            in atomic mass units.',
      ],
      1569 => [
        'Id' => 'P47',
        'Name' => 'kilomole per kilogram',
        'Description' => '1000-fold of the SI base unit mol divided by the SI base unit
            kilogram.',
      ],
      1570 => [
        'Id' => 'P48',
        'Name' => 'pound mole per pound',
        'Description' => 'Non SI-conforming unit of the material molar flux divided by the avoirdupois
            pound for mass according to the avoirdupois unit system.',
      ],
      1571 => [
        'Id' => 'P49',
        'Name' => 'newton square metre per ampere',
        'Description' => 'Product of the derived SI unit newton and the power of SI base unit metre with
            exponent 2 divided by the SI base unit ampere.',
      ],
      1572 => [
        'Id' => 'P5',
        'Name' => 'five pack',
        'Description' => 'A unit of count defining the number of five-packs (five-pack: set of five items
            packaged together).',
      ],
      1573 => [
        'Id' => 'P50',
        'Name' => 'weber metre',
        'Description' => 'Product of the derived SI unit weber and SI base unit metre.',
      ],
      1574 => [
        'Id' => 'P51',
        'Name' => 'mol per kilogram pascal',
        'Description' => 'SI base unit mol divided by the product of the SI base unit kilogram and the
            derived SI unit pascal.',
      ],
      1575 => [
        'Id' => 'P52',
        'Name' => 'mol per cubic metre pascal',
        'Description' => 'SI base unit mol divided by the product of the power from the SI base unit
            metre with exponent 3 and the derived SI unit pascal.',
      ],
      1576 => [
        'Id' => 'P53',
        'Name' => 'unit pole',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit for magnetic flux of a magnetic pole
            (according to the interaction of identical poles of 1 dyn at a distance of a
            cm).',
      ],
      1577 => [
        'Id' => 'P54',
        'Name' => 'milligray per second',
        'Description' => '0,001-fold of the derived SI unit gray divided by the SI base unit
            second.',
      ],
      1578 => [
        'Id' => 'P55',
        'Name' => 'microgray per second',
        'Description' => '0,000 001-fold of the derived SI unit gray divided by the SI base unit
            second.',
      ],
      1579 => [
        'Id' => 'P56',
        'Name' => 'nanogray per second',
        'Description' => '0,000 000 001-fold of the derived SI unit gray divided by the SI base unit
            second.',
      ],
      1580 => [
        'Id' => 'P57',
        'Name' => 'gray per minute',
        'Description' => 'SI derived unit gray divided by the unit minute.',
      ],
      1581 => [
        'Id' => 'P58',
        'Name' => 'milligray per minute',
        'Description' => '0,001-fold of the derived SI unit gray divided by the unit
            minute.',
      ],
      1582 => [
        'Id' => 'P59',
        'Name' => 'microgray per minute',
        'Description' => '0,000 001-fold of the derived SI unit gray divided by the unit
            minute.',
      ],
      1583 => [
        'Id' => 'P60',
        'Name' => 'nanogray per minute',
        'Description' => '0,000 000 001-fold of the derived SI unit gray divided by the unit
            minute.',
      ],
      1584 => [
        'Id' => 'P61',
        'Name' => 'gray per hour',
        'Description' => 'SI derived unit gray divided by the unit hour.',
      ],
      1585 => [
        'Id' => 'P62',
        'Name' => 'milligray per hour',
        'Description' => '0,001-fold of the derived SI unit gray divided by the unit hour.',
      ],
      1586 => [
        'Id' => 'P63',
        'Name' => 'microgray per hour',
        'Description' => '0,000 001-fold of the derived SI unit gray divided by the unit
            hour.',
      ],
      1587 => [
        'Id' => 'P64',
        'Name' => 'nanogray per hour',
        'Description' => '0,000 000 001-fold of the derived SI unit gray divided by the unit
            hour.',
      ],
      1588 => [
        'Id' => 'P65',
        'Name' => 'sievert per second',
        'Description' => 'Derived SI unit sievert divided by the SI base unit second.',
      ],
      1589 => [
        'Id' => 'P66',
        'Name' => 'millisievert per second',
        'Description' => '0,001-fold of the derived SI unit sievert divided by the SI base unit
            second.',
      ],
      1590 => [
        'Id' => 'P67',
        'Name' => 'microsievert per second',
        'Description' => '0,000 001-fold of the derived SI unit sievert divided by the SI base unit
            second.',
      ],
      1591 => [
        'Id' => 'P68',
        'Name' => 'nanosievert per second',
        'Description' => '0,000 000 001-fold of the derived SI unit sievert divided by the SI base unit
            second.',
      ],
      1592 => [
        'Id' => 'P69',
        'Name' => 'rem per second',
        'Description' => 'Unit for the equivalent tin rate relating to DIN 1301-3:1979: 1 rem/s = 0,01
            J/(kg·s) = 1 Sv/s.',
      ],
      1593 => [
        'Id' => 'P70',
        'Name' => 'sievert per hour',
        'Description' => 'Derived SI unit sievert divided by the unit hour.',
      ],
      1594 => [
        'Id' => 'P71',
        'Name' => 'millisievert per hour',
        'Description' => '0,001-fold of the derived SI unit sievert divided by the unit
            hour.',
      ],
      1595 => [
        'Id' => 'P72',
        'Name' => 'microsievert per hour',
        'Description' => '0,000 001-fold of the derived SI unit sievert divided by the unit
            hour.',
      ],
      1596 => [
        'Id' => 'P73',
        'Name' => 'nanosievert per hour',
        'Description' => '0,000 000 001-fold of the derived SI unit sievert divided by the unit
            hour.',
      ],
      1597 => [
        'Id' => 'P74',
        'Name' => 'sievert per minute',
        'Description' => 'Derived SI unit sievert divided by the unit minute.',
      ],
      1598 => [
        'Id' => 'P75',
        'Name' => 'millisievert per minute',
        'Description' => '0,001-fold of the derived SI unit sievert divided by the unit
            minute.',
      ],
      1599 => [
        'Id' => 'P76',
        'Name' => 'microsievert per minute',
        'Description' => '0,000 001-fold of the derived SI unit sievert divided by the unit
            minute.',
      ],
      1600 => [
        'Id' => 'P77',
        'Name' => 'nanosievert per minute',
        'Description' => '0,000 000 001-fold of the derived SI unit sievert divided by the unit
            minute.',
      ],
      1601 => [
        'Id' => 'P78',
        'Name' => 'reciprocal square inch',
        'Description' => 'Complement of the power of the unit inch according to the Anglo-American and
            Imperial system of units by exponent 2.',
      ],
      1602 => [
        'Id' => 'P79',
        'Name' => 'pascal square metre per kilogram',
        'Description' => 'Unit of the burst index as derived unit for pressure pascal related to the
            substance, represented as a quotient from the SI base unit kilogram divided by the power
            of the SI base unit metre by exponent 2.',
      ],
      1603 => [
        'Id' => 'P80',
        'Name' => 'millipascal per metre',
        'Description' => '0,001-fold of the derived SI unit pascal divided by the SI base unit
            metre.',
      ],
      1604 => [
        'Id' => 'P81',
        'Name' => 'kilopascal per metre',
        'Description' => '1000-fold of the derived SI unit pascal divided by the SI base unit
            metre.',
      ],
      1605 => [
        'Id' => 'P82',
        'Name' => 'hectopascal per metre',
        'Description' => '100-fold of the derived SI unit pascal divided by the SI base unit
            metre.',
      ],
      1606 => [
        'Id' => 'P83',
        'Name' => 'standard atmosphere per metre',
        'Description' => 'Outdated unit of the pressure divided by the SI base unit metre.',
      ],
      1607 => [
        'Id' => 'P84',
        'Name' => 'technical atmosphere per metre',
        'Description' => 'Obsolete and non-legal unit of the pressure which is generated by a 10 metre
            water column divided by the SI base unit metre.',
      ],
      1608 => [
        'Id' => 'P85',
        'Name' => 'torr per metre',
        'Description' => 'CGS (Centimetre-Gram-Second system) unit of the pressure divided by the SI base
            unit metre.',
      ],
      1609 => [
        'Id' => 'P86',
        'Name' => 'psi per inch',
        'Description' => 'Compound unit for pressure (pound-force according to the Anglo-American unit
            system divided by the power of the unit inch according to the Anglo-American and
            Imperial system of units with the exponent 2) divided by the unit inch according to the
            Anglo-American and Imperial system of units .',
      ],
      1610 => [
        'Id' => 'P87',
        'Name' => 'cubic metre per second square metre',
        'Description' => 'Unit of volume flow cubic meters by second related to the transmission surface
            in square metres.',
      ],
      1611 => [
        'Id' => 'P88',
        'Name' => 'rhe',
        'Description' => 'Non SI-conforming unit of fluidity of dynamic viscosity.',
      ],
      1612 => [
        'Id' => 'P89',
        'Name' => 'pound-force foot per inch',
        'Description' => 'Unit for length-related rotational moment according to the Anglo-American and
            Imperial system of units.',
      ],
      1613 => [
        'Id' => 'P90',
        'Name' => 'pound-force inch per inch',
        'Description' => 'Unit for length-related rotational moment according to the Anglo-American and
            Imperial system of units.',
      ],
      1614 => [
        'Id' => 'P91',
        'Name' => 'perm (0 ºC)',
        'Description' => 'Traditional unit for the ability of a material to allow the transition of the
            steam, defined at a temperature of 0 °C as steam transmittance, where the mass of one
            grain steam penetrates an area of one foot squared at a pressure from one inch mercury
            per hour.',
      ],
      1615 => [
        'Id' => 'P92',
        'Name' => 'perm (23 ºC)',
        'Description' => 'Traditional unit for the ability of a material to allow the transition of the
            steam, defined at a temperature of 23 °C as steam transmittance at which the mass of one
            grain of steam penetrates an area of one square foot at a pressure of one inch mercury
            per hour.',
      ],
      1616 => [
        'Id' => 'P93',
        'Name' => 'byte per second',
        'Description' => 'Unit byte divided by the SI base unit second.',
      ],
      1617 => [
        'Id' => 'P94',
        'Name' => 'kilobyte per second',
        'Description' => '1000-fold of the unit byte divided by the SI base unit second.',
      ],
      1618 => [
        'Id' => 'P95',
        'Name' => 'megabyte per second',
        'Description' => '1 000 000-fold of the unit byte divided by the SI base unit
            second.',
      ],
      1619 => [
        'Id' => 'P96',
        'Name' => 'reciprocal volt',
        'Description' => 'Reciprocal of the derived SI unit volt.',
      ],
      1620 => [
        'Id' => 'P97',
        'Name' => 'reciprocal radian',
        'Description' => 'Reciprocal of the unit radian.',
      ],
      1621 => [
        'Id' => 'P98',
        'Name' => 'pascal to the power sum of stoichiometric numbers',
        'Description' => 'Unit of the equilibrium constant on the basis of the pressure(ISO 80000-9:2009,
            9-35.a).',
      ],
      1622 => [
        'Id' => 'P99',
        'Name' => 'mole per cubiv metre to the power sum of stoichiometric numbers',
        'Description' => 'Unit of the equilibrium constant on the basis of the concentration (ISO
            80000-9:2009, 9-36.a).',
      ],
      1623 => [
        'Id' => 'PAL',
        'Name' => 'pascal',
        'Description' => '',
      ],
      1624 => [
        'Id' => 'PD',
        'Name' => 'pad',
        'Description' => 'A unit of count defining the number of pads (pad: block of paper sheets
            fastened together at one end).',
      ],
      1625 => [
        'Id' => 'PFL',
        'Name' => 'proof litre',
        'Description' => 'A unit of volume equal to one litre of proof spirits, or the alcohol equivalent
            thereof. Used for measuring the strength of distilled alcoholic liquors, expressed as a
            percentage of the alcohol content of a standard mixture at a specific
            temperature.',
      ],
      1626 => [
        'Id' => 'PGL',
        'Name' => 'proof gallon',
        'Description' => 'A unit of volume equal to one gallon of proof spirits, or the alcohol
            equivalent thereof. Used for measuring the strength of distilled alcoholic liquors,
            expressed as a percentage of the alcohol content of a standard mixture at a specific
            temperature.',
      ],
      1627 => [
        'Id' => 'PI',
        'Name' => 'pitch',
        'Description' => 'A unit of count defining the number of characters that fit in a horizontal
            inch.',
      ],
      1628 => [
        'Id' => 'PLA',
        'Name' => 'degree Plato',
        'Description' => 'A unit of proportion defining the sugar content of a product, especially in
            relation to beer.',
      ],
      1629 => [
        'Id' => 'PO',
        'Name' => 'pound per inch of length',
        'Description' => '',
      ],
      1630 => [
        'Id' => 'PQ',
        'Name' => 'page per inch',
        'Description' => 'A unit of quantity defining the degree of thickness of a bound publication,
            expressed as the number of pages per inch of thickness.',
      ],
      1631 => [
        'Id' => 'PR',
        'Name' => 'pair',
        'Description' => 'A unit of count defining the number of pairs (pair: item described by
            two\'s).',
      ],
      1632 => [
        'Id' => 'PS',
        'Name' => 'pound-force per square inch',
        'Description' => '',
      ],
      1633 => [
        'Id' => 'PTD',
        'Name' => 'dry pint (US)',
        'Description' => '',
      ],
      1634 => [
        'Id' => 'PTI',
        'Name' => 'pint (UK)',
        'Description' => '',
      ],
      1635 => [
        'Id' => 'PTL',
        'Name' => 'liquid pint (US)',
        'Description' => '',
      ],
      1636 => [
        'Id' => 'PTN',
        'Name' => 'portion',
        'Description' => 'A quantity of allowance of food allotted to, or enough for, one
            person.',
      ],
      1637 => [
        'Id' => 'Q10',
        'Name' => 'joule per tesla',
        'Description' => 'Unit of the magnetic dipole moment of the molecule as derived SI unit joule
            divided by the derived SI unit tesla.',
      ],
      1638 => [
        'Id' => 'Q11',
        'Name' => 'erlang',
        'Description' => 'Unit of the market value according to the feature of a single feature as a
            statistical measurement of the existing utilization.',
      ],
      1639 => [
        'Id' => 'Q12',
        'Name' => 'octet',
        'Description' => 'Synonym for byte: 1 octet = 8 bit = 1 byte.',
      ],
      1640 => [
        'Id' => 'Q13',
        'Name' => 'octet per second',
        'Description' => 'Unit octet divided by the SI base unit second.',
      ],
      1641 => [
        'Id' => 'Q14',
        'Name' => 'shannon',
        'Description' => 'Logarithmic unit for information equal to the content of decision of a sentence
            of two mutually exclusive events, expressed as a logarithm to base 2.',
      ],
      1642 => [
        'Id' => 'Q15',
        'Name' => 'hartley',
        'Description' => 'Logarithmic unit for information equal to the content of decision of a sentence
            of ten mutually exclusive events, expressed as a logarithm to base 10.',
      ],
      1643 => [
        'Id' => 'Q16',
        'Name' => 'natural unit of information',
        'Description' => 'Logarithmic unit for information equal to the content of decision of a sentence
            of ,718 281 828 459 mutually exclusive events, expressed as a logarithm to base Euler
            value e.',
      ],
      1644 => [
        'Id' => 'Q17',
        'Name' => 'shannon per second',
        'Description' => 'Time related logarithmic unit for information equal to the content of decision
            of a sentence of two mutually exclusive events, expressed as a logarithm to base
            2.',
      ],
      1645 => [
        'Id' => 'Q18',
        'Name' => 'hartley per second',
        'Description' => 'Time related logarithmic unit for information equal to the content of decision
            of a sentence of ten mutually exclusive events, expressed as a logarithm to base
            10.',
      ],
      1646 => [
        'Id' => 'Q19',
        'Name' => 'natural unit of information per second',
        'Description' => 'Time related logarithmic unit for information equal to the content of decision
            of a sentence of 2,718 281 828 459 mutually exclusive events, expressed as a logarithm
            to base of the Euler value e.',
      ],
      1647 => [
        'Id' => 'Q20',
        'Name' => 'second per kilogramm',
        'Description' => 'Unit of the Einstein transition probability for spontaneous or inducing
            emissions and absorption according to ISO 80000-7:2008, expressed as SI base unit second
            divided by the SI base unit kilogram.',
      ],
      1648 => [
        'Id' => 'Q21',
        'Name' => 'watt square metre',
        'Description' => 'Unit of the first radiation constants c1 = 2·p·h·c0², the value of which is
            3,741 771 18·10?¹6-fold that of the comparative value of the product of the derived SI
            unit watt multiplied with the power of the SI base unit metre with the exponent
            2.',
      ],
      1649 => [
        'Id' => 'Q22',
        'Name' => 'second per radian cubic metre',
        'Description' => 'Unit of the density of states as an expression of angular frequency as
            complement of the product of hertz and radiant and the power of SI base unit metre by
            exponent 3 .',
      ],
      1650 => [
        'Id' => 'Q23',
        'Name' => 'weber to the power minus one',
        'Description' => 'Complement of the derived SI unit weber as unit of the Josephson constant,
            which value is equal to the 384 597,891-fold of the reference value gigahertz divided by
            volt.',
      ],
      1651 => [
        'Id' => 'Q24',
        'Name' => 'reciprocal inch',
        'Description' => 'Complement of the unit inch according to the Anglo-American and Imperial system
            of units.',
      ],
      1652 => [
        'Id' => 'Q25',
        'Name' => 'dioptre',
        'Description' => 'Unit used at the statement of relative refractive indexes of optical systems as
            complement of the focal length with correspondence to: 1 dpt = 1/m.',
      ],
      1653 => [
        'Id' => 'Q26',
        'Name' => 'one per one',
        'Description' => 'Value of the quotient from two physical units of the same kind as a numerator
            and denominator whereas the units are shortened mutually.',
      ],
      1654 => [
        'Id' => 'Q27',
        'Name' => 'newton metre per metre',
        'Description' => 'Unit for length-related rotational moment as product of the derived SI unit
            newton and the SI base unit metre divided by the SI base unit metre.',
      ],
      1655 => [
        'Id' => 'Q28',
        'Name' => 'kilogram per square metre pascal second',
        'Description' => 'Unit for the ability of a material to allow the transition of
            steam.',
      ],
      1656 => [
        'Id' => 'Q29',
        'Name' => 'microgram per hectogram',
        'Description' => 'Microgram per hectogram.',
      ],
      1657 => [
        'Id' => 'Q30',
        'Name' => 'pH (potential of Hydrogen)',
        'Description' => 'The activity of the (solvated) hydrogen ion (a logarithmic measure used to
            state the acidity or alkalinity of a chemical solution).',
      ],
      1658 => [
        'Id' => 'Q31',
        'Name' => 'kilojoule per gram',
        'Description' => '',
      ],
      1659 => [
        'Id' => 'Q32',
        'Name' => 'femtolitre',
        'Description' => '',
      ],
      1660 => [
        'Id' => 'Q33',
        'Name' => 'picolitre',
        'Description' => '',
      ],
      1661 => [
        'Id' => 'Q34',
        'Name' => 'nanolitre',
        'Description' => '',
      ],
      1662 => [
        'Id' => 'Q35',
        'Name' => 'megawatts per minute',
        'Description' => 'A unit of power defining the total amount of bulk energy transferred or
            consumer per minute.',
      ],
      1663 => [
        'Id' => 'Q36',
        'Name' => 'square metre per cubic metre',
        'Description' => 'A unit of the amount of surface area per unit volume of an object or collection
            of objects.',
      ],
      1664 => [
        'Id' => 'Q37',
        'Name' => 'Standard cubic metre per day',
        'Description' => 'Standard cubic metre (temperature 15°C and pressure 101325 millibars ) per
            day',
      ],
      1665 => [
        'Id' => 'Q38',
        'Name' => 'Standard cubic metre per hour',
        'Description' => 'Standard cubic metre (temperature 15°C and pressure 101325 millibars ) per
            hour',
      ],
      1666 => [
        'Id' => 'Q39',
        'Name' => 'Normalized cubic metre per day',
        'Description' => 'Normalized cubic metre (temperature 0°C and pressure 101325 millibars ) per
            day',
      ],
      1667 => [
        'Id' => 'Q40',
        'Name' => 'Normalized cubic metre per hour',
        'Description' => 'Normalized cubic metre (temperature 0°C and pressure 101325 millibars ) per
            hour',
      ],
      1668 => [
        'Id' => 'Q41',
        'Name' => 'Joule per normalised cubic metre',
      ],
      1669 => [
        'Id' => 'Q42',
        'Name' => 'Joule per standard cubic metre',
      ],
      1670 => [
        'Id' => 'Q3',
        'Name' => 'meal',
        'Description' => 'A unit of count defining the number of meals (meal: an amount of food to be
            eaten on a single occasion).',
      ],
      1671 => [
        'Id' => 'QA',
        'Name' => 'page - facsimile',
        'Description' => 'A unit of count defining the number of facsimile pages.',
      ],
      1672 => [
        'Id' => 'QAN',
        'Name' => 'quarter (of a year)',
        'Description' => 'A unit of time defining the number of quarters (3 months).',
      ],
      1673 => [
        'Id' => 'QB',
        'Name' => 'page - hardcopy',
        'Description' => 'A unit of count defining the number of hardcopy pages (hardcopy page: a page
            rendered as printed or written output on paper, film, or other permanent
            medium).',
      ],
      1674 => [
        'Id' => 'QR',
        'Name' => 'quire',
        'Description' => 'A unit of count for paper, expressed as the number of quires (quire: a number
            of paper sheets, typically 25).',
      ],
      1675 => [
        'Id' => 'QTD',
        'Name' => 'dry quart (US)',
        'Description' => '',
      ],
      1676 => [
        'Id' => 'QTI',
        'Name' => 'quart (UK)',
        'Description' => '',
      ],
      1677 => [
        'Id' => 'QTL',
        'Name' => 'liquid quart (US)',
        'Description' => '',
      ],
      1678 => [
        'Id' => 'QTR',
        'Name' => 'quarter (UK)',
        'Description' => 'A traditional unit of weight equal to 1/4 hundredweight. In the United Kingdom,
            one quarter equals 28 pounds.',
      ],
      1679 => [
        'Id' => 'R1',
        'Name' => 'pica',
        'Description' => 'A unit of count defining the number of picas. (pica: typographical length equal
            to 12 points or 4.22 mm (approx.)).',
      ],
      1680 => [
        'Id' => 'R9',
        'Name' => 'thousand cubic metre',
        'Description' => 'A unit of volume equal to one thousand cubic metres.',
      ],
      1681 => [
        'Id' => 'RH',
        'Name' => 'running or operating hour',
        'Description' => 'A unit of time defining the number of hours of operation.',
      ],
      1682 => [
        'Id' => 'RM',
        'Name' => 'ream',
        'Description' => 'A unit of count for paper, expressed as the number of reams (ream: a large
            quantity of paper sheets, typically 500).',
      ],
      1683 => [
        'Id' => 'ROM',
        'Name' => 'room',
        'Description' => 'A unit of count defining the number of rooms.',
      ],
      1684 => [
        'Id' => 'RP',
        'Name' => 'pound per ream',
        'Description' => 'A unit of mass for paper, expressed as pounds per ream. (ream: a large quantity
            of paper, typically 500 sheets).',
      ],
      1685 => [
        'Id' => 'RPM',
        'Name' => 'revolutions per minute',
        'Description' => 'Refer ISO/TC12 SI Guide',
      ],
      1686 => [
        'Id' => 'RPS',
        'Name' => 'revolutions per second',
        'Description' => 'Refer ISO/TC12 SI Guide',
      ],
      1687 => [
        'Id' => 'RT',
        'Name' => 'revenue ton mile',
        'Description' => 'A unit of information typically used for billing purposes, expressed as the
            number of revenue tons (revenue ton: either a metric ton or a cubic metres, whichever is
            the larger), moved over a distance of one mile.',
      ],
      1688 => [
        'Id' => 'S3',
        'Name' => 'square foot per second',
        'Description' => 'Synonym: foot squared per second',
      ],
      1689 => [
        'Id' => 'S4',
        'Name' => 'square metre per second',
        'Description' => 'Synonym: metre squared per second (square metres/second US)',
      ],
      1690 => [
        'Id' => 'SAN',
        'Name' => 'half year (6 months)',
        'Description' => 'A unit of time defining the number of half years (6 months).',
      ],
      1691 => [
        'Id' => 'SCO',
        'Name' => 'score',
        'Description' => 'A unit of count defining the number of units in multiples of 20.',
      ],
      1692 => [
        'Id' => 'SCR',
        'Name' => 'scruple',
        'Description' => '',
      ],
      1693 => [
        'Id' => 'SEC',
        'Name' => 'second [unit of time]',
        'Description' => '',
      ],
      1694 => [
        'Id' => 'SET',
        'Name' => 'set',
        'Description' => 'A unit of count defining the number of sets (set: a number of objects grouped
            together).',
      ],
      1695 => [
        'Id' => 'SG',
        'Name' => 'segment',
        'Description' => 'A unit of information equal to 64000 bytes.',
      ],
      1696 => [
        'Id' => 'SIE',
        'Name' => 'siemens',
        'Description' => '',
      ],
      1697 => [
        'Id' => 'SM3',
        'Name' => 'Standard cubic metre',
        'Description' => 'Standard cubic metre (temperature 15°C and pressure 101325 millibars
            )',
      ],
      1698 => [
        'Id' => 'SMI',
        'Name' => 'mile (statute mile)',
        'Description' => '',
      ],
      1699 => [
        'Id' => 'SQ',
        'Name' => 'square',
        'Description' => 'A unit of count defining the number of squares (square: rectangular
            shape).',
      ],
      1700 => [
        'Id' => 'SQR',
        'Name' => 'square, roofing',
        'Description' => 'A unit of count defining the number of squares of roofing materials, measured
            in multiples of 100 square feet.',
      ],
      1701 => [
        'Id' => 'SR',
        'Name' => 'strip',
        'Description' => 'A unit of count defining the number of strips (strip: long narrow piece of an
            object).',
      ],
      1702 => [
        'Id' => 'STC',
        'Name' => 'stick',
        'Description' => 'A unit of count defining the number of sticks (stick: slender and often
            cylindrical piece of a substance).',
      ],
      1703 => [
        'Id' => 'STI',
        'Name' => 'stone (UK)',
        'Description' => '',
      ],
      1704 => [
        'Id' => 'STK',
        'Name' => 'stick, cigarette',
        'Description' => 'A unit of count defining the number of cigarettes in the smallest unit for
            stock-taking and/or duty computation.',
      ],
      1705 => [
        'Id' => 'STL',
        'Name' => 'standard litre',
        'Description' => 'A unit of volume defining the number of litres of a product at a temperature of
            15 degrees Celsius, especially in relation to hydrocarbon oils.',
      ],
      1706 => [
        'Id' => 'STN',
        'Name' => 'ton (US) or short ton (UK/US)',
        'Description' => 'Synonym: net ton (2000 lb)',
      ],
      1707 => [
        'Id' => 'STW',
        'Name' => 'straw',
        'Description' => 'A unit of count defining the number of straws (straw: a slender tube used for
            sucking up liquids).',
      ],
      1708 => [
        'Id' => 'SW',
        'Name' => 'skein',
        'Description' => 'A unit of count defining the number of skeins (skein: a loosely-coiled bundle
            of yarn or thread).',
      ],
      1709 => [
        'Id' => 'SX',
        'Name' => 'shipment',
        'Description' => 'A unit of count defining the number of shipments (shipment: an amount of goods
            shipped or transported).',
      ],
      1710 => [
        'Id' => 'SYR',
        'Name' => 'syringe',
        'Description' => 'A unit of count defining the number of syringes (syringe: a small device for
            pumping, spraying and/or injecting liquids through a small aperture).',
      ],
      1711 => [
        'Id' => 'T0',
        'Name' => 'telecommunication line in service',
        'Description' => 'A unit of count defining the number of lines in service.',
      ],
      1712 => [
        'Id' => 'T3',
        'Name' => 'thousand piece',
        'Description' => 'A unit of count defining the number of pieces in multiples of 1000 (piece: a
            single item, article or exemplar).',
      ],
      1713 => [
        'Id' => 'TAH',
        'Name' => 'kiloampere hour (thousand ampere hour)',
        'Description' => '',
      ],
      1714 => [
        'Id' => 'TAN',
        'Name' => 'total acid number',
        'Description' => 'A unit of chemistry defining the amount of potassium hydroxide (KOH) in
            milligrams that is needed to neutralize the acids in one gram of oil. It is an important
            quality measurement of crude oil.',
      ],
      1715 => [
        'Id' => 'TI',
        'Name' => 'thousand square inch',
        'Description' => '',
      ],
      1716 => [
        'Id' => 'TIC',
        'Name' => 'metric ton, including container',
        'Description' => 'A unit of mass defining the number of metric tons of a product, including its
            container.',
      ],
      1717 => [
        'Id' => 'TIP',
        'Name' => 'metric ton, including inner packaging',
        'Description' => 'A unit of mass defining the number of metric tons of a product, including its
            inner packaging materials.',
      ],
      1718 => [
        'Id' => 'TKM',
        'Name' => 'tonne kilometre',
        'Description' => 'A unit of information typically used for billing purposes, expressed as the
            number of tonnes (metric tons) moved over a distance of one kilometre.',
      ],
      1719 => [
        'Id' => 'TMS',
        'Name' => 'kilogram of imported meat, less offal',
        'Description' => 'A unit of mass equal to one thousand grams of imported meat, disregarding less
            valuable by-products such as the entrails.',
      ],
      1720 => [
        'Id' => 'TNE',
        'Name' => 'tonne (metric ton)',
        'Description' => 'Synonym: metric ton',
      ],
      1721 => [
        'Id' => 'TP',
        'Name' => 'ten pack',
        'Description' => 'A unit of count defining the number of items in multiples of 10.',
      ],
      1722 => [
        'Id' => 'TPI',
        'Name' => 'teeth per inch',
        'Description' => 'The number of teeth per inch.',
      ],
      1723 => [
        'Id' => 'TPR',
        'Name' => 'ten pair',
        'Description' => 'A unit of count defining the number of pairs in multiples of 10 (pair: item
            described by two\'s).',
      ],
      1724 => [
        'Id' => 'TQD',
        'Name' => 'thousand cubic metre per day',
        'Description' => 'A unit of volume equal to one thousand cubic metres per day.',
      ],
      1725 => [
        'Id' => 'TRL',
        'Name' => 'trillion (EUR)',
        'Description' => '',
      ],
      1726 => [
        'Id' => 'TST',
        'Name' => 'ten set',
        'Description' => 'A unit of count defining the number of sets in multiples of 10 (set: a number
            of objects grouped together).',
      ],
      1727 => [
        'Id' => 'TTS',
        'Name' => 'ten thousand sticks',
        'Description' => 'A unit of count defining the number of sticks in multiples of 10000 (stick:
            slender and often cylindrical piece of a substance).',
      ],
      1728 => [
        'Id' => 'U1',
        'Name' => 'treatment',
        'Description' => 'A unit of count defining the number of treatments (treatment: subjection to the
            action of a chemical, physical or biological agent).',
      ],
      1729 => [
        'Id' => 'U2',
        'Name' => 'tablet',
        'Description' => 'A unit of count defining the number of tablets (tablet: a small flat or
            compressed solid object).',
      ],
      1730 => [
        'Id' => 'UB',
        'Name' => 'telecommunication line in service average',
        'Description' => 'A unit of count defining the average number of lines in service.',
      ],
      1731 => [
        'Id' => 'UC',
        'Name' => 'telecommunication port',
        'Description' => 'A unit of count defining the number of network access ports.',
      ],
      1732 => [
        'Id' => 'VA',
        'Name' => 'volt - ampere per kilogram',
        'Description' => '',
      ],
      1733 => [
        'Id' => 'VLT',
        'Name' => 'volt',
        'Description' => '',
      ],
      1734 => [
        'Id' => 'VP',
        'Name' => 'percent volume',
        'Description' => 'A measure of concentration, typically expressed as the percentage volume of a
            solute in a solution.',
      ],
      1735 => [
        'Id' => 'W2',
        'Name' => 'wet kilo',
        'Description' => 'A unit of mass defining the number of kilograms of a product, including the
            water content of the product.',
      ],
      1736 => [
        'Id' => 'WA',
        'Name' => 'watt per kilogram',
        'Description' => '',
      ],
      1737 => [
        'Id' => 'WB',
        'Name' => 'wet pound',
        'Description' => 'A unit of mass defining the number of pounds of a material, including the water
            content of the material.',
      ],
      1738 => [
        'Id' => 'WCD',
        'Name' => 'cord',
        'Description' => 'A unit of volume used for measuring lumber. One board foot equals 1/12 of a
            cubic foot.',
      ],
      1739 => [
        'Id' => 'WE',
        'Name' => 'wet ton',
        'Description' => 'A unit of mass defining the number of tons of a material, including the water
            content of the material.',
      ],
      1740 => [
        'Id' => 'WEB',
        'Name' => 'weber',
        'Description' => '',
      ],
      1741 => [
        'Id' => 'WEE',
        'Name' => 'week',
        'Description' => '',
      ],
      1742 => [
        'Id' => 'WG',
        'Name' => 'wine gallon',
        'Description' => 'A unit of volume equal to 231 cubic inches.',
      ],
      1743 => [
        'Id' => 'WHR',
        'Name' => 'watt hour',
        'Description' => '',
      ],
      1744 => [
        'Id' => 'WM',
        'Name' => 'working month',
        'Description' => 'A unit of time defining the number of working months.',
      ],
      1745 => [
        'Id' => 'WSD',
        'Name' => 'standard',
        'Description' => 'A unit of volume of finished lumber equal to 165 cubic feet. Synonym: standard
            cubic foot',
      ],
      1746 => [
        'Id' => 'WTT',
        'Name' => 'watt',
        'Description' => '',
      ],
      1747 => [
        'Id' => 'X1',
        'Name' => 'Gunter\'s chain',
        'Description' => 'A unit of distance used or formerly used by British surveyors.',
      ],
      1748 => [
        'Id' => 'YDK',
        'Name' => 'square yard',
        'Description' => '',
      ],
      1749 => [
        'Id' => 'YDQ',
        'Name' => 'cubic yard',
        'Description' => '',
      ],
      1750 => [
        'Id' => 'YRD',
        'Name' => 'yard',
        'Description' => '',
      ],
      1751 => [
        'Id' => 'Z11',
        'Name' => 'hanging container',
        'Description' => 'A unit of count defining the number of hanging containers.',
      ],
      1752 => [
        'Id' => 'Z9',
        'Name' => 'nanomole',
      ],
      1753 => [
        'Id' => 'ZP',
        'Name' => 'page',
        'Description' => 'A unit of count defining the number of pages.',
      ],
      1754 => [
        'Id' => 'ZZ',
        'Name' => 'mutually defined',
        'Description' => 'A unit of measure as agreed in common between two or more
            parties.',
      ],
      1755 => [
        'Id' => 'X1A',
        'Name' => 'Drum, steel',
      ],
      1756 => [
        'Id' => 'X1B',
        'Name' => 'Drum, aluminium',
      ],
      1757 => [
        'Id' => 'X1D',
        'Name' => 'Drum, plywood',
      ],
      1758 => [
        'Id' => 'X1F',
        'Name' => 'Container, flexible',
        'Description' => 'A packaging container of flexible construction.',
      ],
      1759 => [
        'Id' => 'X1G',
        'Name' => 'Drum, fibre',
      ],
      1760 => [
        'Id' => 'X1W',
        'Name' => 'Drum, wooden',
      ],
      1761 => [
        'Id' => 'X2C',
        'Name' => 'Barrel, wooden',
      ],
      1762 => [
        'Id' => 'X3A',
        'Name' => 'Jerrican, steel',
      ],
      1763 => [
        'Id' => 'X3H',
        'Name' => 'Jerrican, plastic',
      ],
      1764 => [
        'Id' => 'X43',
        'Name' => 'Bag, super bulk',
        'Description' => 'A cloth plastic or paper based bag having the dimensions of the pallet on which
            it is constructed.',
      ],
      1765 => [
        'Id' => 'X44',
        'Name' => 'Bag, polybag',
        'Description' => 'A type of plastic bag, typically used to wrap promotional pieces, publications,
            product samples, and/or catalogues.',
      ],
      1766 => [
        'Id' => 'X4A',
        'Name' => 'Box, steel',
      ],
      1767 => [
        'Id' => 'X4B',
        'Name' => 'Box, aluminium',
      ],
      1768 => [
        'Id' => 'X4C',
        'Name' => 'Box, natural wood',
      ],
      1769 => [
        'Id' => 'X4D',
        'Name' => 'Box, plywood',
      ],
      1770 => [
        'Id' => 'X4F',
        'Name' => 'Box, reconstituted wood',
      ],
      1771 => [
        'Id' => 'X4G',
        'Name' => 'Box, fibreboard',
      ],
      1772 => [
        'Id' => 'X4H',
        'Name' => 'Box, plastic',
      ],
      1773 => [
        'Id' => 'X5H',
        'Name' => 'Bag, woven plastic',
      ],
      1774 => [
        'Id' => 'X5L',
        'Name' => 'Bag, textile',
      ],
      1775 => [
        'Id' => 'X5M',
        'Name' => 'Bag, paper',
      ],
      1776 => [
        'Id' => 'X6H',
        'Name' => 'Composite packaging, plastic receptacle',
      ],
      1777 => [
        'Id' => 'X6P',
        'Name' => 'Composite packaging, glass receptacle',
      ],
      1778 => [
        'Id' => 'X7A',
        'Name' => 'Case, car',
        'Description' => 'A type of portable container designed to store equipment for carriage in an
            automobile.',
      ],
      1779 => [
        'Id' => 'X7B',
        'Name' => 'Case, wooden',
        'Description' => 'A case made of wood for retaining substances or articles.',
      ],
      1780 => [
        'Id' => 'X8A',
        'Name' => 'Pallet, wooden',
        'Description' => 'A platform or open-ended box, made of wood, on which goods are retained for
            ease of mechanical handling during transport and storage.',
      ],
      1781 => [
        'Id' => 'X8B',
        'Name' => 'Crate, wooden',
        'Description' => 'A receptacle, made of wood, on which goods are retained for ease of mechanical
            handling during transport and storage.',
      ],
      1782 => [
        'Id' => 'X8C',
        'Name' => 'Bundle, wooden',
        'Description' => 'Loose or unpacked pieces of wood tied or wrapped together.',
      ],
      1783 => [
        'Id' => 'XAA',
        'Name' => 'Intermediate bulk container, rigid plastic',
      ],
      1784 => [
        'Id' => 'XAB',
        'Name' => 'Receptacle, fibre',
        'Description' => 'Containment vessel made of fibre used for retaining substances or
            articles.',
      ],
      1785 => [
        'Id' => 'XAC',
        'Name' => 'Receptacle, paper',
        'Description' => 'Containment vessel made of paper for retaining substances or
            articles.',
      ],
      1786 => [
        'Id' => 'XAD',
        'Name' => 'Receptacle, wooden',
        'Description' => 'Containment vessel made of wood for retaining substances or
            articles.',
      ],
      1787 => [
        'Id' => 'XAE',
        'Name' => 'Aerosol',
      ],
      1788 => [
        'Id' => 'XAF',
        'Name' => 'Pallet, modular, collars 80cms * 60cms',
        'Description' => 'Standard sized pallet of dimensions 80 centimeters by 60 centimeters
            (cms).',
      ],
      1789 => [
        'Id' => 'XAG',
        'Name' => 'Pallet, shrinkwrapped',
        'Description' => 'Pallet load secured with transparent plastic film that has been wrapped around
            and then shrunk tightly.',
      ],
      1790 => [
        'Id' => 'XAH',
        'Name' => 'Pallet, 100cms * 110cms',
        'Description' => 'Standard sized pallet of dimensions 100centimeters by 110 centimeters
            (cms).',
      ],
      1791 => [
        'Id' => 'XAI',
        'Name' => 'Clamshell',
      ],
      1792 => [
        'Id' => 'XAJ',
        'Name' => 'Cone',
        'Description' => 'Container used in the transport of linear material such as yarn.',
      ],
      1793 => [
        'Id' => 'XAL',
        'Name' => 'Ball',
        'Description' => 'A spherical containment vessel for retaining substances or
            articles.',
      ],
      1794 => [
        'Id' => 'XAM',
        'Name' => 'Ampoule, non-protected',
      ],
      1795 => [
        'Id' => 'XAP',
        'Name' => 'Ampoule, protected',
      ],
      1796 => [
        'Id' => 'XAT',
        'Name' => 'Atomizer',
      ],
      1797 => [
        'Id' => 'XAV',
        'Name' => 'Capsule',
      ],
      1798 => [
        'Id' => 'XB4',
        'Name' => 'Belt',
        'Description' => 'A band use to retain multiple articles together.',
      ],
      1799 => [
        'Id' => 'XBA',
        'Name' => 'Barrel',
      ],
      1800 => [
        'Id' => 'XBB',
        'Name' => 'Bobbin',
      ],
      1801 => [
        'Id' => 'XBC',
        'Name' => 'Bottlecrate / bottlerack',
      ],
      1802 => [
        'Id' => 'XBD',
        'Name' => 'Board',
      ],
      1803 => [
        'Id' => 'XBE',
        'Name' => 'Bundle',
      ],
      1804 => [
        'Id' => 'XBF',
        'Name' => 'Balloon, non-protected',
      ],
      1805 => [
        'Id' => 'XBG',
        'Name' => 'Bag',
        'Description' => 'A receptacle made of flexible material with an open or closed
            top.',
      ],
      1806 => [
        'Id' => 'XBH',
        'Name' => 'Bunch',
      ],
      1807 => [
        'Id' => 'XBI',
        'Name' => 'Bin',
      ],
      1808 => [
        'Id' => 'XBJ',
        'Name' => 'Bucket',
      ],
      1809 => [
        'Id' => 'XBK',
        'Name' => 'Basket',
      ],
      1810 => [
        'Id' => 'XBL',
        'Name' => 'Bale, compressed',
      ],
      1811 => [
        'Id' => 'XBM',
        'Name' => 'Basin',
      ],
      1812 => [
        'Id' => 'XBN',
        'Name' => 'Bale, non-compressed',
      ],
      1813 => [
        'Id' => 'XBO',
        'Name' => 'Bottle, non-protected, cylindrical',
        'Description' => 'A narrow-necked cylindrical shaped vessel without external protective packing
            material.',
      ],
      1814 => [
        'Id' => 'XBP',
        'Name' => 'Balloon, protected',
      ],
      1815 => [
        'Id' => 'XBQ',
        'Name' => 'Bottle, protected cylindrical',
        'Description' => 'A narrow-necked cylindrical shaped vessel with external protective packing
            material.',
      ],
      1816 => [
        'Id' => 'XBR',
        'Name' => 'Bar',
      ],
      1817 => [
        'Id' => 'XBS',
        'Name' => 'Bottle, non-protected, bulbous',
        'Description' => 'A narrow-necked bulb shaped vessel without external protective packing
            material.',
      ],
      1818 => [
        'Id' => 'XBT',
        'Name' => 'Bolt',
      ],
      1819 => [
        'Id' => 'XBU',
        'Name' => 'Butt',
      ],
      1820 => [
        'Id' => 'XBV',
        'Name' => 'Bottle, protected bulbous',
        'Description' => 'A narrow-necked bulb shaped vessel with external protective packing
            material.',
      ],
      1821 => [
        'Id' => 'XBW',
        'Name' => 'Box, for liquids',
      ],
      1822 => [
        'Id' => 'XBX',
        'Name' => 'Box',
      ],
      1823 => [
        'Id' => 'XBY',
        'Name' => 'Board, in bundle/bunch/truss',
      ],
      1824 => [
        'Id' => 'XBZ',
        'Name' => 'Bars, in bundle/bunch/truss',
      ],
      1825 => [
        'Id' => 'XCA',
        'Name' => 'Can, rectangular',
      ],
      1826 => [
        'Id' => 'XCB',
        'Name' => 'Crate, beer',
      ],
      1827 => [
        'Id' => 'XCC',
        'Name' => 'Churn',
      ],
      1828 => [
        'Id' => 'XCD',
        'Name' => 'Can, with handle and spout',
      ],
      1829 => [
        'Id' => 'XCE',
        'Name' => 'Creel',
      ],
      1830 => [
        'Id' => 'XCF',
        'Name' => 'Coffer',
      ],
      1831 => [
        'Id' => 'XCG',
        'Name' => 'Cage',
      ],
      1832 => [
        'Id' => 'XCH',
        'Name' => 'Chest',
      ],
      1833 => [
        'Id' => 'XCI',
        'Name' => 'Canister',
      ],
      1834 => [
        'Id' => 'XCJ',
        'Name' => 'Coffin',
      ],
      1835 => [
        'Id' => 'XCK',
        'Name' => 'Cask',
      ],
      1836 => [
        'Id' => 'XCL',
        'Name' => 'Coil',
      ],
      1837 => [
        'Id' => 'XCM',
        'Name' => 'Card',
        'Description' => 'A flat package usually made of fibreboard from/to which product is often hung
            or attached.',
      ],
      1838 => [
        'Id' => 'XCN',
        'Name' => 'Container, not otherwise specified as transport equipment',
      ],
      1839 => [
        'Id' => 'XCO',
        'Name' => 'Carboy, non-protected',
      ],
      1840 => [
        'Id' => 'XCP',
        'Name' => 'Carboy, protected',
      ],
      1841 => [
        'Id' => 'XCQ',
        'Name' => 'Cartridge',
        'Description' => 'Package containing a charge such as propelling explosive for firearms or ink
            toner for a printer.',
      ],
      1842 => [
        'Id' => 'XCR',
        'Name' => 'Crate',
      ],
      1843 => [
        'Id' => 'XCS',
        'Name' => 'Case',
      ],
      1844 => [
        'Id' => 'XCT',
        'Name' => 'Carton',
      ],
      1845 => [
        'Id' => 'XCU',
        'Name' => 'Cup',
      ],
      1846 => [
        'Id' => 'XCV',
        'Name' => 'Cover',
      ],
      1847 => [
        'Id' => 'XCW',
        'Name' => 'Cage, roll',
      ],
      1848 => [
        'Id' => 'XCX',
        'Name' => 'Can, cylindrical',
      ],
      1849 => [
        'Id' => 'XCY',
        'Name' => 'Cylinder',
      ],
      1850 => [
        'Id' => 'XCZ',
        'Name' => 'Canvas',
      ],
      1851 => [
        'Id' => 'XDA',
        'Name' => 'Crate, multiple layer, plastic',
      ],
      1852 => [
        'Id' => 'XDB',
        'Name' => 'Crate, multiple layer, wooden',
      ],
      1853 => [
        'Id' => 'XDC',
        'Name' => 'Crate, multiple layer, cardboard',
      ],
      1854 => [
        'Id' => 'XDG',
        'Name' => 'Cage, Commonwealth Handling Equipment Pool (CHEP)',
      ],
      1855 => [
        'Id' => 'XDH',
        'Name' => 'Box, Commonwealth Handling Equipment Pool (CHEP), Eurobox',
        'Description' => 'A box mounted on a pallet base under the control of CHEP.',
      ],
      1856 => [
        'Id' => 'XDI',
        'Name' => 'Drum, iron',
      ],
      1857 => [
        'Id' => 'XDJ',
        'Name' => 'Demijohn, non-protected',
      ],
      1858 => [
        'Id' => 'XDK',
        'Name' => 'Crate, bulk, cardboard',
      ],
      1859 => [
        'Id' => 'XDL',
        'Name' => 'Crate, bulk, plastic',
      ],
      1860 => [
        'Id' => 'XDM',
        'Name' => 'Crate, bulk, wooden',
      ],
      1861 => [
        'Id' => 'XDN',
        'Name' => 'Dispenser',
      ],
      1862 => [
        'Id' => 'XDP',
        'Name' => 'Demijohn, protected',
      ],
      1863 => [
        'Id' => 'XDR',
        'Name' => 'Drum',
      ],
      1864 => [
        'Id' => 'XDS',
        'Name' => 'Tray, one layer no cover, plastic',
      ],
      1865 => [
        'Id' => 'XDT',
        'Name' => 'Tray, one layer no cover, wooden',
      ],
      1866 => [
        'Id' => 'XDU',
        'Name' => 'Tray, one layer no cover, polystyrene',
      ],
      1867 => [
        'Id' => 'XDV',
        'Name' => 'Tray, one layer no cover, cardboard',
      ],
      1868 => [
        'Id' => 'XDW',
        'Name' => 'Tray, two layers no cover, plastic tray',
      ],
      1869 => [
        'Id' => 'XDX',
        'Name' => 'Tray, two layers no cover, wooden',
      ],
      1870 => [
        'Id' => 'XDY',
        'Name' => 'Tray, two layers no cover, cardboard',
      ],
      1871 => [
        'Id' => 'XEC',
        'Name' => 'Bag, plastic',
      ],
      1872 => [
        'Id' => 'XED',
        'Name' => 'Case, with pallet base',
      ],
      1873 => [
        'Id' => 'XEE',
        'Name' => 'Case, with pallet base, wooden',
      ],
      1874 => [
        'Id' => 'XEF',
        'Name' => 'Case, with pallet base, cardboard',
      ],
      1875 => [
        'Id' => 'XEG',
        'Name' => 'Case, with pallet base, plastic',
      ],
      1876 => [
        'Id' => 'XEH',
        'Name' => 'Case, with pallet base, metal',
      ],
      1877 => [
        'Id' => 'XEI',
        'Name' => 'Case, isothermic',
      ],
      1878 => [
        'Id' => 'XEN',
        'Name' => 'Envelope',
      ],
      1879 => [
        'Id' => 'XFB',
        'Name' => 'Flexibag',
        'Description' => 'A flexible containment bag made of plastic, typically for the transportation
            bulk non-hazardous cargoes using standard size shipping containers.',
      ],
      1880 => [
        'Id' => 'XFC',
        'Name' => 'Crate, fruit',
      ],
      1881 => [
        'Id' => 'XFD',
        'Name' => 'Crate, framed',
      ],
      1882 => [
        'Id' => 'XFE',
        'Name' => 'Flexitank',
        'Description' => 'A flexible containment tank made of plastic, typically for the transportation
            bulk non-hazardous cargoes using standard size shipping containers.',
      ],
      1883 => [
        'Id' => 'XFI',
        'Name' => 'Firkin',
      ],
      1884 => [
        'Id' => 'XFL',
        'Name' => 'Flask',
      ],
      1885 => [
        'Id' => 'XFO',
        'Name' => 'Footlocker',
      ],
      1886 => [
        'Id' => 'XFP',
        'Name' => 'Filmpack',
      ],
      1887 => [
        'Id' => 'XFR',
        'Name' => 'Frame',
      ],
      1888 => [
        'Id' => 'XFT',
        'Name' => 'Foodtainer',
      ],
      1889 => [
        'Id' => 'XFW',
        'Name' => 'Cart, flatbed',
        'Description' => 'Wheeled flat bedded device on which trays or other regular shaped items are
            packed for transportation purposes.',
      ],
      1890 => [
        'Id' => 'XFX',
        'Name' => 'Bag, flexible container',
      ],
      1891 => [
        'Id' => 'XGB',
        'Name' => 'Bottle, gas',
        'Description' => 'A narrow-necked metal cylinder for retention of liquefied or compressed
            gas.',
      ],
      1892 => [
        'Id' => 'XGI',
        'Name' => 'Girder',
      ],
      1893 => [
        'Id' => 'XGL',
        'Name' => 'Container, gallon',
        'Description' => 'A container with a capacity of one gallon.',
      ],
      1894 => [
        'Id' => 'XGR',
        'Name' => 'Receptacle, glass',
        'Description' => 'Containment vessel made of glass for retaining substances or
            articles.',
      ],
      1895 => [
        'Id' => 'XGU',
        'Name' => 'Tray, containing horizontally stacked flat items',
        'Description' => 'Tray containing flat items stacked on top of one another.',
      ],
      1896 => [
        'Id' => 'XGY',
        'Name' => 'Bag, gunny',
        'Description' => 'A sack made of gunny or burlap, used for transporting coarse commodities, such
            as grains, potatoes, and other agricultural products.',
      ],
      1897 => [
        'Id' => 'XGZ',
        'Name' => 'Girders, in bundle/bunch/truss',
      ],
      1898 => [
        'Id' => 'XHA',
        'Name' => 'Basket, with handle, plastic',
      ],
      1899 => [
        'Id' => 'XHB',
        'Name' => 'Basket, with handle, wooden',
      ],
      1900 => [
        'Id' => 'XHC',
        'Name' => 'Basket, with handle, cardboard',
      ],
      1901 => [
        'Id' => 'XHG',
        'Name' => 'Hogshead',
      ],
      1902 => [
        'Id' => 'XHN',
        'Name' => 'Hanger',
        'Description' => 'A purpose shaped device with a hook at the top for hanging items from a
            rail.',
      ],
      1903 => [
        'Id' => 'XHR',
        'Name' => 'Hamper',
      ],
      1904 => [
        'Id' => 'XIA',
        'Name' => 'Package, display, wooden',
      ],
      1905 => [
        'Id' => 'XIB',
        'Name' => 'Package, display, cardboard',
      ],
      1906 => [
        'Id' => 'XIC',
        'Name' => 'Package, display, plastic',
      ],
      1907 => [
        'Id' => 'XID',
        'Name' => 'Package, display, metal',
      ],
      1908 => [
        'Id' => 'XIE',
        'Name' => 'Package, show',
      ],
      1909 => [
        'Id' => 'XIF',
        'Name' => 'Package, flow',
        'Description' => 'A flexible tubular package or skin, possibly transparent, often used for
            containment of foodstuffs (e.g. salami sausage).',
      ],
      1910 => [
        'Id' => 'XIG',
        'Name' => 'Package, paper wrapped',
      ],
      1911 => [
        'Id' => 'XIH',
        'Name' => 'Drum, plastic',
      ],
      1912 => [
        'Id' => 'XIK',
        'Name' => 'Package, cardboard, with bottle grip-holes',
        'Description' => 'Packaging material made out of cardboard that facilitates the separation of
            individual glass or plastic bottles.',
      ],
      1913 => [
        'Id' => 'XIL',
        'Name' => 'Tray, rigid, lidded stackable (CEN TS 14482:2002)',
        'Description' => 'Lidded stackable rigid tray compliant with CEN TS 14482:2002.',
      ],
      1914 => [
        'Id' => 'XIN',
        'Name' => 'Ingot',
      ],
      1915 => [
        'Id' => 'XIZ',
        'Name' => 'Ingots, in bundle/bunch/truss',
      ],
      1916 => [
        'Id' => 'XJB',
        'Name' => 'Bag, jumbo',
        'Description' => 'A flexible containment bag, widely used for storage, transportation and
            handling of powder, flake or granular materials. Typically constructed from woven
            polypropylene (PP) fabric in the form of cubic bags.',
      ],
      1917 => [
        'Id' => 'XJC',
        'Name' => 'Jerrican, rectangular',
      ],
      1918 => [
        'Id' => 'XJG',
        'Name' => 'Jug',
      ],
      1919 => [
        'Id' => 'XJR',
        'Name' => 'Jar',
      ],
      1920 => [
        'Id' => 'XJT',
        'Name' => 'Jutebag',
      ],
      1921 => [
        'Id' => 'XJY',
        'Name' => 'Jerrican, cylindrical',
      ],
      1922 => [
        'Id' => 'XKG',
        'Name' => 'Keg',
      ],
      1923 => [
        'Id' => 'XKI',
        'Name' => 'Kit',
        'Description' => 'A set of articles or implements used for a specific purpose.',
      ],
      1924 => [
        'Id' => 'XLE',
        'Name' => 'Luggage',
        'Description' => 'A collection of bags, cases and/or containers which hold personal belongings
            for a journey.',
      ],
      1925 => [
        'Id' => 'XLG',
        'Name' => 'Log',
      ],
      1926 => [
        'Id' => 'XLT',
        'Name' => 'Lot',
      ],
      1927 => [
        'Id' => 'XLU',
        'Name' => 'Lug',
        'Description' => 'A wooden box for the transportation and storage of fruit or
            vegetables.',
      ],
      1928 => [
        'Id' => 'XLV',
        'Name' => 'Liftvan',
        'Description' => 'A wooden or metal container used for packing household goods and personal
            effects.',
      ],
      1929 => [
        'Id' => 'XLZ',
        'Name' => 'Logs, in bundle/bunch/truss',
      ],
      1930 => [
        'Id' => 'XMA',
        'Name' => 'Crate, metal',
        'Description' => 'Containment box made of metal for retaining substances or
            articles.',
      ],
      1931 => [
        'Id' => 'XMB',
        'Name' => 'Bag, multiply',
      ],
      1932 => [
        'Id' => 'XMC',
        'Name' => 'Crate, milk',
      ],
      1933 => [
        'Id' => 'XME',
        'Name' => 'Container, metal',
        'Description' => 'A type of containment box made of metal for retaining substances or articles,
            not otherwise specified as transport equipment.',
      ],
      1934 => [
        'Id' => 'XMR',
        'Name' => 'Receptacle, metal',
        'Description' => 'Containment vessel made of metal for retaining substances or
            articles.',
      ],
      1935 => [
        'Id' => 'XMS',
        'Name' => 'Sack, multi-wall',
      ],
      1936 => [
        'Id' => 'XMT',
        'Name' => 'Mat',
      ],
      1937 => [
        'Id' => 'XMW',
        'Name' => 'Receptacle, plastic wrapped',
        'Description' => 'Containment vessel wrapped with plastic for retaining substances or
            articles.',
      ],
      1938 => [
        'Id' => 'XMX',
        'Name' => 'Matchbox',
      ],
      1939 => [
        'Id' => 'XNA',
        'Name' => 'Not available',
      ],
      1940 => [
        'Id' => 'XNE',
        'Name' => 'Unpacked or unpackaged',
      ],
      1941 => [
        'Id' => 'XNF',
        'Name' => 'Unpacked or unpackaged, single unit',
      ],
      1942 => [
        'Id' => 'XNG',
        'Name' => 'Unpacked or unpackaged, multiple units',
      ],
      1943 => [
        'Id' => 'XNS',
        'Name' => 'Nest',
      ],
      1944 => [
        'Id' => 'XNT',
        'Name' => 'Net',
      ],
      1945 => [
        'Id' => 'XNU',
        'Name' => 'Net, tube, plastic',
      ],
      1946 => [
        'Id' => 'XNV',
        'Name' => 'Net, tube, textile',
      ],
      1947 => [
        'Id' => 'XO1',
        'Name' => 'Two sided cage on wheels with fixing strap',
      ],
      1948 => [
        'Id' => 'XO2',
        'Name' => 'Trolley',
      ],
      1949 => [
        'Id' => 'XO3',
        'Name' => 'Oneway pallet ISO 0 - 1/2 EURO Pallet',
      ],
      1950 => [
        'Id' => 'XO4',
        'Name' => 'Oneway pallet ISO 1 - 1/1 EURO Pallet',
      ],
      1951 => [
        'Id' => 'XO5',
        'Name' => 'Oneway pallet ISO 2 - 2/1 EURO Pallet',
      ],
      1952 => [
        'Id' => 'XO6',
        'Name' => 'Pallet with exceptional dimensions',
      ],
      1953 => [
        'Id' => 'XO7',
        'Name' => 'Wooden pallet  40 cm x 80 cm',
      ],
      1954 => [
        'Id' => 'XO8',
        'Name' => 'Plastic pallet SRS 60 cm x 80 cm',
      ],
      1955 => [
        'Id' => 'XO9',
        'Name' => 'Plastic pallet SRS 80 cm x 120 cm',
      ],
      1956 => [
        'Id' => 'XOA',
        'Name' => 'Pallet, CHEP 40 cm x 60 cm',
        'Description' => 'Commonwealth Handling Equipment Pool (CHEP) standard pallet of dimensions 40
            centimeters x 60 centimeters.',
      ],
      1957 => [
        'Id' => 'XOB',
        'Name' => 'Pallet, CHEP 80 cm x 120 cm',
        'Description' => 'Commonwealth Handling Equipment Pool (CHEP) standard pallet of dimensions 80
            centimeters x 120 centimeters.',
      ],
      1958 => [
        'Id' => 'XOC',
        'Name' => 'Pallet, CHEP 100 cm x 120 cm',
        'Description' => 'Commonwealth Handling Equipment Pool (CHEP) standard pallet of dimensions 100
            centimeters x 120 centimeters.',
      ],
      1959 => [
        'Id' => 'XOD',
        'Name' => 'Pallet, AS 4068-1993',
        'Description' => 'Australian standard pallet of dimensions 115.5 centimeters x 116.5
            centimeters.',
      ],
      1960 => [
        'Id' => 'XOE',
        'Name' => 'Pallet, ISO T11',
        'Description' => 'ISO standard pallet of dimensions 110 centimeters x 110 centimeters, prevalent
            in Asia - Pacific region.',
      ],
      1961 => [
        'Id' => 'XOF',
        'Name' => 'Platform, unspecified weight or dimension',
        'Description' => 'A pallet equivalent shipping platform of unknown dimensions or unknown
            weight.',
      ],
      1962 => [
        'Id' => 'XOG',
        'Name' => 'Pallet ISO 0 - 1/2 EURO Pallet',
      ],
      1963 => [
        'Id' => 'XOH',
        'Name' => 'Pallet ISO 1 - 1/1 EURO Pallet',
      ],
      1964 => [
        'Id' => 'XOI',
        'Name' => 'Pallet ISO 2 – 2/1 EURO Pallet',
      ],
      1965 => [
        'Id' => 'XOK',
        'Name' => 'Block',
        'Description' => 'A solid piece of a hard substance, such as granite, having one or more flat
            sides.',
      ],
      1966 => [
        'Id' => 'XOJ',
        'Name' => '1/4 EURO Pallet',
      ],
      1967 => [
        'Id' => 'XOL',
        'Name' => '1/8 EURO Pallet',
      ],
      1968 => [
        'Id' => 'XOM',
        'Name' => 'Synthetic pallet ISO 1',
      ],
      1969 => [
        'Id' => 'XON',
        'Name' => 'Synthetic pallet ISO 2',
      ],
      1970 => [
        'Id' => 'XOP',
        'Name' => 'Wholesaler pallet',
      ],
      1971 => [
        'Id' => 'XOQ',
        'Name' => 'Pallet 80 X 100 cm',
      ],
      1972 => [
        'Id' => 'XOR',
        'Name' => 'Pallet 60 X 100 cm',
      ],
      1973 => [
        'Id' => 'XOS',
        'Name' => 'Oneway pallet',
      ],
      1974 => [
        'Id' => 'XOV',
        'Name' => 'Returnable pallet',
      ],
      1975 => [
        'Id' => 'XOW',
        'Name' => 'Large bag, pallet sized',
      ],
      1976 => [
        'Id' => 'XOT',
        'Name' => 'Octabin',
        'Description' => 'A standard cardboard container of large dimensions for storing for example
            vegetables, granules of plastics or other dry products.',
      ],
      1977 => [
        'Id' => 'XOU',
        'Name' => 'Container, outer',
        'Description' => 'A type of containment box that serves as the outer shipping container, not
            otherwise specified as transport equipment.',
      ],
      1978 => [
        'Id' => 'XOX',
        'Name' => 'A wheeled pallet with raised rim (81 x 67 x 135)',
      ],
      1979 => [
        'Id' => 'XOY',
        'Name' => 'A Wheeled pallet with raised rim (81 x 72 x 135)',
      ],
      1980 => [
        'Id' => 'XOZ',
        'Name' => 'Wheeled pallet with raised rim ( 81 x 60 x 16)',
      ],
      1981 => [
        'Id' => 'XP1',
        'Name' => 'CHEP pallet 60 cm x 80 cm',
      ],
      1982 => [
        'Id' => 'XP2',
        'Name' => 'Pan',
        'Description' => 'A shallow, wide, open container, usually of metal.',
      ],
      1983 => [
        'Id' => 'XP3',
        'Name' => 'LPR pallet 60 cm x 80 cm',
      ],
      1984 => [
        'Id' => 'XP4',
        'Name' => 'LPR pallet 80 cm x 120 cm',
      ],
      1985 => [
        'Id' => 'XPA',
        'Name' => 'Packet',
        'Description' => 'Small package.',
      ],
      1986 => [
        'Id' => 'XPB',
        'Name' => 'Pallet, box Combined open-ended box and pallet',
      ],
      1987 => [
        'Id' => 'XPC',
        'Name' => 'Parcel',
      ],
      1988 => [
        'Id' => 'XPD',
        'Name' => 'Pallet, modular, collars 80cms * 100cms',
        'Description' => 'Standard sized pallet of dimensions 80 centimeters by 100 centimeters
            (cms).',
      ],
      1989 => [
        'Id' => 'XPE',
        'Name' => 'Pallet, modular, collars 80cms * 120cms',
        'Description' => 'Standard sized pallet of dimensions 80 centimeters by 120 centimeters
            (cms).',
      ],
      1990 => [
        'Id' => 'XPF',
        'Name' => 'Pen',
        'Description' => 'A small open top enclosure for retaining animals.',
      ],
      1991 => [
        'Id' => 'XPG',
        'Name' => 'Plate',
      ],
      1992 => [
        'Id' => 'XPH',
        'Name' => 'Pitcher',
      ],
      1993 => [
        'Id' => 'XPI',
        'Name' => 'Pipe',
      ],
      1994 => [
        'Id' => 'XPJ',
        'Name' => 'Punnet',
      ],
      1995 => [
        'Id' => 'XPK',
        'Name' => 'Package',
        'Description' => 'Standard packaging unit.',
      ],
      1996 => [
        'Id' => 'XPL',
        'Name' => 'Pail',
      ],
      1997 => [
        'Id' => 'XPN',
        'Name' => 'Plank',
      ],
      1998 => [
        'Id' => 'XPO',
        'Name' => 'Pouch',
      ],
      1999 => [
        'Id' => 'XPP',
        'Name' => 'Piece',
        'Description' => 'A loose or unpacked article.',
      ],
      2000 => [
        'Id' => 'XPR',
        'Name' => 'Receptacle, plastic',
        'Description' => 'Containment vessel made of plastic for retaining substances or
            articles.',
      ],
      2001 => [
        'Id' => 'XPT',
        'Name' => 'Pot',
      ],
      2002 => [
        'Id' => 'XPU',
        'Name' => 'Tray',
      ],
      2003 => [
        'Id' => 'XPV',
        'Name' => 'Pipes, in bundle/bunch/truss',
      ],
      2004 => [
        'Id' => 'XPX',
        'Name' => 'Pallet',
        'Description' => 'Platform or open-ended box, usually made of wood, on which goods are retained
            for ease of mechanical handling during transport and storage.',
      ],
      2005 => [
        'Id' => 'XPY',
        'Name' => 'Plates, in bundle/bunch/truss',
      ],
      2006 => [
        'Id' => 'XPZ',
        'Name' => 'Planks, in bundle/bunch/truss',
      ],
      2007 => [
        'Id' => 'XQA',
        'Name' => 'Drum, steel, non-removable head',
      ],
      2008 => [
        'Id' => 'XQB',
        'Name' => 'Drum, steel, removable head',
      ],
      2009 => [
        'Id' => 'XQC',
        'Name' => 'Drum, aluminium, non-removable head',
      ],
      2010 => [
        'Id' => 'XQD',
        'Name' => 'Drum, aluminium, removable head',
      ],
      2011 => [
        'Id' => 'XQF',
        'Name' => 'Drum, plastic, non-removable head',
      ],
      2012 => [
        'Id' => 'XQG',
        'Name' => 'Drum, plastic, removable head',
      ],
      2013 => [
        'Id' => 'XQH',
        'Name' => 'Barrel, wooden, bung type',
      ],
      2014 => [
        'Id' => 'XQJ',
        'Name' => 'Barrel, wooden, removable head',
      ],
      2015 => [
        'Id' => 'XQK',
        'Name' => 'Jerrican, steel, non-removable head',
      ],
      2016 => [
        'Id' => 'XQL',
        'Name' => 'Jerrican, steel, removable head',
      ],
      2017 => [
        'Id' => 'XQM',
        'Name' => 'Jerrican, plastic, non-removable head',
      ],
      2018 => [
        'Id' => 'XQN',
        'Name' => 'Jerrican, plastic, removable head',
      ],
      2019 => [
        'Id' => 'XQP',
        'Name' => 'Box, wooden, natural wood, ordinary',
      ],
      2020 => [
        'Id' => 'XQQ',
        'Name' => 'Box, wooden, natural wood, with sift proof walls',
      ],
      2021 => [
        'Id' => 'XQR',
        'Name' => 'Box, plastic, expanded',
      ],
      2022 => [
        'Id' => 'XQS',
        'Name' => 'Box, plastic, solid',
      ],
      2023 => [
        'Id' => 'XRD',
        'Name' => 'Rod',
      ],
      2024 => [
        'Id' => 'XRG',
        'Name' => 'Ring',
      ],
      2025 => [
        'Id' => 'XRJ',
        'Name' => 'Rack, clothing hanger',
      ],
      2026 => [
        'Id' => 'XRK',
        'Name' => 'Rack',
      ],
      2027 => [
        'Id' => 'XRL',
        'Name' => 'Reel',
        'Description' => 'Cylindrical rotatory device with a rim at each end on which materials are
            wound.',
      ],
      2028 => [
        'Id' => 'XRO',
        'Name' => 'Roll',
      ],
      2029 => [
        'Id' => 'XRT',
        'Name' => 'Rednet',
        'Description' => 'Containment material made of red mesh netting for retaining articles (e.g.
            trees).',
      ],
      2030 => [
        'Id' => 'XRZ',
        'Name' => 'Rods, in bundle/bunch/truss',
      ],
      2031 => [
        'Id' => 'XSA',
        'Name' => 'Sack',
      ],
      2032 => [
        'Id' => 'XSB',
        'Name' => 'Slab',
      ],
      2033 => [
        'Id' => 'XSC',
        'Name' => 'Crate, shallow',
      ],
      2034 => [
        'Id' => 'XSD',
        'Name' => 'Spindle',
      ],
      2035 => [
        'Id' => 'XSE',
        'Name' => 'Sea-chest',
      ],
      2036 => [
        'Id' => 'XSH',
        'Name' => 'Sachet',
      ],
      2037 => [
        'Id' => 'XSI',
        'Name' => 'Skid',
        'Description' => 'A low movable platform or pallet to facilitate the handling and transport of
            goods.',
      ],
      2038 => [
        'Id' => 'XSK',
        'Name' => 'Case, skeleton',
      ],
      2039 => [
        'Id' => 'XSL',
        'Name' => 'Slipsheet',
        'Description' => 'Hard plastic sheeting primarily used as the base on which to stack goods to
            optimise the space within a container. May be used as an alternative to a palletized
            packaging.',
      ],
      2040 => [
        'Id' => 'XSM',
        'Name' => 'Sheetmetal',
      ],
      2041 => [
        'Id' => 'XSO',
        'Name' => 'Spool',
        'Description' => 'A packaging container used in the transport of such items as wire, cable, tape
            and yarn.',
      ],
      2042 => [
        'Id' => 'XSP',
        'Name' => 'Sheet, plastic wrapping',
      ],
      2043 => [
        'Id' => 'XSS',
        'Name' => 'Case, steel',
      ],
      2044 => [
        'Id' => 'XST',
        'Name' => 'Sheet',
      ],
      2045 => [
        'Id' => 'XSU',
        'Name' => 'Suitcase',
      ],
      2046 => [
        'Id' => 'XSV',
        'Name' => 'Envelope, steel',
      ],
      2047 => [
        'Id' => 'XSW',
        'Name' => 'Shrinkwrapped',
        'Description' => 'Goods retained in a transparent plastic film that has been wrapped around and
            then shrunk tightly on to the goods.',
      ],
      2048 => [
        'Id' => 'XSX',
        'Name' => 'Set',
      ],
      2049 => [
        'Id' => 'XSY',
        'Name' => 'Sleeve',
      ],
      2050 => [
        'Id' => 'XSZ',
        'Name' => 'Sheets, in bundle/bunch/truss',
      ],
      2051 => [
        'Id' => 'XT1',
        'Name' => 'Tablet',
        'Description' => 'A loose or unpacked article in the form of a bar, block or piece.',
      ],
      2052 => [
        'Id' => 'XTB',
        'Name' => 'Tub',
      ],
      2053 => [
        'Id' => 'XTC',
        'Name' => 'Tea-chest',
      ],
      2054 => [
        'Id' => 'XTD',
        'Name' => 'Tube, collapsible',
      ],
      2055 => [
        'Id' => 'XTE',
        'Name' => 'Tyre',
        'Description' => 'A ring made of rubber and/or metal surrounding a wheel.',
      ],
      2056 => [
        'Id' => 'XTG',
        'Name' => 'Tank container, generic',
        'Description' => 'A specially constructed container for transporting liquids and gases in
            bulk.',
      ],
      2057 => [
        'Id' => 'XTI',
        'Name' => 'Tierce',
      ],
      2058 => [
        'Id' => 'XTK',
        'Name' => 'Tank, rectangular',
      ],
      2059 => [
        'Id' => 'XTL',
        'Name' => 'Tub, with lid',
      ],
      2060 => [
        'Id' => 'XTN',
        'Name' => 'Tin',
      ],
      2061 => [
        'Id' => 'XTO',
        'Name' => 'Tun',
      ],
      2062 => [
        'Id' => 'XTR',
        'Name' => 'Trunk',
      ],
      2063 => [
        'Id' => 'XTS',
        'Name' => 'Truss',
      ],
      2064 => [
        'Id' => 'XTT',
        'Name' => 'Bag, tote',
        'Description' => 'A capacious bag or basket.',
      ],
      2065 => [
        'Id' => 'XTU',
        'Name' => 'Tube',
      ],
      2066 => [
        'Id' => 'XTV',
        'Name' => 'Tube, with nozzle',
        'Description' => 'A tube made of plastic, metal or cardboard fitted with a nozzle, containing a
            liquid or semi-liquid product, e.g. silicon.',
      ],
      2067 => [
        'Id' => 'XTW',
        'Name' => 'Pallet, triwall',
        'Description' => 'A lightweight pallet made from heavy duty corrugated board.',
      ],
      2068 => [
        'Id' => 'XTY',
        'Name' => 'Tank, cylindrical',
      ],
      2069 => [
        'Id' => 'XTZ',
        'Name' => 'Tubes, in bundle/bunch/truss',
      ],
      2070 => [
        'Id' => 'XUC',
        'Name' => 'Uncaged',
      ],
      2071 => [
        'Id' => 'XUN',
        'Name' => 'Unit',
        'Description' => 'A type of package composed of a single item or object, not otherwise specified
            as a unit of transport equipment.',
      ],
      2072 => [
        'Id' => 'XVA',
        'Name' => 'Vat',
      ],
      2073 => [
        'Id' => 'XVG',
        'Name' => 'Bulk, gas (at 1031 mbar and 15°C)',
      ],
      2074 => [
        'Id' => 'XVI',
        'Name' => 'Vial',
      ],
      2075 => [
        'Id' => 'XVK',
        'Name' => 'Vanpack',
        'Description' => 'A type of wooden crate.',
      ],
      2076 => [
        'Id' => 'XVL',
        'Name' => 'Bulk, liquid',
      ],
      2077 => [
        'Id' => 'XVO',
        'Name' => 'Bulk, solid, large particles (“nodules”)',
      ],
      2078 => [
        'Id' => 'XVP',
        'Name' => 'Vacuum-packed',
      ],
      2079 => [
        'Id' => 'XVQ',
        'Name' => 'Bulk, liquefied gas (at abnormal temperature/pressure)',
      ],
      2080 => [
        'Id' => 'XVN',
        'Name' => 'Vehicle',
        'Description' => 'A self-propelled means of conveyance.',
      ],
      2081 => [
        'Id' => 'XVR',
        'Name' => 'Bulk, solid, granular particles (“grains”)',
      ],
      2082 => [
        'Id' => 'XVS',
        'Name' => 'Bulk, scrap metal',
        'Description' => 'Loose or unpacked scrap metal transported in bulk form.',
      ],
      2083 => [
        'Id' => 'XVY',
        'Name' => 'Bulk, solid, fine particles (“powders”)',
      ],
      2084 => [
        'Id' => 'XWA',
        'Name' => 'Intermediate bulk container',
        'Description' => 'A reusable container made of metal, plastic, textile, wood or composite
            materials used to facilitate transportation of bulk solids and liquids in manageable
            volumes.',
      ],
      2085 => [
        'Id' => 'XWB',
        'Name' => 'Wickerbottle',
      ],
      2086 => [
        'Id' => 'XWC',
        'Name' => 'Intermediate bulk container, steel',
      ],
      2087 => [
        'Id' => 'XWD',
        'Name' => 'Intermediate bulk container, aluminium',
      ],
      2088 => [
        'Id' => 'XWF',
        'Name' => 'Intermediate bulk container, metal',
      ],
      2089 => [
        'Id' => 'XWG',
        'Name' => 'Intermediate bulk container, steel, pressurised > 10 kpa',
      ],
      2090 => [
        'Id' => 'XWH',
        'Name' => 'Intermediate bulk container, aluminium, pressurised > 10 kpa',
      ],
      2091 => [
        'Id' => 'XWJ',
        'Name' => 'Intermediate bulk container, metal, pressure 10 kpa',
      ],
      2092 => [
        'Id' => 'XWK',
        'Name' => 'Intermediate bulk container, steel, liquid',
      ],
      2093 => [
        'Id' => 'XWL',
        'Name' => 'Intermediate bulk container, aluminium, liquid',
      ],
      2094 => [
        'Id' => 'XWM',
        'Name' => 'Intermediate bulk container, metal, liquid',
      ],
      2095 => [
        'Id' => 'XWN',
        'Name' => 'Intermediate bulk container, woven plastic, without coat/liner',
      ],
      2096 => [
        'Id' => 'XWP',
        'Name' => 'Intermediate bulk container, woven plastic, coated',
      ],
      2097 => [
        'Id' => 'XWQ',
        'Name' => 'Intermediate bulk container, woven plastic, with liner',
      ],
      2098 => [
        'Id' => 'XWR',
        'Name' => 'Intermediate bulk container, woven plastic, coated and liner',
      ],
      2099 => [
        'Id' => 'XWS',
        'Name' => 'Intermediate bulk container, plastic film',
      ],
      2100 => [
        'Id' => 'XWT',
        'Name' => 'Intermediate bulk container, textile with out coat/liner',
      ],
      2101 => [
        'Id' => 'XWU',
        'Name' => 'Intermediate bulk container, natural wood, with inner liner',
      ],
      2102 => [
        'Id' => 'XWV',
        'Name' => 'Intermediate bulk container, textile, coated',
      ],
      2103 => [
        'Id' => 'XWW',
        'Name' => 'Intermediate bulk container, textile, with liner',
      ],
      2104 => [
        'Id' => 'XWX',
        'Name' => 'Intermediate bulk container, textile, coated and liner',
      ],
      2105 => [
        'Id' => 'XWY',
        'Name' => 'Intermediate bulk container, plywood, with inner liner',
      ],
      2106 => [
        'Id' => 'XWZ',
        'Name' => 'Intermediate bulk container, reconstituted wood, with inner liner',
      ],
      2107 => [
        'Id' => 'XXA',
        'Name' => 'Bag, woven plastic, without inner coat/liner',
      ],
      2108 => [
        'Id' => 'XXB',
        'Name' => 'Bag, woven plastic, sift proof',
      ],
      2109 => [
        'Id' => 'XXC',
        'Name' => 'Bag, woven plastic, water resistant',
      ],
      2110 => [
        'Id' => 'XXD',
        'Name' => 'Bag, plastics film',
      ],
      2111 => [
        'Id' => 'XXF',
        'Name' => 'Bag, textile, without inner coat/liner',
      ],
      2112 => [
        'Id' => 'XXG',
        'Name' => 'Bag, textile, sift proof',
      ],
      2113 => [
        'Id' => 'XXH',
        'Name' => 'Bag, textile, water resistant',
      ],
      2114 => [
        'Id' => 'XXJ',
        'Name' => 'Bag, paper, multi-wall',
      ],
      2115 => [
        'Id' => 'XXK',
        'Name' => 'Bag, paper, multi-wall, water resistant',
      ],
      2116 => [
        'Id' => 'XYA',
        'Name' => 'Composite packaging, plastic receptacle in steel drum',
      ],
      2117 => [
        'Id' => 'XYB',
        'Name' => 'Composite packaging, plastic receptacle in steel crate box',
      ],
      2118 => [
        'Id' => 'XYC',
        'Name' => 'Composite packaging, plastic receptacle in aluminium drum',
      ],
      2119 => [
        'Id' => 'XYD',
        'Name' => 'Composite packaging, plastic receptacle in aluminium crate',
      ],
      2120 => [
        'Id' => 'XYF',
        'Name' => 'Composite packaging, plastic receptacle in wooden box',
      ],
      2121 => [
        'Id' => 'XYG',
        'Name' => 'Composite packaging, plastic receptacle in plywood drum',
      ],
      2122 => [
        'Id' => 'XYH',
        'Name' => 'Composite packaging, plastic receptacle in plywood box',
      ],
      2123 => [
        'Id' => 'XYJ',
        'Name' => 'Composite packaging, plastic receptacle in fibre drum',
      ],
      2124 => [
        'Id' => 'XYK',
        'Name' => 'Composite packaging, plastic receptacle in fibreboard box',
      ],
      2125 => [
        'Id' => 'XYL',
        'Name' => 'Composite packaging, plastic receptacle in plastic drum',
      ],
      2126 => [
        'Id' => 'XYM',
        'Name' => 'Composite packaging, plastic receptacle in solid plastic box',
      ],
      2127 => [
        'Id' => 'XYN',
        'Name' => 'Composite packaging, glass receptacle in steel drum',
      ],
      2128 => [
        'Id' => 'XYP',
        'Name' => 'Composite packaging, glass receptacle in steel crate box',
      ],
      2129 => [
        'Id' => 'XYQ',
        'Name' => 'Composite packaging, glass receptacle in aluminium drum',
      ],
      2130 => [
        'Id' => 'XYR',
        'Name' => 'Composite packaging, glass receptacle in aluminium crate',
      ],
      2131 => [
        'Id' => 'XYS',
        'Name' => 'Composite packaging, glass receptacle in wooden box',
      ],
      2132 => [
        'Id' => 'XYT',
        'Name' => 'Composite packaging, glass receptacle in plywood drum',
      ],
      2133 => [
        'Id' => 'XYV',
        'Name' => 'Composite packaging, glass receptacle in wickerwork hamper',
      ],
      2134 => [
        'Id' => 'XYW',
        'Name' => 'Composite packaging, glass receptacle in fibre drum',
      ],
      2135 => [
        'Id' => 'XYX',
        'Name' => 'Composite packaging, glass receptacle in fibreboard box',
      ],
      2136 => [
        'Id' => 'XYY',
        'Name' => 'Composite packaging, glass receptacle in expandable plastic pack',
      ],
      2137 => [
        'Id' => 'XYZ',
        'Name' => 'Composite packaging, glass receptacle in solid plastic pack',
      ],
      2138 => [
        'Id' => 'XZA',
        'Name' => 'Intermediate bulk container, paper, multi-wall',
      ],
      2139 => [
        'Id' => 'XZB',
        'Name' => 'Bag, large',
      ],
      2140 => [
        'Id' => 'XZC',
        'Name' => 'Intermediate bulk container, paper, multi-wall, water resistant',
      ],
      2141 => [
        'Id' => 'XZD',
        'Name' => 'Intermediate bulk container, rigid plastic, with structural equipment, solids',
      ],
      2142 => [
        'Id' => 'XZF',
        'Name' => 'Intermediate bulk container, rigid plastic, freestanding, solids',
      ],
      2143 => [
        'Id' => 'XZG',
        'Name' => 'Intermediate bulk container, rigid plastic, with structural equipment,
            pressurised',
      ],
      2144 => [
        'Id' => 'XZH',
        'Name' => 'Intermediate bulk container, rigid plastic, freestanding, pressurised',
      ],
      2145 => [
        'Id' => 'XZJ',
        'Name' => 'Intermediate bulk container, rigid plastic, with structural equipment, liquids',
      ],
      2146 => [
        'Id' => 'XZK',
        'Name' => 'Intermediate bulk container, rigid plastic, freestanding, liquids',
      ],
      2147 => [
        'Id' => 'XZL',
        'Name' => 'Intermediate bulk container, composite, rigid plastic, solids',
      ],
      2148 => [
        'Id' => 'XZM',
        'Name' => 'Intermediate bulk container, composite, flexible plastic, solids',
      ],
      2149 => [
        'Id' => 'XZN',
        'Name' => 'Intermediate bulk container, composite, rigid plastic, pressurised',
      ],
      2150 => [
        'Id' => 'XZP',
        'Name' => 'Intermediate bulk container, composite, flexible plastic, pressurised',
      ],
      2151 => [
        'Id' => 'XZQ',
        'Name' => 'Intermediate bulk container, composite, rigid plastic, liquids',
      ],
      2152 => [
        'Id' => 'XZR',
        'Name' => 'Intermediate bulk container, composite, flexible plastic, liquids',
      ],
      2153 => [
        'Id' => 'XZS',
        'Name' => 'Intermediate bulk container, composite',
      ],
      2154 => [
        'Id' => 'XZT',
        'Name' => 'Intermediate bulk container, fibreboard',
      ],
      2155 => [
        'Id' => 'XZU',
        'Name' => 'Intermediate bulk container, flexible',
      ],
      2156 => [
        'Id' => 'XZV',
        'Name' => 'Intermediate bulk container, metal, other than steel',
      ],
      2157 => [
        'Id' => 'XZW',
        'Name' => 'Intermediate bulk container, natural wood',
      ],
      2158 => [
        'Id' => 'XZX',
        'Name' => 'Intermediate bulk container, plywood',
      ],
      2159 => [
        'Id' => 'XZY',
        'Name' => 'Intermediate bulk container, reconstituted wood',
      ],
      2160 => [
        'Id' => 'XZZ',
        'Name' => 'Mutually defined',
      ],
    ];
    return $array;
}
}    