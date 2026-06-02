<?php

declare(strict_types=1);

/**
 * What?  The complete list of Electronic Address Identifier (EAI) scheme IDs registered
 *        in the Peppol network, e.g. '0088' (GS1 GLN), '0192' (Norwegian organisation
 *        number), '9914' (Austrian company UID), '9930' (Swedish VAT number).
 * Why?   The schemeID attribute on cbc:EndpointID must be a recognised Peppol
 *        participant-addressing scheme; any unknown value triggers rule PEPPOL-CL-0008.
 * When?  Loaded once per process by CodeList::load(CodeLists::EAID) the first time an
 *        endpoint scheme ID is validated; the result is held in CodeList's static cache.
 * Where? Consumed inside PeppolValidator::validateEndpointSchemeIDs() via
 *        CodeList::contains() against every //cbc:EndpointID[@schemeID] in the XML.
 * How?   Returns a flat PHP array of strings sorted in ascending numeric order. PHP
 *        opcache compiles this file to bytecode so no parsing overhead is incurred.
 *
 * Quarterly update: compare against the official Peppol participant identifier scheme
 * list published at https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/
 */
return [
    '0002', '0007', '0009', '0037', '0060', '0088', '0096', '0097', '0106', '0130',
    '0135', '0142', '0147', '0151', '0154', '0158', '0170', '0177', '0183', '0184',
    '0188', '0190', '0191', '0192', '0193', '0194', '0195', '0196', '0198', '0199',
    '0200', '0201', '0202', '0203', '0204', '0205', '0208', '0209', '0210', '0211',
    '0212', '0213', '0215', '0216', '0217', '0218', '0221', '0225', '0230', '0235',
    '0240', '9910', '9913', '9914', '9915', '9918', '9919', '9920', '9922', '9923',
    '9924', '9925', '9926', '9927', '9928', '9929', '9930', '9931', '9932', '9933',
    '9934', '9935', '9936', '9937', '9938', '9939', '9940', '9941', '9942', '9943',
    '9944', '9945', '9946', '9947', '9948', '9949', '9950', '9951', '9952', '9953',
    '9957', '9959',
];
