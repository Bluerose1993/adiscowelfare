<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('benefit_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->decimal('received_amount', 12, 2)->nullable()->after('approved_amount');
            $table->timestamp('receipt_confirmed_at')->nullable()->after('approved_at');
        });

        DB::table('benefit_requests')
            ->whereIn('status', ['approved', 'paid'])
            ->whereNotNull('reviewed_at')
            ->update(['approved_at' => DB::raw('reviewed_at')]);
    }

    public function down(): void
    {
        Schema::table('benefit_requests', fn (Blueprint $table) => $table->dropColumn([
            'approved_at', 'received_amount', 'receipt_confirmed_at',
        ]));
    }
};
