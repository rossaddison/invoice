<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\Setting\SettingException;
use DateTime;
use DateTimeZone;

trait SettingGovMtdTrait
{

    /**
     * @return array
     */
    public function getServer(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
        return (array) $params['server'];
    }

    /**
     * @return array
     */
    public function getLicense(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
        return (array) $params['license'];
    }

    /**
     * @return array
     */
    public function getProduct(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
        return (array) $params['product'];
    }

    /**
     * Related logic:
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/
        reference-guide#errors
     * Related logic: see https://developer.service.hmrc.gov.uk/guides/
        fraud-prevention/connection-method/web-app-via-server/
        #gov-client-multi-factor
     * @param string $mfaType e.g. TOTP (Timed One Time Password)
     * @param string $uniqueReference
     * @return string
     */
    public function fphGenerateMultiFactor(string $mfaType,
                                                string $uniqueReference): string
    {
        // Current timestamp in ISO 8601 format
        // https://developer.service.hmrc.gov.uk/guides/fraud-prevention/
        // change-log/
        // The timestamp field must contain a T and use the 24 hour format
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        return 'type=' . $mfaType . '&'
               . 'timestamp=' . rawurlencode($timestamp) . '&'
               . 'unique-reference=' . $uniqueReference;
    }

    public function getGovClientPublicIp(): ?string
    {
        $server = $this->getServer();
        if (!$server) {
            return null;
        }

        // Prefer HTTP_CLIENT_IP if set (rarely used, sometimes unreliable)
        if (!empty($server['http_client_ip'])) {
            return (string) $server['http_client_ip'];
        }

        // Prefer the first valid IP from X-Forwarded-For
        if (!empty($server['http_x_forwarded_for'])) {
            // X-Forwarded-For can contain multiple IPs, pick the first one
            $ips = explode(',', (string) $server['http_x_forwarded_for']);
            $clientIp = trim(reset($ips));
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        // Fallback to remote_addr
        if (!empty($server['remote_addr'])) {
            return (string) $server['remote_addr'];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getGovClientPublicIpTimestamp(): ?string
    {
        $ip = $this->getGovClientPublicIp();
        if (null !== $ip) {
            $this->validateIp($ip);

            //Submit a UTC timestamp in the format yyyy-MM-ddThh:mm:ss.sssZ
            //where Z designates zero time offset e.g 2020-09-21T14:30:05.123Z
            $date = new DateTime('now', new DateTimeZone('UTC'));
            return $date->format('Y-m-d\TH:i:s.v\Z');
        }
        return null;
    }

    /**
     * Validate the retrieved IP address.
     *
     * @param string $ip The IP address to validate.
     * @throws SettingException If the IP address is invalid.
     */
    private function validateIp(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new SettingException('Invalid IP address format.');
        }
    }

    public function getEntityPositionsArray(string $entity): array
    {
        return match ($entity) {
            'client' => ['custom.fields', 'address', 'contact.information',
                                    'personal.information', 'tax.information'],
            'family' => ['custom.fields'],
            'product' => ['custom.fields'],
            'invoice' => ['custom.fields'],
            'payment' => ['custom.fields'],
            'quote' => ['custom.fields'],
            'user' => ['custom.fields', 'account.information', 'address',
                                    'tax.information', 'contact.information'],
        };
    }

    public function viewPositionsArray(): array
    {
        return [
            'client' => ['custom.fields', 'address', 'contact.information',
                                    'personal.information', 'tax.information'],
            'family' => ['custom.fields'],
            'product' => ['custom.fields'],
            'invoice' => ['custom.fields'],
            'payment' => ['custom.fields'],
            'quote' => ['custom.fields'],
            'user' => ['custom.fields', 'account.information', 'address',
                                    'tax.information', 'contact.information'],
        ];
    }

    public function getGovClientPublicPort(): int
    {
        $server = $this->getServer();
        return (int) ($server['remote_port'] ?? 0);
    }

    /**
     * Note: You will need to adapt this code if you have more than one screen
     * Related logic: https://developer.service.hmrc.gov.uk/guides/
        fraud-prevention/connection-method/web-app-via-server/#gov-client-screens
     * e.g. width=1920&height=1080&scaling-factor=1&colour-depth=16,
        width=3000&height=2000&scaling-factor=1.25&colour-depth=16
     * Width and height must be positive whole numbers
     * @return string
     */
    public function getGovClientScreens(): string
    {
        $width = $this->getSetting('fph_screen_width');
        $height = $this->getSetting('fph_screen_height');
        $scalingFactor = $this->getSetting('fph_screen_scaling_factor');
        $colourDepth = $this->getSetting('fph_screen_colour_depth');
        if ($width > 0 && $height > 0 && $scalingFactor > 0 && $colourDepth > 0) {
            return 'width=' . $width . '&'
                   . 'height=' . $height . '&'
                   . 'scaling-factor=' . $scalingFactor . '&'
                   . 'colour-depth=' . $colourDepth;
        }
        return '';
    }

    /**
     * Related logic: see https://developer.service.hmrc.gov.uk/guides/
        fraud-prevention/connection-method/web-app-via-server/#gov-client-user-ids
     * e.g. my-application=alice123
     */
    public function getGovClientUserIDs(): string
    {
        $uuid = $this->getSetting('fph_gov_client_user_id');
        return 'my-gov-client-user-id' . '=' . $uuid;
    }

    /**
     * Related logic: see https://developer.service.hmrc.gov.uk/guides/
        fraud-prevention/connection-method/web-app-via-server/#gov-client-timezone
     * e.g. UTC+01:00
     */
    public function getGovClientTimezone(): string
    {
        $date = new DateTime();
        $offset = $date->format('P');
        return 'UTC' . $offset;
    }

    /**
     * Related logic: see https://developer.service.hmrc.gov.uk/guides/
        fraud-prevention/connection-method/web-app-via-server/
                                                           #gov-vendor-forwarded
     * The by field must be the public IP address that the server received
                                                                  the request on.
     * For the first hop, this is the public IP address of the server and value
                                                          of Gov-Vendor-Public-IP.
     * The for field must be the public IP address of the request sender.
       For the first hop, this is the public IP address of the client and value
                                                          of Gov-Client-Public-IP.
     * For subsequent hops, it is the public IP address of the intermediate server.
     * e.g. by=203.0.113.6&for=198.51.100.0
     */
    public function getGovVendorForwarded(): string
    {
        $govClientPublicIp = $this->getGovClientPublicIp();
        if (null !== $govClientPublicIp) {
            return 'by=' . $this->getGovVendorPublicIp() . '&' . 'for='
                    . $govClientPublicIp;
        }
        return 'by=' . $this->getGovVendorPublicIp();
    }

    /**
     * Related logic:
        https://developer.service.hmrc.gov.uk/guides/fraud-prevention/
         connection-method/web-app-via-server/#gov-vendor-license-ids
     * e.g. my-licensed-software=8D7963490527D33716835EE7C195516D5E562E03B224E
        9B359836466EE40CDE1
     */
    public function getGovVendorLicenseIDs(): string
    {
        $license = $this->getLicense();
        return 'my-licensed-software=' . (string) $license['id'];
    }

    /**
     * Related logic: https://developer.service.hmrc.gov.uk/guides/
       fraud-prevention/connection-method/web-app-via-server/
        #gov-vendor-product-name
     * e.g.
     */
    public function getGovVendorProductName(): string
    {
        $product = $this->getProduct();
        return rawurlencode((string) $product['name']) ?: 'unknown';
    }

    public function getGovVendorPublicIP(): string
    {
        $url = 'https://api.ipify.org?format=json';
        $response = file_get_contents($url);

        if ($response === false) {
            throw new SettingException('Failed to fetch the public IP from the API.');
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['ip']) || !is_string($data['ip'])) {
            throw new SettingException("Invalid response format or missing 'ip' key.");
        }

        return $data['ip'];
    }

    // connection-method/web-app-via-server/
    public function getGovVendorVersion(): string
    {
        $product = $this->getProduct();
        return rawurlencode('client') . '='
                . rawurlencode((string) ($product['client'] ?? ''))
                . '&' . rawurlencode('server')
                . '=' . rawurlencode((string) ($product['server'] ?? ''));
    }
}
