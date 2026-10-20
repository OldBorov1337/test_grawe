<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('parameters');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['tariff_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_rules');
    }
};
