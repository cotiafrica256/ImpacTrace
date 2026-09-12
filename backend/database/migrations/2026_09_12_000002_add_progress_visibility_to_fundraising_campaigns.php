<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fundraising_campaigns', function (Blueprint $table) {
            $table->boolean('show_progress')->default(true)->after('target_amount_ugx');
        });
    }

    public function down(): void
    {
        Schema::table('fundraising_campaigns', function (Blueprint $table) {
            $table->dropColumn('show_progress');
        });
    }
};
