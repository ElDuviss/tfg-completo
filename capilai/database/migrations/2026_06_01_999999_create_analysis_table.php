<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('analysis', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->unsignedBigInteger('cuestionario_id')->nullable();
            $table->unsignedBigInteger('datofoto_id')->nullable();
            $table->longText('ai_response')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('set null');

            $table->foreign('cuestionario_id')
                ->references('id')
                ->on('cuestionarios')
                ->onDelete('cascade');

            $table->foreign('datofoto_id')
                ->references('id')
                ->on('datofotos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis');
    }
};