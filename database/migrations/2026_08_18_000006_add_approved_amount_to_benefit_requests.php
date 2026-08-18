<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('benefit_requests', function (Blueprint $table) {
            $table->decimal('approved_amount', 12, 2)->nullable()->after('requested_amount');
        });
    }

    public function down(): void
    {
        Schema::table('benefit_requests', fn (Blueprint $table) => $table->dropColumn('approved_amount'));
    }
};
