<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_relationships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('related_user_id');
            $table->enum('type', ['pending', 'friend', 'blocked']);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('related_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['user_id', 'related_user_id']);
            $table->index('user_id');
            $table->index('related_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_relationships');
    }
};
