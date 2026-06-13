<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * AS4 Constants aligned with eDelivery AS4 2.0 specification
 *
 * Implements Common Profile and Profile Enhancements per:
 * https://ec.europa.eu/digital-building-blocks/sites/spaces/DIGITAL/pages/845480153
 */
class As4Constants
{
    // Namespace URIs — these are frozen specification identifiers, not live URLs
    public const string SOAP_NS = 'http://www.w3.org/2003/05/soap-envelope'; // NOSONAR
    public const string SOAP_ENCODING_NS =
        'http://schemas.xmlsoap.org/soap/encoding/'; // NOSONAR
    public const string EBMS3_NS =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/'; // NOSONAR
    public const string WSS_NS =
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'; // NOSONAR
    public const string WSS11_NS =
        'http://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd'; // NOSONAR
    public const string WSS_UTIL_NS =
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd'; // NOSONAR
    public const string XMLDSIG_NS =
        'http://www.w3.org/2000/09/xmldsig#'; // NOSONAR
    public const string XMLDSIG_MORE_NS =
        'http://www.w3.org/2021/04/xmldsig-more#'; // NOSONAR
    public const string XMLDSIG11_NS =
        'http://www.w3.org/2009/xmldsig11#'; // NOSONAR
    public const string XMLENC_NS = 'http://www.w3.org/2001/04/xmlenc#'; // NOSONAR
    public const string XMLENC11_NS = 'http://www.w3.org/2009/xmlenc11#'; // NOSONAR
    public const string WSS_X509_NS =
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0'; // NOSONAR
    public const string WSS_SOAP_SWA_NS =
        'http://docs.oasis-open.org/wss/oasis-wss-SwAProfile-1.1#'; // NOSONAR

    // AS4 Transport Profiles
    public const string AS4_TRANSPORT_PROFILE = 'bdxr-transport-ebms3-as4-v1p0';
    public const string PEPPOL_TRANSPORT_PROFILE = 'peppol-transport-as4-v2_0';

    // Peppol BIS Billing 3.0 — process and document type identifiers
    public const string PEPPOL_PROCESS_BIS3 =
        'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    public const string PEPPOL_DOCTYPE_INVOICE_BIS3 =
        'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::Invoice' .
        '##urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0::2.1';
    public const string PEPPOL_DOCTYPE_CREDITNOTE_BIS3 =
        'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2::CreditNote' .
        '##urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0::2.1';

    // ebMS3 party roles (composed from EBMS3_NS to avoid hardcoded URIs)
    public const string ROLE_INITIATOR = self::EBMS3_NS . 'initiator';
    public const string ROLE_RESPONDER = self::EBMS3_NS . 'responder';

    // SMP / BDXR service metadata
    public const string SMP_NS = 'http://docs.oasis-open.org/bdxr/ns/SMP/1.0/'; // NOSONAR
    public const string SMP_PARTICIPANT_SCHEME = 'iso6523-actorid-upis';

    // Message Exchange Patterns (MEPs)
    public const string MEP_ONE_WAY =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/oneWay'; // NOSONAR
    public const string MEP_TWO_WAY =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/twoWay'; // NOSONAR

    // MEP Bindings
    public const string MEPBINDING_PUSH =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/push'; // NOSONAR
    public const string MEPBINDING_PUSH_AND_PUSH =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pushAndPush'; // NOSONAR
    public const string MEPBINDING_PULL =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pull'; // NOSONAR
    public const string MEPBINDING_PUSH_AND_PULL =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pushAndPull'; // NOSONAR
    public const string MEPBINDING_PULL_AND_PUSH =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pullAndPush'; // NOSONAR

    // Service & Action for Test Service (ping)
    public const string TEST_SERVICE =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/service'; // NOSONAR
    public const string TEST_ACTION =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/test'; // NOSONAR

    // Default Message Partition Channel
    public const string DEFAULT_MPC =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/defaultMPC'; // NOSONAR

