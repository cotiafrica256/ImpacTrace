<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Each row is a client organisation using the platform (e.g. MECPA Uganda).
        // COTIA itself is not a row here — COTIA staff are 'super_admin' users with
        // no organization_id, sitting above every tenant.
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // short slug, e.g. "mecpa-uganda"
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            // No FK constraint here: organizations is created before the users table
            // exists (users belongs to an organization), and the reverse link
            // (which super_admin created this org) is informational, not relational.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
