<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AdminMfaSms
{
    public function send(string $phone, string $code): void
    {
        $sms = app(SmsService::class);
        $provider = $sms->otpProvider();
        if (! in_array($provider, ['twilio', 'exotel', 'msg91'], true)
            || ($provider === 'msg91' && $sms->usesMsg91WidgetOtp())) {
            throw new RuntimeException('Configure Twilio, Exotel, or MSG91 API for admin mobile verification.');
        }

        if ($provider !== 'msg91') {
            $template = NotificationTemplate::where('key', 'sms.otp')->where('is_active', true)->value('body')
                ?: 'Your OTP code is {{otp}}. It is valid for 5 minutes.';
            $message = str_replace('{{otp}}', $code, $template);
            $this->sendMessage($provider, $phone, $message);

            return;
        }

        // The shared MSG91 login method generates its own OTP. Admin recovery
        // explicitly sends our challenge-specific code so another login OTP cannot authorize it.
        $key = trim((string) AppSetting::getValue('msg91_authkey', ''));
        $template = trim((string) AppSetting::getValue('msg91_otp_template_id', ''));
        if ($key === '' || $template === '') {
            throw new RuntimeException('MSG91 API credentials or OTP template are missing.');
        }
        $response = Http::timeout(15)->withHeaders(['authkey' => $key])->acceptJson()
            ->post('https://control.msg91.com/api/v5/otp?' . http_build_query([
                'template_id' => $template, 'mobile' => ltrim($phone, '+'),
                'otp' => $code, 'otp_length' => 6, 'otp_expiry' => 5,
            ]), []);
        if (! $response->successful() || $response->json('type') !== 'success') {
            throw new RuntimeException('Admin verification SMS could not be sent. Please check the SMS provider configuration.');
        }
    }

    private function sendMessage(string $provider, string $phone, string $message): void
    {
        // Do not use generic SMS error logging: provider bodies can echo OTPs and phone numbers.
        if ($provider === 'twilio') {
            $sid = $this->setting('twilio_account_sid');
            $key = $sid;
            $token = $this->setting('twilio_auth_token');
            $sender = $this->setting('twilio_phone_number');
            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';
        } else {
            $sid = $this->setting('exotel_sid');
            $key = $this->setting('exotel_api_key');
            $token = $this->setting('exotel_api_token');
            $sender = $this->setting('exotel_sender_id');
            $host = $this->setting('exotel_subdomain');
            $url = 'https://' . $host . '/v1/Accounts/' . rawurlencode($sid) . '/Sms/send.json';
        }
        $response = Http::timeout(15)->withBasicAuth($key, $token)->asForm()
            ->post($url, ['From' => $sender, 'To' => $phone, 'Body' => $message]);
        if (! $response->successful()) {
            throw new RuntimeException('Admin verification SMS delivery failed.');
        }
    }

    private function setting(string $name): string
    {
        $value = trim((string) AppSetting::getValue($name, ''));
        if ($value === '') {
            throw new RuntimeException('Admin SMS provider configuration is incomplete.');
        }

        return $value;
    }
}
