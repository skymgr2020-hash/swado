<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use Illuminate\Console\Command;

class CheckAdminMfaConfiguration extends Command
{
    protected $signature = 'admin:mfa-check';

    protected $description = 'Check admin recovery SMS configuration without sending SMS or exposing credentials';

    public function handle(): int
    {
        $provider = strtolower(trim((string) AppSetting::getValue('otp_service_provider', '')));
        $required = match ($provider) {
            'twilio' => ['twilio_account_sid', 'twilio_auth_token', 'twilio_phone_number'],
            'exotel' => ['exotel_sid', 'exotel_api_key', 'exotel_api_token', 'exotel_subdomain', 'exotel_sender_id'],
            'msg91' => ['msg91_authkey', 'msg91_otp_template_id'],
            default => [],
        };
        if (! $required || ($provider === 'msg91' && AppSetting::getValue('msg91_otp_mode', 'widget') !== 'api')) {
            $this->error('Admin MFA requires Twilio, Exotel, or MSG91 API. Configure a supported provider before rollout.');

            return self::FAILURE;
        }
        $missing = array_filter($required, fn ($key) => trim((string) AppSetting::getValue($key, '')) === '');
        if ($missing) {
            $this->error('Missing SMS settings: ' . implode(', ', $missing));

            return self::FAILURE;
        }
        $this->info('Admin MFA SMS configuration is present (' . $provider . '). No SMS was sent.');
        $this->warn('This checks configuration only. Confirm real SMS delivery in staging before rollout.');

        return self::SUCCESS;
    }
}
