<?php

require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\TeacherProfile;

$mentor = User::where("email", "minho@guru.com")->first();

echo "=== SEBELUM UPDATE ===\n";
echo "User ID: {$mentor->id}\n";
echo "Email: {$mentor->email}\n";
echo "Status: {$mentor->status}\n";
echo "Verified at: " . ($mentor->verified_at ?? "NULL") . "\n";

$profile = $mentor->teacherProfile;
echo "Verification Stage: {$profile->verification_stage}\n";
echo "Documents checked at: " . ($profile->documents_checked_at ?? "NULL") . "\n";
echo "WhatsApp test passed at: " . ($profile->whatsapp_test_passed_at ?? "NULL") . "\n";
echo "Verified by: {$profile->verified_by}\n";
echo "Verification notes: " . ($profile->verification_notes ?? "NULL") . "\n";

echo "\n=== UPDATE AKUN ===\n";
$mentor->update(["status" => "active", "verified_at" => now()]);

$profile->update([
    "verification_stage" => "whatsapp_passed",
    "whatsapp_test_passed_at" => now(),
    "documents_checked_at" => now(),
    "verified_by" => 1,
    "verification_notes" => "Verifikasi tutor disetujui oleh admin. Akun aktif dan siap digunakan.",
]);

$mentor->refresh();
$profile->refresh();

echo "\n=== SETELAH UPDATE ===\n";
echo "Status: {$mentor->status}\n";
echo "Verified at: {$mentor->verified_at}\n";
echo "Verification Stage: {$profile->verification_stage}\n";
echo "WhatsApp test passed at: {$profile->whatsapp_test_passed_at}\n";
echo "Documents checked at: {$profile->documents_checked_at}\n";
echo "Verification notes: {$profile->verification_notes}\n";

echo "\nAKUN MINHO TELAH DIAKTIFKAN.\n";
