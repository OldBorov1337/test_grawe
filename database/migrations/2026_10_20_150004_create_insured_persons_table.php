<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insured_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('birth_date');
            $table->unsignedTinyInteger('age');
            $table->json('coefficients');
            $table->decimal('premium_base', 12, 2);
            $table->decimal('premium_uah', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insured_persons');
    }
};
