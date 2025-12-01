<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Sso;

/**
 * Multipass helper for Brizy Cloud SSO.
 *
 * Implements AES-128-CBC encryption of the payload and HMAC-SHA256 signing.
 */
class Multipass
{
    private string $encryptionKey;
    private string $signatureKey;
    private string $initVector;

    public function __construct(string $secretKey)
    {
        // Derive 32-byte key material from the secret, then split into two 16-byte keys
        $keyMaterial = hash('SHA256', $secretKey, true);
        $this->encryptionKey = substr($keyMaterial, 0, 16);
        $this->signatureKey  = substr($keyMaterial, 16, 16);

        // Generate a 16-byte initialization vector from the encryption key
        $ivMaterial = hash('SHA256', $this->encryptionKey, true);
        $this->initVector = substr($ivMaterial, 0, 16);
    }

    /**
     * Encode and sign the payload to produce a Multipass token.
     *
     * @param array $payload Associative array.
     *
     * @return string URL-safe token to append to the SSO redirect URL.
     */
    public function encode(array $payload): string
    {
        // Convert payload to JSON
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        // Encrypt JSON with AES-128-CBC
        $encrypted = openssl_encrypt(
            $json,
            'AES-128-CBC',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $this->initVector
        );

        // Base64-url encode the encrypted segment
        $segment = $this->urlsafeBase64Encode($encrypted);

        // Sign the segment with HMAC-SHA256
        $signatureRaw = hash_hmac('SHA256', $segment, $this->signatureKey, true);
        $signature    = $this->urlsafeBase64Encode($signatureRaw);

        // Return token as "encrypted.signature"
        return $segment . '.' . $signature;
    }

    /**
     * URL-safe Base64 encode (RFC 4648 §5).
     *
     * @param string $input Raw binary data.
     *
     * @return string Base64-url encoded string without padding.
     */
    private function urlsafeBase64Encode(string $input): string
    {
        // Convert to base64, replace '+'/'/' with '-_' and trim '=' padding
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
