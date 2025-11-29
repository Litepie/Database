<?php

namespace App\Examples;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * Versionable Trait Examples
 * 
 * This file demonstrates all features of the Versionable trait.
 * The trait provides version history tracking for Eloquent models.
 * 
 * Setup:
 * 1. Add trait to model: use Litepie\Database\Traits\Versionable;
 * 2. Run migration for model_versions table
 * 3. Configure options: $maxVersions, $versionableExclude, etc.
 */
class VersionableExamples
{
    /**
     * Example 1: Basic Version Creation
     * Create versions manually with reasons.
     */
    public function example1(): void
    {
        $post = Post::find(1);
        
        // Create a version manually
        $version = $post->createVersion('Major content update', auth()->user());
        
        // Version includes:
        // - version_number (auto-incremented)
        // - data (all model attributes except excluded ones)
        // - reason ('Major content update')
        // - user_id and user_type (who created the version)
        // - hash (SHA-256 of data for integrity)
        // - created_at timestamp
        
        echo "Created version: " . $version->version_number;
    }

    /**
     * Example 2: Automatic Versioning on Update
     * Versions are created automatically when model is updated.
     */
    public function example2(): void
    {
        $post = Post::find(1);
        
        // Automatic versioning is enabled by default
        $post->title = 'Updated Title';
        $post->content = 'New content';
        $post->save(); // Version automatically created
        
        // Disable auto-versioning temporarily
        $post->disableAutoVersioning();
        $post->title = 'Another update';
        $post->save(); // No version created
        
        // Re-enable auto-versioning
        $post->enableAutoVersioning();
        $post->title = 'Final title';
        $post->save(); // Version created again
    }

    /**
     * Example 3: Rollback to Previous Version
     * Restore model to a previous state.
     */
    public function example3(): void
    {
        $post = Post::find(1);
        
        // Rollback to version 5
        $post->rollbackToVersion(5);
        
        // Rollback to previous version
        $post->rollbackToPrevious();
        
        // Rollback without creating a new version
        $post->rollbackToVersion(3, createVersion: false);
    }

    /**
     * Example 4: View Version History
     * Get all versions and their metadata.
     */
    public function example4(): void
    {
        $post = Post::find(1);
        
        // Get all versions (newest first)
        $versions = $post->getVersionHistory();
        
        foreach ($versions as $version) {
            echo "Version {$version->version_number}\n";
            echo "Created: {$version->created_at}\n";
            echo "Reason: {$version->reason}\n";
            echo "By User ID: {$version->user_id}\n";
            echo "Data: " . json_encode($version->data) . "\n\n";
        }
        
        // Get versions oldest first
        $oldestFirst = $post->getVersionHistory('asc');
        
        // Get specific version
        $version5 = $post->getVersion(5);
        
        // Get latest version
        $latest = $post->getLatestVersion();
        
        // Get previous version
        $previous = $post->getPreviousVersion();
    }

    /**
     * Example 5: Compare Versions
     * See what changed between versions.
     */
    public function example5(): void
    {
        $post = Post::find(1);
        
        // Compare version 3 and version 5
        $differences = $post->compareVersions(3, 5);
        
        /*
        Returns:
        [
            'title' => [
                'version_3' => 'Old Title',
                'version_5' => 'New Title',
                'changed' => true,
            ],
            'content' => [
                'version_3' => 'Old content...',
                'version_5' => 'New content...',
                'changed' => true,
            ],
        ]
        */
        
        foreach ($differences as $field => $change) {
            if ($change['changed']) {
                echo "Field '{$field}' changed\n";
                echo "From: {$change['version_3']}\n";
                echo "To: {$change['version_5']}\n\n";
            }
        }
    }

    /**
     * Example 6: Compare Current State with Version
     * See what's different from a specific version.
     */
    public function example6(): void
    {
        $post = Post::find(1);
        
        // Compare current state with version 5
        $differences = $post->compareWithVersion(5);
        
        /*
        Returns:
        [
            'title' => [
                'current' => 'Current Title',
                'version_5' => 'Version 5 Title',
                'changed' => true,
            ],
        ]
        */
        
        // Preview what would change if rolled back
        $preview = $post->previewRollback(5);
        
        // Same as compareWithVersion
        foreach ($preview as $field => $change) {
            echo "Rolling back would change '{$field}'\n";
            echo "Current: {$change['current']}\n";
            echo "Would become: {$change['version_5']}\n";
        }
    }

