<?php
/**
 * Copy this file to config/mpesa.local.php and fill in the Daraja app values.
 * mpesa.local.php is for this machine only. Do not commit live secrets.
 *
 * Sandbox PayBill shortcode is 174379.
 * The sandbox passkey is published in the Daraja Lipa na M-Pesa Online docs.
 * Party B is the PayBill that receives the money. For a normal PayBill STK
 * setup it is the same number as the shortcode.
 */
return [
    'environment' => 'sandbox',
    'consumer_key' => '',
    'consumer_secret' => '',
    'shortcode' => '174379',
    'party_b' => '174379',
    'passkey' => '',
    'transaction_type' => 'CustomerPayBillOnline',
    'callback_url' => 'https://YOUR_PUBLIC_HTTPS_HOST/mpesa_callback.php',
    'account_reference_prefix' => 'VAYUMA',
    'transaction_desc' => 'VAYUMA Payment',
];
