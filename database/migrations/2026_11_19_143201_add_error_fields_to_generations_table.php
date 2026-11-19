<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-field problems of a provider validation error, e.g. {"size": ["…"]}, so the UI can show
     * them next to the right field even though the provider is called from a queued job.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->json('error_fields')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn('error_fields');
        });
    }
};
