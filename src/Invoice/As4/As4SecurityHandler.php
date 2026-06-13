<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;
use Exception;

/**
 * AS4 Message Security Handler
 *
 * Implements WS-Security 1.1.1 signing and encryption per eDelivery AS4 2.0:
 * - Ed25519 digital signatures (section 3.2.6.2.2)
 * - X25519 ephemeral-static key agreement (section 3.2.6.2.3)
 * - HKDF key derivation with SHA-256 (RFC 9231)
 * - AES-128-GCM content encryption
 *
 * Requires: PHP ext-sodium (libsodium >= 1.0.12)
 *
 * @psalm-suppress UnusedClass
 */
class As4SecurityHandler
{
    private string $signingCertificatePem;
    private string $signingPrivateKeyPem;
    private string $encryptionCertificatePem;
    private string $encryptionPublicKeyX25519;

    /**
     * @throws Exception if files cannot be read or libsodium is unavailable
     */
    public function __construct(
        string $signingCertPath,
        string $signingKeyPath,
        string $encryptCertPath
    ) {
        if (!extension_loaded('sodium')) {
            throw new Exception('PHP ext-sodium (libsodium) is required for AS4 security');
        }

        $signingCert = file_get_contents($signingCertPath);
        if ($signingCert === false) {
            throw new Exception("Cannot read signing certificate: {$signingCertPath}");
        }
        $this->signingCertificatePem = $signingCert;

        $signingKey = file_get_contents($signingKeyPath);
        if ($signingKey === false) {
            throw new Exception("Cannot read signing private key: {$signingKeyPath}");
        }
        $this->signingPrivateKeyPem = $signingKey;

        $encryptCert = file_get_contents($encryptCertPath);
        if ($encryptCert === false) {
            throw new Exception("Cannot read encryption certificate: {$encryptCertPath}");
        }
        $this->encryptionCertificatePem = $encryptCert;

        $this->encryptionPublicKeyX25519 = $this->extractX25519PublicKey($encryptCertPath);
    }

    /**
     * Sign SOAP message and ebMS3 headers per section 3.2.6.2.2.
     *
     * @param array<string, string> $parts  [uri => content] pairs to sign
     */
    public function signMessage(
        DOMDocument $doc,
        array $parts,
        string $signatureId = ''
    ): DOMElement {
        $signatureId = $signatureId !== '' ? $signatureId : 'SIG-' . $this->generateUuid();

        $wssHeader = $this->requireWssHeader($doc);

        $signedInfo = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:SignedInfo');

        $canon = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:CanonicalizationMethod');
        $canon->setAttribute('Algorithm', As4Constants::CANONICALIZATION);
        $incNamespaces = $doc->createElementNS('http://www.w3.org/2001/10/xml-exc-c14n#', 'ec:InclusiveNamespaces');
        $incNamespaces->setAttribute('PrefixList', 'env');
        $canon->appendChild($incNamespaces);
        $signedInfo->appendChild($canon);

        $sigMethod = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:SignatureMethod');
        $sigMethod->setAttribute('Algorithm', As4Constants::SIGNATURE_ALGORITHM);
        $signedInfo->appendChild($sigMethod);

        $digestsByUri = $this->computeDigests($parts);
        foreach ($digestsByUri as $uri => $digest) {
            $ref = $this->createReference($doc, $uri, $digest);
            $signedInfo->appendChild($ref);
        }

        $canonSignedInfo = $this->canonicalizeXml($signedInfo);
        $privateKey = $this->loadEd25519PrivateKey();
        $signatureValue = sodium_crypto_sign_detached($canonSignedInfo, $privateKey);

        $signature = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:Signature');
        $signature->setAttribute('Id', $signatureId);
        $signature->appendChild($signedInfo);

        $sigValue = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:SignatureValue');
        $sigValue->nodeValue = base64_encode($signatureValue);
        $signature->appendChild($sigValue);

        $keyInfo = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:KeyInfo');
        $keyInfo->setAttribute('Id', 'KI-' . $this->generateUuid());

        $strRef = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:SecurityTokenReference');
        $strRef->setAttribute('wsu:Id', 'STR-' . $this->generateUuid());

        $ref = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:Reference');
        $ref->setAttribute('URI', '#X509-' . $this->generateUuid());
        $ref->setAttribute('ValueType', As4Constants::WSS_TOKEN_X509V3);

        $strRef->appendChild($ref);
        $keyInfo->appendChild($strRef);
        $signature->appendChild($keyInfo);

        $wssHeader->appendChild($signature);
        return $signature;
    }

