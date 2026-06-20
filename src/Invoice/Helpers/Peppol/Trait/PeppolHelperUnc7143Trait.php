<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

trait PeppolHelperUnc7143Trait
{
    /**
     * Related logic:
     * https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/
      structure/codelist/UNCL7143.xml
     * @return array
     */
    public function getUnc7143(): array
    {
        return array_merge(
            $this->unc7143Chunk0(),
            $this->unc7143Chunk1(),
            $this->unc7143Chunk2(),
            $this->unc7143Chunk3(),
            $this->unc7143Chunk4a(),
            $this->unc7143Chunk4b(),
            $this->unc7143Chunk5(),
            $this->unc7143Chunk6(),
            $this->unc7143Chunk7(),
            $this->unc7143Chunk8(),
        );
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk0(): array
    {
        return [
            0 => [
                'Id' => 'AA',
                'Name' => 'Product version number',
                'Description' => 'Number assigned by manufacturer or seller '
                . 'to identify the release of a product.',
            ],
            1 => [
                'Id' => 'AB',
                'Name' => 'Assembly',
                'Description' => 'The item number is that of an assembly.',
            ],
            2 => [
                'Id' => 'AC',
                'Name' => 'HIBC (Health Industry Bar Code)',
                'Description' => 'Article identifier used within health sector '
                . 'to indicate data used conforms to HIBC.',
            ],
            3 => [
                'Id' => 'AD',
                'Name' => 'Cold roll number',
                'Description' => 'Number assigned to a cold roll.',
            ],
            4 => [
                'Id' => 'AE',
                'Name' => 'Hot roll number',
                'Description' => 'Number assigned to a hot roll.',
            ],
            5 => [
                'Id' => 'AF',
                'Name' => 'Slab number',
                'Description' => 'Number assigned to a slab, which is '
                . 'produced in a particular production step.',
            ],
            6 => [
                'Id' => 'AG',
                'Name' => 'Software revision number',
                'Description' => 'A number assigned to indicate'
                . ' a revision of software.',
            ],
            7 => [
                'Id' => 'AH',
                'Name' => 'UPC (Universal Product Code) Consumer package '
                . 'code (1-5-5)',
                'Description' => 'An 11-digit code that uniquely identifies '
                . 'consumer does not have a check digit.',
            ],
            8 => [
                'Id' => 'AI',
                'Name' => 'UPC (Universal Product Code) '
                . 'Consumer package code (1-5-5-1)',
                'Description' => 'A 12-digit code that uniquely identifies '
                . 'the consumer packaging of a product, including a check digit.',
            ],
            9 => [
                'Id' => 'AJ',
                'Name' => 'Sample number',
                'Description' => 'Number assigned to a sample.',
            ],
            10 => [
                'Id' => 'AK',
                'Name' => 'Pack number',
                'Description' => 'Number assigned to a pack containing '
                . 'a stack of items put together (e.g. cold roll sheets '
                . '(steel product)).',
            ],
            11 => [
                'Id' => 'AL',
                'Name' => 'UPC (Universal Product Code) Shipping container code '
                . '(1-2-5-5)',
                'Description' => 'A 13-digit code that uniquely identifies '
                . 'the manufacturer\'s shipping unit, including the '
                . 'packaging indicator.',
            ],
            12 => [
                'Id' => 'AM',
                'Name' => 'UPC (Universal Product Code)/EAN '
                . '(European article number) Shipping container code (1-2-5-5-1)',
                'Description' => 'Shipping container code '
                . '(1-2-5-5-1)manufacturer\'s shipping unit, including the
                  packagingindicator and the check digit.',
            ],
            13 => [
                'Id' => 'AN',
                'Name' => 'UPC (Universal Product Code) suffix',
                'Description' => 'A suffix used in conjunction with a '
                . 'higher level UPC (Universal product code) to '
                . 'define packing variations for a product.',
            ],
            14 => [
                'Id' => 'AO',
                'Name' => 'State label code',
                'Description' => 'A code which specifies the '
                . 'codification of the state\'s labelling requirements.',
            ],
            15 => [
                'Id' => 'AP',
                'Name' => 'Heat number',
                'Description' => 'Number assigned to the heat '
                . '(also known as the iron charge) for the '
                . 'production of steel products.',
            ],
            16 => [
                'Id' => 'AQ',
                'Name' => 'Coupon number',
                'Description' => 'A number identifying a coupon.',
            ],
            17 => [
                'Id' => 'AR',
                'Name' => 'Resource number',
                'Description' => 'A number to identify a resource.',
            ],
            18 => [
                'Id' => 'AS',
                'Name' => 'Work task number',
                'Description' => 'A number to identify a work task.',
            ],
            19 => [
                'Id' => 'AT',
                'Name' => 'Price look up number',
                'Description' => 'Identification number on a product allowing '
                . 'a quick electronic retrieval of price information '
                . 'for that product.',
            ],
            20 => [
                'Id' => 'AU',
                'Name' => 'NSN (North Atlantic Treaty Organization Stock Number)',
                'Description' => 'Number assigned under the NATO '
                . '(North Atlantic Treaty Organization) codification system to '
                . 'provide the identification of an approved item of supply.',
            ],
            21 => [
                'Id' => 'AV',
                'Name' => 'Refined product code',
                'Description' => 'A code specifying the product refinement '
                . 'designation.',
            ],
            22 => [
                'Id' => 'AW',
                'Name' => 'Exhibit',
                'Description' => 'A code indicating that the product is '
                . 'identified by an',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk1(): array
    {
        return [
            0 => [
                'Id' => 'AX',
                'Name' => 'End item',
                'Description' => 'A number specifying an end item.',
            ],
            1 => [
                'Id' => 'AY',
                'Name' => 'Federal supply classification',
                'Description' => 'A code to specify a product\'s Federal '
                . 'supply classification.',
            ],
            2 => [
                'Id' => 'AZ',
                'Name' => 'Engineering data list',
                'Description' => 'A code specifying the product\'s engineering '
                . 'data list.',
            ],
            3 => [
                'Id' => 'BA',
                'Name' => 'Milestone event number',
                'Description' => 'A number to identify a milestone event.',
            ],
            4 => [
                'Id' => 'BB',
                'Name' => 'Lot number',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'product.',
            ],
            5 => [
                'Id' => 'BC',
                'Name' => 'National drug code 4-4-2 format',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'drug format 4-4-2.',
            ],
            6 => [
                'Id' => 'BD',
                'Name' => 'National drug code 5-3-2 format',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'drug format 5-3-2.',
            ],
            7 => [
                'Id' => 'BE',
                'Name' => 'National drug code 5-4-1 format',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'drug format 5-4-1.',
            ],
            8 => [
                'Id' => 'BF',
                'Name' => 'National drug code 5-4-2 format',
                'Description' => 'A code identifying the product in national '
                . 'drug format 5-4-2.',
            ],
            9 => [
                'Id' => 'BG',
                'Name' => 'National drug code',
                'Description' => 'A code specifying the national drug '
                . 'classification.',
            ],
            10 => [
                'Id' => 'BH',
                'Name' => 'Part number',
                'Description' => 'A number indicating the part.',
            ],
            11 => [
                'Id' => 'BI',
                'Name' => 'Local Stock Number (LSN)',
                'Description' => 'A local number assigned to an item of stock.',
            ],
            12 => [
                'Id' => 'BJ',
                'Name' => 'Next higher assembly number',
                'Description' => 'A number specifying the next higher '
                . 'assembly or component into which the product is being '
                . 'incorporated.',
            ],
            13 => [
                'Id' => 'BK',
                'Name' => 'Data category',
                'Description' => 'A code specifying a category of data.',
            ],
            14 => [
                'Id' => 'BL',
                'Name' => 'Control number',
                'Description' => 'To specify the control number.',
            ],
            15 => [
                'Id' => 'BM',
                'Name' => 'Special material identification code',
                'Description' => 'A number to identify the special material code.',
            ],
            16 => [
                'Id' => 'BN',
                'Name' => 'Locally assigned control number',
                'Description' => 'A number assigned locally for control purposes.',
            ],
            17 => [
                'Id' => 'BO',
                'Name' => 'Buyer\'s colour',
                'Description' => 'Colour assigned by buyer.',
            ],
            18 => [
                'Id' => 'BP',
                'Name' => 'Buyer\'s part number',
                'Description' => 'Reference number assigned by the buyer to '
                . 'identify an article.',
            ],
            19 => [
                'Id' => 'BQ',
                'Name' => 'Variable measure product code',
                'Description' => 'A code assigned to identify a variable '
                . 'measure item.',
            ],
            20 => [
                'Id' => 'BR',
                'Name' => 'Financial phase',
                'Description' => 'To specify as an item, the financial phase.',
            ],
            21 => [
                'Id' => 'BS',
                'Name' => 'Contract breakdown',
                'Description' => 'To specify as an item, the contract breakdown.',
            ],
            22 => [
                'Id' => 'BT',
                'Name' => 'Technical phase',
                'Description' => 'To specify as an item, the technical phase.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk2(): array
    {
        return [
            0 => [
                'Id' => 'BU',
                'Name' => 'Dye lot number',
                'Description' => 'Number identifying a dye lot.',
            ],
            1 => [
                'Id' => 'BV',
                'Name' => 'Daily statement of activities',
                'Description' => 'A statement listing activities of one day.',
            ],
            2 => [
                'Id' => 'BW',
                'Name' => 'Periodical statement of activities within a '
                . 'bilaterally agreed time period',
                'Description' => 'Periodical statement listing activities '
                . 'within a bilaterally agreed time period.',
            ],
            3 => [
                'Id' => 'BX',
                'Name' => 'Calendar week statement of activities',
                'Description' => 'A statement listing activities of a '
                . 'calendar week.',
            ],
            4 => [
                'Id' => 'BY',
                'Name' => 'Calendar month statement of activities',
                'Description' => 'A statement listing activities of a '
                . 'calendar month.',
            ],
            5 => [
                'Id' => 'BZ',
                'Name' => 'Original equipment number',
                'Description' => 'Original equipment number allocated to '
                . 'spare parts by the manufacturer.',
            ],
            6 => [
                'Id' => 'CC',
                'Name' => 'Industry commodity code',
                'Description' => 'The codes given to certain commodities '
                . 'by an industry.',
            ],
            7 => [
                'Id' => 'CG',
                'Name' => 'Commodity grouping',
                'Description' => 'Code for a group of articles with common '
                . 'characteristics (e.g. used for statistical purposes).',
            ],
            8 => [
                'Id' => 'CL',
                'Name' => 'Colour number',
                'Description' => 'Code for the colour of an article.',
            ],
            9 => [
                'Id' => 'CR',
                'Name' => 'Contract number',
                'Description' => 'Reference number identifying a contract.',
            ],
            10 => [
                'Id' => 'CV',
                'Name' => 'Customs article number',
                'Description' => 'Code defined by Customs authorities to an '
                . 'article or a group of articles for Customs purposes.',
            ],
            11 => [
                'Id' => 'DR',
                'Name' => 'Drawing revision number',
                'Description' => 'Reference number indicating that a change '
                . 'or revision has been applied to a drawing.',
            ],
            12 => [
                'Id' => 'DW',
                'Name' => 'Drawing',
                'Description' => 'Reference number identifying a drawing '
                . 'of an article.',
            ],
            13 => [
                'Id' => 'EC',
                'Name' => 'Engineering change level',
                'Description' => 'Reference number indicating that a change '
                . 'or revision has been applied to an article\'s specification.',
            ],
            14 => [
                'Id' => 'EF',
                'Name' => 'Material code',
                'Description' => 'Code defining the material\'s type, surface, '
                . 'geometric form plus various classifying characteristics.',
            ],
            15 => [
                'Id' => 'EMD',
                'Name' => 'EMDN (European Medical Device Nomenclature)',
                'Description' => 'Nomenclature system for identification of '
                . 'medical devices based on European Medical Device '
                . 'Nomenclature classification system.',
            ],
            16 => [
                'Id' => 'EN',
                'Name' => 'International Article Numbering Association (EAN)',
                'Description' => 'Number assigned to a manufacturer\'s product '
                . 'according to the International Article Numbering Association.',
            ],
            17 => [
                'Id' => 'FS',
                'Name' => 'Fish species',
                'Description' => 'Identification of fish species.',
            ],
            18 => [
                'Id' => 'GB',
                'Name' => 'Buyer\'s internal product group code',
                'Description' => 'Product group code used within a buyer\'s '
                . 'internal systems.',
            ],
            19 => [
                'Id' => 'GN',
                'Name' => 'National product group code',
                'Description' => 'National product group code. Administered by '
                . 'a national agency.',
            ],
            20 => [
                'Id' => 'GS',
                'Name' => 'General specification number',
                'Description' => 'The item number is a general specification '
                . 'number.',
            ],
            21 => [
                'Id' => 'HS',
                'Name' => 'Harmonised system',
                'Description' => 'The item number is part of, or is generated '
                . 'in the context of the Harmonised Commodity Description and '
                . 'Coding System (Harmonised System), as developed and '
                . 'maintained by the World Customs Organization (WCO).',
            ],
            22 => [
                'Id' => 'IB',
                'Name' => 'ISBN (International Standard Book Number)',
                'Description' => 'A unique number identifying a book.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk3(): array
    {
        return [
            0 => [
                'Id' => 'IN',
                'Name' => 'Buyer\'s item number',
                'Description' => 'The item number has been allocated '
                . 'by the buyer.',
            ],
            1 => [
                'Id' => 'IS',
                'Name' => 'ISSN (International Standard Serial Number)',
                'Description' => 'A unique number identifying a serial '
                . 'publication.',
            ],
            2 => [
                'Id' => 'IT',
                'Name' => 'Buyer\'s style number',
                'Description' => 'Number given by the buyer to a specific '
                . 'style or form of an article, especially used for garments.',
            ],
            3 => [
                'Id' => 'IZ',
                'Name' => 'Buyer\'s size code',
                'Description' => 'Code given by the buyer to designate the '
                . 'size of an article in textile and shoe industry.',
            ],
            4 => [
                'Id' => 'MA',
                'Name' => 'Machine number',
                'Description' => 'The item number is a machine number.',
            ],
            5 => [
                'Id' => 'MF',
                'Name' => 'Manufacturer\'s (producer\'s) article number',
                'Description' => 'The number given to an article by its '
                . 'manufacturer.',
            ],
            6 => [
                'Id' => 'MN',
                'Name' => 'Model number',
                'Description' => 'Reference number assigned by the '
                . 'manufacturer to differentiate variations in similar '
                . 'products in a class or group.',
            ],
            7 => [
                'Id' => 'MP',
                'Name' => 'Product/service identification number',
                'Description' => 'Reference number identifying a product '
                . 'or service.',
            ],
            8 => [
                'Id' => 'NB',
                'Name' => 'Batch number',
                'Description' => 'The item number is a batch number',
            ],
            9 => [
                'Id' => 'ON',
                'Name' => 'Customer order number',
                'Description' => 'Reference number of a customer\'s order.',
            ],
            10 => [
                'Id' => 'PD',
                'Name' => 'Part number description',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'description associated with a number ultimately used to '
                . 'identify an article.',
            ],
            11 => [
                'Id' => 'PL',
                'Name' => 'Purchaser\'s order line number',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'line entry '
                . 'in a customer\'s order for goods or services.',
            ],
            12 => [
                'Id' => 'PO',
                'Name' => 'Purchase order number',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'customer\'s order.',
            ],
            13 => [
                'Id' => 'PV',
                'Name' => 'Promotional variant number',
                'Description' => 'The item number is a promotional '
                . 'variant number.',
            ],
            14 => [
                'Id' => 'QS',
                'Name' => 'Buyer\'s qualifier for size',
                'Description' => 'The item number qualifies the size of '
                . 'the buyer.',
            ],
            15 => [
                'Id' => 'RC',
                'Name' => 'Returnable container number',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'returnable container.',
            ],
            16 => [
                'Id' => 'RN',
                'Name' => 'Release number',
                'Description' => 'Reference number identifying a release '
                . 'from a buyer\'s purchase order.',
            ],
            17 => [
                'Id' => 'RU',
                'Name' => 'Run number',
                'Description' => 'The item number identifies the '
                . 'production or manufacturing run or sequence in which the '
                . 'item was manufactured, processed or assembled.',
            ],
            18 => [
                'Id' => 'RY',
                'Name' => 'Record keeping of model year',
                'Description' => 'The item number relates to the year in '
                . 'which the particular model was kept.',
            ],
            19 => [
                'Id' => 'SA',
                'Name' => 'Supplier\'s article number',
                'Description' => 'Number assigned to an article by the '
                . 'supplier of that article.',
            ],
            20 => [
                'Id' => 'SG',
                'Name' => 'Standard group of products (mixed assortment)',
                'Description' => 'The item number relates to a standard '
                . 'group of other items (mixed) which are grouped together '
                . 'as a single item for identification purposes.',
            ],
            21 => [
                'Id' => 'SK',
                'Name' => 'SKU (Stock keeping unit)',
                'Description' => 'Reference number of a stock keeping unit.',
            ],
            22 => [
                'Id' => 'SN',
                'Name' => 'Serial number',
                'Description' => 'Identification number of an item which '
                . 'distinguishes this specific item out of a number '
                . 'of identical items.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk4a(): array
    {
        return [
            0 => [
                'Id' => 'SRS',
                'Name' => 'RSK number',
                'Description' => 'Plumbing and heating.',
            ],
            1 => [
                'Id' => 'SRT',
                'Name' => 'IFLS (Institut Francais du Libre Service) '
                . '5 digit product classification code',
                'Description' => '5 digit code for product classification '
                . 'managed by the Institut Francais du Libre Service.',
            ],
            2 => [
                'Id' => 'SRU',
                'Name' => 'IFLS (Institut Francais du Libre Service) '
                . '9 digit product classification code',
                'Description' => '9 digit code for product classification '
                . 'managed by the Institut Francais du Libre Service.',
            ],
            3 => [
                'Id' => 'SRV',
                'Name' => 'GS1 Global Trade Item Number',
                'Description' => 'A unique number, up to 14-digits, '
                . 'assigned according to the numbering structure '
                . 'of the GS1 system.',
            ],
            4 => [
                'Id' => 'SRW',
                'Name' => 'EDIS (Energy Data Identification System)',
                'Description' => 'European system for identification '
                . 'of meter data.',
            ],
            5 => [
                'Id' => 'SRX',
                'Name' => 'Slaughter number',
                'Description' => 'Unique number given by a slaughterhouse '
                . 'to an animal or a group of animals of the same breed.',
            ],
            6 => [
                'Id' => 'SRY',
                'Name' => 'Official animal number',
                'Description' => 'Unique number given by a national authority '
                . 'to identify an animal individually.',
            ],
            7 => [
                'Id' => 'SRZ',
                'Name' => 'Harmonized tariff schedule',
                'Description' => 'The international Harmonized Tariff Schedule '
                . '(HTS) to classify the article for customs, statistical '
                . 'and other purposes.',
            ],
            8 => [
                'Id' => 'SS',
                'Name' => 'Supplier\'s supplier article number',
                'Description' => 'Article number referring to a sales '
                . 'catalogue of supplier\'s supplier.',
            ],
            9 => [
                'Id' => 'SSA',
                'Name' => '46 Level DOT Code',
                'Description' => 'A US Department of Transportation (DOT) '
                . 'code to identify hazardous (dangerous) goods, managed '
                . 'by the Customs and Border Protection (CBP) agency.',
            ],
            10 => [
                'Id' => 'SSB',
                'Name' => 'Airline Tariff 6D',
                'Description' => 'A US code agreed to by the airline '
                . 'industry to identify hazardous (dangerous) goods, '
                . 'managed by the Customs and Border Protection (CBP) agency.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk4b(): array
    {
        return [
            0 => [
                'Id' => 'SSC',
                'Name' => 'Title 49 Code of Federal Regulations',
                'Description' => 'A US Customs and Border Protection '
                . '(CBP) code used to identify hazardous (dangerous) goods.',
            ],
            1 => [
                'Id' => 'SSD',
                'Name' => 'International Civil Aviation Administration code',
                'Description' => 'A US Department of '
                . 'Transportation/Federal Aviation Administration code '
                . 'used to identify hazardous (dangerous) goods, '
                . 'managed by the Customs and Border Protection (CBP) agency.',
            ],
            2 => [
                'Id' => 'SSE',
                'Name' => 'Hazardous Materials ID DOT',
                'Description' => 'A US Department of Transportation (DOT)
                    code used toCustoms and Border
                  Protection (CBP) agency.',
            ],
            3 => [
                'Id' => 'SSF',
                'Name' => 'Endorsement',
                'Description' => 'A US Customs and Border Protection (CBP) '
                . 'code used to identify hazardous (dangerous) goods.',
            ],
            4 => [
                'Id' => 'SSG',
                'Name' => 'Air Force Regulation 71-4',
                'Description' => 'A department of Defense/Air Force code used to'
                 . 'identifyBorder Protection (CBP) agency.',
            ],
            5 => [
                'Id' => 'SSH',
                'Name' => 'Breed',
                'Description' => 'The breed of the item (e.g. plant or animal).',
            ],
            6 => [
                'Id' => 'SSI',
                'Name' => 'Chemical Abstract Service (CAS) registry number',
                'Description' => 'A unique numerical identifier for chemical '
                . 'compounds, polymers, biological sequences, '
                . 'mixtures and alloys.',
            ],
            7 => [
                'Id' => 'SSJ',
                'Name' => 'Engine model designation',
                'Description' => 'A name or designation to identify '
                . 'an engine model.',
            ],
            8 => [
                'Id' => 'SSK',
                'Name' => 'Institutional Meat Purchase '
                . 'Specifications (IMPS) Number',
                'Description' => 'A number assigned by agricultural '
                . 'authorities to identify and track meat and meat products.',
            ],
            9 => [
                'Id' => 'SSL',
                'Name' => 'Price Look-Up code (PLU)',
                'Description' => 'A number assigned by agricultural '
                . 'authorities to identify and track meat and meat products.',
            ],
            10 => [
                'Id' => 'SSM',
                'Name' => 'International Maritime Organization (IMO) Code',
                'Description' => 'An International Maritime Organization (IMO) '
                . 'code used to identify hazardous (dangerous) goods.',
            ],
            11 => [
                'Id' => 'SSN',
                'Name' => 'Bureau of Explosives 600-A (rail)',
                'Description' => 'A Department of Transportation/Federal '
                . 'Railroad Administration code used to '
                . 'identify hazardous (dangerous) goods.',
            ],
        ];
    }

    private function unc7143Chunk5(): array
    {
        return [
            0 => [
                'Id' => 'SSO',
                'Name' => 'United Nations Dangerous Goods List',
                'Description' => 'A UN code used to classify and '
                . 'identify dangerous goods.',
            ],
            1 => [
                'Id' => 'SSP',
                'Name' => 'International Code of Botanical Nomenclature (ICBN)',
                'Description' => 'A code established by the '
                . 'International Code of Botanical Nomenclature (ICBN) used '
                . 'to classify and identify botanical articles and commodities.',
            ],
            2 => [
                'Id' => 'SSQ',
                'Name' => 'International Code of Zoological Nomenclature (ICZN)',
                'Description' => 'A code established by the '
                . 'International Code of Zoological Nomenclature (ICZN) used '
                . 'to classify and identify animals.',
            ],
            3 => [
                'Id' => 'SSR',
                'Name' => 'International Code of Nomenclature '
                . 'for Cultivated Plants (ICNCP)',
                'Description' => 'A code established by the International '
                . 'Code of Nomenclature for Cultivated Plants (ICNCP) '
                . 'used to classify and identify animals.',
            ],
            4 => [
                'Id' => 'SSS',
                'Name' => 'Distributorâ€™s article identifier',
                'Description' => 'Identifier assigned to an article by the '
                . 'distributor of that article.',
            ],
            5 => [
                'Id' => 'SST',
                'Name' => 'Norwegian Classification system ENVA',
                'Description' => 'Product classification system used in the '
                . 'Norwegian market.',
            ],
            6 => [
                'Id' => 'SSU',
                'Name' => 'Supplier assigned classification',
                'Description' => 'Product classification assigned '
                . 'by the supplier.',
            ],
            7 => [
                'Id' => 'SSV',
                'Name' => 'Mexican classification system AMECE',
                'Description' => 'Product classification system used in '
                . 'the Mexican market.',
            ],
            8 => [
                'Id' => 'SSW',
                'Name' => 'German classification system CCG',
                'Description' => 'Product classification system used in '
                . 'the German market.',
            ],
            9 => [
                'Id' => 'SSX',
                'Name' => 'Finnish classification system EANFIN',
                'Description' => 'Product classification system used in '
                . 'the Finnish market.',
            ],
            10 => [
                'Id' => 'SSY',
                'Name' => 'Canadian classification system ICC',
                'Description' => 'Product classification system used in '
                . 'the Canadian market.',
            ],
            11 => [
                'Id' => 'SSZ',
                'Name' => 'French classification system IFLS5',
                'Description' => 'Product classification system used in '
                . 'the French market.',
            ],
            12 => [
                'Id' => 'ST',
                'Name' => 'Style number',
                'Description' => 'Number given to a specific style or '
                . 'form of an article, especially used for garments.',
            ],
            13 => [
                'Id' => 'STA',
                'Name' => 'Dutch classification system CBL',
                'Description' => 'Product classification system used in '
                . 'the Dutch market.',
            ],
            14 => [
                'Id' => 'STB',
                'Name' => 'Japanese classification system JICFS',
                'Description' => 'Product classification system used in '
                . 'the Japanese market.',
            ],
            15 => [
                'Id' => 'STC',
                'Name' => 'European Union dairy subsidy '
                . 'eligibility classification',
                'Description' => 'Category of product eligible for '
                . 'EU subsidy (applies for certain dairy products with '
                . 'specific level of fat content).',
            ],
            16 => [
                'Id' => 'STD',
                'Name' => 'GS1 Spain classification system',
                'Description' => 'Product classification system used in the '
                . 'Spanish market.',
            ],
            17 => [
                'Id' => 'STE',
                'Name' => 'GS1 Poland classification system',
                'Description' => 'Product classification system used '
                . 'in the Polish market.',
            ],
            18 => [
                'Id' => 'STF',
                'Name' => 'Federal Agency on Technical Regulating and '
                . 'Metrology of the Russian Federation',
                'Description' => 'A Russian government agency that serves '
                . 'as a national standardization body of the Russian Federation.',
            ],
            19 => [
                'Id' => 'STG',
                'Name' => 'Efficient Consumer Response (ECR) Austria '
                . 'classification system',
                'Description' => 'Product classification system used '
                . 'in the Austrian market.',
            ],
            20 => [
                'Id' => 'STH',
                'Name' => 'GS1 Italy classification system',
                'Description' => 'Product classification system used '
                . 'in the Italian market.',
            ],
            21 => [
                'Id' => 'STI',
                'Name' => 'CPV (Common Procurement Vocabulary)',
                'Description' => 'Official classification system for '
                . 'public procurement in the European Union.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk6(): array
    {
        return [
            0 => [
                'Id' => 'STJ',
                'Name' => 'IFDA (International Foodservice '
                . 'Distributors Association)',
                'Description' => 'International Foodservice '
                . 'Distributors Association (IFDA).',
            ],
            1 => [
                'Id' => 'STK',
                'Name' => 'AHFS (American Hospital Formulary Service) '
                . 'pharmacologic -therapeutic classification',
                'Description' => 'Pharmacologic -therapeutic classification '
                . 'maintained by the American Hospital Formulary Service (AHFS).',
            ],
            2 => [
                'Id' => 'STL',
                'Name' => 'ATC (Anatomical Therapeutic Chemical) '
                . 'classification system',
                'Description' => 'Anatomical Therapeutic Chemical '
                . 'classification system maintained by the '
                . 'World Health Organisation (WHO).',
            ],
            3 => [
                'Id' => 'STM',
                'Name' => 'CLADIMED (Classification des Dispositifs MÃ©dicaux)',
                'Description' => 'A five level classification '
                . 'system for medical decvices maintained by the CLADIMED '
                . 'organisation used in the French market.',
            ],
            4 => [
                'Id' => 'STN',
                'Name' => 'CMDR (Canadian Medical Device Regulations) '
                . 'classification system',
                'Description' => 'Classification system related to '
                . 'the Canadian Medical Device Regulations maintained '
                . 'by Health Canada.',
            ],
            5 => [
                'Id' => 'STO',
                'Name' => 'CNDM (Classificazione Nazionale dei Dispositivi Medici)',
                'Description' => 'A classification system for '
                . 'medical devices used in the Italian market.',
            ],
            6 => [
                'Id' => 'STP',
                'Name' => 'UK DM&D (Dictionary of Medicines & Devices) '
                . 'standard coding scheme',
                'Description' => 'A classification system '
                . 'for medicines and devices used in the UK market.',
            ],
            7 => [
                'Id' => 'STQ',
                'Name' => 'eCl@ss',
                'Description' => 'Standardized material and service '
                . 'classification and dictionary maintained by eClass e.V.',
            ],
            8 => [
                'Id' => 'STR',
                'Name' => 'EDMA (European Diagnostic Manufacturers Association) '
                . 'Product Classification',
                'Description' => 'Classification for in vitro diagnostics '
                . 'medical devices maintained by the European Diagnostic '
                . 'Manufacturers Association.',
            ],
            9 => [
                'Id' => 'STS',
                'Name' => 'EGAR (European Generic Article Register)',
                'Description' => 'A classification system for medical devices.',
            ],
            10 => [
                'Id' => 'STT',
                'Name' => 'GMDN (Global Medical Devices Nomenclature)',
                'Description' => 'Nomenclature system for identification '
                . 'of medical devices officially '
                . 'apprroved by the European Union.',
            ],
            11 => [
                'Id' => 'STU',
                'Name' => 'GPI (Generic Product Identifier)',
                'Description' => 'A drug classification system '
                . 'managed by Medi-Span.',
            ],
            12 => [
                'Id' => 'STV',
                'Name' => 'HCPCS (Healthcare Common Procedure Coding System)',
                'Description' => 'A classification system used with '
                . 'US healthcare insurance programs.',
            ],
            13 => [
                'Id' => 'STW',
                'Name' => 'ICPS (International Classification for Patient Safety)',
                'Description' => 'A patient safety taxonomy maintained '
                . 'by the World Health Organisation.',
            ],
            14 => [
                'Id' => 'STX',
                'Name' => 'MedDRA (Medical Dictionary for Regulatory Activities)',
                'Description' => 'A medical dictionary maintained '
                . 'by the International Federation of Pharmaceutical '
                . 'Manufacturers and Associations (IFPMA).',
            ],
            15 => [
                'Id' => 'STY',
                'Name' => 'Medical Columbus',
                'Description' => 'Medical product classification '
                . 'system used in the German market.',
            ],
            16 => [
                'Id' => 'STZ',
                'Name' => 'NAPCS (North American Product Classification System)',
                'Description' => 'Product classification system used '
                . 'in the North American market.',
            ],
            17 => [
                'Id' => 'SUA',
                'Name' => 'NHS (National Health Services) eClass',
                'Description' => 'Product and Service classification '
                . 'system used in United Kingdom market.',
            ],
            18 => [
                'Id' => 'SUB',
                'Name' => 'US FDA (Food and Drug Administration) Product Code '
                . 'Classification Database',
                'Description' => 'US FDA Product Code Classification '
                . 'Database contains medical device names and associated '
                . 'information developed by the Center for Devices and '
                . 'Radiological Health (CDRH).',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk7(): array
    {
        return [
            0 => [
                'Id' => 'SUC',
                'Name' => 'SNOMED CT (Systematized Nomenclature of '
                . 'Medicine-Clinical Terms)',
                'Description' => 'A medical nomenclature system developed '
                . 'between the NHS and the College of American Pathologists.',
            ],
            1 => [
                'Id' => 'SUD',
                'Name' => 'UMDNS (Universal Medical Device Nomenclature System)',
                'Description' => 'A standard international nomenclature '
                . 'and computer coding system for medical devices maintained '
                . 'by the Emergency Care Research Institute (ECRI).',
            ],
            2 => [
                'Id' => 'SUE',
                'Name' => 'GS1 Global Returnable Asset Identifier, '
                . 'non-serialised',
                'Description' => 'A unique, 13-digit number assigned '
                . 'according to the numbering structure of the GS1 system '
                . 'and used to identify a type of Reusable Transport Item (RTI).',
            ],
            3 => [
                'Id' => 'SUF',
                'Name' => 'IMEI',
                'Description' => 'The International Mobile Station '
                . 'Equipment Identity (IMEI) is a unique number to identify '
                . 'mobile phones. It includes the origin, model and serial '
                . 'number of the device. The structure is specified in '
                . '3GPP TS 23.003.',
            ],
            4 => [
                'Id' => 'SUG',
                'Name' => 'Waste Type (EMSA)',
                'Description' => 'Classification of waste as defined by '
                . 'the European Maritime Safety Agency (EMSA).',
            ],
            5 => [
                'Id' => 'SUH',
                'Name' => 'Ship\'s store classification type',
                'Description' => 'Classification of shipâ€™s stores.',
            ],
            6 => [
                'Id' => 'SUI',
                'Name' => 'Emergency fire code',
                'Description' => 'Classification for emergency response '
                . 'procedures related to fire.',
            ],
            7 => [
                'Id' => 'SUJ',
                'Name' => 'Emergency spillage code',
                'Description' => 'Classification for emergency response '
                . 'procedures related to spillage.',
            ],
            8 => [
                'Id' => 'SUK',
                'Name' => 'IMDG packing group',
                'Description' => 'Packing group as defined in the '
                . 'International Marititme Dangerous Goods (IMDG) specification.',
            ],
            9 => [
                'Id' => 'SUL',
                'Name' => 'MARPOL Code IBC',
                'Description' => 'International Bulk Chemical (IBC) '
                . 'code defined by the International Convention for the '
                . 'Prevention of Pollution from Ships (MARPOL).',
            ],
            10 => [
                'Id' => 'SUM',
                'Name' => 'IMDG subsidiary risk class',
                'Description' => 'Subsidiary risk class as defined in the '
                . 'International Maritime Dangerous Goods (IMDG) specification.',
            ],
            11 => [
                'Id' => 'TG',
                'Name' => 'Transport group number',
                'Description' => '(8012) Additional number to form article '
                . 'groups for packing and/or transportation purposes.',
            ],
            12 => [
                'Id' => 'TSN',
                'Name' => 'Taxonomic Serial Number',
                'Description' => 'A unique number assigned to a taxonomic '
                . 'entity, commonly to a species of plants or animals, '
                . 'providing information on their hierarchical classification, '
                . 'scientific name, taxonomic rank, associated synonyms and '
                . 'vernacular names where appropriate, data source information '
                . 'and data quality indicators.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk8(): array
    {
        return [
            0 => [
                'Id' => 'TSO',
                'Name' => 'IMDG main hazard class',
                'Description' => 'Main hazard class as defined in the '
                . 'International Maritime Dangerous Goods (IMDG) specification.',
            ],
            1 => [
                'Id' => 'TSP',
                'Name' => 'EU Combined Nomenclature',
                'Description' => 'The number is part of, or is generated '
                . 'in the context of the Combined Nomenclature classification, '
                . 'as developed and maintained by the European Union (EU).',
            ],
            2 => [
                'Id' => 'TSQ',
                'Name' => 'Therapeutic classification number',
                'Description' => 'A code to specify a product\'s therapeutic '
                . 'classification.',
            ],
            3 => [
                'Id' => 'TSR',
                'Name' => 'European Waste Catalogue',
                'Description' => 'Waste type number according to the European '
                . 'Waste Catalogue (EWC).',
            ],
            4 => [
                'Id' => 'TSS',
                'Name' => 'Price grouping code',
                'Description' => 'Number assigned to identify a grouping of '
                . 'products based on price.',
            ],
            5 => [
                'Id' => 'TST',
                'Name' => 'UNSPSC',
                'Description' => 'The UNSPSC commodity classification system.',
            ],
            6 => [
                'Id' => 'TSU',
                'Name' => 'EU RoHS Directive',
                'Description' => 'European Union Directive on the '
                . 'restriction of hazardous substances.',
            ],
            7 => [
                'Id' => 'UA',
                'Name' => 'Ultimate customer\'s article number',
                'Description' => 'Number assigned by ultimate customer to '
                . 'identify relevant article.',
            ],
            8 => [
                'Id' => 'UP',
                'Name' => 'UPC (Universal product code)',
                'Description' => 'Number assigned to a manufacturer\'s '
                . 'product by the Product Code Council.',
            ],
            9 => [
                'Id' => 'VN',
                'Name' => 'Vendor item number',
                'Description' => 'Reference number assigned by a '
                . 'vendor/seller identifying',
            ],
            10 => [
                'Id' => 'VP',
                'Name' => 'Vendor\'s (seller\'s) part number',
                'Description' => 'Reference number assigned by a '
                . 'vendor/seller identifying a product/service/article.',
            ],
            11 => [
                'Id' => 'VS',
                'Name' => 'Vendor\'s supplemental item number',
                'Description' => 'The item number is a specified by the '
                . 'vendor as a supplemental number for the vendor\'s purposes.',
            ],
            12 => [
                'Id' => 'VX',
                'Name' => 'Vendor specification number',
                'Description' => 'The item number has been allocated by the '
                . 'vendor as a specification number.',
            ],
            13 => [
                'Id' => 'ZZZ',
                'Name' => 'Mutually defined',
                'Description' => 'Item type identification mutually agreed '
                . ' between interchanging parties.',
            ],
        ];
    }
}
