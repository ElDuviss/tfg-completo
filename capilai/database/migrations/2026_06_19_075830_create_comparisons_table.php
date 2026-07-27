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
            $table->unsignedBigInteger('datofoto_nuevo_id');
            $table->unsignedBigInteger('datofoto_antiguo_id');
            $table->unsignedBigInteger('cuestionario_nuevo_id');
            $table->unsignedBigInteger('cuestionario_antiguo_id');
            $table->unique(['datofoto_nuevo_id', 'datofoto_antiguo_id']);
            $table->unique(['cuestionario_nuevo_id', 'cuestionario_antiguo_id']);
            $table->text('comparison_text');
            $table->timestamps();


            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            $table->foreign('datofoto_nuevo_id')
                ->references('id')
                ->on('datofotos')
                ->onDelete('cascade');

            $table->foreign('datofoto_antiguo_id')
                ->references('id')
                ->on('datofotos')
                ->onDelete('cascade');
            
            $table->foreign('cuestionario_nuevo_id')
                ->references('id')
                ->on('cuestionarios')
                ->onDelete('cascade');

            $table->foreign('cuestionario_antiguo_id')
                ->references('id')
                ->on('cuestionarios')
                ->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparisons');
    }
};
