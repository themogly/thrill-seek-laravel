<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // Marks a customer whose personal/medical data has been erased (GDPR);
            // anonymised financial records are kept, the row stays for referential integrity.
            $table->timestamp('erased_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('erased_at');
        });
    }
};
