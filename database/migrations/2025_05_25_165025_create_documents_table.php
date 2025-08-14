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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->enum('reference_type', ['parent', 'child']);
            $table->string('reference_id');
            // Document Information
            $table->enum('document_type', [
                'government_id',
                'marriage_certificate',
                'recent_utility_bill',
                'school_report_card_2_years'
            ]);
            $table->string('document_name'); // Original filename
            $table->string('file_path'); // Storage path or Google Drive ID
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();

            // Document Status & Review
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
