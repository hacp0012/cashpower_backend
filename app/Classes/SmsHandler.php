<?php

namespace App\Classes;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsHandler
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    /** CONVERT ACCENT CAHR -> NO ACCENT CHARS.
     *
     * ```php
     * // Exemple :
     * $texte = "Élève très motivé à l'école";
     * echo removeAccents($texte); // Affiche : Eleve tres motive a l'ecole
     * ```
     */
    static function removeAccents(string $text): string
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    }

    # SEND SMS.
    static public function send(string $sms, ?string $phoneNumber = null, string|null $senderName = null): bool
    {
        if (env('SMS_API_DISABLE', false)) return false;
        if ($phoneNumber == null) return false;

        $url = env('SMS_API_URL', null);

        $phoneNumber = Str::replace('-', '', $phoneNumber);

        $data = [
            "api_id" => env('SMS_API_ID', ''),
            "api_password" => env('SMS_API_PASSWORD', ''),
            "sms_type" => "T",
            "encoding" => "UFS",
            "sender_id" => $senderName ?: env('SMS_API_SENDER_NAME', 'CFC'),
            "phonenumber" => $phoneNumber,
            "textmessage" => SmsHandler::removeAccents($sms),
            // "templateid" => "null",
            // "V1" => null,
            // "V2" => null,
            // "V3" => null,
            // "V4" => null,
            // "V5" => null,
            // "ValidityPeriodInSeconds" => 60,
            // "uid" => "xyz",
            // "callback_url" => "https://xyz.com/",
            // "pe_id" => NULL,
            // "template_id" => NULL
        ];

        if ($url) {
            try {
                $requestResponse = Http::post(url: $url, data: $data);
                /*  RESPONSE FORMAT :
          {
            "message_id": 4125,
            "status": "S", // 'S' : success | 'F' : Failed
            "remarks": "Message Submitted Successfully" ,
            “uid”: “xyz”
          }
        */

                if ($requestResponse->successful()) {
                    $response = $requestResponse->json(key: 'status', default: null);

                    if ($response && $response == 'S') return true;
                    else return false;
                }
            } catch (Exception $e) {
                return true; // Defaultly.
            }
        }
        return false;
    }
}
