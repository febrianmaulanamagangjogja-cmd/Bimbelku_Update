<?php

namespace Tests\Feature;

use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPaymentSettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_or_update_payment_settings(): void
    {
        $this->postJson('/api/admin/payment-settings', [
            'merchant_name' => 'BimbelKu',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ])->assertUnauthorized(); // HTTP 401
    }

    public function test_student_cannot_update_payment_settings(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        Sanctum::actingAs($student);

        $this->postJson('/api/admin/payment-settings', [
            'merchant_name' => 'BimbelKu',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ])->assertForbidden(); // HTTP 403
    }

    public function test_teacher_cannot_update_payment_settings(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        Sanctum::actingAs($teacher);

        $this->postJson('/api/admin/payment-settings', [
            'merchant_name' => 'BimbelKu',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ])->assertForbidden(); // HTTP 403
    }

    public function test_authorized_admin_can_update_payment_settings_with_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        // Validation test: invalid account number
        $this->postJson('/api/admin/payment-settings', [
            'merchant_name' => 'BimbelKu',
            'bank_name' => 'BCA',
            'account_number' => '123', // too short (< 6 digits)
            'account_name' => 'BimbelKu Official',
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['account_number']);

        // Valid payload
        $response = $this->postJson('/api/admin/payment-settings', [
            'merchant_name' => 'BimbelKu Official',
            'bank_name' => 'Bank Central Asia',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ])->assertOk();

        // Ensure no secret keys, passwords, or credentials leak in response
        $data = $response->json('data');
        $this->assertArrayNotHasKey('api_key', $data);
        $this->assertArrayNotHasKey('secret', $data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertEquals('Bank Central Asia', $data['bank_name']);
        $this->assertEquals('1234567890', $data['account_number']);
    }

    public function test_public_get_payment_settings_does_not_leak_secrets(): void
    {
        PaymentSetting::create([
            'singleton_key' => 1,
            'merchant_name' => 'BimbelKu',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ]);

        $response = $this->getJson('/api/payment-settings')->assertOk();
        $payload = $response->json();

        $this->assertArrayNotHasKey('api_key', $payload);
        $this->assertArrayNotHasKey('secret', $payload);
        $this->assertArrayNotHasKey('token', $payload);
        $this->assertEquals('BCA', $payload['bank_name']);
        $this->assertEquals('1234567890', $payload['account_number']);
    }
}
