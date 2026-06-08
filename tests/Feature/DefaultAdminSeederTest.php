<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_is_created_with_the_documented_credentials(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('change-me-now', $admin->password));
    }
}
