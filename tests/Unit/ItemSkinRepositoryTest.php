<?php

namespace Tests\Unit;

use App\Repositories\ItemSkinRepository;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemSkinRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_a_valid_skin_in_demo_mode_simulates_success(): void
    {
        $result = $this->skins()->setSkin('Weapons', 'WA101', 'WA102', 'tester', '127.0.0.1');

        $this->assertTrue($result['simulated']);
        $this->assertSame('WA101', $result['code']);
        $this->assertSame('WA102', $result['skin_code']);
    }

    public function test_removing_a_skin_with_null_is_accepted(): void
    {
        $result = $this->skins()->setSkin('Weapons', 'WA101', null, 'tester', '127.0.0.1');

        $this->assertTrue($result['simulated']);
        $this->assertNull($result['skin_code']);
    }

    public function test_skin_code_missing_from_items_h_catalog_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('não existe em items.h');

        $this->skins()->setSkin('Weapons', 'WA101', 'ZZ999', 'tester', '127.0.0.1');
    }

    public function test_incompatible_category_skin_is_rejected(): void
    {
        // WA101 é uma arma (pasta Weapon); OR101 é um anel (pasta Accessory).
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('incompatível');

        $this->skins()->setSkin('Weapons', 'WA101', 'OR101', 'tester', '127.0.0.1');
    }

    public function test_original_item_must_exist(): void
    {
        $this->expectException(DomainException::class);

        $this->skins()->setSkin('Weapons', 'ZZ999', 'WA102', 'tester', '127.0.0.1');
    }

    public function test_skin_cannot_equal_the_original_code(): void
    {
        $this->expectException(DomainException::class);

        $this->skins()->setSkin('Weapons', 'WA101', 'WA101', 'tester', '127.0.0.1');
    }

    public function test_invalid_table_is_rejected(): void
    {
        $this->expectException(DomainException::class);

        $this->skins()->setSkin('NotATable', 'WA101', 'WA102', 'tester', '127.0.0.1');
    }

    public function test_available_skins_only_lists_same_category_candidates(): void
    {
        $available = $this->skins()->availableSkinsFor('WA101');

        $codes = array_column($available, 'code');

        $this->assertContains('WA102', $codes);
        $this->assertNotContains('OR101', $codes);
        $this->assertNotContains('WA101', $codes, 'não deve sugerir o próprio código como skin');
    }

    private function skins(): ItemSkinRepository
    {
        return app(ItemSkinRepository::class);
    }
}
