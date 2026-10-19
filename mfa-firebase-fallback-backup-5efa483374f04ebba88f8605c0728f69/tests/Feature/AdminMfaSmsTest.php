<?php

namespace Tests\Feature;

use App\Exceptions\AdminMfaSmsException;
use App\Services\AdminMfaSms;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AdminMfaTestCase;

class AdminMfaSmsTest extends AdminMfaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('body');
            $table->boolean('is_active');
        });
    }

    public function test_msg91_explicitly_sends_challenge_specific_six_digit_code(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'msg91', 'msg91_otp_mode' => 'api', 'msg91_authkey' => 'test-key', 'msg91_otp_template_id' => 'test-template']);
        Http::fake(['control.msg91.com/*' => Http::response(['type' => 'success'])]);
        app(AdminMfaSms::class)->send('+919876543210', '123456');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'otp=123456')
            && str_contains($request->url(), 'otp_expiry=5') && str_contains($request->url(), 'otp_length=6')
            && $request->hasHeader('authkey', 'test-key'));
    }

    public function test_twilio_uses_existing_credentials_and_requested_destination(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'twilio', 'twilio_account_sid' => 'test-sid', 'twilio_auth_token' => 'test-token', 'twilio_phone_number' => '+12025550123']);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'test-message'], 201)]);
        app(AdminMfaSms::class)->send('+919876543210', '123456');
        Http::assertSent(fn ($request) => $request['To'] === '+919876543210' && str_contains($request['Body'], '123456'));
    }

    public function test_exotel_uses_existing_sms_configuration(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'exotel', 'exotel_sid' => 'test-sid', 'exotel_api_key' => 'test-key', 'exotel_api_token' => 'test-token', 'exotel_subdomain' => 'api.exotel.com', 'exotel_sender_id' => 'TEST']);
        Http::fake(['api.exotel.com/*' => Http::response(['SMSMessage' => ['Sid' => 'test-message']])]);
        app(AdminMfaSms::class)->send('+919876543210', '123456');
        Http::assertSent(fn ($request) => $request['To'] === '+919876543210' && str_contains($request['Body'], '123456'));
    }

    public function test_provider_failure_is_not_treated_as_success(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'msg91', 'msg91_otp_mode' => 'api', 'msg91_authkey' => 'test-key', 'msg91_otp_template_id' => 'test-template']);
        Http::fake(['control.msg91.com/*' => Http::response(['type' => 'error', 'code' => '418', 'message' => 'request contained 123456 for +919876543210'], 400)]);
        try {
            app(AdminMfaSms::class)->send('+919876543210', '123456');
            $this->fail('Provider rejection must fail.');
        } catch (AdminMfaSmsException $e) {
            $this->assertSame([
                'provider' => 'msg91',
                'reason' => 'provider_rejected',
                'status' => 400,
                'provider_code' => '418',
            ], $e->context());
            $this->assertStringNotContainsString('123456', $e->getMessage());
            $this->assertStringNotContainsString('+919876543210', $e->getMessage());
        }
    }

    public function test_unsupported_provider_is_rejected_without_sending_sms(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'firebase']);
        try {
            app(AdminMfaSms::class)->send('+919876543210', '123456');
            $this->fail('Firebase must be rejected.');
        } catch (AdminMfaSmsException $e) {
            $this->assertSame('unsupported_provider_or_mode', $e->reason);
            $this->assertStringContainsString('requires Twilio, Exotel, or MSG91 SendOTP API', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_msg91_widget_mode_has_an_actionable_error_without_sending_sms(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'msg91', 'msg91_otp_mode' => 'widget']);

        try {
            app(AdminMfaSms::class)->send('+919876543210', '123456');
            $this->fail('MSG91 widget mode must be rejected.');
        } catch (AdminMfaSmsException $e) {
            $this->assertSame('msg91', $e->provider);
            $this->assertSame('unsupported_provider_or_mode', $e->reason);
            $this->assertStringContainsString('Widget/SDK mode is not supported', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_preflight_checks_credentials_without_sending_sms(): void
    {
        Cache::put('app_settings', ['otp_service_provider' => 'twilio']);
        $this->artisan('admin:mfa-check')->assertExitCode(1);
        Cache::put('app_settings', ['otp_service_provider' => 'twilio', 'twilio_account_sid' => 'test-sid', 'twilio_auth_token' => 'test-token', 'twilio_phone_number' => '+12025550123']);
        $this->artisan('admin:mfa-check')->assertExitCode(0);
        Http::assertNothingSent();
    }
}
