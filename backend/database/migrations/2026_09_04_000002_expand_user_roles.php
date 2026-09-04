<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','ed','meo','po','fo','customer_service','reader_manager') NOT NULL DEFAULT 'fo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','ed','meo','po','fo') NOT NULL DEFAULT 'fo'");
    }
};