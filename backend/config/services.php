<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway
    |--------------------------------------------------------------------------
    |
    | Drives the /api/v1/communication/sms endpoint. Leave `driver` empty
    | (or unset SMS_DRIVER in .env) to disable SMS sending entirely; the
    | endpoint will return 503 with a clear "not configured" message and
    | will not pretend that messages were delivered.
    |
    | Supported drivers (to be implemented when we sign with a provider):
    |   - termii          (Termii, Nigeria)
    |   - twilio          (Twilio, global)
    |   - africastalking  (Africa's Talking, Africa)
    |
    | Each driver expects its credentials under its own key below.
    |
    */
    'sms' => [
        'driver'      => env('SMS_DRIVER'),
        'sender_id'   => env('SMS_SENDER_ID', 'CASI360'),

        'termii' => [
            'api_key' => env('SMS_TERMII_API_KEY'),
            'channel' => env('SMS_TERMII_CHANNEL', 'generic'),
        ],
        'twilio' => [
            'sid'         => env('SMS_TWILIO_SID'),
            'auth_token'  => env('SMS_TWILIO_AUTH_TOKEN'),
            'from_number' => env('SMS_TWILIO_FROM'),
        ],
        'africastalking' => [
            'api_key'  => env('SMS_AT_API_KEY'),
            'username' => env('SMS_AT_USERNAME'),
        ],
    ],

];
