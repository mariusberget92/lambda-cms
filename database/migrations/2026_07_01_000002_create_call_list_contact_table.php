<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_list_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->enum('call_status', ['not_called', 'called', 'no_answer', 'callback', 'completed'])->default('not_called');
            $table->text('notes')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['call_list_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_list_contact');
    }
};
