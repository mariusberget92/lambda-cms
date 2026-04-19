<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autosaves', function (Blueprint $table) {
            $table->id();
            $table->morphs('autosaveable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['autosaveable_type', 'autosaveable_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autosaves');
    }
};
