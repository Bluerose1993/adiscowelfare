<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('starting_year');
            $table->unsignedTinyInteger('starting_month');
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['staff_id', 'created_at']);
        });

        Schema::table('dues_payments', function (Blueprint $table) {
            $table->foreignId('receipt_id')->nullable()->after('id')->constrained('dues_payment_receipts')->nullOnDelete();
        });
        Schema::table('dues_payment_deletion_requests', function (Blueprint $table) {
            $table->foreignId('receipt_id')->nullable()->after('id')->constrained('dues_payment_receipts')->nullOnDelete();
        });

        DB::table('dues_payments')->whereNull('deleted_at')->orderBy('id')->get()->each(function ($payment) {
            $receiptId = DB::table('dues_payment_receipts')->insertGetId([
                'staff_id' => $payment->staff_id,
                'amount' => $payment->amount,
                'starting_year' => $payment->payment_year,
                'starting_month' => $payment->payment_month,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
                'recorded_by' => $payment->recorded_by,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ]);
            DB::table('dues_payments')->where('id', $payment->id)->update(['receipt_id' => $receiptId]);
        });
    }

    public function down(): void
    {
        Schema::table('dues_payment_deletion_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('receipt_id'));
        Schema::table('dues_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('receipt_id'));
        Schema::dropIfExists('dues_payment_receipts');
    }
};
