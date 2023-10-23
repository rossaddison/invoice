<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\UserInv;
use App\Invoice\Entity\Sumex as EntitySumex;
use App\Invoice\Helpers\InvoiceHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as sR;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Error;
use Yiisoft\Session\SessionInterface as Session;

use DOMDocument;
use DOMElement;

class Sumex
{
    const ROLES = [
        'physician',
        'physiotherapist',
        'chiropractor',
        'ergotherapist',
        'nutritionist',
        'midwife',
        'logotherapist',
        'hospital',
        'pharmacist',
        'dentist',
        'labtechnician',
        'dentaltechnician',
        'othertechnician',
        'psychologist',
        'wholesaler',
        'nursingstaff',
        'transport',
        'druggist',
        'naturopathicdoctor',
        'naturopathictherapist',
        'other'];
    const PLACES = [
        'practice',
        'hospital',
        'lab',
        'association',
        'company'
    ];
    const CANTONS = [
        "AG",
        "AI",
        "AR",
        "BE",
        "BL",
        "BS",
        "FR",
        "GE",
        "GL",
        "GR",
        "JU",
        "LU",
        "NE",
        "NW",
        "OW",
        "SG",
        "SH",
        "SO",
        "SZ",
        "TI",
        "TG",
        "UR",
        "VD",
        "VS",
        "ZG",
        "ZH",
        "LI",
        "A",
        "D",
        "F",
        "I"
    ];
    public Inv $invoice;
    public string $_lang = "it";
    public string $_mode = "production";
    public string $_copy = "0";
    public string $_storno = "0";
    public string $_role = "physiotherapist";
    public string $_place = "practice";
    public string $_currency = "CHF";
    public string $_paymentperiod = "P30D";
    public string $_canton = "TI";
    public string $_esrType = "9";

    public array $_patient = [
        'gender' => 'male',
        'birthdate' => '1970-01-01',
        'familyName' => 'FamilyName',
        'givenName' => 'GivenName',
        'street' => 'ClientStreet 10',
        'zip' => '0000',
        'city' => 'ClientCity',
        'phone' => '000 000 00 00',
        'avs' => '7000000000000'
    ];

    public string $_casedate = "1970-01-01";
    public string $_casenumber = "0";
    public string $_insuredid = '1234567';

    public array $_treatment = [
        'start' => '',
        'end' => '',
        'reason' => 'disease',
        'diagnosis' => '.'
    ];

    public array $_company = [
        'name' => 'SomeCompany GmbH',
        'street' => 'Via Cantonale 5',
        'zip' => '6900',
        'city' => 'Lugano',
        'phone' => '091 902 11 00',
        'gln' => '123456789123', // EAN 13
        'rcc' => 'C000002'
    ];

    public array $_insurance = [ 
        'gln' => '7634567890000',
        'name' => 'SUVA',
        'street' => 'ChangeMe 12',
        'zip' => '6900',
        'city' => 'Lugano'
    ];

    public array $_options = [
        'copy' => "0",
        'storno' => "0"
    ];
    
    public sR $s;
    public ArrayCollection $items;
    public InvAmount $invoice_amount;
    public InvoiceHelper $invoice_helper;
    public UserInv $user_details;
    public EntitySumex $sumex_treatment;
    public DateHelper $date_helper;
    
