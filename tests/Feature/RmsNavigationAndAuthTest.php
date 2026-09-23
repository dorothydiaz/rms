<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RmsNavigationAndAuthTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('username', 'peter')->first();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'identity' => 'peter',
            'password' => 'Admin@12345',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'identity' => 'peter',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->getAdminUser();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login?logged_out=1');
        $this->assertGuest();
    }

    public function test_all_hr_pages_render_for_authenticated_user(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'hr.dashboard',
            'hr.employee',
            'hr.users-auth',
            'hr.attendance-schedule',
            'hr.attendance-checkin',
            'hr.employee-leave',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('Restaurant Management System');
        }
    }

    public function test_all_sales_pages_render_for_authenticated_user(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'sales.dashboard',
            'sales.daily-sales',
            'sales.payment-report',
            'sales.reconciliations',
            'sales.discount-config',
            'sales.voucher-config',
            'sales.bundle-promotions',
            'sales.customer-masterlist',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('Restaurant Management System');
        }
    }

    public function test_all_inventory_pages_render_for_authenticated_user(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'inventory.dashboard',
            'inventory.stocks-overview',
            'inventory.beg-balance',
            'inventory.stock-in',
            'inventory.stock-out',
            'inventory.stock-adjustment',
            'inventory.waste-expiry',
            'inventory.product-categories',
            'inventory.recipe-management',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('Restaurant Management System');
        }
    }

    public function test_all_purchase_pages_render_for_authenticated_user(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'purchase.dashboard',
            'purchase.request-quotations',
            'purchase.purchase-orders',
            'purchase.vendor-masterlist',
            'purchase.vendor-bills',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('Restaurant Management System');
        }
    }

    public function test_all_config_and_credits_pages_render_for_authenticated_user(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'config.business-settings',
            'config.account-settings',
            'credits.tickets',
            'credits.developers',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('Restaurant Management System');
        }
    }
}