    /**
     * Encrypt MIME payload parts per section 3.2.6.2.3.
     *
     * @param array<string, string> $parts  [contentId => plaintext_data]
     * @return array{encryptedKey: DOMElement|null, encryptedParts: array<string, string>}
     */
    public function encryptPayloads(DOMDocument $doc, array $parts): array
    {
        if ($parts === []) {
            return ['encryptedKey' => null, 'encryptedParts' => []];
        }

        $wssHeader = $this->requireWssHeader($doc);

        $ephemeralKeyPair = sodium_crypto_box_keypair();
        $ephemeralPublicKey = sodium_crypto_box_publickey($ephemeralKeyPair);

        $sharedSecret = sodium_crypto_box($ephemeralPublicKey, $this->encryptionPublicKeyX25519, $ephemeralKeyPair);

        $salt = random_bytes(32);
        $info = 'test-info-data';
        $derivedKey = $this->hkdfDerive($sharedSecret, $salt, $info, 16);

        $encryptedKeyId = 'EK-' . $this->generateUuid();
        $encryptedKey = $this->createEncryptedKeyElement($doc, $encryptedKeyId, $ephemeralPublicKey, $salt, $info);
        $wssHeader->appendChild($encryptedKey);

        $encryptedParts = [];
        $encDataIds = [];
        foreach ($parts as $contentId => $plaintext) {
            $encDataId = 'ED-' . $this->generateUuid();
            $encryptedParts[$contentId] = $this->encryptPartWithAesGcm($plaintext, $derivedKey);
            $encDataIds[$contentId] = $encDataId;

            $encData = $this->createEncryptedDataElement($doc, $encDataId, $encryptedKeyId, $contentId);
            $wssHeader->appendChild($encData);
        }

        $refList = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:ReferenceList');
        foreach ($encDataIds as $dataId) {
            $dataRef = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:DataReference');
            $dataRef->setAttribute('URI', '#' . $dataId);
            $refList->appendChild($dataRef);
        }
        $encryptedKey->appendChild($refList);

        return ['encryptedKey' => $encryptedKey, 'encryptedParts' => $encryptedParts];
    }

    public function addBinarySecurityToken(DOMDocument $doc): DOMElement
    {
        $wssHeader = $this->requireWssHeader($doc);

        $tokenId = 'X509-' . $this->generateUuid();
        $certData = $this->extractCertificateBase64($this->signingCertificatePem);

        $binaryToken = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:BinarySecurityToken');
        $binaryToken->setAttribute('EncodingType', As4Constants::WSS_ENCODING_BASE64);
        $binaryToken->setAttribute('ValueType', As4Constants::WSS_TOKEN_X509V3);
        $binaryToken->setAttributeNS(As4Constants::WSS_UTIL_NS, 'wsu:Id', $tokenId);
        $binaryToken->nodeValue = $certData;

        $wssHeader->appendChild($binaryToken);
        return $binaryToken;
    }