    public function __construct(
        Inv $inv,              
        InvAmount $inv_amount,
        UserInv $user_details,
        EntitySumex $sumex_treatment,
        sR $s, 
        Session $session,
    )
    {
        $this->invoice_helper = new InvoiceHelper($s, $session);
        $this->date_helper = new DateHelper($s);
        $this->invoice = $inv;
        $this->invoice_amount = $inv_amount;
        $this->user_details = $user_details;
        $this->sumex_treatment = $sumex_treatment;
        $this->items = $inv->getItems();
        
        $this->s = $s;
        /** @var string $this->_options['storno'] */
        $this->_storno = $this->_options['storno'];
        /** @var string $this->_options['copy'] */
        $this->_copy = $this->_options['copy'];

        $this->_patient['givenName'] = $this->invoice->getClient()?->getClient_name();
        $this->_patient['familyName'] = $this->invoice->getClient()?->getClient_surname();
        $this->_patient['birthdate'] = $this->invoice->getClient()?->getClient_birthdate();
        $this->_patient['gender'] = ($this->invoice->getClient()?->getClient_gender() == "0" ? "male" : "female");
        $this->_patient['street'] = $this->invoice->getClient()?->getClient_address_1();
        $this->_patient['zip'] = $this->invoice->getClient()?->getClient_zip();
        $this->_patient['city'] = $this->invoice->getClient()?->getClient_city();
        $this->_patient['phone'] = ($this->invoice->getClient()?->getClient_phone() == "" ? null : $this->invoice->getClient()?->getClient_phone());
        $this->_patient['avs'] = $this->invoice->getClient()?->getClient_avs();
                
        $this->_company['name'] = $this->user_details->getName();
        
        $this->_company['street'] = $this->user_details->getAddress_1();
        
        $this->_company['zip'] = $this->user_details->getZip();
        
        $this->_company['city'] = $this->user_details->getCity();
        
        $this->_company['phone'] = $this->user_details->getPhone();
        
        $this->_company['gln'] = $this->user_details->getGln();
        
        $this->_company['rcc'] = $this->user_details->getRcc();
        /** @var DateTimeImmutable $this->sumex_treatment->getCasedate()->format($this->date_helper->style()) */
        $this->_casedate = $this->sumex_treatment->getCasedate()->format($this->date_helper->style());
        /** @var string $this->sumex_treatment->getCasenumber() */
        $this->_casenumber = $this->sumex_treatment->getCasenumber() ?: 'No Case Number';
        /** @var string $this->invoice->getClient()?->getClient_insurednumber() */
        $this->_insuredid = $this->invoice->getClient()?->getClient_insurednumber() ?: 'Not Available';

        $treatments = [
            'disease',
            'accident',
            'maternity',
            'prevention',
            'birthdefect',
            'unknown'
        ];

        $this->_treatment = [
            /** @var DateTimeImmutable $this->sumex_treatment->getTreatmentstart() */
            'start' => $this->sumex_treatment->getTreatmentstart(),
            /** @var DateTimeImmutable $this->sumex_treatment->getTreatmentend() */
            'end' => $this->sumex_treatment->getTreatmentend(),
            /** @var string $this->sumex_treatment->getReason() */
            'reason' => $treatments[(int)$this->sumex_treatment->getReason() ?: 1],
            /** @var string $this->sumex_treatment->getDiagnosis() */
            'diagnosis' => $this->sumex_treatment->getDiagnosis(),
            /** @var string $this->sumex_treatment->getObservations() */ 
            'observations' => $this->sumex_treatment->getObservations(),
        ];

        $esrTypes = ["9", "red"];
        $this->_esrType = $esrTypes[(int)$this->s->get_setting('sumex_sliptype')];
        $this->_currency = $this->s->get_setting('currency_code');
        $this->_role = self::ROLES[(int)$this->s->get_setting('sumex_role')];
        $this->_place = self::PLACES[(int)$this->s->get_setting('sumex_place')];
        $this->_canton = self::CANTONS[(int)$this->s->get_setting('sumex_canton')];
    }
    
    /**
     * This function can be developed later.
     * 
     * inv/generate_sumex_pdf   ...... calls 
     * pdf_helper->generate_inv_sumex ......initiates 
     * $sumexPDF = $sumex->pdf() .... this function
     * 
     * @param int $inv_id
     * 
     * @return string|bool
     */
    public function pdf(int $inv_id) : string|bool
    {
        // TODO
        return ($inv_id ? true : false);
    }

