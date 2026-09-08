<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('locker_number')->nullable()->unique()->after('department');
        });
        Schema::create('locker_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('preferred_locker_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('assigned_locker_number')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_requests');
        Schema::table('staff', fn (Blueprint $table) => $table->dropColumn('locker_number'));
    }
};
