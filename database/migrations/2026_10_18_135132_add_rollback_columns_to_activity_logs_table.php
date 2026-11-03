<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('model_type')->nullable()->after('description');
            $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            $table->json('old_values')->nullable()->after('model_id');
            $table->json('new_values')->nullable()->after('old_values');
            $table->boolean('is_rolled_back')->default(false)->after('new_values');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['model_type', 'model_id', 'old_values', 'new_values', 'is_rolled_back']);
        });
    }
};
