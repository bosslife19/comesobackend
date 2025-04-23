<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client;

class messaging
{
    static function sendSms()
    {
        function sendSms($phone, $message)
        {
            $smsKey=getenv("SMS_KEY");
            try {
            $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$phone&from=COMESO&sms=$message");
            } catch (\Throwable $th) {
                \Log::info($th);
                
            }
           
        }
    }

    
}
