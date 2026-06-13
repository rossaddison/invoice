<?php
/**
 * P-Mode Configuration Examples
 * eDelivery AS4 2.0 Common Profile
 * 
 * Reference: Section 3.5 P-Mode Parameters
 * 
 * This file demonstrates how to instantiate and configure P-Modes
 * for different scenarios:
 * 1. Standard invoice transmission (One-Way/Push)
 * 2. Two-Way request-response pattern
 * 3. Four Corner Topology with intermediaries
 * 4. Pull feature for unreliable receivers
 */

use Invoice\As4\PMode;
use Invoice\As4\As4Constants;

// ============================================================================
// Example 1: Standard Invoice Transmission (One-Way/Push)
// ============================================================================
// Seller transmits invoice to Buyer via Peppol network
// - Standard point-to-point AS4 exchange
// - Buyer MSH sends back a non-repudiation receipt
// - Full signing and encryption per Common Profile

$pmodeInvoiceTransmit = new PMode(
    initiatorParty: '5412345000016',  // Seller GLN
    responderParty: '5412345000023',  // Buyer GLN
    responderProtocolAddress: 'https://buyer-as4.example.com/as4',
    service: 'urn:service:invoice:transmission',
    action: 'SendInvoice'
);

$pmodeInvoiceTransmit
    ->setInitiatorRole('Seller')
    ->setResponderRole('Buyer')
    ->setMep(As4Constants::MEP_ONE_WAY)
    ->setMepBinding(As4Constants::MEPBINDING_PUSH)
    ->setCompressionEnabled(true)
    // Certificates loaded from filesystem or HSM
    // In production: load from secure key store
    ->setSignCertificate('-----BEGIN CERTIFICATE-----\n...seller-sign-cert...\n-----END CERTIFICATE-----')
    ->setEncryptCertificate('-----BEGIN CERTIFICATE-----\n...buyer-encrypt-cert...\n-----END CERTIFICATE-----');

