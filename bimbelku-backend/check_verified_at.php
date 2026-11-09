<?php

require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$mentor = User::where("email", "minho@guru.com")->first();
$profile = $mentor->teacherProfile;

echo "User status: {$mentor->status}\n";
echo "Profile verified_at: " . ($profile->verified_at ? $profile->verified_at->toDateTimeString() : "NULL") . "\n";
echo "Profile verification_stage: {$profile->verification_stage}\n";
