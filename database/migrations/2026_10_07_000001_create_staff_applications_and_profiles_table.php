<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('full_name');
            $table->string('contact_number', 30);
            $table->text('address');
            $table->date('date_of_birth');
            $table->string('preferred_position');
            $table->string('department')->nullable();
            $table->text('skills')->nullable();
            $table->text('experience')->nullable();
            $table->text('reason');
            $table->text('additional_information')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_remarks')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('staff_id')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('skills')->nullable();
            $table->text('experience')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->text('additional_information')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'profile_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('staff_applications');
    }
};
