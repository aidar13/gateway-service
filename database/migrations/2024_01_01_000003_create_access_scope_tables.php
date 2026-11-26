<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_scopes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scope_service', function (Blueprint $table): void {
            $table->foreignId('scope_id')->constrained('access_scopes')->cascadeOnDelete();
            $table->foreignId('microservice_id')->constrained()->cascadeOnDelete();
            $table->primary(['scope_id', 'microservice_id']);
        });

        Schema::create('scope_route', function (Blueprint $table): void {
            $table->foreignId('scope_id')->constrained('access_scopes')->cascadeOnDelete();
            $table->foreignId('service_route_id')->constrained()->cascadeOnDelete();
            $table->primary(['scope_id', 'service_route_id']);
        });

        // No foreign key to oauth_clients: that table is created by a later Passport migration.
        Schema::create('client_scope', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id');
            $table->foreignId('scope_id')->constrained('access_scopes')->cascadeOnDelete();
            $table->primary(['client_id', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_scope');
        Schema::dropIfExists('scope_route');
        Schema::dropIfExists('scope_service');
        Schema::dropIfExists('access_scopes');
    }
};
