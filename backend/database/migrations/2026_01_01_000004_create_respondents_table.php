<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One row per unique person ever interviewed, system-wide.
        // This is what makes "an ID/signature can't be used twice" possible:
        // we hash the ID number and refuse to create a second respondent
        // with the same hash. A fuzzy backup key (name+dob+village) catches
        // people without a formal ID card.
        Schema::create('respondents', function (Blueprint $table) {
            $table->id();
            // Dedup is scoped to one organisation — two different client
            // organisations are not compared against each other's respondents.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('respondent_code'); // human-readable, e.g. RSP-000123 — unique within the organisation
            $table->string('full_name');
            $table->enum('sex', ['female', 'male', 'other'])->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('id_type')->nullable(); // National ID, LC letter, Voter's card, none...
            $table->string('id_number_hash')->nullable(); // SHA-256, never store raw ID number in the clear — unique within the organisation
            $table->string('id_number_last4')->nullable(); // for human lookup/help-desk only
            $table->string('fuzzy_key')->nullable()->index(); // sha256(name+dob/age+village) fallback dedup
            $table->string('village')->nullable();
            $table->string('parish')->nullable();
            $table->string('sub_county')->nullable();
            $table->string('district')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'respondent_code']);
            $table->unique(['organization_id', 'id_number_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respondents');
    }
};
