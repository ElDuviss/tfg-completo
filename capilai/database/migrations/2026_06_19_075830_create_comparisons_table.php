<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparisons', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('photo_a_id');
            $table->unsignedBigInteger('photo_b_id');
            $table->unique(['photo_a_id', 'photo_b_id']);
            $table->text('comparison_text');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('photo_a_id')->references('id')->on('fotos')->onDelete('cascade');
            $table->foreign('photo_b_id')->references('id')->on('fotos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparisons');
    }
};