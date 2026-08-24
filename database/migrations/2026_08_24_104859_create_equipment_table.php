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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_fabrication_id')->constrained()->cascadeOnDelete();
            $table->string('serie_number');
            $table->text('observations')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'serie_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
