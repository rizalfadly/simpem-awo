<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('subject', 150)->nullable();
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->string('status', 20)->default('new')->index();     // new | handled
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });

        // Tanda "tampil di website publik". Semuanya default TERTUTUP dan dipilih eksplisit (izin content.publish).
        Schema::table('albums', fn (Blueprint $t) => $t->boolean('is_public')->default(false)->after('status'));
        Schema::table('news_posts', fn (Blueprint $t) => $t->boolean('is_public')->default(false)->after('status'));
        Schema::table('archive_documents', fn (Blueprint $t) => $t->boolean('is_public')->default(false)->after('access'));
        Schema::table('activities', function (Blueprint $t) {
            $t->boolean('is_public')->default(false)->after('status');
            $t->string('public_slug', 190)->nullable()->unique()->after('is_public');
            $t->string('public_summary', 2000)->nullable()->after('public_slug');   // ringkasan khusus publik, terpisah dari deskripsi internal
        });
        // Persetujuan anggota sendiri untuk menampilkan nama & foto di struktur pengurus publik.
        Schema::table('members', fn (Blueprint $t) => $t->boolean('public_profile')->default(false)->after('status'));
    }

    public function down(): void
    {
        Schema::table('members', fn (Blueprint $t) => $t->dropColumn('public_profile'));
        Schema::table('activities', function (Blueprint $t) {
            $t->dropUnique(['public_slug']);
            $t->dropColumn(['public_summary', 'public_slug', 'is_public']);
        });
        Schema::table('archive_documents', fn (Blueprint $t) => $t->dropColumn('is_public'));
        Schema::table('news_posts', fn (Blueprint $t) => $t->dropColumn('is_public'));
        Schema::table('albums', fn (Blueprint $t) => $t->dropColumn('is_public'));
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('site_settings');
    }
};
