<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->string('name');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->string('default_value')->nullable();
            $table->string('width')->default('full');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('form_fields'); }
};