    /**
     * @return string
     */
    public function xml() : string
    {
        /** @var DOMDocument $doc */
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $this->xmlRoot($doc);
        $root->appendChild($this->xmlInvoiceProcessing($doc));
        $root->appendChild($this->xmlInvoicePayload($doc));

        $doc->appendChild($root);
        return $doc->saveXML();
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    public function xmlRoot(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:request');
        $node->setAttribute('xmlns:invoice', 'http://www.forum-datenaustausch.ch/invoice');
        $node->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $node->setAttribute('xmlns:nsxenc', 'http://www.w3.org/2001/04/xmlenc#');
        $node->setAttribute('xsi:schemaLocation', 'http://www.forum-datenaustausch.ch/invoice generalInvoiceRequest_440.xsd');
        $node->setAttribute('modus', $this->_mode);
        $node->setAttribute('language', $this->_lang);
        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceProcessing(DOMDocument $doc) : DOMElement
    {
        // TODO: CHECK!
        // Only to pass XML validation. This DOES NOT represent a valid TARMED file.
        $node = $doc->createElement('invoice:processing');
        $node->setAttribute('print_at_intermediate', 'false');
        $node->setAttribute('print_patient_copy', 'true');

        $transport = $doc->createElement('invoice:transport');
        $transport->setAttribute('from', (string)$this->_company['gln']);
        $transport->setAttribute('to', '7601003000078'); # Example: SUVA

        $via = $doc->createElement('invoice:via');
        $via->setAttribute('via', '7601003000078'); # Example: SUVA
        $via->setAttribute('sequence_id', '1');

        $transport->appendChild($via);

        $node->appendChild($transport);
        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoicePayload(DOMDocument $doc) : DOMElement
    {
        $date_helper = new DateHelper($this->s);
        
        $node = $doc->createElement('invoice:payload');
        $node->setAttribute('type', 'invoice');
        $node->setAttribute('copy', $this->_copy);
        $node->setAttribute('storno', $this->_storno);

        $invoiceInvoice = $doc->createElement('invoice:invoice');
        $invoiceInvoice->setAttribute('request_timestamp', (string)time());
        $invoiceInvoice->setAttribute('request_id', (string)$this->invoice->getNumber());
        $invoiceInvoice->setAttribute('request_date', date("Y-m-d\TH:i:s", strtotime(($this->invoice->getDate_modified())->format($date_helper->style()))));

        $invoiceBody = $this->xmlInvoiceBody($doc);

        $node->appendChild($invoiceInvoice);
        $node->appendChild($invoiceBody);

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceBody(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:body');
        $node->setAttribute('role', $this->_role);
        $node->setAttribute('place', $this->_place);

        if ($this->_esrType == "9") {
            $esr = $this->xmlInvoiceEsr9($doc);
        } else {
            // Red
            $esr = $this->xmlInvoiceEsrRed($doc);
        }

        $prolog = $this->xmlInvoiceProlog($doc);
        $remark = $this->xmlInvoiceRemark($doc);
        $balance = $this->xmlInvoiceBalance($doc);
        $tiersGarant = $this->xmlInvoiceTiersGarant($doc);
        //$tiersPayant = $this->xmlInvoiceTiersPayant();
        //$mvg = $this->xmlInvoiceMvg();
        $org = $this->xmlInvoiceOrg($doc);
        $treatment = $this->xmlInvoiceTreatment($doc);
        $services = $this->xmlServices($doc);

        $node->appendChild($prolog);
        /** @var string $this->_treatment['observations'] */
        if ($this->_treatment['observations'] != "") {
            $node->appendChild($remark);
        }
        $node->appendChild($balance);
        $node->appendChild($esr);
        $node->appendChild($tiersGarant);
        //$node->appendChild($tiersPayant);
        $node->appendChild($org);
        $node->appendChild($treatment);
        $node->appendChild($services);

        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     * @throws Error
     */
    protected function xmlInvoiceEsr9(DOMDocument $doc) : DOMElement 
    {
        $date_helper = new DateHelper($this->s);
        $node = $doc->createElement('invoice:esr9');            
        
        $subNumb = $this->user_details->getSubscribernumber();

        $node->setAttribute('participant_number', $subNumb ?: 'No Participating Number'); // MUST begin with 01
        $node->setAttribute('type', '16or27'); // 16or27 = 01, 16or27plus = 04

        // 26numbers + 1 chek
        $referenceNumber = "";

        // Custom style, we create the reference number as following:
        // 5 digits for client id, 10 digits for invoice ID, 9 digits for Invoice Date, 1 for checksum

        $referenceNumber .= "06"; // Dog Fooding
        $referenceNumber .= sprintf("%05d", ($this->invoice->getClient()?->getClient_id() ?: 'No Client Id'));
        $referenceNumber .= sprintf("%010d", $this->invoice->getId() ?: 'No Invoice Id provided');
        $referenceNumber .= sprintf("%09d", date("Ymd", strtotime(($this->invoice->getDate_modified())->format($date_helper->style()))));
        $refCsum = $this->invoice_helper->invoice_recMod10($referenceNumber);
        $referenceNumber = $referenceNumber . $refCsum;

        if (!preg_match("/\d{27}/", $referenceNumber)) {
            throw new Error("Invalid reference number!");
        }

        $slipType = "01"; // ISR in CHF
        
        $amount = $this->invoice_amount->getTotal();

        $formattedRN = "";
        $formattedRN .= substr($referenceNumber, 0, 2);
        $formattedRN .= " ";
        $formattedRN .= substr($referenceNumber, 2, 5);
        $formattedRN .= " ";
        $formattedRN .= substr($referenceNumber, 7, 5);
        $formattedRN .= " ";
        $formattedRN .= substr($referenceNumber, 12, 5);
        $formattedRN .= " ";
        $formattedRN .= substr($referenceNumber, 17, 5);
        $formattedRN .= " ";
        $formattedRN .= substr($referenceNumber, 22, 5);

        $codingLine =  $this->invoice_helper->invoice_genCodeline($slipType, $amount, $formattedRN, $subNumb);

        $node->setAttribute('reference_number', $formattedRN);
        $node->setAttribute('coding_line', $codingLine);

        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceEsrRed(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:esrRed');

        $reason = $doc->createElement('invoice:payment_reason');
        $reason->nodeValue = $this->invoice->getNumber();

        $subNumb = $this->user_details->getSubscribernumber();
        // postal_account: coding_line2
        // bank_account: coding_line1 + coding_line2
        // Assume always postal: This should be have an option in the future
        $node->setAttribute('payment_to', 'postal_account');
        $node->setAttribute('post_account', (string)$subNumb);


        // IBAN not required
        //$node->setAttribute('iban', 'CH1111111111111111111');
        $node->setAttribute('reference_number', '1112111111');
        $node->setAttribute('coding_line1', '111111111111111111111111111+ 071234567>');
        $node->setAttribute('coding_line2', str_replace('-', '', (string)$subNumb) . '>');

        $node->appendChild($reason);

        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceProlog(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:prolog');

        $package = $doc->createElement('invoice:package');
        $package->setAttribute('version', '150');
        $package->setAttribute('name', 'Yii3-i');

        $generator = $doc->createElement('invoice:generator');
        $generator->setAttribute('name', 'PHP_Sumex');
        $generator->setAttribute('version', '100');

        $node->appendChild($package);
        $node->appendChild($generator);

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceRemark(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:remark');
        $node->nodeValue = (string)$this->_treatment['observations'];
        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceBalance(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:balance');
        $node->setAttribute('currency', $this->_currency);

        $node->setAttribute('amount', (string)($this->invoice_amount->getTotal() ?: 0.00));
        $node->setAttribute('amount_due', (string)($this->invoice_amount->getBalance() ?: 0.00));
        // TODO: Check amount_obligations
        $node->setAttribute('amount_obligations', (string)($this->invoice_amount->getTotal() ?: 0.00));

        $vat = $doc->createElement('invoice:vat');
        $vat->setAttribute('vat', (string)($this->invoice_amount->getTax_total() ?: 0.00));

        $vatRate = $doc->createElement('invoice:vat_rate');
        /** @var InvAmount $this->inv_amount */
        $vatRate->setAttribute('vat_rate', (string)(($this->invoice_amount->getTax_total() ?: 0.00)/(($this->inv_amount->getTotal() ?: 0.00)*100)));
        $vatRate->setAttribute('amount', (string)($this->invoice_amount->getTax_total() ?: 0.00));
        $vatRate->setAttribute('vat', (string)($this->invoice_amount->getTax_total() ?: 0.00));

        $vat->appendChild($vatRate);
        $node->appendChild($vat);

        return $node;
    }
    
    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceTiersGarant(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:tiers_garant');
        $node->setAttribute('payment_period', $this->_paymentperiod);

        $biller = $doc->createElement('invoice:biller');
        $provider = $doc->createElement('invoice:provider');
        $patient = $doc->createElement('invoice:patient');
        $guarantor = $doc->createElement('guarantor');

        // <invoice:biller>
        // TODO: Check ean_party, zsr, specialty
        /** @var string $this->_company['gln'] */
        $biller->setAttribute('ean_party', $this->_company['gln']);
        /** @var string $this->_company['rcc'] */
        $biller->setAttribute('zsr', $this->_company['rcc']); // Zahlstellenregister-Nummer (RCC)
        //$biller->setAttribute('specialty', 'unknown');

        $bcompany = $this->xmlCompany($doc);
        $biller->appendChild($bcompany);
        // </invoice:biller>

        // <invoice:provider>
        // TODO: Check if **always** same as biller
        // TODO: Check ean_party, zsr, speciality
        /** @var string $this->_company['gln'] */
        $provider->setAttribute('ean_party', $this->_company['gln']);
        /** @var string $this->_company['rcc'] */
        $provider->setAttribute('zsr', $this->_company['rcc']); // Zahlstellenregister-Nummer (RCC)
        //$provider->setAttribute('specialty', 'Allgemein');

        $pcompany = $this->xmlCompany($doc);
        $provider->appendChild($pcompany);
        // </invoice:provider>

        // <invoice:patient>
        $patient->setAttribute('gender', (string)$this->_patient['gender']);
        $patient->setAttribute('birthdate', date("Y-m-d\TH:i:s", strtotime((string)$this->_patient['birthdate'])));

        $person_is_patient = $this->generatePerson($doc, (string)$this->_patient['street'], (string)$this->_patient['zip'], (string)$this->_patient['city'], (string)$this->_patient['phone']);
        $patient->appendChild($person_is_patient);
        // </invoice:patient>

        // <invoice:guarantor>
        $guarantor->setAttribute('xmlns', 'http://www.forum-datenaustausch.ch/invoice');
        $person_is_guarantor = $this->generatePerson($doc, (string)$this->_patient['street'], (string)$this->_patient['zip'], (string)$this->_patient['city'], (string)$this->_patient['phone']);
        $guarantor->appendChild($person_is_guarantor);
        // </invoice:guarantor>

        $node->appendChild($biller);
        $node->appendChild($provider);
        $node->appendChild($patient);
        $node->appendChild($guarantor);

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlCompany(DOMDocument $doc) : DOMElement
    {
        // <invoice:company>
        $bcompany = $doc->createElement('invoice:company');
        $bcompany_name = $doc->createElement('invoice:companyname');
        $bcompany_name->nodeValue = (string)$this->_company['name'];

        $bcompany->appendChild($bcompany_name);

        $bcompany_postal = $this->generatePostal($doc, (string)$this->_company['street'], (string)$this->_company['zip'], (string)$this->_company['city']);
        $bcompany->appendChild($bcompany_postal);

        $bcompany_telecom = $doc->createElement('invoice:telecom');
        $bcompany_telecom_phone = $doc->createElement('invoice:phone');
        $bcompany_telecom_phone->nodeValue = (string)$this->_company['phone'];

        $bcompany_telecom->appendChild($bcompany_telecom_phone);
        $bcompany->appendChild($bcompany_telecom);
        // </invoice:company>

        return $bcompany;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param string $street
     * @param string $zip
     * @param string $city
     * @return DOMElement
     */
    protected function generatePostal(DOMDocument $doc, string $street, string $zip, string $city) : DOMElement
    {
        $postal = $doc->createElement('invoice:postal');

        $postal_street = $doc->createElement('invoice:street');
        $postal_street->nodeValue = $street;

        $postal_zip = $doc->createELement('invoice:zip');
        $postal_zip->nodeValue = $zip;

        $postal_city = $doc->createElement('invoice:city');
        $postal_city->nodeValue = $city;

        $postal->appendChild($postal_street);
        $postal->appendChild($postal_zip);
        $postal->appendChild($postal_city);

        return $postal;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param string $street
     * @param string $zip
     * @param string $city
     * @param string $phone
     * @return DOMElement
     */
    protected function generatePerson(DOMDocument $doc, string $street, string $zip, string $city, string $phone) : DOMElement
    {
        $person = $doc->createElement('invoice:person');

        $familyName = $doc->createElement('invoice:familyname');
        /** @var string $this->_patient['familyName'] */
        $familyName->nodeValue = $this->_patient['familyName'];
        
        $givenName = $doc->createElement('invoice:givenname');
        /** @var string $this->_patient['givenName'] */
        $givenName->nodeValue = $this->_patient['givenName'];

        $postal = $this->generatePostal($doc, $street, $zip, $city);

        if ($phone != null) {
            $telecom = $this->generateTelecom($doc, $phone);
        } else {
            $telecom = null;
        }
        
        $person->appendChild($familyName);
        $person->appendChild($givenName);
        $person->appendChild($postal);
        if ($telecom != null) {
            $person->appendChild($telecom);
        }

        return $person;
    }
    
    /**
     * @param DOMDocument $doc
     * @param string $phoneNr
     * @return DOMElement
     */
    protected function generateTelecom(DOMDocument $doc, string $phoneNr) : DOMElement
    {
        $telecom = $doc->createElement('invoice:telecom');
        $phone = $doc->createElement('invoice:phone');
        $phone->nodeValue = $phoneNr;
        $telecom->appendChild($phone);
        return $telecom;
    }
    
    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceOrg(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:org');
        $node->setAttribute('case_date', date("Y-m-d\TH:i:s", strtotime($this->_casedate)));
        if ($this->_casenumber != "") {
            $node->setAttribute('case_id', $this->_casenumber);
        }

        if ($this->_insuredid != "") {
            $node->setAttribute('insured_id', $this->_insuredid);
        }

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceTreatment(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:treatment');
        $node->setAttribute('date_begin', date("Y-m-d\TH:i:s", strtotime((string)$this->_treatment['start'])));
        $node->setAttribute('date_end', date("Y-m-d\TH:i:s", strtotime((string)$this->_treatment['end'])));
        $node->setAttribute('canton', $this->_canton);
        $node->setAttribute('reason', (string)$this->_treatment['reason']);

        if ($this->_treatment['diagnosis'] != "") {
            $diag = $doc->createElement('invoice:diagnosis');
            $diag->setAttribute('type', 'freetext');
            //$diag->setAttribute('code', );
            $diag->nodeValue = (string)$this->_treatment['diagnosis'];
            $node->appendChild($diag);
        }

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlServices(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('services');
        $node->setAttribute('xmlns', 'http://www.forum-datenaustausch.ch/invoice');

        $recordId = 1;
        /** @var InvItem $item */
        foreach ($this->items as $item) {
            $node->appendChild($this->generateRecord($doc, $recordId, $item));
            $recordId++;
        }
        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param int $recordId
     * @param InvItem $item
     * @return DOMElement
     */
    protected function generateRecord(DOMDocument $doc, int $recordId, InvItem $item) : DOMElement
    {
        $date_helper = new DateHelper($this->s);
        $node = $doc->createElement('invoice:record_other');
        $node->setAttribute('record_id', (string)$recordId);
        $node->setAttribute('tariff_type', (string)590);
        $node->setAttribute('code', ($item->getProduct()?->getProduct_sku() ?: 'Not available'));
        $node->setAttribute('session', (string)1);
        $node->setAttribute('quantity', (string)($item->getQuantity() ?: 0.00));
        $node->setAttribute('date_begin', date("Y-m-d\TH:i:s", strtotime(($item->getDate())->format($date_helper->style()))));
        $node->setAttribute('provider_id', (string)$this->_company['gln']);
        $node->setAttribute('responsible_id', (string)$this->_company['gln']);
        $node->setAttribute('unit', (string)$item->getPrice());
        #$node->setAttribute('unit_factor', 1);
        $node->setAttribute('amount', (string)(($item->getPrice() ?: 0.00)*($item->getQuantity() ?: 0.00)));
        #$node->setAttribute('validate', 0);
        #$node->setAttribute('service_attributes', 0);
        #$node->setAttribute('obligation', 0);
        $node->setAttribute('name', ($item->getName() ?: 'Not Available'));
        return $node;
    }
    
    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceTiersPayant(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:tiers_payant');
        $node->setAttribute('payment_period', $this->_paymentperiod);

        $biller = $doc->createElement('invoice:biller');
        $provider = $doc->createElement('invoice:provider');
        $insurance = $doc->createElement('invoice:insurance');
        $patient = $doc->createElement('invoice:patient');
        $insured = $doc->createElement('invoice:insured');
        $guarantor = $doc->createElement('invoice:guarantor');

        // <invoice:biller>
        // TODO: Check ean_party, zsr, specialty
        $biller->setAttribute('ean_party', (string)$this->_company['gln']);
        $biller->setAttribute('zsr', (string)$this->_company['rcc']); // Zahlstellenregister-Nummer (RCC)
        //$biller->setAttribute('specialty', 'unknown');

        $bcompany = $this->xmlCompany($doc);
        $biller->appendChild($bcompany);
        // </invoice:biller>

        // <invoice:provider>
        // TODO: Check if **always** same as biller
        // TODO: Check ean_party, zsr, speciality
        $provider->setAttribute('ean_party', (string)$this->_company['gln']);
        $provider->setAttribute('zsr', (string)$this->_company['rcc']); // Zahlstellenregister-Nummer (RCC)
        //$provider->setAttribute('specialty', 'Allgemein');

        $pcompany = $this->xmlCompany($doc);
        $provider->appendChild($pcompany);
        // </invoice:provider>

        // <invoice:insurance>
        $insurance->setAttribute('ean_party', (string)$this->_insurance['gln']);
        $insuranceCompany = $this->xmlInsurance($doc);
        $insurance->appendChild($insuranceCompany);
        // </invoice:insurance>

        // <invoice:patient>
        $patient->setAttribute('gender', (string)$this->_patient['gender']);
        $patient->setAttribute('birthdate', date("Y-m-d\TH:i:s", strtotime((string)$this->_patient['birthdate'])));

        $person_is_patient = $this->generatePerson($doc, (string)$this->_patient['street'], (string)$this->_patient['zip'], (string)$this->_patient['city'], (string)$this->_patient['phone']);
        $patient->appendChild($person_is_patient);
        // </invoice:patient>

        // <invoice:insured>
        $insured->setAttribute('gender', (string)$this->_patient['gender']);
        $insured->setAttribute('birthdate', date("Y-m-d\TH:i:s", strtotime((string)$this->_patient['birthdate'])));

        $person_is_insured = $this->generatePerson($doc, (string)$this->_patient['street'], (string)$this->_patient['zip'], (string)$this->_patient['city'], (string)$this->_patient['phone']);
        $insured->appendChild($person_is_insured);
        // </invoice:insured>

        // <invoice:guarantor>
        $guarantor->setAttribute('xmlns', 'http://www.forum-datenaustausch.ch/invoice');
        $person_is_guarantor = $this->generatePerson($doc, (string)$this->_patient['street'], (string)$this->_patient['zip'], (string)$this->_patient['city'], (string)$this->_patient['phone']);
        $guarantor->appendChild($person_is_guarantor);
        // </invoice:guarantor>

        $node->appendChild($biller);
        $node->appendChild($provider);
        $node->appendChild($insurance);
        $node->appendChild($patient);
        //$node->appendChild($insured);
        $node->appendChild($guarantor);

        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInsurance(DOMDocument $doc) : DOMElement
    {
        // <invoice:company>
        $bcompany = $doc->createElement('invoice:company');
        $bcompany_name = $doc->createElement('invoice:companyname');
        $bcompany_name->nodeValue = (string)$this->_insurance['name'];

        $bcompany->appendChild($bcompany_name);

        $bcompany_postal = $this->generatePostal($doc,
                                (string)$this->_insurance['street'], 
                                (string)$this->_insurance['zip'], 
                                (string)$this->_insurance['city']
                            );
        $bcompany->appendChild($bcompany_postal);

        /*$bcompany_telecom = $this->doc->createElement('invoice:telecom');
        $bcompany_telecom_phone = $this->doc->createElement('invoice:phone');
        $bcompany_telecom_phone->nodeValue = $this->_company['phone'];
        $bcompany_telecom->appendChild($bcompany_telecom_phone);
        $bcompany->appendChild($bcompany_telecom);*/

        // </invoice:company>

        return $bcompany;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlInvoiceMvg(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('invoice:mvg');
        $node->setAttribute('ssn', (string)$this->_patient['avs']);
        #$node->setAttribute('insured_id', '1234');
        $node->setAttribute('case_date', date("Y-m-d\TH:i:s", strtotime($this->_casedate)));

        return $node;
    }
}
