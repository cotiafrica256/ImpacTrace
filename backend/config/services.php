<?php
return [
 'momo' => [
  'provider' => env('MOMO_PROVIDER',''),
  'merchant_code' => env('MOMO_MERCHANT_CODE',''),
  'ussd_code' => env('MOMO_USSD_CODE',''),
   'base_url' => env('MOMO_BASE_URL',''),
   'api_key' => env('MOMO_API_KEY',''),
   'api_secret' => env('MOMO_API_SECRET',''),
   'webhook_secret' => env('MOMO_WEBHOOK_SECRET',''),
 ],
];