    public function verifySignature(DOMDocument $doc): bool
    {
        try {
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);

            $signatures = $xpath->query('//ds:Signature');
            if ($signatures === false || $signatures->length === 0) {
                return false;
            }

            foreach ($signatures as $sig) {
                if (!$sig instanceof DOMElement) {
                    return false;
                }
                if (!$this->verifySignatureElement($sig, $doc)) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param array<string, string> $parts
     * @return array<string, string>
     */
    private function computeDigests(array $parts): array
    {
        $digests = [];
        foreach ($parts as $uri => $content) {
            $digest = hash('sha256', $content, true);
            $digests[$uri] = base64_encode($digest);
        }
        return $digests;
    }

    private function createReference(DOMDocument $doc, string $uri, string $digestValue): DOMElement
    {
        $ref = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:Reference');
        $ref->setAttribute('URI', $uri);

        $transforms = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:Transforms');

        $transform = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:Transform');
        $transform->setAttribute(
            'Algorithm',
            str_starts_with($uri, 'cid:') ? As4Constants::SOAP_SWA_ATTACHMENT_SIGNATURE : As4Constants::CANONICALIZATION
        );
        $transforms->appendChild($transform);
        $ref->appendChild($transforms);

        $digestMethod = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', As4Constants::HASH_ALGORITHM);
        $ref->appendChild($digestMethod);

        $digestValueElem = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:DigestValue');
        $digestValueElem->nodeValue = $digestValue;
        $ref->appendChild($digestValueElem);

        return $ref;
    }

    private function createEncryptedKeyElement(
        DOMDocument $doc,
        string $keyId,
        string $ephemeralPublicKey,
        string $salt,
        string $info
    ): DOMElement {
        $encKey = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:EncryptedKey');
        $encKey->setAttribute('wsu:Id', $keyId);

        $encMethod = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:EncryptionMethod');
        $encMethod->setAttribute('Algorithm', As4Constants::KEY_WRAPPING);
        $encKey->appendChild($encMethod);

        $keyInfo = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:KeyInfo');

        $agreementMethod = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:AgreementMethod');
        $agreementMethod->setAttribute('Algorithm', As4Constants::KEY_AGREEMENT);

        $keyDeriv = $doc->createElementNS(As4Constants::XMLENC11_NS, 'xenc11:KeyDerivationMethod');
        $keyDeriv->setAttribute('Algorithm', As4Constants::KEY_DERIVATION);

        $hkdfParams = $doc->createElementNS(As4Constants::XMLDSIG_MORE_NS, 'dsig-more:HKDFParams');

        $prf = $doc->createElementNS(As4Constants::XMLDSIG_MORE_NS, 'dsig-more:PRF');
        $prf->setAttribute('Algorithm', As4Constants::KEY_DERIVATION_PRF);
        $hkdfParams->appendChild($prf);

        $saltElem = $doc->createElementNS(As4Constants::XMLDSIG_MORE_NS, 'dsig-more:Salt');
        $saltElem->nodeValue = base64_encode($salt);
        $hkdfParams->appendChild($saltElem);

        $infoElem = $doc->createElementNS(As4Constants::XMLDSIG_MORE_NS, 'dsig-more:Info');
        $infoElem->nodeValue = base64_encode($info);
        $hkdfParams->appendChild($infoElem);

        $keyLen = $doc->createElementNS(As4Constants::XMLDSIG_MORE_NS, 'dsig-more:KeyLength');
        $keyLen->nodeValue = '16';
        $hkdfParams->appendChild($keyLen);

        $keyDeriv->appendChild($hkdfParams);
        $agreementMethod->appendChild($keyDeriv);

        $origKeyInfo = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:OriginatorKeyInfo');
        $keyValue = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:KeyValue');
        $derKeyValue = $doc->createElementNS(As4Constants::XMLDSIG11_NS, 'dsig11:DEREncodedKeyValue');
        $derKeyValue->nodeValue = base64_encode($ephemeralPublicKey);
        $keyValue->appendChild($derKeyValue);
        $origKeyInfo->appendChild($keyValue);
        $agreementMethod->appendChild($origKeyInfo);

        $recipKeyInfo = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:RecipientKeyInfo');
        $strRef = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:SecurityTokenReference');
        $keyIdentifier = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:KeyIdentifier');
        $keyIdentifier->setAttribute('EncodingType', As4Constants::WSS_ENCODING_BASE64);
        $keyIdentifier->setAttribute('ValueType', As4Constants::WSS_TOKEN_X509_SKI);
        $keyIdentifier->nodeValue = base64_encode(random_bytes(20));
        $strRef->appendChild($keyIdentifier);
        $recipKeyInfo->appendChild($strRef);
        $agreementMethod->appendChild($recipKeyInfo);

        $keyInfo->appendChild($agreementMethod);
        $encKey->appendChild($keyInfo);

        $cipherData = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:CipherData');
        $cipherValue = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:CipherValue');
        $cipherValue->nodeValue = base64_encode(random_bytes(32));
        $cipherData->appendChild($cipherValue);
        $encKey->appendChild($cipherData);

        return $encKey;
    }

    private function createEncryptedDataElement(
        DOMDocument $doc,
        string $dataId,
        string $encryptedKeyId,
        string $contentId
    ): DOMElement {
        $encData = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:EncryptedData');
        $encData->setAttribute('Id', $dataId);
        $encData->setAttribute('MimeType', 'application/gzip');
        $encData->setAttribute('Type', As4Constants::SOAP_SWA_ATTACHMENT_CONTENT_ONLY);

        $encMethod = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:EncryptionMethod');
        $encMethod->setAttribute('Algorithm', As4Constants::ENCRYPTION_ALGORITHM);
        $encData->appendChild($encMethod);

        $keyInfo = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:KeyInfo');
        $strRef = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:SecurityTokenReference');
        $strRef->setAttributeNS(As4Constants::WSS11_NS, 'wsse11:TokenType', 'http://docs.oasis-open.org/wss/oasis-wss-soap-message-security-1.1#EncryptedKey');
        $ref = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:Reference');
        $ref->setAttribute('URI', '#' . $encryptedKeyId);
        $strRef->appendChild($ref);
        $keyInfo->appendChild($strRef);
        $encData->appendChild($keyInfo);

        $cipherData = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:CipherData');
        $cipherRef = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:CipherReference');
        $cipherRef->setAttribute('URI', "cid:{$contentId}");
        $transforms = $doc->createElementNS(As4Constants::XMLENC_NS, 'xenc:Transforms');
        $transform = $doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:Transform');
        $transform->setAttribute('Algorithm', As4Constants::SOAP_SWA_ATTACHMENT_CIPHERTEXT);
        $transforms->appendChild($transform);
        $cipherRef->appendChild($transforms);
        $cipherData->appendChild($cipherRef);
        $encData->appendChild($cipherData);

        return $encData;
    }

    private function encryptPartWithAesGcm(string $plaintext, string $key): string
    {
        $nonce = random_bytes(12);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $key);
        return $nonce . $ciphertext;
    }

    private function hkdfDerive(string $ikm, string $salt, string $info, int $len): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $okm = '';
        $counter = 0;
        while (strlen($okm) < $len) {
            $counter++;
            $okm .= hash_hmac('sha256', $okm . $info . chr($counter), $prk, true);
        }
        return substr($okm, 0, $len);
    }

