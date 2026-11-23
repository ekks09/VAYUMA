<?php
/** IDs are confidential. Configure a stable base64-encoded 32-byte secret in
 * VAYUMA_FARMER_ID_KEY, backed up separately from the database. Losing or
 * changing it without re-encryption makes previously saved IDs unreadable. */
function vayuma_farmer_id_valid(string $value): bool {
    return (bool)preg_match('/^[0-9]{5,20}$/D', $value);
}
function vayuma_farmer_id_key(): string {
    if (!function_exists('sodium_crypto_secretbox')) throw new RuntimeException('ID encryption unavailable');
    $value = getenv('VAYUMA_FARMER_ID_KEY');
    $key = is_string($value) ? base64_decode($value, true) : false;
    if (!is_string($key) || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) throw new RuntimeException('ID encryption unavailable');
    return $key;
}
function vayuma_farmer_id_encrypt(string $value): string {
    if (!vayuma_farmer_id_valid($value)) throw new InvalidArgumentException('Invalid ID');
    $key = vayuma_farmer_id_key();
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $key));
}
function vayuma_farmer_id_decrypt(?string $encoded): string {
    if ($encoded === null || $encoded === '') return '';
    $key = vayuma_farmer_id_key();
    $bytes = base64_decode($encoded, true);
    if (!is_string($bytes) || strlen($bytes) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) throw new RuntimeException('ID decryption unavailable');
    $nonce = substr($bytes, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open(substr($bytes, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $key);
    if (!is_string($plain) || !vayuma_farmer_id_valid($plain)) throw new RuntimeException('ID decryption unavailable');
    return $plain;
}
