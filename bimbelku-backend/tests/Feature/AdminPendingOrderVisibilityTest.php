<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\LearningPackage;
use App\Models\PackagePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPendingOrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_order_without_proof_is_visible_to_admin(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $plan = PackagePlan::create([
            'name' => 'Paket Uji',
            'slug' => 'paket-uji',
            'session_count' => 1,
            'validity_days' => 30,
            'maximum_subjects' => 1,
            'is_active' => true,
        ]);
        $package = LearningPackage::create([
            'student_id' => $student->id,
            'package_plan_id' => $plan->id,
            'package_code' => 'BKU-UTV-001',
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
            'order_id' => 'PKG-20260401-001',
            'subtotal_amount' => 100000,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/finance/payments');

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $order->id,
            'order_id' => 'PKG-20260401-001',
            'status' => 'pending',
        ]);
    }
}
