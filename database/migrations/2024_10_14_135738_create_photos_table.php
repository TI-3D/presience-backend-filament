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
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('photo1', 255)->default(null);
            $table->string('photo2', 255)->nullable();
            $table->string('photo3', 255)->nullable();
            $table->string('photo4', 255)->nullable();
            $table->string('photo5', 255)->nullable();
            $table->string('image_public_id1')->nullable();
            $table->string('image_public_id2')->nullable();
            $table->string('image_public_id3')->nullable();
            $table->string('image_public_id4')->nullable();
            $table->string('image_public_id5')->nullable();
            $table->foreignId('student_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
