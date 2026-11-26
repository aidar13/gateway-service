<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microservices', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_url');
            $table->string('openapi_path')->default('/api/openapi.json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('microservice_id')->constrained()->cascadeOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->boolean('requires_auth')->default(true);
            $table->string('permission')->nullable();
            $table->string('summary')->nullable();
            $table->timestamps();

            $table->unique(['microservice_id', 'method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_routes');
        Schema::dropIfExists('microservices');
    }
};
