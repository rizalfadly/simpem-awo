<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_sequences', function (Blueprint $table) {
            $table->string('scope', 30)->primary();          // 'asset' atau 'loan-2026'
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Satu baris = satu JENIS barang. Jumlahnya dipecah per kondisi.
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('description', 1000)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('unit', 20)->default('unit');
            $table->string('serial_number', 80)->nullable();
            $table->date('acquired_at')->nullable();
            $table->string('acquisition_source', 60)->nullable();
            $table->unsignedBigInteger('acquisition_cost')->nullable();     // total rupiah
            $table->unsignedInteger('qty_good')->default(0);
            $table->unsignedInteger('qty_minor')->default(0);               // rusak ringan
            $table->unsignedInteger('qty_broken')->default(0);              // rusak berat
            $table->unsignedInteger('qty_lost')->default(0);
            $table->boolean('is_loanable')->default(true);
            $table->string('status', 20)->default('active')->index();       // active | retired
            $table->timestamp('retired_at')->nullable();
            $table->string('retired_note', 255)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_thumb_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('borrower_member_id')->constrained('members');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 255);
            $table->date('loan_date');
            $table->date('due_date');
            $table->string('status', 20)->default('requested')->index();   // requested|approved|lent|returned|rejected|cancelled
            $table->string('notes', 500)->nullable();
            $table->string('status_note', 500)->nullable();                // alasan tolak / batal
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('handed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lent_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->string('return_note', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });

        Schema::create('asset_loan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('asset_loans')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets');
            $table->unsignedInteger('quantity');
            // kondisi saat dikembalikan (jumlahnya = quantity)
            $table->unsignedInteger('returned_good')->nullable();
            $table->unsignedInteger('returned_minor')->nullable();
            $table->unsignedInteger('returned_broken')->nullable();
            $table->unsignedInteger('returned_lost')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_loan_items');
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('asset_sequences');
    }
};
