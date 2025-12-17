<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schema_settings_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            // Polymorphic Columns for Model-Scoped Settings
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Audit information
            $table->string('user_type')->nullable(); // Polymorphic to any authenticatable
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50); // 'created', 'updated', 'deleted'

            $table->timestamp('created_at');

            // Indexes
            $table->index(['key']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['user_type', 'user_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_settings_history');
    }
};

