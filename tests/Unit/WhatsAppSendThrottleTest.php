<?php

namespace Tests\Unit;

use App\Services\WhatsAppSendThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WhatsAppSendThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_calculate_delay_low_volume_is_within_base_range(): void
    {
        Config::set('whatsapp.send_throttle.volume_tiers', []);

        $svc = new WhatsAppSendThrottle();

        $min = 5;
        $max = 10;
        $delay = $svc->calculateDelaySeconds(0, $min, $max);

        $this->assertGreaterThanOrEqual($min, $delay);
        $this->assertLessThanOrEqual($max, $delay);
    }

    public function test_volume_bonus_is_zero_for_low_count_with_default_tiers(): void
    {
        Config::set('whatsapp.send_throttle.volume_tiers', [
            ['threshold' => 11, 'bonus_min' => 5,  'bonus_max' => 15],
            ['threshold' => 31, 'bonus_min' => 15, 'bonus_max' => 30],
            ['threshold' => 51, 'bonus_min' => 30, 'bonus_max' => 60],
        ]);

        $svc = new WhatsAppSendThrottle();

        $this->assertSame(0, $svc->calculateVolumeBonus(0));
        $this->assertSame(0, $svc->calculateVolumeBonus(10));
    }

    public function test_volume_bonus_applies_for_high_count(): void
    {
        Config::set('whatsapp.send_throttle.volume_tiers', [
            ['threshold' => 11, 'bonus_min' => 5,  'bonus_max' => 15],
            ['threshold' => 31, 'bonus_min' => 15, 'bonus_max' => 30],
            ['threshold' => 51, 'bonus_min' => 30, 'bonus_max' => 60],
        ]);

        $svc = new WhatsAppSendThrottle();

        $bonus = $svc->calculateVolumeBonus(35);
        $this->assertGreaterThanOrEqual(15, $bonus);
        $this->assertLessThanOrEqual(30, $bonus);
    }

    public function test_calculate_delay_high_volume_includes_bonus_range(): void
    {
        Config::set('whatsapp.send_throttle.volume_tiers', [
            ['threshold' => 11, 'bonus_min' => 5, 'bonus_max' => 15],
        ]);

        $svc = new WhatsAppSendThrottle();

        $min = 5;
        $max = 10;
        $delay = $svc->calculateDelaySeconds(11, $min, $max);

        $this->assertGreaterThanOrEqual($min + 5, $delay);
        $this->assertLessThanOrEqual($max + 15, $delay);
    }

    public function test_wait_before_send_when_disabled_does_not_write_cache(): void
    {
        Config::set('whatsapp.send_throttle.enabled', false);

        $svc = new WhatsAppSendThrottle();
        $svc->waitBeforeSend();

        $this->assertFalse(Cache::has('waha_next_send_at'));
        $this->assertFalse(Cache::has('waha_send_count_5m'));
    }

    public function test_wait_before_send_when_enabled_writes_next_slot_and_count(): void
    {
        Config::set('whatsapp.send_throttle.enabled', true);
        Config::set('whatsapp.send_throttle.min_delay', 0);
        Config::set('whatsapp.send_throttle.max_delay', 0);
        Config::set('whatsapp.send_throttle.volume_tiers', []);

        $svc = new WhatsAppSendThrottle();
        $svc->waitBeforeSend();

        $this->assertTrue(Cache::has('waha_next_send_at'));
        $this->assertSame(1, (int) Cache::get('waha_send_count_5m'));
    }

    public function test_sequential_reservations_push_next_send_slot_forward(): void
    {
        Config::set('whatsapp.send_throttle.enabled', true);
        Config::set('whatsapp.send_throttle.min_delay', 5);
        Config::set('whatsapp.send_throttle.max_delay', 5);
        Config::set('whatsapp.send_throttle.volume_tiers', []);

        $svc = new WhatsAppSendThrottle();
        $svc->waitBeforeSend();

        $firstSlot = (int) Cache::get('waha_next_send_at');
        $this->assertGreaterThanOrEqual(time(), $firstSlot);

        $svc->waitBeforeSend();

        $secondSlot = (int) Cache::get('waha_next_send_at');
        $this->assertGreaterThanOrEqual($firstSlot + 5, $secondSlot);
    }
}
