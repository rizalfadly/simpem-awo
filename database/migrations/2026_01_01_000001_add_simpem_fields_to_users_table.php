<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Login memakai Nomor Anggota. Di V1.1 kolom ini akan dihubungkan ke tabel members (member_id).
            $table->string('nomor_anggota', 30)->nullable()->unique()->after('id');
            $table->string('email')->nullable()->change();
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['last_login_ip', 'last_login_at', 'must_change_password', 'is_active']);
            $table->dropUnique(['nomor_anggota']);
            $table->dropColumn('nomor_anggota');
        });
    }
};
