<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Expand profile_created_by enum in users table ──────────────────
        DB::statement("ALTER TABLE users MODIFY COLUMN profile_created_by ENUM('self','parents','siblings','relative','friend','other')");

        // ── profiles table — add missing columns ───────────────────────────
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('nick_name', 100)->nullable()->after('user_id');
            $table->enum('profile_created_for', ['self','son','daughter','brother','sister','relative'])->nullable()->after('nick_name');
            $table->enum('looking_for', ['bride','groom'])->nullable()->after('profile_created_for');
            $table->enum('body_type', ['slim','average','athletic','heavy'])->nullable()->after('weight_kg');
            $table->string('eye_color', 50)->nullable()->after('body_type');
            $table->string('hair_color', 50)->nullable()->after('eye_color');
            $table->string('disability', 50)->nullable()->after('hair_color');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->enum('residing_status', ['citizen','permanent_resident','work_permit','student_visa','visitor_visa','refugee','other'])->nullable()->after('postal_code');
            $table->text('what_looking_for')->nullable()->after('about_me');
        });

        // ── religious_details table ────────────────────────────────────────
        Schema::table('religious_details', function (Blueprint $table) {
            $table->enum('religiousness', ['very_religious','religious','moderate','not_religious'])->nullable()->after('manglik_status');
            $table->enum('pray', ['always','usually','sometimes','rarely','never'])->nullable()->after('religiousness');
        });

        // ── family_details table ───────────────────────────────────────────
        Schema::table('family_details', function (Blueprint $table) {
            $table->enum('has_children', ['no','yes'])->nullable()->after('sisters_count');
            $table->string('child_living_status', 100)->nullable()->after('has_children');
            $table->enum('family_values', ['traditional','moderate','liberal','religious'])->nullable()->after('child_living_status');
            $table->tinyInteger('sibling_position')->nullable()->after('family_values');
        });

        // ── lifestyles table ───────────────────────────────────────────────
        Schema::table('lifestyles', function (Blueprint $table) {
            $table->enum('eye_wear', ['none','glasses','contact_lens'])->nullable()->after('drinking');
        });

        // ── education_careers table ────────────────────────────────────────
        Schema::table('education_careers', function (Blueprint $table) {
            $table->string('institution_name_year', 300)->nullable()->after('college_university');
            $table->string('employer_name', 200)->nullable()->after('institution_name_year');
            $table->string('job_location', 200)->nullable()->after('employer_name');
            $table->string('designation', 200)->nullable()->after('job_location');
            $table->tinyInteger('experience_years')->nullable()->after('designation');
        });
    }

    public function down(): void
    {
        // Reverse education_careers
        Schema::table('education_careers', function (Blueprint $table) {
            $table->dropColumn(['institution_name_year','employer_name','job_location','designation','experience_years']);
        });

        // Reverse lifestyles
        Schema::table('lifestyles', function (Blueprint $table) {
            $table->dropColumn('eye_wear');
        });

        // Reverse family_details
        Schema::table('family_details', function (Blueprint $table) {
            $table->dropColumn(['has_children','child_living_status','family_values','sibling_position']);
        });

        // Reverse religious_details
        Schema::table('religious_details', function (Blueprint $table) {
            $table->dropColumn(['religiousness','pray']);
        });

        // Reverse profiles
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'nick_name','profile_created_for','looking_for',
                'body_type','eye_color','hair_color','disability',
                'postal_code','residing_status','what_looking_for',
            ]);
        });

        // Revert users enum
        DB::statement("ALTER TABLE users MODIFY COLUMN profile_created_by ENUM('self','parents','siblings')");
    }
};

