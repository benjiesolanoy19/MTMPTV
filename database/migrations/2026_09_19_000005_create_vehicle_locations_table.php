<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->string('status')->default('offline');
            $table->boolean('is_sharing')->default(false);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['status', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_locations');
    }
};
