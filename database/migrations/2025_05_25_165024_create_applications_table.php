<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Child Information
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);

            // School Information
            $table->string('current_school_name');
            $table->string('current_school_location');
            $table->enum('current_grade', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'])->nullable();
            $table->string('school_year_applying_for')->nullable();
            $table->json('school_wish_to_apply_in')->nullable();

            // Grant Application
            $table->boolean('is_applying_for_grant')->default(true);
            $table->boolean('attended_school_past_year')->default(false);

            // Parent Information (Father)
            $table->string('father_first_name');
            $table->string('father_last_name');
            $table->string('father_phone');
            $table->string('father_email');
            $table->string('father_address');
            $table->string('father_city');
            $table->string('father_state');
            $table->string('father_pincode');
            $table->string('father_country')->default('USA');

            // Parent Information (Mother)
            $table->string('mother_first_name');
            $table->string('mother_last_name');
            $table->string('mother_phone');
            $table->string('mother_email');
            $table->boolean('mother_has_different_address')->default(false);
            $table->string('mother_address')->nullable();
            $table->string('mother_city')->nullable();
            $table->string('mother_state')->nullable();
            $table->string('mother_pincode')->nullable();
            $table->string('mother_country')->nullable();

            // Family Information
            $table->enum('family_status', ['single_parent', 'married', 'divorced', 'other'])->default('married');
            $table->integer('no_of_children_in_household')->default(0);
            $table->string('synagogue_affiliation');

            // Declaration
            $table->text('declaration_signature');
            $table->date('declaration_date');
            $table->boolean('info_is_true')->default(false);
            $table->boolean('applicants_are_jewish')->default(false);
            $table->boolean('parent_is_of_bukharian_descent')->default(false);

            // Application Status & Notes
            $table->enum('status', ['submitted', 'pending', 'fix_needed', 'approved', 'resubmitted', 'rejected'])->default('submitted');
            $table->text('additional_notes')->nullable();
            $table->text('admin_comments')->nullable();

            // Tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
