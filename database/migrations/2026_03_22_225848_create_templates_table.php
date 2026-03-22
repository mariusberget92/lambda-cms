<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['blog-index', 'single-post', 'archive', 'search-results']);
            $table->string('title');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->json('blocks')->nullable();
            $table->string('meta_title', 100)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