// Configuration array for storage/serialization
$invoiceTransmitConfig = $pmodeInvoiceTransmit->toArray();
echo "Invoice Transmission P-Mode Configuration:\n";
echo json_encode($invoiceTransmitConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================================================
// Example 2: Two-Way Request-Response (Purchase Order + Order Response)
// ============================================================================
// Buyer sends purchase order, Seller sends order response
// - Two separate messages with correlation via RefToMessageId
// - Each leg has its own P-Mode configuration
// - Responses must include RefToMessageId per section 3.2.2

$pmodeOrderRequest = new PMode(
    initiatorParty: '5412345000023',  // Buyer
    responderParty: '5412345000016',  // Seller
    responderProtocolAddress: 'https://seller-as4.example.com/as4',
    service: 'urn:service:order:transmission',
    action: 'SendPurchaseOrder'
);

$pmodeOrderRequest
    ->setInitiatorRole('Buyer')
    ->setResponderRole('Seller')
    ->setMep(As4Constants::MEP_TWO_WAY)
    ->setMepBinding(As4Constants::MEPBINDING_PUSH_AND_PUSH)
    ->setReceiptReplyPattern('Callback');  // For Two-Way, can use callback

$orderRequestConfig = $pmodeOrderRequest->toArray();
echo "Order Request P-Mode (Two-Way):\n";
echo json_encode($orderRequestConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================================================
// Example 3: Four Corner Topology (Profile Enhancement 4.1)
// ============================================================================
// Messages routed through intermediaries
// C1 (Seller) -> C2 (Seller Access Point) -> C3 (Buyer Access Point) -> C4 (Buyer)
// 
// Key differences:
// - originalSender and finalRecipient properties in message
// - eb:From/eb:To identify access points (C2, C3)
// - C1 and C4 are in message properties, not SOAP headers
// Reference: Section 4.1

$pmodeC2ToC3 = new PMode(
    initiatorParty: '5412345000016',  // C2: Seller's Access Point
    responderParty: '5412345000009',  // C3: Buyer's Access Point
    responderProtocolAddress: 'https://buyer-ap.example.com/as4',
    service: 'urn:service:invoice:transmission',
    action: 'SendInvoice'
);

$pmodeC2ToC3
    ->setInitiatorRole('SenderAccessPoint')
    ->setResponderRole('ReceiverAccessPoint')
    ->setFourCornerEnabled(true)
    ->setMep(As4Constants::MEP_ONE_WAY)
    ->setMepBinding(As4Constants::MEPBINDING_PUSH)
    ->setCompressionEnabled(true)
    ->setSignCertificate('-----BEGIN CERTIFICATE-----\n...c2-cert...\n-----END CERTIFICATE-----')
    ->setEncryptCertificate('-----BEGIN CERTIFICATE-----\n...c3-cert...\n-----END CERTIFICATE-----');

// Message properties for Four Corner Topology (section 4.1.2)
$fourCornerProperties = [
    As4Constants::PROPERTY_ORIGINAL_SENDER => '5412345000016',  // C1: Original Seller
    As4Constants::PROPERTY_FINAL_RECIPIENT => '5412345000023',  // C4: Final Buyer
    As4Constants::PROPERTY_TRACKING_IDENTIFIER => 'tracking-ref-2024-06-12-001',
];

$fourCornerConfig = $pmodeC2ToC3->toArray();
echo "Four Corner Topology P-Mode (C2->C3):\n";
echo json_encode($fourCornerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Four Corner Message Properties:\n";
echo json_encode($fourCornerProperties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================================================
// Example 4: Pull Feature (Profile Enhancement 4.4)
// ============================================================================
// For receivers without stable IP/DNS (small businesses, mobile networks)
// Receiver pulls messages from Sender instead of Sender pushing
// 
// When to use:
// - Receiver not addressable from internet
// - Firewall policy prevents inbound connections
// - Receiver not available 24/7
//
// Trade-off: Variable latency between message submission and retrieval

$pmodePullReceive = new PMode(
    initiatorParty: '5412345000023',  // Buyer (pulls)
    responderParty: '5412345000016',  // Seller (provides MPC)
    responderProtocolAddress: 'https://seller-as4.example.com/as4/pull',
    service: 'urn:service:invoice:transmission',
    action: 'SendInvoice'
);

$pmodePullReceive
    ->setInitiatorRole('Buyer')
    ->setResponderRole('Seller')
    ->setMep(As4Constants::MEP_ONE_WAY)
    ->setMepBinding(As4Constants::MEPBINDING_PULL)
    ->setMpc('urn:mpc:invoice:buyer-5412345000023')  // Unique MPC per party
    ->setReceiptReplyPattern('Callback');  // Async receipts for Pull

$pullConfig = $pmodePullReceive->toArray();
echo "Pull Feature P-Mode:\n";
echo json_encode($pullConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================================================
// Example 5: Dynamic Sender (Profile Enhancement 4.3)
// ============================================================================
// For marketplaces/networks where receiver info is discovered at runtime
// - P-Mode template without responder details
// - Discovery infrastructure (SMP) provides endpoint and certificates
// - Useful for many-to-many communication patterns

$pmodeDynamicTemplate = new PMode(
    initiatorParty: '5412345000016',  // Sender (known)
    responderParty: 'DYNAMIC',         // Will be filled by discovery
    responderProtocolAddress: '',      // Will be filled by discovery
    service: 'urn:service:invoice:transmission',
    action: 'SendInvoice'
);

$pmodeDynamicTemplate
    ->setInitiatorRole('Seller')
    ->setResponderRole('Buyer')
    ->setMep(As4Constants::MEP_ONE_WAY)
    ->setMepBinding(As4Constants::MEPBINDING_PUSH)
    ->setCompressionEnabled(true)
    // Sign cert is known (sender's own)
    ->setSignCertificate('-----BEGIN CERTIFICATE-----\n...sender-cert...\n-----END CERTIFICATE-----')
    // Encrypt cert will be discovered
    ->setEncryptCertificate('');

$dynamicConfig = $pmodeDynamicTemplate->toArray();
echo "Dynamic Sender P-Mode Template:\n";
echo json_encode($dynamicConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Discovery would provide (e.g., from SMP):\n";
$discoveredData = [
    'responder_party' => '5412345000023',
    'responder_protocol_address' => 'https://buyer-as4-example.com/as4',
    'responder_encrypt_certificate' => '-----BEGIN CERTIFICATE-----\n...discovered-cert...\n-----END CERTIFICATE-----',
];
echo json_encode($discoveredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================================================
// Common P-Mode Parameter Values (per section 3.5)
// ============================================================================
echo "=== Common P-Mode Parameters Reference (Section 3.5) ===\n\n";

echo "MEPs:\n";
echo "  - oneWay:      " . As4Constants::MEP_ONE_WAY . "\n";
echo "  - twoWay:      " . As4Constants::MEP_TWO_WAY . "\n\n";

echo "MEP Bindings:\n";
echo "  - push:        " . As4Constants::MEPBINDING_PUSH . "\n";
echo "  - pushAndPush: " . As4Constants::MEPBINDING_PUSH_AND_PUSH . "\n";
echo "  - pull:        " . As4Constants::MEPBINDING_PULL . "\n";
echo "  - pushAndPull: " . As4Constants::MEPBINDING_PUSH_AND_PULL . "\n";
echo "  - pullAndPush: " . As4Constants::MEPBINDING_PULL_AND_PUSH . "\n\n";

echo "Security Algorithms (Common Profile 2.0):\n";
echo "  - Signature:           " . As4Constants::SIGNATURE_ALGORITHM . "\n";
echo "  - Hash:                " . As4Constants::HASH_ALGORITHM . "\n";
echo "  - Encryption:          " . As4Constants::ENCRYPTION_ALGORITHM . "\n";
echo "  - Key Agreement:       " . As4Constants::KEY_AGREEMENT . "\n";
echo "  - Key Wrapping:        " . As4Constants::KEY_WRAPPING . "\n";
echo "  - Key Derivation:      " . As4Constants::KEY_DERIVATION . "\n";
echo "  - Key Derivation PRF:  " . As4Constants::KEY_DERIVATION_PRF . "\n\n";

echo "Alternative ECC (Profile Enhancement 4.7):\n";
echo "  - ECDSA Signature:     " . As4Constants::SIGNATURE_ALGORITHM_ECDSA . "\n";
echo "  - ECDH-ES Agreement:   " . As4Constants::KEY_AGREEMENT_ECDH . "\n\n";

echo "Test Service (Ping):\n";
echo "  - Service:   " . As4Constants::TEST_SERVICE . "\n";
echo "  - Action:    " . As4Constants::TEST_ACTION . "\n";
