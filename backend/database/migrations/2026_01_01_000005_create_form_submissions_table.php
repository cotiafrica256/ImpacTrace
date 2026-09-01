<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_code')->unique(); // e.g. SUB-2026-000001
            $table->foreignId('project_id')->constrained();
            $table->foreignId('project_form_id')->constrained();
            $table->foreignId('respondent_id')->nullable()->constrained();
            $table->foreignId('collected_by')->constrained('users'); // field officer
            $table->date('activity_date'); // date of the visit/interview, for weekly/monthly/attendance grouping
            $table->string('village')->nullable();
            $table->string('parish')->nullable();
            $table->string('sub_county')->nullable();
            $table->string('district')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->json('answers'); // the actual filled-in form data, keyed by field id
            $table->unsignedTinyInteger('vulnerability_score')->nullable(); // section O total /80 style scores
            $table->string('vulnerability_class')->nullable();
            $table->enum('status', ['draft', 'submitted', 'flagged_duplicate', 'reviewed', 'approved'])->default('submitted');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('synced_at')->nullable(); // when an offline-collected record reached the server
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
