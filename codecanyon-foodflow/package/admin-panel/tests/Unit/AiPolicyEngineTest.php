<?php

namespace Tests\Unit;

use App\Services\Ai\AiPolicyEngine;
use App\Services\Ai\AiSettingsService;
use PHPUnit\Framework\TestCase;

class AiPolicyEngineTest extends TestCase
{
    public function test_disabled_ai_blocks_actions(): void
    {
        $policy = new AiPolicyEngine(new FakeAiSettingsService([
            'ai_enabled' => false,
            'ai_kill_switch' => false,
        ]));

        $result = $policy->evaluate(['tool' => 'create_gig', 'risk' => 'medium']);

        $this->assertFalse($result['allowed']);
        $this->assertTrue($result['requires_approval']);
    }

    public function test_read_tools_are_allowed_when_ai_enabled(): void
    {
        $policy = new AiPolicyEngine(new FakeAiSettingsService([
            'ai_enabled' => true,
            'ai_kill_switch' => false,
        ]));

        $result = $policy->evaluate(['tool' => 'get_business_summary', 'read_only' => true]);

        $this->assertTrue($result['allowed']);
        $this->assertFalse($result['requires_approval']);
    }

    public function test_monitor_mode_simulates_and_requires_approval_for_actions(): void
    {
        $policy = new AiPolicyEngine(new FakeAiSettingsService([
            'ai_enabled' => true,
            'ai_kill_switch' => false,
            'ai_autonomy_mode' => 'monitor',
            'ai_simulation_mode' => true,
            'ai_low_risk_auto_enabled' => false,
            'ai_max_action_amount' => 1000,
        ]));

        $result = $policy->evaluate([
            'tool' => 'create_gig',
            'risk' => 'low',
            'params' => ['maximum_budget' => 50],
        ]);

        $this->assertTrue($result['allowed']);
        $this->assertTrue($result['simulation']);
        $this->assertTrue($result['requires_approval']);
    }

    public function test_amount_guardrail_blocks_large_actions(): void
    {
        $policy = new AiPolicyEngine(new FakeAiSettingsService([
            'ai_enabled' => true,
            'ai_kill_switch' => false,
            'ai_autonomy_mode' => 'auto',
            'ai_simulation_mode' => false,
            'ai_low_risk_auto_enabled' => true,
            'ai_max_action_amount' => 100,
        ]));

        $result = $policy->evaluate([
            'tool' => 'create_customer_offer',
            'risk' => 'low',
            'params' => ['maximum_budget' => 500],
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertTrue($result['requires_approval']);
    }
}

class FakeAiSettingsService extends AiSettingsService
{
    public function __construct(private readonly array $values)
    {
    }

    public function all(): array
    {
        return $this->values + AiSettingsService::DEFAULTS;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
