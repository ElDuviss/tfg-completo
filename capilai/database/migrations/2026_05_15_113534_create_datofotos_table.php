<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('datofotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('archivo_json');
            $table->unsignedBigInteger('foto_frontal_id')->nullable();
            $table->unsignedBigInteger('foto_superior_id')->nullable();
            $table->unsignedBigInteger('foto_lateral_izquierda_id')->nullable();
            $table->unsignedBigInteger('foto_lateral_derecha_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('set null');

            $table->foreign('foto_frontal_id')
                ->references('id')
                ->on('fotos')
                ->onDelete('set null');

            $table->foreign('foto_superior_id')
                ->references('id')
                ->on('fotos')
                ->onDelete('set null');

            $table->foreign('foto_lateral_izquierda_id')
                ->references('id')
                ->on('fotos')
                ->onDelete('set null');

            $table->foreign('foto_lateral_derecha_id')
                ->references('id')
                ->on('fotos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datofotos');
    }
};