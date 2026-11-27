<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Hr\InternalNotification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContextSwitcherNotificationTest extends TestCase
{
    protected User $user;
    protected User $otherUser;
    protected Company $company;
    protected Branch $mainBranch;
    protected Branch $makatiBranch;
    protected Branch $cebuBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'company_code' => 'ECS-CORP',
            'company_legal_name' => 'ECS Test Holdings Inc.',
            'company_trade_name' => 'ECS Bistro',
            'company_tax_id' => '111-222-333-000',
            'company_tax_type' => 'VAT Registered',
            'company_base_currency_code' => 'PHP',
            'company_timezone' => 'Asia/Manila',
            'fiscal_year_start_month' => 1,
            'company_country_code' => 'PH',
            'company_type' => 'Company',
            'company_is_active' => true,
        ]);

        $this->mainBranch = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'MB',
            'branch_name' => 'Main Branch',
            'branch_city' => 'Quezon City',
            'branch_type' => 'Store / Dine-In',
            'branch_is_active' => true,
        ]);

        $this->makatiBranch = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'MK',
            'branch_name' => 'Makati Branch',
            'branch_city' => 'Makati City',
            'branch_type' => 'Store / Dine-In',
            'branch_is_active' => true,
        ]);

        $this->cebuBranch = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'CB',
            'branch_name' => 'Cebu Branch',
            'branch_city' => 'Cebu City',
            'branch_type' => 'Store / Dine-In',
            'branch_is_active' => true,
        ]);

        $this->user = User::create([
            'username' => 'ecs_admin',
            'full_name' => 'John Peter Jabiguero',
            'email' => 'jp@ecs.test',
            'password' => bcrypt('password123'),
            'role' => 'Admin',
            'status' => 'Active',
            'branch_id' => $this->mainBranch->branch_id,
            'default_company_id' => $this->company->company_id,
            'default_branch_id' => $this->mainBranch->branch_id,
        ]);

        $this->otherUser = User::create([
            'username' => 'ecs_staff',
            'full_name' => 'Other Staff',
            'email' => 'other@ecs.test',
            'password' => bcrypt('password123'),
            'role' => 'Staff',
            'status' => 'Active',
            'branch_id' => $this->makatiBranch->branch_id,
            'default_company_id' => $this->company->company_id,
            'default_branch_id' => $this->makatiBranch->branch_id,
        ]);

        Cache::forget('user_nav_tree_v2_' . $this->user->id);
    }

    public function test_topbar_switcher_renders_multi_branch_chips_notif_pill_and_popup_sections(): void
    {
        InternalNotification::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->mainBranch->branch_id,
            'title' => 'Low Stock Alert',
            'message' => 'Arabica Beans fell below reorder level.',
            'type' => 'inventory',
            'is_read' => false,
        ]);

        InternalNotification::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->makatiBranch->branch_id,
            'title' => 'PO Approved',
            'message' => 'PO-2026-0012 has been approved.',
            'type' => 'purchasing',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->company_id,
                'active_branch_id' => $this->mainBranch->branch_id,
                'checked_company_ids' => [$this->company->company_id],
                'checked_branch_ids' => [$this->mainBranch->branch_id, $this->makatiBranch->branch_id],
            ])
            ->get(route('config.business-settings'));

        $response->assertStatus(200);
        $response->assertSee('id="entityContextSwitcher"', false);
        $response->assertSee('John Peter Jabiguero');
        $response->assertSee('Main Branch +1');
        $response->assertSee('ecsAvatarNotifPill', false);
        $response->assertSee('ecsNotifSectionBadge', false);
        $response->assertSee('Low Stock Alert');
        $response->assertSee('PO Approved');
        $response->assertSee('Profile settings');
        $response->assertSee('Manage branches');
        $response->assertSee('Sign out');
        $response->assertDontSee('class="icon-btn notification-btn"', false);
    }

    public function test_multi_branch_selection_updates_checked_branch_ids_in_session(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('context.scope'), [
                'company_ids' => [$this->company->company_id],
                'branch_ids' => [$this->mainBranch->branch_id, $this->makatiBranch->branch_id, $this->cebuBranch->branch_id],
                'active_company_id' => $this->company->company_id,
                'active_branch_id' => $this->mainBranch->branch_id,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'active_branch_id' => $this->mainBranch->branch_id,
                'checked_branch_ids' => [
                    $this->mainBranch->branch_id,
                    $this->makatiBranch->branch_id,
                    $this->cebuBranch->branch_id,
                ],
            ]);

        $response->assertSessionHas('checked_branch_ids', [
            $this->mainBranch->branch_id,
            $this->makatiBranch->branch_id,
            $this->cebuBranch->branch_id,
        ]);
    }

    public function test_user_can_mark_single_notification_and_all_notifications_as_read(): void
    {
        $n1 = InternalNotification::create([
            'user_id' => $this->user->id,
            'title' => 'First Notice',
            'message' => 'Details 1',
            'type' => 'general',
            'is_read' => false,
        ]);

        $n2 = InternalNotification::create([
            'user_id' => $this->user->id,
            'title' => 'Second Notice',
            'message' => 'Details 2',
            'type' => 'general',
            'is_read' => false,
        ]);

        $singleRes = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $n1->id));

        $singleRes->assertOk()
            ->assertJson([
                'success' => true,
                'id' => $n1->id,
                'unread_count' => 1,
            ]);

        $this->assertTrue($n1->fresh()->is_read);
        $this->assertFalse($n2->fresh()->is_read);

        $allRes = $this->actingAs($this->user)
            ->postJson(route('notifications.read-all'));

        $allRes->assertOk()
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);

        $this->assertTrue($n2->fresh()->is_read);
    }

    public function test_user_cannot_mark_another_users_private_notification_as_read(): void
    {
        $privateNotif = InternalNotification::create([
            'user_id' => $this->otherUser->id,
            'title' => 'Private Notice',
            'message' => 'For other user only',
            'type' => 'general',
            'is_read' => false,
        ]);

        $res = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $privateNotif->id));

        $res->assertStatus(403);
        $this->assertFalse($privateNotif->fresh()->is_read);
    }
}

