<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This is the heart of the "flexible for many more projects" requirement.
        // Every data-collection instrument (household survey, attendance sheet,
        // distribution list, monitoring checklist, etc.) is stored as a versioned
        // JSON schema here instead of being hard-coded — so the ED / M&E Officer
        // can create a brand-new form for a brand-new project without touching code.
        Schema::create('project_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->unsignedInteger('version')->default(1);
            $table->json('form_schema'); // sections -> fields definition (see docs)
            $table->boolean('requires_consent')->default(true);
            $table->boolean('requires_signature')->default(true);
            $table->boolean('requires_id_capture')->default(true);
            $table->boolean('requires_photo')->default(true);
            $table->boolean('allows_voice_note')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['project_id', 'slug', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_forms');
    }
};
