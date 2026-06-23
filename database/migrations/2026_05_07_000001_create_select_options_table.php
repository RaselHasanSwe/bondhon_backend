<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('select_options', function (Blueprint $table) {
            $table->id();

            $table->string('group_key', 60);
            // What kind of option: 'religion', 'caste', 'country', 'marital_status', etc.

            $table->unsignedBigInteger('parent_id')->nullable();
            // NULL  → top-level
            // set   → child of that row (caste under religion, district under division)

            $table->string('value', 100);
            // Machine value: 'islam', 'dhaka', 'never_married'

            $table->string('label', 255);
            // Display label: 'Islam', 'Dhaka', 'Never Married'

            $table->json('metadata')->nullable();
            // Extra data: {"iso": "BD", "dial_code": "+880"}

            $table->smallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Unique constraint
            $table->unique(['group_key', 'value', 'parent_id'], 'uq_group_value_parent');

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('select_options')
                  ->onDelete('cascade');

            $table->index(['group_key', 'parent_id', 'sort_order'], 'idx_group_parent_sort');
            $table->index('parent_id', 'idx_parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('select_options');
    }
};

