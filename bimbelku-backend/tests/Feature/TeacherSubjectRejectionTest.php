<?php

namespace Tests\Feature;

use App\Models\CurriculumSubject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherSubjectRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_subject_rejection_flow(): void
    {
        // 1. Setup Admin
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email' => 'admin@bimbelku.test',
        ]);

        // 2. Setup Curriculum Subjects
        $math = CurriculumSubject::create([
            'name' => 'Matematika',
            'normalized_name' => 'matematika',
            'education_levels' => ['SD', 'SMP', 'SMA'],
            'is_active' => true,
        ]);

        $japanese = CurriculumSubject::create([
            'name' => 'Bahasa Jepang',
            'normalized_name' => 'bahasa-jepang',
            'education_levels' => ['SD', 'SMP', 'SMA'],
            'is_active' => true,
        ]);

        // 3. Setup active Teacher with Matematika
        $teacher = User::factory()->create([
            'email' => 'budi@guru.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $profile = TeacherProfile::create([
            'user_id' => $teacher->id,
            'verification_stage' => 'approved',
            'verified_at' => now(),
            'is_accepting_requests' => true,
            'expertise' => 'Matematika',
        ]);

        TeacherSubject::create([
            'teacher_profile_id' => $profile->id,
            'name' => 'Matematika',
            'curriculum_subject_id' => $math->id,
            'levels' => ['SD', 'SMP'],
            'is_active' => true,
            'is_online' => true,
            'is_offline' => false,
            'is_private_active' => true,
            'is_group_active' => false,
        ]);

        // Verify initial state
        $this->assertEquals('Matematika', $teacher->fresh()->subjects->first()->name);

        // 4. Teacher requests subject change to Bahasa Jepang
        $token = $teacher->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher/subjects', [
                'subjects' => [
                    [
                        'name' => 'Bahasa Jepang',
                        'levels' => ['SD', 'SMP'],
                        'is_online' => true,
                        'is_offline' => false,
                        'is_private_active' => true,
                        'is_group_active' => false,
                    ]
                ]
            ]);

        $response->assertOk()
            ->assertJsonPath('reverification_required', true);

        // Check DB state after request: status should be pending, subject in DB is Bahasa Jepang, previous_subjects contains Matematika
        $this->assertEquals('pending', $teacher->fresh()->status);
        $this->assertEquals('Bahasa Jepang', $teacher->fresh()->subjects->first()->name);
        $this->assertNotNull($profile->fresh()->previous_subjects);
        $this->assertEquals('Matematika', $profile->fresh()->previous_subjects[0]['name']);

        // 5. Admin rejects the request
        Sanctum::actingAs($admin);
        $rejectResponse = $this->postJson('/api/admin/verify-teacher', [
            'user_id' => $teacher->id,
            'status' => 'rejected',
            'notes' => 'Alasan penolakan: Kualifikasi Bahasa Jepang tidak mencukupi.',
        ]);

        $rejectResponse->assertOk();

        // 6. Verify DB state after rejection
        $teacherFresh = $teacher->fresh();
        $profileFresh = $profile->fresh();

        // Status must be rejected
        $this->assertEquals('rejected', $teacherFresh->status);
        
        // Subject MUST be restored back to Matematika
        $this->assertEquals(1, $teacherFresh->subjects->count());
        $this->assertEquals('Matematika', $teacherFresh->subjects->first()->name);
        $this->assertEquals($math->id, $teacherFresh->subjects->first()->curriculum_subject_id);

        // previous_subjects should be cleared
        $this->assertNull($profileFresh->previous_subjects);

        // Admin pending list should NOT include this rejected teacher
        $pendingListResponse = $this->getJson('/api/admin/pending-teachers');
        
        $pendingListResponse->assertOk();
        $pendingIds = collect($pendingListResponse->json())->pluck('id')->all();
        $this->assertNotContains($teacher->id, $pendingIds);
    }
}