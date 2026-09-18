<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type', 80);
            $table->date('date_submitted');
            $table->string('location')->nullable();
            $table->text('description');
            $table->enum('status', ['Submitted', 'Pending', 'Under Review', 'Resolved', 'Closed'])->default('Submitted');
            $table->string('processing_status')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->index(['submitted_by', 'status']);
            $table->index(['status', 'date_submitted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};