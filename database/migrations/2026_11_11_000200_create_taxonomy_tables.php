<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['practice_areas', 'languages', 'courts'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                if ($name === 'practice_areas') {
                    $table->string('description', 500)->nullable();
                }
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('practice_areas');
    }
};