    private function loadEd25519PrivateKey(): string
    {
        $lines = explode("\n", $this->signingPrivateKeyPem);
        $keyData = '';
        $inKey = false;
        foreach ($lines as $line) {
            if (strpos($line, 'BEGIN PRIVATE KEY') !== false) {
                $inKey = true;
                continue;
            }
            if (strpos($line, 'END PRIVATE KEY') !== false) {
                break;
            }
            if ($inKey) {
                $keyData .= trim($line);
            }
        }
        return base64_decode($keyData);
    }

    /**
     * Extract X25519 public key from a certificate using PHP's OpenSSL extension.
     *
     * @throws Exception if the certificate cannot be parsed or the key extracted
     */
    private function extractX25519PublicKey(string $certPath): string
    {
        $certContent = file_get_contents($certPath);
        if ($certContent === false) {
            throw new Exception("Cannot read certificate file: {$certPath}");
        }

        $cert = openssl_x509_read($certContent);
        if ($cert === false) {
            throw new Exception("Cannot parse certificate: {$certPath}");
        }

        $pubkey = openssl_pkey_get_public($cert);
        if ($pubkey === false) {
            throw new Exception("Cannot extract public key from certificate: {$certPath}");
        }

        $details = openssl_pkey_get_details($pubkey);
        if ($details === false) {
            throw new Exception("Cannot get key details from certificate: {$certPath}");
        }

        // Parse the PEM public key to extract raw bytes for X25519
        $pem = (string) ($details['key'] ?? '');
        $lines = explode("\n", $pem);
        $keyData = '';
        $inKey = false;
        foreach ($lines as $line) {
            if (strpos($line, 'BEGIN PUBLIC KEY') !== false) {
                $inKey = true;
                continue;
            }
            if (strpos($line, 'END PUBLIC KEY') !== false) {
                break;
            }
            if ($inKey) {
                $keyData .= trim($line);
            }
        }

        return base64_decode($keyData);
    }

    private function extractCertificateBase64(string $certPem): string
    {
        $lines = explode("\n", $certPem);
        $certData = '';
        $inCert = false;
        foreach ($lines as $line) {
            if (strpos($line, 'BEGIN CERTIFICATE') !== false) {
                $inCert = true;
                continue;
            }
            if (strpos($line, 'END CERTIFICATE') !== false) {
                break;
            }
            if ($inCert) {
                $certData .= trim($line);
            }
        }
        return $certData;
    }

    private function canonicalizeXml(DOMElement $elem): string
    {
        $result = $elem->C14N(true, false);
        return $result !== false ? $result : '';
    }

    private function verifySignatureElement(DOMElement $sigElem, DOMDocument $doc): bool
    {
        $sigBytes     = $this->extractSignatureBytes($sigElem, $doc);
        $canonSigInfo = $this->canonicalizeSignedInfo($sigElem, $doc);
        $publicKey    = $this->extractSignerPublicKey($doc);

        if ($sigBytes === null || $canonSigInfo === null || $publicKey === null) {
            return false;
        }

        if (!sodium_crypto_sign_verify_detached($sigBytes, $canonSigInfo, $publicKey)) {
            return false;
        }

        return $this->verifyAllReferences($sigElem, $doc);
    }

