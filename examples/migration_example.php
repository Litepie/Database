<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Example migration demonstrating all the enhanced macros
 * provided by the Litepie Database package.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Basic product fields
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            
            // Slug support
            $table->slug('slug', true); // Creates indexed, unique slug column
            
            // Status management
            $table->status('status', 'draft'); // Enum with default value
            
            // Positioning/ordering
            $table->position('sort_order'); // For manual ordering
            
            // SEO fields
            $table->seoColumns(); // Adds meta_title, meta_description, etc.
            
            // JSON fields with indexes
            $table->jsonWithIndex('specifications', ['weight', 'dimensions']);
            $table->json('metadata');
            
            // Money field (stored as integer cents)
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            
            // Relationships
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            
            // Audit trail with all tracking fields
            $table->auditColumns();
            
            // Additional indexes
            $table->index(['status', 'created_at']);
            $table->index(['category_id', 'status']);
        });
        
        Schema::create('articles', function (Blueprint $table) {
            // UUID primary key example
            $table->uuidPrimary('id');
            
            $table->string('title');
            $table->text('content');
            $table->slug('slug');
            
            // Archive-specific columns
            $table->archivedAt('archived_at');
            $table->string('archived_by')->nullable();
            $table->text('archived_reason')->nullable();
            
            $table->status('status', 'published');
            $table->timestamps();
            
            // Full-text search indexes (MySQL)
            $table->fullText(['title', 'content']);
        });
        
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // User preferences as JSON with schema
            $table->json('preferences');
            $table->json('settings');
            
            // Profile slug
            $table->slug('username');
            
            $table->auditColumns();
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('products');
    }
};
