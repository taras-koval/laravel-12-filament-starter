<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', static function (Blueprint $table) {
            $table->id();
            $table->string('key', 500);
            $table->string('group')->nullable();
            $table->json('values');
            $table->timestamps();

            $table->unique(['key', 'group']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
