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
        // The "final work" of a module: exactly one per module.
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->unique()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('instructions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
