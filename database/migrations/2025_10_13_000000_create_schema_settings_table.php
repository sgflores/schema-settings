<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schema_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key');
            $table->text('value')->nullable(); // Stores the serialized setting value

            // Polymorphic Columns for Model-Scoped Settings
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamps();

            // Enforce uniqueness for a key within a specific scope
            $table->unique(['key', 'reference_type', 'reference_id'], 'settings_unique_key_scope');

            // Add an index for faster lookups
            $table->index(['reference_type', 'reference_id']);

            $table->index(['key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_settings');
    }
};