    /**
     * Example 7: Version Count and Checks
     * Check version existence and count.
     */
    public function example7(): void
    {
        $post = Post::find(1);
        
        // Get total version count
        $count = $post->getVersionCount();
        echo "Total versions: {$count}\n";
        
        // Check if has any versions
        if ($post->hasVersions()) {
            echo "Post has version history\n";
        }
        
        // Check if specific version exists
        if ($post->hasVersion(5)) {
            echo "Version 5 exists\n";
        }
    }

    /**
     * Example 8: Delete Versions
     * Remove old or all versions.
     */
    public function example8(): void
    {
        $post = Post::find(1);
        
        // Delete all versions
        $post->deleteAllVersions();
        
        // Delete old versions, keep last 10
        $deletedCount = $post->deleteOldVersions(keepLast: 10);
        echo "Deleted {$deletedCount} old versions\n";
    }

    /**
     * Example 9: Version Metadata
     * Store additional data with versions.
     */
    public function example9(): void
    {
        $post = Post::find(1);
        
        // Create version with metadata
        $version = $post->createVersion(
            reason: 'SEO optimization',
            user: auth()->user(),
            metadata: [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'action' => 'bulk_update',
                'batch_id' => 'batch_123',
            ]
        );
        
        // Access metadata later
        echo "Version created from IP: " . $version->metadata['ip_address'];
    }

    /**
     * Example 10: Filter Versions by User
     * Get versions created by specific user.
     */
    public function example10(): void
    {
        $post = Post::find(1);
        
        // Get versions by user
        $userVersions = $post->getVersionsByUser(auth()->user());
        
        // Or by user ID
        $userVersions = $post->getVersionsByUser(5);
        
        foreach ($userVersions as $version) {
            echo "Version {$version->version_number} by User {$version->user_id}\n";
        }
    }

    /**
     * Example 11: Filter Versions by Reason
     * Search versions by reason text.
     */
    public function example11(): void
    {
        $post = Post::find(1);
        
        // Get versions with specific reason
        $autoSaves = $post->getVersionsByReason('Auto-saved');
        $rollbacks = $post->getVersionsByReason('Rollback');
        
        echo "Found {$autoSaves->count()} auto-saved versions\n";
        echo "Found {$rollbacks->count()} rollback versions\n";
    }

    /**
     * Example 12: Version Statistics
     * Get analytical data about versions.
     */
    public function example12(): void
    {
        $post = Post::find(1);
        
        $stats = $post->getVersionStats();
        
        /*
        Returns:
        [
            'total_versions' => 15,
            'first_version' => Carbon instance,
            'latest_version' => Carbon instance,
            'unique_users' => 3,
            'versions_by_reason' => [
                'Auto-saved version' => 10,
                'Major update' => 3,
                'Rollback to version 5' => 2,
            ],
        ]
        */
        
        echo "Total versions: {$stats['total_versions']}\n";
        echo "Unique contributors: {$stats['unique_users']}\n";
        echo "First version: {$stats['first_version']}\n";
    }

    /**
     * Example 13: Configure Versioning Behavior
     * Set max versions and excluded columns.
     */
    public function example13(): void
    {
        // In your model:
        /*
        class Post extends Model
        {
            use Versionable;
            
            // Keep only last 20 versions (auto-prune old ones)
            protected int $maxVersions = 20;
            
            // Exclude these columns from versioning
            protected array $versionableExclude = [
                'created_at',
                'updated_at',
                'views',
                'last_viewed_at',
            ];
            
            // Enable/disable auto-versioning
            protected bool $autoVersioning = true;
            
            // Create version on model creation
            protected bool $versionOnCreate = false;
        }
        */
        
        // Runtime configuration
        $post = Post::find(1);
        
        // Set max versions at runtime
        $post->setMaxVersions(50);
        $post->createVersion('This will auto-prune if > 50 versions exist');
    }

    /**
     * Example 14: Version Data Integrity
     * Each version has a hash for integrity checking.
     */
    public function example14(): void
    {
        $post = Post::find(1);
        
        $version = $post->getVersion(5);
        
        // Hash is SHA-256 of serialized data
        echo "Version hash: {$version->hash}\n";
        
        // Verify integrity (regenerate hash and compare)
        $currentHash = hash('sha256', serialize($version->data));
        
        if ($currentHash === $version->hash) {
            echo "Version data is intact\n";
        } else {
            echo "WARNING: Version data may be corrupted!\n";
        }
    }

    /**
     * Example 15: Version Relationships
     * Access user who created the version.
     */
    public function example15(): void
    {
        $post = Post::find(1);
        
        $versions = $post->versions()
            ->with('user') // Eager load user
            ->get();
        
        foreach ($versions as $version) {
            $user = $version->user;
            echo "Version {$version->version_number} by {$user->name}\n";
        }
    }

