<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->enum('type', ['weekly_activity', 'monthly_activity', 'monthly_me', 'quarterly_progress', 'annual']);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('auto_stats'); // system-computed numbers (counts, disaggregation, vulnerability distribution...)
            $table->json('narrative')->nullable(); // officer-written sections (achievements, challenges, lessons, next steps)
            $table->enum('status', ['draft', 'submitted_for_review', 'approved', 'published'])->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
