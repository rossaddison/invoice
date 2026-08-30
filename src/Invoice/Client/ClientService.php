<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use chillerlan\QRCode\QRCode;
use Yiisoft\Security\Random;

final readonly class ClientService
{
    public function __construct(private ClientRepository $repository)
    {
    }

    /**
     * Returns this client's home-care QR token, generating and
     * persisting one on first use. Shared by both the guest "print my QR
     * code" action and the staff "print QR code for this client" action so
     * every printout for a given client encodes the same scan URL.
     */
    public function getOrCreateQrToken(Client $client): string
    {
        $token = $client->getClientQrToken();
        if ($token !== null && $token !== '') {
            return $token;
        }
        $token = Random::string(32);
        $client->setClientQrToken($token);
        $this->repository->save($client);
        return $token;
    }

    /**
     * Renders a QR code image as a data URI, ready for an <img src="...">.
     */
    public function renderQrDataUri(string $content): string
    {
        return (string) new QRCode()->render($content);
    }

    /**
     * @param Client $model
     * @param array $body
     * @return int|null
     * @psalm-suppress UnusedParam
     */
    public function saveClient(Client $model, array $body): ?int
    {
        $this->applyClientIdentityFields($model, $body);
        $this->applyClientAddressFields($model, $body);
        $this->applyClientContactFields($model, $body);
        $this->applyClientSpecialFields($model, $body);
        if ($model->hasIdentity()) {
            $model->setClientActive(true);
            $model->setPostaladdressId(0);
        }
        $this->repository->save($model);
        return $model->hasIdentity() ? $model->reqId() : null;
    }

    private function applyClientIdentityFields(Client $model, array $body): void
    {
        isset($body['client_title']) ? $model->setClientTitle((string) $body['client_title']) : '';
        isset($body['client_name']) ? $model->setClientName((string) $body['client_name']) : '';
        isset($body['client_surname']) ? $model->setClientSurname((string) $body['client_surname']) : '';
        // A real browser always submits every text input on the form, blank
        // or not, so client_name/client_surname are normally both present
        // (see the isset() guards just above). But a partial POST -- an
        // incomplete API call, or a future htmx/fetch submission that only
        // sends a subset of fields -- previously crashed here with
        // "Undefined array key client_surname" reading it unguarded, unlike
        // every other field in this class. Found & fixed 2026-08-30 as a
        // follow-up to the fresh-install workflow test (see
        // project_fresh_install_workflow_test_and_fixes memory).
        $client_name = isset($body['client_name']) ? (string) $body['client_name'] : '';
        $client_surname = isset($body['client_surname']) ? (string) $body['client_surname'] : '';
        $model->setClientFullName($client_name . ' ' . $client_surname);
        isset($body['client_frequency']) ? $model->setClientFrequency((string) $body['client_frequency']) : '';
        isset($body['client_group']) ? $model->setClientGroup((string) $body['client_group']) : '';
        isset($body['client_number']) ? $model->setClientNumber((string) $body['client_number']) : '';
    }

    private function applyClientAddressFields(Client $model, array $body): void
    {
        isset($body['client_address_1']) ? $model->setClientAddress1((string) $body['client_address_1']) : '';
        isset($body['client_address_2']) ? $model->setClientAddress2((string) $body['client_address_2']) : '';
        isset($body['client_building_number']) ? $model->setClientBuildingNumber((string) $body['client_building_number']) : '';
        isset($body['client_city']) ? $model->setClientCity((string) $body['client_city']) : '';
        isset($body['client_state']) ? $model->setClientState((string) $body['client_state']) : '';
        isset($body['client_zip']) ? $model->setClientZip((string) $body['client_zip']) : '';
        isset($body['client_country']) ? $model->setClientCountry((string) $body['client_country']) : '';
    }

    private function applyClientContactFields(Client $model, array $body): void
    {
        isset($body['client_phone']) ? $model->setClientPhone((string) $body['client_phone']) : '';
        isset($body['client_fax']) ? $model->setClientFax((string) $body['client_fax']) : '';
        isset($body['client_mobile']) ? $model->setClientMobile((string) $body['client_mobile']) : '';
        isset($body['client_email']) ? $model->setClientEmail((string) $body['client_email']) : '';
        isset($body['client_web']) ? $model->setClientWeb((string) $body['client_web']) : '';
        isset($body['client_vat_id']) ? $model->setClientVatId((string) $body['client_vat_id']) : '';
        isset($body['client_tax_code']) ? $model->setClientTaxCode((string) $body['client_tax_code']) : '';
        isset($body['client_language']) ? $model->setClientLanguage((string) $body['client_language']) : '';
    }

    private function applyClientSpecialFields(Client $model, array $body): void
    {
        $model->setClientActive($body['client_active'] === '1' ? true : false);
        $datetime = new \DateTimeImmutable();
        if (isset($body['client_birthdate'])) {
            $birthdate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $body['client_birthdate']) ?: $datetime;
            $model->setClientBirthdate($birthdate);
        }
        isset($body['client_age']) ? $model->setClientAge((int) $body['client_age']) : '';
        isset($body['client_gender']) ? $model->setClientGender((int) $body['client_gender']) : '';
        isset($body['postaladdress_id']) ? $model->setPostaladdressId((int) $body['postaladdress_id']) : '';
        $model->setClientTelegramChatId(isset($body['client_telegram_chat_id']) ? (string) $body['client_telegram_chat_id'] : null);
    }

    /**
     * @param array|Client|null $model
     */
    public function deleteClient(array|Client|null $model): void
    {
        $this->repository->delete($model);
    }
}
