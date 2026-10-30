<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HrOrganizationDropdownTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::firstOrCreate(
            ['username' => 'peter'],
            [
                'full_name' => 'Peter Admin',
                'email' => 'peter@test.com',
                'password' => bcrypt('password123'),
                'role' => 'Admin',
                'status' => 'Active',
            ]
        );
    }

    public function test_organization_dropdown_renders_on_employee_management(): void
    {
        $user = $this->getAdminUser();
        $response = $this->actingAs($user)->get(route('hr.people.employees'));

        $response->assertStatus(200);
        $response->assertSee('hr-tab-dropdown-wrap', false);
        $response->assertSee('hr-tab-dropdown-trigger', false);
        $response->assertSee('toggleHrTabDropdown', false);
        $response->assertSee('Organization');
        $response->assertSee(route('hr.people.departments'));
        $response->assertSee(route('hr.people.positions'));
        $response->assertDontSee(route('hr.people.branches'));
        $response->assertDontSee(route('hr.people.companies'));
    }

    public function test_organization_dropdown_renders_active_state_on_organization_pages(): void
    {
        $user = $this->getAdminUser();

        // 1. Departments Page
        $resDept = $this->actingAs($user)->get(route('hr.people.departments'));
        $resDept->assertStatus(200);
        $resDept->assertSee('hr-tab-dropdown-trigger active', false);
        $resDept->assertSee(route('hr.people.positions'));

        // 2. Positions Page
        $resPos = $this->actingAs($user)->get(route('hr.people.positions'));
        $resPos->assertStatus(200);
        $resPos->assertSee('hr-tab-dropdown-trigger active', false);
        $resPos->assertDontSee(route('hr.people.branches'));
        $resPos->assertDontSee(route('hr.people.companies'));
    }

    public function test_members_modal_pagination_bar_is_positioned_above_modal_footer(): void
    {
        $user = $this->getAdminUser();

        foreach ([route('hr.people.departments'), route('hr.people.positions'), route('config.companies-branches.index')] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertStatus(200);

            $content = $response->getContent();
            $modalPos = strpos($content, 'id="membersRosterModal"');
            $this->assertNotFalse($modalPos, "membersRosterModal must exist in {$url}");
            $modalContent = substr($content, $modalPos);

            $paginationPos = strpos($modalContent, 'id="membersModalPaginationBar"');
            $footerPos = strpos($modalContent, 'class="hr-modal-footer"');

            $this->assertNotFalse($paginationPos, "membersModalPaginationBar must exist in {$url}");
            $this->assertNotFalse($footerPos, "hr-modal-footer must exist in {$url}");
            $this->assertLessThan($footerPos, $paginationPos, "Pagination bar must be positioned directly above modal footer in {$url}");
        }
    }
}

