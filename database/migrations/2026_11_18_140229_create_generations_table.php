<?php

use App\Enums\GenerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Money columns are integers only. Provider cost and customer price are exact nano-USD (the
     * price is rounded to cents only on totals); the estimate is a display range in cents.
     * user_id is nullable with nullOnDelete: deleting an account keeps its generations, which the
     * admin report must still reconcile against Pixelforge's GET /v1/usage.
     */
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_id')->nullable()->unique();

            $table->text('prompt');
            $table->string('size', 16);
            $table->string('quality', 16);
            $table->json('reference_images')->nullable();

            $table->string('status', 16)->default(GenerationStatus::Pending->value)->index();

            $table->bigInteger('estimate_min_cents')->nullable();
            $table->bigInteger('estimate_max_cents')->nullable();

            $table->json('usage')->nullable();
            $table->json('rates')->nullable();
            $table->bigInteger('provider_cost_nusd')->nullable();
            $table->bigInteger('price_nusd')->nullable();

            $table->text('image_url')->nullable();
            $table->timestamp('image_url_expires_at')->nullable();

            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
