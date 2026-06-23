<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_group_configs', function (Blueprint $table) {
            $table->id();

            $table->string('group_key', 60)->unique();
            // Machine key used in select_options.group_key: 'religion', 'custom_tribe' etc.

            $table->string('label', 255);
            // Admin-visible name: 'Religion', 'Tribe'

            $table->enum('profile_tab', [
                'basic','location','religion','career',
                'lifestyle','family','horoscope','preferences','none',
            ])->default('none');
            // Which profile-edit tab this field lives in

            $table->string('field_name', 100)->nullable();
            // Maps to profile model field (e.g. 'religion', 'diet').
            // NULL = purely custom group → saved in profile custom_fields JSON

            $table->enum('input_type', ['select','multi_select'])->default('select');

            $table->string('parent_group_key', 60)->nullable();
            // The group whose options act as parents.
            // Same as group_key → self-nesting within the group (e.g. location hierarchy).
            // Different key → cross-group nesting (e.g. 'caste' parents are from 'religion').
            // NULL → top-level group (no parent)

            $table->unsignedTinyInteger('max_nesting_depth')->default(1);
            // How many levels deep can this tree go? 1 = flat, 5 = max

            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            // System groups were seeded and cannot be deleted from admin

            $table->timestamps();

            $table->index('profile_tab');
            $table->index('parent_group_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_group_configs');
    }
};

