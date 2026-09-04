<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->index();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->longText('content')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('status')->default('draft')->index(); // draft, review, approved, published
            $table->boolean('is_featured')->default(false);
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['reading', 'download']);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('amount_ugx');
            $table->unsignedBigInteger('momo_amount_ugx')->nullable();
            $table->boolean('allows_download')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_package_id')->constrained()->cascadeOnDelete();
            $table->string('method'); // gateway, momo_manual
            $table->string('provider')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('amount_ugx');
            $table->string('provider_reference')->nullable()->index();
            $table->string('last5_reference')->nullable()->index();
            $table->enum('status', ['pending', 'paid', 'failed', 'rejected'])->default('pending')->index();
            $table->json('provider_payload')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reading_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('can_download')->default(false);
            $table->string('device_hash')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['public_user_id', 'publication_id']);
        });

        Schema::create('geographic_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('geographic_units')->cascadeOnDelete();
            $table->enum('type', ['district', 'county', 'sub_county', 'parish', 'village']);
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geographic_unit_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->longText('content')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stakeholder_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('meeting_type')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->text('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->json('action_points')->nullable();
            $table->timestamps();
        });

        Schema::create('par_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_number');
            $table->enum('stage', ['plan', 'act', 'observe', 'reflect'])->default('plan');
            $table->string('title');
            $table->text('problem')->nullable();
            $table->longText('activities')->nullable();
            $table->longText('observations')->nullable();
            $table->longText('reflection')->nullable();
            $table->longText('decisions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'cycle_number']);
        });

        Schema::create('advocacy_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('geographic_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('problem')->nullable();
            $table->longText('evidence')->nullable();
            $table->longText('community_voices')->nullable();
            $table->longText('recommendations')->nullable();
            $table->string('target_decision_maker')->nullable();
            $table->enum('status', ['identified', 'evidence_collected', 'engagement', 'action', 'resolved'])->default('identified');
            $table->timestamps();
        });

        Schema::create('finance_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('quickbooks');
            $table->string('file_path')->nullable();
            $table->unsignedInteger('rows_imported')->default(0);
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finance_import_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('type'); // income, expense, transfer
            $table->string('account')->nullable();
            $table->string('category')->nullable();
            $table->string('project_code')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('UGX');
            $table->timestamps();
            $table->index(['organization_id', 'transaction_date']);
        });

        Schema::create('presentation_decks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->json('slides')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('publication_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('public_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('geographic_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['publication_comments','presentation_decks','finance_transactions','finance_imports','advocacy_issues','par_cycles','stakeholder_meetings','development_plans','geographic_units','reading_accesses','payments','access_packages','publications','public_users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
