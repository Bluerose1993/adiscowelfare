<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('staff_id')->nullable()->unique();
            $table->string('full_name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('gender')->nullable();
            $table->string('department')->nullable()->index();
            $table->string('position')->nullable();
            $table->string('employment_status')->nullable();
            $table->date('date_joined')->nullable();
            $table->date('association_joined_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('full_name');
        });

        Schema::create('dues_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['effective_from', 'effective_to']);
        });

        Schema::create('benefit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('dues_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedSmallInteger('payment_year')->index();
            $table->unsignedTinyInteger('payment_month')->index();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deleted_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['staff_id', 'payment_year', 'payment_month']);
            $table->index(['payment_year', 'payment_month']);
        });

        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('benefit_type_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('incident_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['staff_id', 'status']);
            $table->index(['status', 'payment_date']);
        });

        Schema::create('benefit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('benefit_type_id')->constrained()->restrictOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->date('incident_date')->nullable();
            $table->string('status')->default('submitted')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('resulting_benefit_id')->nullable()->unique()->constrained('benefits')->nullOnDelete();
            $table->timestamps();
            $table->index(['staff_id', 'status']);
        });

        Schema::create('benefit_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_request_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('status')->default('previewed')->index();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('staff_created')->default(0);
            $table->unsignedInteger('staff_matched')->default(0);
            $table->unsignedInteger('payments_created')->default(0);
            $table->unsignedInteger('duplicates_skipped')->default(0);
            $table->unsignedInteger('manual_review_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('import_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('staff_id')->nullable();
            $table->string('full_name')->nullable();
            $table->json('monthly_amounts')->nullable();
            $table->decimal('reported_total', 12, 2)->nullable();
            $table->foreignId('matched_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('pending_review')->index();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['import_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_rows');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('benefit_request_attachments');
        Schema::dropIfExists('benefit_requests');
        Schema::dropIfExists('benefits');
        Schema::dropIfExists('dues_payments');
        Schema::dropIfExists('benefit_types');
        Schema::dropIfExists('dues_rates');
        Schema::dropIfExists('staff');
    }
};
