<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_limits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('month', 7)->index();          // "YYYY-MM"
            $t->decimal('limit', 12, 2)->default(0);  // limit amount
            $t->timestamps();
            $t->unique(['user_id', 'month']);         // one limit per user per month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_limits');
    }
};
