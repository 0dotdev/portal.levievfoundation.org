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
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('father_first_name');
            $table->string('father_last_name');
            $table->string('father_phone');
            $table->string('father_email');

            // Mother Information
            $table->string('mother_first_name');
            $table->string('mother_last_name');
            $table->string('mother_phone');
            $table->string('mother_email');

            // Father Address
            $table->string('father_address');
            $table->string('father_city');
            $table->string('father_state');
            $table->string('father_pincode');
            $table->string('father_country')->default('USA');

            // Mother Address (if different)
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

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