    /**
     * Example 16: Batch Operations with Versioning
     * Handle versioning during bulk updates.
     */
    public function example16(): void
    {
        $posts = Post::where('status', 'draft')->get();
        
        foreach ($posts as $post) {
            $post->status = 'published';
            $post->createVersion(
                reason: 'Bulk publish operation',
                user: auth()->user(),
                metadata: ['batch_operation' => true]
            );
            $post->save();
        }
    }

    /**
     * Example 17: Complete Workflow Example
     * Real-world content management workflow.
     */
    public function example17(): void
    {
        // Editor creates a post
        $post = Post::create([
            'title' => 'My Article',
            'content' => 'Initial content',
            'status' => 'draft',
        ]);
        
        // Manually create initial version
        $post->createVersion('Initial draft', auth()->user());
        
        // Editor makes changes
        $post->title = 'My Awesome Article';
        $post->content = 'Updated content with more details';
        $post->save(); // Auto-versioned
        
        // Preview changes
        $changes = $post->compareWithVersion(1);
        foreach ($changes as $field => $diff) {
            echo "Changed {$field}\n";
        }
        
        // Manager reviews and reverts some changes
        $post->rollbackToVersion(1);
        
        // Editor makes final changes
        $post->content = 'Final polished content';
        $post->status = 'published';
        $post->createVersion('Ready for publication', auth()->user());
        $post->save();
        
        // View complete history
        $history = $post->getVersionHistory();
        foreach ($history as $version) {
            echo "v{$version->version_number}: {$version->reason} at {$version->created_at}\n";
        }
    }

    /**
     * Example 18: Audit Trail
     * Use versioning as an audit log.
     */
    public function example18(): void
    {
        $product = Product::find(1);
        
        // Track price changes
        $product->price = 29.99;
        $product->createVersion(
            reason: 'Price adjustment - seasonal sale',
            user: auth()->user(),
            metadata: [
                'old_price' => $product->getOriginal('price'),
                'new_price' => 29.99,
                'reason_code' => 'SEASONAL_SALE',
            ]
        );
        $product->save();
        
        // Later, audit price changes
        $priceChanges = $product->versions()
            ->where('reason', 'like', '%Price%')
            ->get();
        
        foreach ($priceChanges as $version) {
            echo "Price changed on {$version->created_at}\n";
            echo "Old: \${$version->metadata['old_price']}\n";
            echo "New: \${$version->metadata['new_price']}\n";
            echo "Reason: {$version->metadata['reason_code']}\n\n";
        }
    }

    /**
     * Example 19: Selective Versioning
     * Version only when specific fields change.
     */
    public function example19(): void
    {
        $post = Post::find(1);
        
        // Disable auto-versioning
        $post->disableAutoVersioning();
        
        // Make changes
        $post->views++; // Don't version view count
        $post->save();
        
        // Only version important changes
        if ($post->isDirty('title') || $post->isDirty('content')) {
            $post->createVersion('Content updated');
        }
        
        $post->title = 'Important Update';
        $post->save();
        $post->createVersion('Editorial change', auth()->user());
    }

    /**
     * Example 20: Version Recovery After Deletion
     * Preserve versions even after soft delete.
     */
    public function example20(): void
    {
        $post = Post::find(1);
        
        // Get version history before deletion
        $lastGoodVersion = $post->getLatestVersion();
        
        // Soft delete the post
        $post->delete();
        
        // Later, restore and rollback
        $post = Post::withTrashed()->find(1);
        $post->restore();
        
        if ($lastGoodVersion) {
            $post->rollbackToVersion($lastGoodVersion->version_number);
        }
    }
}

/**
 * USAGE IN MODELS
 */

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Versionable;

class Post extends Model
{
    use Versionable;
    
    // Configuration
    protected int $maxVersions = 20; // Keep last 20 versions
    protected array $versionableExclude = ['views', 'last_viewed_at'];
    protected bool $autoVersioning = true;
    protected bool $versionOnCreate = false;
}

class Product extends Model
{
    use Versionable;
    
    // Keep unlimited versions for compliance
    protected int $maxVersions = 0;
    
    // Exclude audit fields
    protected array $versionableExclude = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
*/

/**
 * MIGRATION
 */

/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelVersionsTable extends Migration
{
    public function up()
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->morphs('versionable');
            $table->integer('version_number');
            $table->json('data');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->json('metadata')->nullable();
            $table->string('hash')->nullable();
            $table->timestamps();
            
            $table->index(['versionable_type', 'versionable_id']);
            $table->index('version_number');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('model_versions');
    }
}
*/