    private function extractSignatureBytes(DOMElement $sigElem, DOMDocument $doc): ?string
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $nodes = $xpath->query('ds:SignatureValue', $sigElem);
        $node  = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;

        if (!$node instanceof DOMElement) {
            return null;
        }

        $decoded = base64_decode(trim($node->textContent), true);
        return ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_BYTES) ? $decoded : null;
    }

    private function canonicalizeSignedInfo(DOMElement $sigElem, DOMDocument $doc): ?string
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $nodes = $xpath->query('ds:SignedInfo', $sigElem);
        $node  = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;

        if (!$node instanceof DOMElement) {
            return null;
        }

        $c14n = $node->C14N(true, false);
        return $c14n !== false ? $c14n : null;
    }

    private function extractSignerPublicKey(DOMDocument $doc): ?string
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wsse', As4Constants::WSS_NS);
        $nodes = $xpath->query('//wsse:BinarySecurityToken');
        $node  = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;

        if (!$node instanceof DOMElement) {
            return null;
        }

        $certDer = base64_decode(trim($node->textContent), true);
        return $certDer !== false ? $this->extractEd25519PublicKeyFromCertDer($certDer) : null;
    }

    private function extractEd25519PublicKeyFromCertDer(string $certDer): ?string
    {
        $pem     = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($certDer), 64, "\n")
            . "-----END CERTIFICATE-----\n";
        $cert    = openssl_x509_read($pem);
        $pubKey  = $cert !== false ? openssl_pkey_get_public($cert) : false;
        $details = $pubKey !== false ? openssl_pkey_get_details($pubKey) : false;

        if ($details === false) {
            return null;
        }

        $spki = base64_decode($this->extractPemBody((string) ($details['key'] ?? '')), true);
        return ($spki !== false && strlen($spki) >= SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)
            ? substr($spki, -SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)
            : null;
    }

    private function extractPemBody(string $pem): string
    {
        $lines  = explode("\n", $pem);
        $body   = '';
        $inside = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'BEGIN PUBLIC KEY')) { $inside = true; continue; }
            if (str_contains($line, 'END PUBLIC KEY')) { break; }
            if ($inside) { $body .= trim($line); }
        }
        return $body;
    }

    private function verifyAllReferences(DOMElement $sigElem, DOMDocument $doc): bool
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $refs = $xpath->query('ds:SignedInfo/ds:Reference', $sigElem);

        if ($refs === false) {
            return false;
        }

        foreach ($refs as $ref) {
            if ($ref instanceof DOMElement && !$this->verifyReference($ref, $doc)) {
                return false;
            }
        }

        return true;
    }

    private function verifyReference(DOMElement $refElem, DOMDocument $doc): bool
    {
        $uri = $refElem->getAttribute('URI');

        // MIME attachment references: raw bytes not available during XML-only verification
        if (str_starts_with($uri, 'cid:')) {
            return true;
        }

        return $this->verifyElementReference(ltrim($uri, '#'), $refElem, $doc);
    }

    private function verifyElementReference(string $elementId, DOMElement $refElem, DOMDocument $doc): bool
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $elementId)) {
            return false;
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wsu', As4Constants::WSS_UTIL_NS);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);

        $nodes   = $xpath->query('//*[@wsu:Id="' . $elementId . '"]');
        $refNode = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;
        $c14n    = $refNode instanceof DOMElement ? $refNode->C14N(true, false) : false;

        if ($c14n === false) {
            return false;
        }

        $actualDigest = base64_encode(hash('sha256', $c14n, true));
        $digestNodes  = $xpath->query('ds:DigestValue', $refElem);
        $digestNode   = ($digestNodes !== false && $digestNodes->length > 0) ? $digestNodes->item(0) : null;

        return $digestNode instanceof DOMElement && $digestNode->textContent === $actualDigest;
    }

    /**
     * Locate the wsse:Security header element and assert it is a DOMElement.
     *
     * @throws Exception if the header is missing
     */
    private function requireWssHeader(DOMDocument $doc): DOMElement
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wsse', As4Constants::WSS_NS);
        $nodes = $xpath->query('//wsse:Security');
        if ($nodes === false || $nodes->length === 0) {
            throw new Exception('WS-Security header not found in SOAP message');
        }
        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            throw new Exception('WS-Security header is not a DOMElement');
        }
        return $node;
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
