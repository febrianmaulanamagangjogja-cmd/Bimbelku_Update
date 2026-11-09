<?php

require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$mentor = User::where("email", "minho@guru.com")->first();
$profile = $mentor->teacherProfile;

$profile->update([
    "verified_at" => now(),
    "verification_stage" => "approved"
]);

$mentor->update([
    "status" => "active"
]);

echo "Updated successfully!\n";
echo "Profile verified_at: " . $profile->verified_at->toDateTimeString() . "\n";
echo "Profile verification_stage: " . $profile->verification_stage . "\n";
echo "User status: " . $mentor->status . "\n";
