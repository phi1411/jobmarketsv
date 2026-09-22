<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformRoutesTest extends TestCase
{
    public function test_public_pages_keep_the_complete_product_interface(): void
    {
        $this->get('/viec-lam')
            ->assertOk()
            ->assertSee('Việc làm gần tôi')
            ->assertSee('Bộ lọc nâng cao')
            ->assertDontSee('thử nghiệm');

        $this->get('/mau-cv-sinh-vien')
            ->assertOk()
            ->assertSee('Mẫu CV')
            ->assertSee('Nội dung ban đầu')
            ->assertSee('create-cv-source-profile', false)
            ->assertSee('create-cv-source-existing', false)
            ->assertSee('create-cv-source-blank', false);

        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_json_api_is_dispatched_without_framework_csrf(): void
    {
        $this->postJson('/login', [
            'email' => 'invalid',
            'password' => 'wrong',
        ])->assertStatus(422);

        $this->getJson('/jobs?per_page=2')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
