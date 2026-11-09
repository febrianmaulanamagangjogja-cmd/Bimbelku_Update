<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\LearningPackage;
use App\Models\PackagePlan;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPaymentScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_qris_payment_method_shown_when_xendit_disabled(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $plan = PackagePlan::create([
            'name' => 'Paket Uji 2',
            'slug' => 'paket-uji-2',
            'session_count' => 1,
            'validity_days' => 30,
            'maximum_subjects' => 1,
            'is_active' => true,
        ]);
        $package = LearningPackage::create([
            'student_id' => $student->id,
            'package_plan_id' => $plan->id,
            'package_code' => 'BKU-UTV-002',
            'education_level' => 'SMP',
            'grade' => 'Kelas 7',
            'learning_mode' => 'online',
            'duration_hours' => 1,
            'status' => 'awaiting_payment',
            'total_sessions' => 1,
            'subtotal_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'payment_due_at' => now()->addDay(),
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'learning_package_id' => $package->id,
            'amount' => 100000,
            'status' => 'pending',
            'payment_proof' => null,
            'order_id' => 'PKG-20261031-1-3',
            'subtotal_amount' => 100000,
        ]);

        PaymentSetting::create([
            'singleton_key' => 1,
            'bank_name' => 'Bank BCA',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ]);

        $response = $this->actingAs($student)
            ->getJson('/api/payment-settings');

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'bank_name' => 'Bank BCA',
            'account_number' => '1234567890',
            'account_name' => 'BimbelKu Official',
        ]);
    }
}