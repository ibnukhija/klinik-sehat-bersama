<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_and_login_renders(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertStatus(200)->assertSee('Klinik Sehat Bersama');
    }
}
