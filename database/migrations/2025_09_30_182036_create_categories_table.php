<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['income','expense']); // keep categories typed
            $table->timestamps();

            $table->unique(['user_id','name','type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('categories');
    }
};
