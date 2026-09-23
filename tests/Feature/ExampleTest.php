<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Unauthenticated guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Login page is accessible to guests.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Restaurant Management System');
        $response->assertSee('Sign In');
    }

    /**
     * Authenticated user can access the dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('username', 'peter')->first() ?? User::factory()->make([
            'username' => 'testuser',
            'full_name' => 'Test User',
            'email' => 'test@rms.local',
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Main dashboard content goes here.');
    }
}
