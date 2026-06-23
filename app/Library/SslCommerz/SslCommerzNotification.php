<?php

namespace App\Library\SslCommerz;

/**
 * SSLCommerz Notification Library
 *
 * Ported from: https://github.com/sslcommerz/SSLCommerz-Laravel
 * Usage: Instantiate and call makePayment() to initiate, orderValidate() to verify.
 */
class SslCommerzNotification
{
    private string $apiDomain;
    private string $apiUrl;
    private string $apiValidationUrl;
    private string $storeId;
    private string $storePasswd;
    private bool   $isLive;

    public function __construct()
    {
        $this->storeId     = (string) config('sslcommerz.store_id', '');
        $this->storePasswd = (string) config('sslcommerz.store_passwd', '');
        $this->isLive      = (bool)   config('sslcommerz.is_live', false);
        $this->setDomainConfig();
    }

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Initiate a payment. Returns the complete SSLCommerz gateway response.
     * On success, response['GatewayPageURL'] contains the redirect URL.
     *
     * @param  array<string, mixed> $postData
     */
    public function makePayment(array $postData, string $type = 'hosted', string $pattern = 'json'): array
    {
        $postData['store_id']     = $this->storeId;
        $postData['store_passwd'] = $this->storePasswd;

        $responseData = $this->curlPost($this->apiUrl, $postData);

        return is_array($responseData) ? $responseData : [];
    }

    /**
     * Validate a transaction after SSLCommerz success callback.
     * Hits the SSLCommerz validation API and cross-checks amount / currency / tran_id.
     *
     * @param  array<string, mixed> $postData  Data from SSLCommerz callback ($_POST)
     */
    public function orderValidate(array $postData, string $trxID, float $amount, string $currency = 'BDT'): bool
    {
        if (empty($postData['val_id'])) {
            return false;
        }

        $url = $this->apiValidationUrl
            . '?val_id='       . urlencode($postData['val_id'])
            . '&store_id='     . urlencode($this->storeId)
            . '&store_passwd=' . urlencode($this->storePasswd)
            . '&v=1&format=json';

        $response = $this->curlGet($url);

        if (empty($response) || ! isset($response['status'])) {
            return false;
        }

        return
            in_array($response['status'], ['VALID', 'VALIDATED'], true)
            && (float) $response['amount'] >= $amount
            && $response['currency_type'] === $currency
            && $response['tran_id']       === $trxID;
    }

    /**
     * Validate SSLCommerz IPN hash to confirm the payload authenticity.
     *
     * @param  array<string, mixed> $postData
     */
    public function ipnHashCheck(array $postData): bool
    {
        if (empty($postData['verify_sign']) || empty($postData['verify_key'])) {
            return false;
        }

        $verifySign = $postData['verify_sign'];
        $verifyKeys = explode(',', $postData['verify_key']);

        // Build key→value map from the verify_key list, append hashed store password
        $dataArr = [];
        foreach ($verifyKeys as $key) {
            if (isset($postData[$key])) {
                $dataArr[$key] = $postData[$key];
            }
        }
        $dataArr['store_passwd'] = md5($this->storePasswd);

        ksort($dataArr);

        $hashParts = [];
        foreach ($dataArr as $key => $value) {
            $hashParts[] = $key . '=' . $value;
        }

        return md5(implode('&', $hashParts)) === $verifySign;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function setDomainConfig(): void
    {
        $this->apiDomain        = $this->isLive
            ? 'https://securepay.sslcommerz.com'
            : 'https://sandbox.sslcommerz.com';

        $this->apiUrl           = $this->apiDomain . '/gwprocess/v4/api.php';
        $this->apiValidationUrl = $this->apiDomain . '/validator/api/validationserverAPI.php';
    }

    /** @return array<string, mixed>|null */
    private function curlPost(string $url, array $data): mixed
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode((string) $result, true);
    }

    /** @return array<string, mixed>|null */
    private function curlGet(string $url): mixed
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode((string) $result, true);
    }
}

