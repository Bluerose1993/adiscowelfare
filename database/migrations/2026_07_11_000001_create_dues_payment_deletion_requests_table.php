<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues_payment_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dues_payment_id')->constrained('dues_payments')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['dues_payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues_payment_deletion_requests');
    }
};
