<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('title', 150);
            $table->string('category', 60)->nullable();
            $table->foreignId('period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pic_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('location', 190)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('draft')->index();

            // Proposal
            $table->text('proposal_background')->nullable();
            $table->text('proposal_objectives')->nullable();
            $table->string('proposal_target_audience', 190)->nullable();
            $table->unsignedInteger('proposal_target_participants')->nullable();
            $table->string('proposal_file_path')->nullable();
            $table->timestamp('proposal_submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_note', 500)->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->string('cancelled_reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['starts_at']);
        });

        Schema::create('activity_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained();
            $table->string('role', 20);                 // leader | deputy | secretary | treasurer | coordinator | member
            $table->string('section', 60)->nullable();  // seksi, mis. Acara, Humas, Konsumsi
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'member_id']);
        });

        Schema::create('activity_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('preparation');   // preparation | rundown
            $table->string('title', 150);
            $table->string('description', 500)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->foreignId('pic_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('status', 20)->default('todo');         // todo | doing | done
            $table->timestamps();

            $table->index(['activity_id', 'kind', 'starts_at']);
        });

        // Rencana anggaran (RAB) + realisasi. Uang disimpan sebagai bilangan bulat rupiah.
        Schema::create('activity_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);                            // income | expense
            $table->string('category', 60);
            $table->string('description', 190);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('planned_amount')->default(0);
            $table->unsignedBigInteger('actual_amount')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'type']);
        });

        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->text('evaluation')->nullable();
            $table->unsignedInteger('realized_participants')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 20)->default('draft');        // draft | submitted | revision | accepted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
        Schema::dropIfExists('activity_budget_items');
        Schema::dropIfExists('activity_timelines');
        Schema::dropIfExists('activity_committee_members');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('activity_sequences');
    }
};
