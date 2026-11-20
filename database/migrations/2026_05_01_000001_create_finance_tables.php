<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('type', 10);                      // income | expense
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['name', 'type']);
        });

        Schema::create('finance_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year');
            $table->string('type', 10);
            $table->unsignedInteger('last_number')->default(0);

            $table->primary(['year', 'type']);
        });

        // Satu laporan per bulan. Angka (saldo) dibekukan saat diajukan.
        Schema::create('finance_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 20)->default('draft')->index();   // draft|submitted|reviewed|revision|approved|published
            $table->bigInteger('opening_balance')->nullable();
            $table->unsignedBigInteger('total_income')->nullable();
            $table->unsignedBigInteger('total_expense')->nullable();
            $table->bigInteger('closing_balance')->nullable();

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_note', 500)->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();                  // KM-2026-0001 / KK-2026-0001
            $table->foreignId('report_id')->constrained('finance_reports');
            $table->string('type', 10);                                 // income | expense
            $table->foreignId('category_id')->constrained('finance_categories');
            $table->date('transaction_date')->index();
            $table->unsignedBigInteger('amount');                       // rupiah bulat
            $table->string('description', 255);
            $table->string('counterparty', 120)->nullable();            // sumber dana / penerima
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('proof_path')->nullable();
            $table->string('proof_name', 190)->nullable();
            $table->string('proof_mime', 100)->nullable();
            $table->string('proof_note', 255)->nullable();              // alasan bila tanpa bukti
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_id', 'type']);
        });

        // Jejak alur kerja laporan. Hanya-tambah.
        Schema::create('finance_report_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('finance_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_label')->nullable();
            $table->string('action', 30);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('finance_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('finance_reports');
            $table->foreignId('transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->foreignId('filed_by')->constrained('users');
            $table->text('reason');
            $table->string('status', 20)->default('open')->index();     // open|clarified|reviewed|upheld|rejected|withdrawn

            $table->text('clarification')->nullable();
            $table->foreignId('clarified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('clarified_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('decision_note', 1000)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_dispute_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('finance_disputes')->cascadeOnDelete();
            $table->string('stage', 20);                                // filing | clarification
            $table->string('path');
            $table->string('original_name', 190);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_dispute_files');
        Schema::dropIfExists('finance_disputes');
        Schema::dropIfExists('finance_report_events');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_reports');
        Schema::dropIfExists('finance_sequences');
        Schema::dropIfExists('finance_categories');
    }
};
