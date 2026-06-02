<?php

declare(strict_types=1);

/**
 * What?  The short list of MIME types that Peppol permits for embedded binary attachments:
 *        PDF documents, PNG and JPEG images, CSV spreadsheets, Excel (XLSX), and ODS files.
 * Why?   Any attachment included in a Peppol invoice must use one of these approved formats;
 *        any other MIME type on the mimeCode attribute triggers validation rule BR-CL-24.
 * When?  Loaded once per process by CodeList::load(CodeLists::MIME) the first time an
 *        attachment MIME type is checked; the result is held in CodeList's static cache.
 * Where? Consumed inside PeppolValidator::validateMimeCodeList() via CodeList::contains()
 *        against the mimeCode attribute of every
 *        //cac:Attachment/cbc:EmbeddedDocumentBinaryObject element in the XML.
 * How?   Returns a flat PHP array of strings. PHP opcache compiles this file to bytecode
 *        so no parsing overhead is incurred on subsequent requests.
 *
 * Quarterly update: compare against the official Peppol MIME code list at
 * https://docs.peppol.eu/poacc/billing/3.0/codelist/MimeCode/
 */
return [
    'application/pdf',
    'application/vnd.oasis.opendocument.spreadsheet',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
    'text/csv',
];
