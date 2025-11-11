<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Entity\Inv;
use App\Invoice\Libraries\Crypt;
use App\Invoice\Setting\SettingRepository;
use Braintree\Gateway;
use Braintree\CustomerGateway;
use Braintree\ClientTokenGateway;
use Braintree\Result\Successful;
use Braintree\Transaction;
use Psr\Log\LoggerInterface;

/**
 * Service for handling Braintree payment operations
 */
class BraintreePaymentService
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly Crypt $crypt,
        private readonly LoggerInterface $logger,
        private string $salt,
    ) {
        $this->salt = (new Crypt())->salt();
    }

    /**
     * Creates and configures a Braintree Gateway instance
     */
    public function createGateway(): Gateway
    {
        return new Gateway([
            'environment' => $this->getEnvironment(),
            'merchantId' => $this->getMerchantId(),
            'publicKey' => $this->getPublicKey(),
            'privateKey' => $this->getPrivateKey(),
        ]);
    }

    /**
     * Finds or creates a Braintree customer for the given invoice
     *
     * @param Inv $invoice The invoice containing client information
     * @return bool True if customer exists or was created successfully, false otherwise
     */
    public function findOrCreateCustomer(Inv $invoice): bool
    {
        $gateway = $this->createGateway();
        $customerGateway = new CustomerGateway($gateway);
        $clientId = $invoice->getClient_id();

        try {
            // Try to find existing customer
            $customerGateway->find($clientId);
            $this->logger->info('Braintree customer found', ['client_id' => $clientId]);
            return true;
        } catch (\Braintree\Exception\NotFound $e) {
            // Customer not found, create new one
            $this->logger->info('Braintree customer not found, creating new one', ['client_id' => $clientId]);

            try {
                $result = $customerGateway->create([
                    'id' => $clientId,
                    'firstName' => $invoice->getClient()?->getClient_name(),
                    'lastName' => $invoice->getClient()?->getClient_surname(),
                    'email' => $invoice->getClient()?->getClient_email(),
                ]);

                if ($result->success) {
                    $this->logger->info('Braintree customer created successfully', ['client_id' => $clientId]);
                    return true;
                }
                $this->logger->error('Failed to create Braintree customer', [
                    'client_id' => $clientId,
                    'errors' => $result->message ?? 'Unknown error',
                ]);
                return false;
            } catch (\Throwable $e) {
                $this->logger->error('Exception occurred while creating Braintree customer', [
                    'client_id' => $clientId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return false;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Exception occurred while finding Braintree customer', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Generates a client token for frontend operations
     */
    public function generateClientToken(): string
    {
        try {
            $gateway = $this->createGateway();
            $clientTokenGateway = new ClientTokenGateway($gateway);
            $token = $clientTokenGateway->generate();

            $this->logger->debug('Braintree client token generated successfully');
            return $token;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate Braintree client token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return '';
        }
    }

    /**
     * Processes a transaction using Braintree
     *
     * @param float $amount The amount to charge
     * @param string $paymentMethodNonce The payment method nonce from client
     * @return array Result array with success status and details
     */
    public function processTransaction(float $amount, string $paymentMethodNonce): array
    {
        $result = [
            'success' => false,
            'transaction_id' => null,
            'message' => '',
            'braintree_result' => null,
        ];

        if (empty($paymentMethodNonce)) {
            $result['message'] = 'Payment method nonce is required';
            $this->logger->warning('Braintree transaction attempted without payment method nonce');
            return $result;
        }

        try {
            $gateway = $this->createGateway();

            $braintreeResult = $gateway->transaction()->sale([
                'amount' => $amount,
                'paymentMethodNonce' => $paymentMethodNonce,
                'options' => [
                    'submitForSettlement' => true,
                ],
            ]);

            $result['braintree_result'] = $braintreeResult;

            if ($braintreeResult->success) {
                $result['success'] = true;
                if ($braintreeResult instanceof Successful && isset($braintreeResult->transaction)) {
                    /** @var Transaction $braintreeResult->transaction */
                    $transaction = $braintreeResult->transaction;
                    /** @psalm-var object{id?: string|int} $transaction */
                    if (isset($transaction->id)) {
                        $result['transaction_id'] = (string) $transaction->id;
                    } else {
                        $result['transaction_id'] = null;
                    }
                    $result['message'] = 'Transaction successful';

                    $this->logger->info('Braintree transaction completed successfully', [
                        'amount' => $amount,
                        'transaction_id' => $result['transaction_id'],
                    ]);
                } else {
                    /** @var string|null $braintreeResult->message */
                    $result['message'] = $braintreeResult->message ?? 'Transaction failed';

                    $this->logger->warning('Braintree transaction failed', [
                        'amount' => $amount,
                        'message' => $result['message'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $result['message'] = 'Transaction processing error: ' . $e->getMessage();

            $this->logger->error('Exception occurred during Braintree transaction', [
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    /**
     * Gets the Braintree merchant ID
     */
    public function getMerchantId(): string
    {
        $merchantId = $this->settings->getSetting('gateway_braintree_merchantId');
        return (string) $this->crypt->decode($merchantId ?: '');
    }

    /**
     * Gets the Braintree environment (sandbox or production)
     */
    public function getEnvironment(): string
    {
        return $this->settings->getSetting('gateway_braintree_sandbox') === '1' ? 'sandbox' : 'production';
    }

    /**
     * Gets the Braintree public key
     */
    private function getPublicKey(): string
    {
        $publicKey = $this->settings->getSetting('gateway_braintree_publicKey');
        return (string) $this->crypt->decode($publicKey ?: '');
    }

    /**
     * Gets the Braintree private key
     */
    private function getPrivateKey(): string
    {
        $privateKey = $this->settings->getSetting('gateway_braintree_privateKey');
        return (string) $this->crypt->decode($privateKey ?: '');
    }

    /**
     * Checks if Braintree is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->getMerchantId())
               && !empty($this->getPublicKey())
               && !empty($this->getPrivateKey());
    }

    /**
     * Gets the Braintree SDK version for display purposes
     */
    public function getVersion(): string
    {
        return \Braintree\Version::get();
    }
}
