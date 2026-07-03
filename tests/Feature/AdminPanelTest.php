<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_a_user_can_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }

    public function test_guest_users_cannot_access_the_admin_panel(): void
    {
        $guest = User::factory()->create(['is_guest' => true]);

        $this->actingAs($guest)->get('/admin')->assertForbidden();
    }

    public function test_the_category_resource_lists_records(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Matematika']);

        $this->actingAs($user)
            ->get('/admin/categories')
            ->assertSuccessful()
            ->assertSee('Matematika');
    }
}
