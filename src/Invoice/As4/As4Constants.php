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
    // Namespace URIs
    public const string SOAP_NS = 'http://www.w3.org/2003/05/soap-envelope';
    public const string SOAP_ENCODING_NS = 'http://schemas.xmlsoap.org/soap/encoding/';
    public const string EBMS3_NS = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/';
    public const string WSS_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    public const string WSS11_NS = 'http://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd';
    public const string WSS_UTIL_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    public const string XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    public const string XMLDSIG_MORE_NS = 'http://www.w3.org/2021/04/xmldsig-more#';
    public const string XMLDSIG11_NS = 'http://www.w3.org/2009/xmldsig11#';
    public const string XMLENC_NS = 'http://www.w3.org/2001/04/xmlenc#';
    public const string XMLENC11_NS = 'http://www.w3.org/2009/xmlenc11#';
    public const string WSS_X509_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0';
    public const string WSS_SOAP_SWA_NS = 'http://docs.oasis-open.org/wss/oasis-wss-SwAProfile-1.1#';

    // AS4 Transport Profiles
    public const string AS4_TRANSPORT_PROFILE        = 'bdxr-transport-ebms3-as4-v1p0';
    public const string PEPPOL_TRANSPORT_PROFILE     = 'peppol-transport-as4-v2_0';

    // SMP / BDXR service metadata
    public const string SMP_NS                       = 'http://docs.oasis-open.org/bdxr/ns/SMP/1.0/';
    public const string SMP_PARTICIPANT_SCHEME        = 'iso6523-actorid-upis';

    // Message Exchange Patterns (MEPs)
    public const string MEP_ONE_WAY = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/oneWay';
    public const string MEP_TWO_WAY = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/twoWay';

    // MEP Bindings
    public const string MEPBINDING_PUSH = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/push';
    public const string MEPBINDING_PUSH_AND_PUSH = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pushAndPush';
    public const string MEPBINDING_PULL = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pull';
    public const string MEPBINDING_PUSH_AND_PULL = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pushAndPull';
    public const string MEPBINDING_PULL_AND_PUSH = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/pullAndPush';

    // Service & Action for Test Service (ping)
    public const string TEST_SERVICE = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/service';
    public const string TEST_ACTION = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/test';

    // Default Message Partition Channel
    public const string DEFAULT_MPC = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/defaultMPC';

    // Signature Algorithms (Common Profile 2.0 mandatory)
    public const string SIGNATURE_ALGORITHM = 'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed25519';
    public const string HASH_ALGORITHM = 'http://www.w3.org/2001/04/xmlenc#sha256';
    public const string CANONICALIZATION = 'http://www.w3.org/2001/10/xml-exc-c14n#';

    // Encryption Algorithms (Common Profile 2.0)
    public const string ENCRYPTION_ALGORITHM = 'http://www.w3.org/2009/xmlenc11#aes128-gcm';
    public const string KEY_AGREEMENT = 'http://www.w3.org/2021/04/xmldsig-more#x25519';
    public const string KEY_WRAPPING = 'http://www.w3.org/2001/04/xmlenc#kw-aes128';
    public const string KEY_DERIVATION = 'http://www.w3.org/2021/04/xmldsig-more#hkdf';
    public const string KEY_DERIVATION_PRF = 'http://www.w3.org/2001/04/xmldsig-more#hmac-sha256';

    // Alternative ECC options (Profile Enhancement 4.7)
    public const string SIGNATURE_ALGORITHM_ECDSA = 'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256';
    public const string KEY_AGREEMENT_ECDH = 'http://www.w3.org/2009/xmlenc11#ECDH-ES';

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

    // WS-Security token types
    public const string WSS_TOKEN_X509V3 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3';
    public const string WSS_TOKEN_X509_SKI = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509SubjectKeyIdentifier';
    public const string WSS_TOKEN_X509_PKIPATH = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509PKIPathv1';

    // Binary encoding
    public const string WSS_ENCODING_BASE64 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    // SOAP Attachment transform
    public const string SOAP_SWA_ATTACHMENT_CONTENT_ONLY = 'http://docs.oasis-open.org/wss/oasis-wss-SwAProfile-1.1#Attachment-Content-Only';
    public const string SOAP_SWA_ATTACHMENT_SIGNATURE = 'http://docs.oasis-open.org/wss/oasis-wss-SwAProfile-1.1#Attachment-Content-Signature-Transform';
    public const string SOAP_SWA_ATTACHMENT_CIPHERTEXT = 'http://docs.oasis-open.org/wss/oasis-wss-SwAProfile-1.1#Attachment-Ciphertext-Transform';

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
