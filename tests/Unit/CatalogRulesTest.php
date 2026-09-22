<?php

namespace Tests\Unit;

use App\Repositories\DropRepository;
use App\Repositories\ItemsHRepository;
use App\Repositories\ItemRepository;
use App\Repositories\RarityRepository;
use App\Repositories\RelicRepository;
use App\Repositories\SkillFileRepository;
use App\Repositories\ValhallaDatabase;
use Tests\TestCase;

class CatalogRulesTest extends TestCase
{
    public function test_items_h_parser_reads_name_and_last_category(): void
    {
        $source = <<<'H'
        { sinWA1 | sin01, "Stone Axe", "WA101", ITEMSIZE * 1, ITEMSIZE * 3, "Weapon", ITEM_CLASS_WEAPON_ONE },
        { sinWV1 | sin01  ,"Fist Snake Brace" ,"WV101", ITEMSIZE * 1, ITEMSIZE * 1,"Weapon", ITEM_CLASS_WEAPON_ONE },
        H;

        $catalog = (new ItemsHRepository)->parse($source);

        $this->assertSame('Stone Axe', $catalog['WA101']['name']);
        $this->assertSame('Fist Snake Brace', $catalog['WV101']['name']);
        $this->assertSame('Weapon', $catalog['WA101']['folder']);
    }

    public function test_drop_tokens_split_on_space_and_keep_gold_air(): void
    {
        $repo = $this->dropRepository();
        $parsed = $repo->parseItemTokens('WA101 Gold Air DA110', 10, 40, ['WA101' => 'Stone Axe']);

        $this->assertSame('WA101', $parsed[0]['code']);
        $this->assertSame('Stone Axe', $parsed[0]['name']);
        $this->assertSame('Gold', $parsed[1]['code']);
        $this->assertSame(10, $parsed[1]['gold_min']);
        $this->assertSame('Air', $parsed[2]['code']);
        $this->assertSame('Nada', $parsed[2]['name']);
    }

    public function test_rarity_common_is_remainder_of_ten_million(): void
    {
        $math = (new RarityRepository(app(ValhallaDatabase::class)))->remainder([
            2 => 1_000_000,
            3 => 500_000,
            4 => 100_000,
            5 => 10_000,
        ]);

        $this->assertSame(8_390_000, $math['common']);
        $this->assertFalse($math['overflow']);
    }

    public function test_rarity_overflow_zeroes_common(): void
    {
        $math = (new RarityRepository(app(ValhallaDatabase::class)))->remainder([
            2 => 9_000_000,
            3 => 2_000_000,
        ]);

        $this->assertTrue($math['overflow']);
        $this->assertSame(0, $math['common']);
    }

    public function test_skill_parser_requires_ten_numeric_values(): void
    {
        $repo = new SkillFileRepository(app(ValhallaDatabase::class));
        $contents = <<<'INI'
        [Lutador]
        ; Bônus no dano
        TB1S1A= 6, 10, 14, 18, 21, 24, 26, 28, 30, 37
        TB_BAD= 1 2 3
        INI;

        $parameters = $repo->parse($contents);

        $this->assertSame('TB1S1A', $parameters[0]['key']);
        $this->assertSame('Bônus no dano', $parameters[0]['label']);
        $this->assertTrue($parameters[0]['valid']);
        $this->assertFalse($parameters[1]['valid']);

        $this->expectException(\InvalidArgumentException::class);
        $repo->assertTenValues([1, 2, 3]);
    }

    public function test_relic_slot_eleven_is_locked(): void
    {
        $repo = new RelicRepository(app(ValhallaDatabase::class));

        $this->assertSame(11, $repo->lockedSlot());
        $this->expectException(\DomainException::class);
        $repo->assertEditableSlot(11);
    }

    private function dropRepository(): DropRepository
    {
        return new DropRepository(
            app(ValhallaDatabase::class),
            app(ItemRepository::class),
        );
    }
}
