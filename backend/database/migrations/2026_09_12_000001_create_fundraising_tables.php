<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fundraising_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->longText('message');
            $table->string('photo_url')->nullable();
            $table->unsignedBigInteger('target_amount_ugx')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fundraising_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundraising_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->string('donor_phone')->nullable();
            $table->unsignedBigInteger('amount_ugx');
            $table->string('method');
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable()->index();
            $table->string('last5_reference')->nullable()->index();
            $table->enum('status', ['pending', 'paid', 'failed', 'rejected'])->default('pending')->index();
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundraising_donations');
        Schema::dropIfExists('fundraising_campaigns');
    }
};
