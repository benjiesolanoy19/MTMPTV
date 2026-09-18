<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('mobile_number', 30);
            $table->text('address');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('viewer');
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('operator_code')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->text('address');
            $table->string('contact_number', 30);
            $table->string('email')->nullable();
            $table->string('valid_id_type')->nullable();
            $table->string('valid_id_number')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_code')->unique();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number')->unique();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('vehicle_type')->default('Tricycle');
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('year_model')->nullable();
            $table->string('registration_number')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->enum('status', ['active', 'inactive', 'impounded'])->default('active');
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->enum('application_type', ['New Franchise', 'Franchise Renewal', 'New Permit', 'Permit Renewal', 'Registration']);
            $table->date('date_submitted');
            $table->enum('status', ['Pending', 'Under Review', 'Approved', 'Rejected', 'Cancelled'])->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        foreach (['franchises' => 'franchise_number', 'permits' => 'permit_number'] as $tableName => $numberColumn) {
            Schema::create($tableName, function (Blueprint $table) use ($numberColumn) {
                $table->id();
                $table->string($numberColumn)->unique();
                $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->enum('status', ['Active', 'Expiring Soon', 'Expired', 'Suspended', 'Cancelled'])->default('Active');
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->string('renewal_number')->unique();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_permit_id')->nullable()->constrained('permits')->nullOnDelete();
            $table->foreignId('previous_franchise_id')->nullable()->constrained('franchises')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_applied');
            $table->date('new_expiry_date');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->string('violation_number')->unique();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('violation_type');
            $table->date('violation_date');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['Unpaid', 'Partially Paid', 'Paid'])->default('Unpaid');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['Open', 'Resolved', 'Dismissed'])->default('Open');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->text('description');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('violations');
        Schema::dropIfExists('renewals');
        Schema::dropIfExists('permits');
        Schema::dropIfExists('franchises');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('operators');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
