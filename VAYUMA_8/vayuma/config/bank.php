<?php
/**
 * VAYUMA bank-transfer payment configuration.
 * Replace the placeholder values with the VAYUMA account details you want buyers to use.
 * Do not place personal banking credentials here; this file is only for the receiving business account details.
 */
return [
    'bank_name' => getenv('VAYUMA_BANK_NAME') ?: 'YOUR BANK NAME',
    'account_name' => getenv('VAYUMA_BANK_ACCOUNT_NAME') ?: 'VAYUMA',
    'account_number' => getenv('VAYUMA_BANK_ACCOUNT_NUMBER') ?: 'YOUR ACCOUNT NUMBER',
    'branch' => getenv('VAYUMA_BANK_BRANCH') ?: 'YOUR BRANCH',
    'swift_code' => getenv('VAYUMA_BANK_SWIFT') ?: '',
    'payment_reference_prefix' => getenv('VAYUMA_BANK_REFERENCE_PREFIX') ?: 'VAYUMA',
];