    // Signature Algorithms (Common Profile 2.0 mandatory)
    public const string SIGNATURE_ALGORITHM =
        'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed25519'; // NOSONAR
    public const string HASH_ALGORITHM =
        'http://www.w3.org/2001/04/xmlenc#sha256'; // NOSONAR
    public const string CANONICALIZATION =
        'http://www.w3.org/2001/10/xml-exc-c14n#'; // NOSONAR

    // Encryption Algorithms (Common Profile 2.0)
    public const string ENCRYPTION_ALGORITHM =
        'http://www.w3.org/2009/xmlenc11#aes128-gcm'; // NOSONAR
    public const string KEY_AGREEMENT =
        'http://www.w3.org/2021/04/xmldsig-more#x25519'; // NOSONAR
    public const string KEY_WRAPPING =
        'http://www.w3.org/2001/04/xmlenc#kw-aes128'; // NOSONAR
    public const string KEY_DERIVATION =
        'http://www.w3.org/2021/04/xmldsig-more#hkdf'; // NOSONAR
    public const string KEY_DERIVATION_PRF =
        'http://www.w3.org/2001/04/xmldsig-more#hmac-sha256'; // NOSONAR

    // Alternative ECC options (Profile Enhancement 4.7)
    public const string SIGNATURE_ALGORITHM_ECDSA =
        'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256'; // NOSONAR
    public const string KEY_AGREEMENT_ECDH =
        'http://www.w3.org/2009/xmlenc11#ECDH-ES'; // NOSONAR

    // Compression
    public const string COMPRESSION_TYPE = 'application/gzip';
    public const string COMPRESSION_ALGORITHM = 'gzip';

    // Error Codes
    public const string ERROR_DECOMPRESSION_FAILURE = 'EBMS:0303';
    public const string ERROR_MISSING_RECEIPT = 'EBMS:0301';
    public const string ERROR_DELIVERY_FAILURE = 'EBMS:0202';

    // Attachment MIME types
    public const string MIME_GZIP = 'application/gzip';
    public const string MIME_XML = 'application/xml';
    public const string MIME_JSON = 'application/json';
    public const string MIME_SOAP = 'application/soap+xml';

    // WS-Security token types (composed from WSS_X509_NS to avoid hardcoded URIs)
    public const string WSS_TOKEN_X509V3 = self::WSS_X509_NS
        . '#X509v3';
    public const string WSS_TOKEN_X509_SKI = self::WSS_X509_NS
        . '#X509SubjectKeyIdentifier';
    public const string WSS_TOKEN_X509_PKIPATH = self::WSS_X509_NS
        . '#X509PKIPathv1';

    // Binary encoding
    public const string WSS_ENCODING_BASE64 =
        'http://docs.oasis-open.org/wss/2004/01/' // NOSONAR
            . 'oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    // SOAP Attachment transform
    public const string SOAP_SWA_ATTACHMENT_CONTENT_ONLY =
        'http://docs.oasis-open.org/wss/' // NOSONAR
            . 'oasis-wss-SwAProfile-1.1#Attachment-Content-Only';
    public const string SOAP_SWA_ATTACHMENT_SIGNATURE =
        'http://docs.oasis-open.org/wss/' // NOSONAR
            . 'oasis-wss-SwAProfile-1.1#Attachment-Content-Signature-Transform';
    public const string SOAP_SWA_ATTACHMENT_CIPHERTEXT =
        'http://docs.oasis-open.org/wss/' // NOSONAR
            . 'oasis-wss-SwAProfile-1.1#Attachment-Ciphertext-Transform';

    // Four Corner Topology Enhancement
    public const string PROPERTY_ORIGINAL_SENDER = 'originalSender';
    public const string PROPERTY_FINAL_RECIPIENT = 'finalRecipient';
    public const string PROPERTY_TRACKING_IDENTIFIER = 'trackingIdentifier';

    // ebCore Party ID types (ISO 6523 scheme)
    public const string PARTY_TYPE_GLN = '0088';
    public const string PARTY_TYPE_DUNS = '0060';
    public const string PARTY_TYPE_VAT = '0007';

    // TLS Requirements
    public const string TLS_VERSION_MIN = 'TLSv1.2';
    public const string TLS_VERSION_RECOMMENDED = 'TLSv1.3';
}
