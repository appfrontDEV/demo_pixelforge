<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per size × quality: the customer price range seen so far, in whole cents.
     */
    public function up(): void
    {
        Schema::create('cost_baselines', function (Blueprint $table) {
            $table->id();
            $table->string('size', 16);
            $table->string('quality', 16);
            $table->unsignedBigInteger('min_cents');
            $table->unsignedBigInteger('max_cents');
            $table->unsignedInteger('sample_count')->default(0);
            $table->timestamps();

            $table->unique(['size', 'quality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_baselines');
    }
};
