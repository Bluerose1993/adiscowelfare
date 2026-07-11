<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('staff_deletion_requests', function (Blueprint $table) { $table->id(); $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete(); $table->foreignId('requested_by')->constrained('users')->restrictOnDelete(); $table->text('reason'); $table->string('status')->default('pending')->index(); $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('reviewed_at')->nullable(); $table->text('review_notes')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('staff_deletion_requests'); }
};
