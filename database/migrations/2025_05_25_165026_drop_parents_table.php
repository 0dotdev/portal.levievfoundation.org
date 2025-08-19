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
        Schema::dropIfExists('parents');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the parents table if needed for rollback
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('father_first_name');
            $table->string('father_last_name');
            $table->string('father_phone');
            $table->string('father_email');
            $table->string('mother_first_name');
            $table->string('mother_last_name');
            $table->string('mother_phone');
            $table->string('mother_email');
            $table->string('father_address');
            $table->string('father_city');
            $table->string('father_state');
            $table->string('father_pincode');
            $table->string('father_country')->default('USA');
            $table->boolean('mother_has_different_address')->default(false);
            $table->string('mother_address')->nullable();
            $table->string('mother_city')->nullable();
            $table->string('mother_state')->nullable();
            $table->string('mother_pincode')->nullable();
            $table->string('mother_country')->nullable();
            $table->enum('family_status', ['single_parent', 'married', 'divorced', 'other'])->default('married');
            $table->integer('no_of_children_in_household')->default(0);
            $table->string('synagogue_affiliation');
            $table->text('declaration_signature');
            $table->date('declaration_date');
            $table->boolean('info_is_true')->default(false);
            $table->boolean('applicants_are_jewish')->default(false);
            $table->boolean('parent_is_of_bukharian_descent')->default(false);
            $table->timestamps();
        });
    }
};
