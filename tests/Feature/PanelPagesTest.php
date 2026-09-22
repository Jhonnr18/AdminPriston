<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_painel(): void
    {
        $this->get('/')->assertRedirect('/painel');
    }

    public function test_overview_renders_in_demo_mode(): void
    {
        $this->get('/painel')->assertOk()->assertSee('Visão geral');
    }

    public function test_item_and_drop_pages_render(): void
    {
        $this->get('/painel/itens')->assertOk()->assertSee('Weapons');
        $this->get('/painel/familia?prefix=WV')->assertOk()->assertSee('WV');
        $this->get('/painel/drops')->assertOk();
        $this->get('/painel/skills')->assertOk();
        $this->get('/painel/raridade')->assertOk();
        $this->get('/painel/reliquias')->assertOk();
        $this->get('/painel/pvp')->assertOk()->assertSee('Não implementável');
        $this->get('/painel/recompensas')->assertOk()->assertSee('Sistema off');
        $this->get('/painel/servidor')->assertOk();
    }

    public function test_item_detail_json_includes_skin_data(): void
    {
        $this->getJson('/painel/itens/WA101')
            ->assertOk()
            ->assertJsonPath('code', 'WA101')
            ->assertJsonStructure(['skin' => ['current', 'available']]);
    }

    public function test_applying_skin_via_endpoint_simulates_in_demo_mode(): void
    {
        $this->postJson('/painel/itens/Weapons/WA101/skin', ['skin_code' => 'WA102'])
            ->assertOk()
            ->assertJsonPath('skin_code', 'WA102')
            ->assertJsonPath('simulated', true);
    }

    public function test_applying_incompatible_skin_via_endpoint_returns_422(): void
    {
        $this->postJson('/painel/itens/Weapons/WA101/skin', ['skin_code' => 'OR101'])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }
}
