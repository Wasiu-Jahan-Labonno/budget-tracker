<?php

use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();                 // e.g., "September Salary" / "Groceries"
            $table->enum('type', array_column(TransactionType::cases(), 'value'));
            $table->decimal('amount', 12, 2);                    // positive value
            $table->date('occurred_on');                         // for month/year reporting
            $table->boolean('is_salary')->default(false);        // mark salaries
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id','occurred_on']);
            $table->index(['user_id','type','occurred_on']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};
