<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained()->cascadeOnDelete();
            $table->boolean('consent_given');
            $table->boolean('permission_for_learning_advocacy')->default(false);
            $table->boolean('permission_for_photos')->default(false);
            $table->string('consent_statement_version')->default('v1');
            $table->string('id_document_path')->nullable();  // photo of the scanned/photographed ID
            $table->string('signature_path')->nullable();    // signature captured on the pad, same session
            $table->string('respondent_photo_path')->nullable();
            $table->string('voice_note_path')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
