<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_anggota', 30)->unique();
            $table->string('name', 120);
            $table->string('nickname', 60)->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('birth_place', 80)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('joined_at');
            $table->string('status', 20)->default('active')->index();  // active | inactive | alumni
            $table->timestamp('status_changed_at')->nullable();
            $table->string('qr_token', 40)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->unique()->after('id')
                ->constrained('members')->nullOnDelete();
        });

        Schema::create('member_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained();
            $table->foreignId('division_id')->nullable()->constrained();
            $table->foreignId('position_id')->constrained();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'ended_at']);
            $table->index(['period_id', 'position_id', 'division_id']);
        });

        // Akun V1.0 yang sudah ada dijadikan anggota (nomor anggota lama dipertahankan agar login tetap sama).
        $now = now();
        foreach (DB::table('users')->whereNull('member_id')->whereNull('deleted_at')->whereNotNull('nomor_anggota')->orderBy('id')->get() as $u) {
            $memberId = DB::table('members')->insertGetId([
                'nomor_anggota' => $u->nomor_anggota,
                'name'          => $u->name,
                'email'         => $u->email,
                'joined_at'     => $u->created_at ? substr((string) $u->created_at, 0, 10) : $now->toDateString(),
                'status'        => $u->is_active ? 'active' : 'inactive',
                'qr_token'      => Str::random(40),
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            DB::table('users')->where('id', $u->id)->update(['member_id' => $memberId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_positions');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('member_id'));
        Schema::dropIfExists('members');
        Schema::dropIfExists('member_sequences');
    }
};
