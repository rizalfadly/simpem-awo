<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Database mitra: UMKM, instansi, sponsor, komunitas dalam satu tabel.
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('name', 150);
            $table->string('type', 20)->index();                       // umkm | institution | sponsor | community | other
            $table->string('sector', 80)->nullable();                  // mis. kuliner, fashion, jasa
            $table->string('owner_name', 120)->nullable();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();   // UMKM milik anggota
            $table->string('description', 2000)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('instagram', 80)->nullable();
            $table->string('public_contact', 150)->nullable();         // satu-satunya kontak yang boleh tampil ke publik
            // Data internal (tidak pernah ditampilkan ke anggota biasa maupun publik)
            $table->string('contact_person', 120)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->string('status', 20)->default('active')->index();   // active | inactive
            $table->date('joined_at')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_thumb_path')->nullable();
            $table->boolean('is_public')->default(false);              // tampil di website publik (V1.7), dipilih eksplisit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partner_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 1000)->nullable();
            $table->unsignedBigInteger('price')->nullable();           // rupiah bulat
            $table->string('price_note', 60)->nullable();              // mis. "mulai dari", "per porsi"
            $table->boolean('is_available')->default(true);
            $table->string('photo_path')->nullable();
            $table->string('photo_thumb_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['partner_id', 'sort_order']);
        });

        Schema::create('partner_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('description', 1500)->nullable();
            $table->string('terms', 500)->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('audience', 20)->default('members');        // members | public
            $table->string('status', 20)->default('draft')->index();    // draft | published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained();
            $table->string('title', 150);
            $table->string('type', 30);                                // sponsorship | mou | barter | event | other
            $table->string('description', 1500)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->date('signed_on')->nullable();
            $table->unsignedBigInteger('value')->nullable();           // nilai kerja sama (rupiah)
            $table->foreignId('pic_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();    // draft | active | completed | terminated
            $table->string('status_note', 500)->nullable();
            $table->string('document_path')->nullable();               // naskah perjanjian (privat)
            $table->string('document_name', 190)->nullable();
            $table->string('document_mime', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partnerships');
        Schema::dropIfExists('partner_promotions');
        Schema::dropIfExists('partner_products');
        Schema::dropIfExists('partners');
    }
};
