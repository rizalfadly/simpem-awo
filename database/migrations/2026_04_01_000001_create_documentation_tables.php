<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->string('description', 2000)->nullable();
            $table->date('event_date')->nullable();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->string('status', 20)->default('draft')->index();   // draft | published | archived
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);                                // photo | video
            $table->string('caption', 255)->nullable();
            $table->string('file_path')->nullable();                   // disk 'local' (privat)
            $table->string('thumb_path')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('video_provider', 20)->nullable();          // youtube | link
            $table->string('video_ref', 500)->nullable();              // id YouTube atau URL https
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['album_id', 'sort_order']);
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->string('slug', 190)->unique();                     // dipakai website publik di V1.7
            $table->string('excerpt', 300)->nullable();
            $table->text('body')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_thumb_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('archive_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('category', 30)->index();
            $table->string('document_number', 60)->nullable();
            $table->date('document_date')->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('original_name', 190);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('access', 20)->default('members');          // members | restricted
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_documents');
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('albums');
    }
};
