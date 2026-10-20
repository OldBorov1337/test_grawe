<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tariff_id')->constrained()->restrictOnDelete();
            $table->date('date_start');
            $table->date('date_end');
            $table->decimal('insurance_sum', 12, 2);
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 12, 6);
            $table->decimal('premium_base', 12, 2);
            $table->decimal('premium_uah', 12, 2);
            $table->string('status')->default('reserved');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
