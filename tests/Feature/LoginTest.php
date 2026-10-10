<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_admin_can_log_in_and_is_sent_to_dashboard(): void
    {
        $admin = $this->admin();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_employee_can_log_in_and_is_sent_to_attendance(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('attendance.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_is_rejected(): void
    {
        $admin = $this->admin();

        $this->from('/login')
            ->post('/login', ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->from('/login')
            ->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_deactivated_employee_cannot_log_in(): void
    {
        [$user, $employee] = $this->employeeWithLogin();
        $employee->update(['status' => 'inactive']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        [$user] = $this->employeeWithLogin(userAttributes: ['is_active' => false]);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guests_are_redirected_away_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/attendance')->assertRedirect('/login');
    }

    public function test_logout_ends_the_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
