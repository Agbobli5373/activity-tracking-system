<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Email or Employee ID');
        $response->assertSee('Password');
        $response->assertSee('Remember me');
        $response->assertSee('Log in');
    }

    public function test_login_screen_has_proper_form_elements()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="login"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('type="submit"', false);
    }

    public function test_login_screen_includes_csrf_protection()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }
}