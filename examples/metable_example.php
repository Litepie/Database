<?php

namespace App\Examples;

use App\Models\Post;
use App\Models\Product;
use App\Models\User;

/**
 * Metable Trait Examples
 * 
 * This file demonstrates all features of the Metable trait.
 * The trait provides flexible key-value metadata storage for models.
 * 
 * Setup:
 * 1. Add trait to model: use Litepie\Database\Traits\Metable;
 * 2. Run migration for model_meta table
 * 3. Start using meta methods
 */
class MetableExamples
{
    /**
     * Example 1: Basic Meta Operations
     * Set, get, and delete meta values.
     */
    public function example1(): void
    {
        $post = Post::find(1);
        
        // Set meta value
        $post->setMeta('sidebar_position', 'left');
        $post->setMeta('featured', true);
        $post->setMeta('view_count', 0);
        
        // Get meta value
        $position = $post->getMeta('sidebar_position'); // 'left'
        $featured = $post->getMeta('featured'); // true
        
        // Get with default value
        $layout = $post->getMeta('layout', 'default'); // 'default' if not set
        
        // Check if meta exists
        if ($post->hasMeta('featured')) {
            echo "Post is featured\n";
        }
        
        // Delete meta
        $post->deleteMeta('sidebar_position');
    }

    /**
     * Example 2: Type-Safe Meta Storage
     * Automatic type detection and preservation.
     */
    public function example2(): void
    {
        $post = Post::find(1);
        
        // String
        $post->setMeta('author_note', 'This is important');
        
        // Boolean
        $post->setMeta('is_premium', true);
        $post->setMeta('allow_comments', false);
        
        // Integer
        $post->setMeta('reading_time', 5);
        
        // Float
        $post->setMeta('rating', 4.5);
        
        // Array
        $post->setMeta('tags', ['laravel', 'php', 'tutorial']);
        
        // Object
        $post->setMeta('seo', (object)[
            'title' => 'SEO Title',
            'description' => 'SEO Description',
        ]);
        
        // Values are automatically unserialized with correct types
        $premium = $post->getMeta('is_premium'); // true (boolean)
        $rating = $post->getMeta('rating'); // 4.5 (float)
        $tags = $post->getMeta('tags'); // ['laravel', 'php', 'tutorial'] (array)
    }

    /**
     * Example 3: Bulk Meta Operations
     * Set and delete multiple meta values at once.
     */
    public function example3(): void
    {
        $post = Post::find(1);
        
        // Set multiple meta values
        $post->setMultipleMeta([
            'theme' => 'dark',
            'sidebar' => 'right',
            'show_author' => true,
            'related_posts' => 5,
        ]);
        
        // Get all meta
        $allMeta = $post->getAllMeta();
        /*
        Returns:
        [
            'theme' => 'dark',
            'sidebar' => 'right',
            'show_author' => true,
            'related_posts' => 5,
        ]
        */
        
        // Delete multiple meta keys
        $post->deleteMultipleMeta(['theme', 'sidebar']);
        
        // Delete all meta
        $post->deleteAllMeta();
    }

    /**
     * Example 4: Numeric Meta Operations
     * Increment and decrement numeric values.
     */
    public function example4(): void
    {
        $post = Post::find(1);
        
        // Set initial value
        $post->setMeta('views', 100);
        
        // Increment
        $newViews = $post->incrementMeta('views'); // 101
        $newViews = $post->incrementMeta('views', 5); // 106
        
        // Decrement
        $newViews = $post->decrementMeta('views'); // 105
        $newViews = $post->decrementMeta('views', 10); // 95
        
        // Works with floats too
        $post->setMeta('rating', 4.5);
        $post->incrementMeta('rating', 0.5); // 5.0
    }

    /**
     * Example 5: Array Meta Operations
     * Append and remove from array meta.
     */
    public function example5(): void
    {
        $post = Post::find(1);
        
        // Initialize array
        $post->setMeta('categories', [1, 2, 3]);
        
        // Append to array
        $post->appendToMeta('categories', 4); // [1, 2, 3, 4]
        $post->appendToMeta('categories', 5); // [1, 2, 3, 4, 5]
        
        // Remove from array
        $post->removeFromMeta('categories', 2); // [1, 3, 4, 5]
        
        // Works with empty arrays
        $post->appendToMeta('tags', 'laravel'); // ['laravel']
        $post->appendToMeta('tags', 'php'); // ['laravel', 'php']
    }

    /**
     * Example 6: Get Meta Keys
     * List all meta keys for a model.
     */
    public function example6(): void
    {
        $post = Post::find(1);
        
        // Get all meta keys
        $keys = $post->getMetaKeys();
        // Returns: ['sidebar_position', 'featured', 'view_count', ...]
        
        foreach ($keys as $key) {
            $value = $post->getMeta($key);
            echo "{$key}: {$value}\n";
        }
    }

    /**
     * Example 7: Search Meta by Pattern
     * Find meta keys matching a pattern.
     */
    public function example7(): void
    {
        $post = Post::find(1);
        
        // Set various meta
        $post->setMeta('seo_title', 'SEO Title');
        $post->setMeta('seo_description', 'SEO Description');
        $post->setMeta('seo_keywords', 'laravel, php');
        $post->setMeta('sidebar_position', 'left');
        
        // Search for all SEO-related meta
        $seoMeta = $post->searchMeta('seo_.*');
        
        foreach ($seoMeta as $meta) {
            echo "{$meta->key}: {$meta->value}\n";
        }
        /*
        Output:
        seo_title: SEO Title
        seo_description: SEO Description
        seo_keywords: laravel, php
        */
    }

    /**
     * Example 8: Copy Meta Between Models
     * Transfer meta from one model to another.
     */
    public function example8(): void
    {
        $originalPost = Post::find(1);
        $duplicatePost = Post::find(2);
        
        // Copy all meta
        $originalPost->copyMetaTo($duplicatePost);
        
        // Copy specific keys only
        $originalPost->copyMetaTo($duplicatePost, ['featured', 'sidebar_position']);
    }

    /**
     * Example 9: Merge Meta from Another Model
     * Combine meta from two models.
     */
    public function example9(): void
    {
        $post1 = Post::find(1);
        $post2 = Post::find(2);
        
        $post1->setMeta('featured', true);
        $post1->setMeta('sidebar', 'left');
        
        $post2->setMeta('sidebar', 'right');
        $post2->setMeta('comments', true);
        
        // Merge without overwriting existing keys
        $post1->mergeMetaFrom($post2, overwrite: false);
        // Result: featured=true, sidebar='left' (not overwritten), comments=true
        
        // Merge with overwriting
        $post1->mergeMetaFrom($post2, overwrite: true);
        // Result: featured=true, sidebar='right' (overwritten), comments=true
    }

    /**
     * Example 10: Query Models by Meta
     * Filter models using meta values.
     */
    public function example10(): void
    {
        // Find posts where featured = true
        $featured = Post::whereMeta('featured', true)->get();
        
        // Find posts with specific sidebar position
        $leftSidebar = Post::whereMeta('sidebar_position', 'left')->get();
        
        // Find posts that have a specific meta key
        $withRating = Post::hasMeta('rating')->get();
        
        // Find posts without a meta key
        $withoutRating = Post::doesntHaveMeta('rating')->get();
        
        // Multiple meta conditions
        $filtered = Post::whereMetaMultiple([
            'featured' => true,
            'sidebar_position' => 'left',
            'allow_comments' => true,
        ])->get();
    }

    /**
     * Example 11: Order Models by Meta Value
     * Sort query results by meta values.
     */
    public function example11(): void
    {
        // Order posts by rating (meta)
        $topRated = Post::orderByMeta('rating', 'desc')
            ->limit(10)
            ->get();
        
        // Order products by custom priority
        $products = Product::orderByMeta('priority', 'asc')->get();
    }

    /**
     * Example 12: Magic Accessors
     * Access meta using property syntax.
     */
    public function example12(): void
    {
        $post = Post::find(1);
        
        // Use magic setter (prefix with meta_)
        $post->meta_featured = true;
        $post->meta_sidebar = 'left';
        
        // Use magic getter
        $featured = $post->meta_featured; // true
        $sidebar = $post->meta_sidebar; // 'left'
        
        // Regular meta methods still work
        $featured = $post->getMeta('featured');
    }

    /**
     * Example 13: User Preferences Example
     * Store user settings as meta.
     */
    public function example13(): void
    {
        $user = User::find(1);
        
        // Store user preferences
        $user->setMultipleMeta([
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'America/New_York',
            'notifications_email' => true,
            'notifications_sms' => false,
            'per_page' => 25,
        ]);
        
        // Retrieve preferences
        $theme = $user->getMeta('theme', 'light');
        $perPage = $user->getMeta('per_page', 15);
        
        // Update specific preference
        $user->setMeta('theme', 'light');
    }

    /**
     * Example 14: Product Custom Fields
     * Flexible product attributes.
     */
    public function example14(): void
    {
        $product = Product::find(1);
        
        // Store custom product data
        $product->setMultipleMeta([
            'manufacturer' => 'ACME Corp',
            'warranty_months' => 24,
            'weight_kg' => 2.5,
            'dimensions' => ['length' => 30, 'width' => 20, 'height' => 10],
            'certifications' => ['CE', 'RoHS', 'FCC'],
            'is_hazardous' => false,
            'country_of_origin' => 'USA',
        ]);
        
        // Retrieve custom fields
        $warranty = $product->getMeta('warranty_months');
        $dimensions = $product->getMeta('dimensions');
        $certs = $product->getMeta('certifications');
    }

    /**
     * Example 15: Page Builder Meta
     * Store page layout configuration.
     */
    public function example15(): void
    {
        $page = Post::find(1); // Assuming Post is used for pages
        
        // Store page builder data
        $page->setMeta('layout', 'full-width');
        $page->setMeta('widgets', [
            ['type' => 'hero', 'position' => 1, 'data' => ['title' => 'Welcome']],
            ['type' => 'features', 'position' => 2, 'data' => ['count' => 3]],
            ['type' => 'cta', 'position' => 3, 'data' => ['button' => 'Sign Up']],
        ]);
        
        // Retrieve and render
        $layout = $page->getMeta('layout');
        $widgets = $page->getMeta('widgets', []);
        
        foreach ($widgets as $widget) {
            // Render widget based on type and data
        }
    }

    /**
     * Example 16: Analytics Tracking
     * Store analytics data as meta.
     */
    public function example16(): void
    {
        $post = Post::find(1);
        
        // Track metrics
        $post->incrementMeta('views');
        $post->incrementMeta('unique_visitors');
        
        // Track with timestamps
        $post->appendToMeta('view_log', [
            'timestamp' => now(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        // Get analytics
        $totalViews = $post->getMeta('views', 0);
        $uniqueVisitors = $post->getMeta('unique_visitors', 0);
        $viewLog = $post->getMeta('view_log', []);
    }

    /**
     * Example 17: Feature Flags
     * Enable/disable features per model.
     */
    public function example17(): void
    {
        $post = Post::find(1);
        
        // Set feature flags
        $post->setMultipleMeta([
            'feature_comments' => true,
            'feature_sharing' => true,
            'feature_related_posts' => false,
            'feature_newsletter_signup' => true,
        ]);
        
        // Check features in views
        if ($post->getMeta('feature_comments', false)) {
            // Show comments section
        }
        
        if ($post->getMeta('feature_sharing', false)) {
            // Show social sharing buttons
        }
    }

    /**
     * Example 18: A/B Testing Configuration
     * Store experiment data.
     */
    public function example18(): void
    {
        $post = Post::find(1);
        
        // Store A/B test configuration
        $post->setMeta('ab_test', [
            'experiment_id' => 'headline_test_01',
            'variant' => 'B',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        
        // Track results
        $post->incrementMeta('ab_variant_a_clicks');
        $post->incrementMeta('ab_variant_b_clicks');
        
        // Get test data
        $test = $post->getMeta('ab_test');
        $variantAClicks = $post->getMeta('ab_variant_a_clicks', 0);
        $variantBClicks = $post->getMeta('ab_variant_b_clicks', 0);
    }

    /**
     * Example 19: Permission Overrides
     * Store model-specific permissions.
     */
    public function example19(): void
    {
        $post = Post::find(1);
        
        // Set custom permissions
        $post->setMultipleMeta([
            'allow_guest_view' => true,
            'allow_guest_comment' => false,
            'restricted_to_roles' => ['admin', 'editor'],
            'password_protected' => false,
        ]);
        
        // Check permissions
        $guestCanView = $post->getMeta('allow_guest_view', false);
        $restrictedRoles = $post->getMeta('restricted_to_roles', []);
    }

    /**
     * Example 20: Complete WordPress-Style Meta Example
     * Replicate WordPress post_meta functionality.
     */
    public function example20(): void
    {
        $post = Post::find(1);
        
        // Set various meta like WordPress
        $post->setMultipleMeta([
            // SEO
            '_yoast_wpseo_title' => 'Custom SEO Title',
            '_yoast_wpseo_metadesc' => 'Custom meta description',
            
            // Featured image
            '_thumbnail_id' => 123,
            
            // Custom fields
            'custom_field_1' => 'Value 1',
            'custom_field_2' => 'Value 2',
            
            // Page template
            '_wp_page_template' => 'custom-template.php',
            
            // Sidebar
            'sidebar_position' => 'left',
            
            // Related posts
            'related_posts' => [5, 10, 15],
        ]);
        
        // Get meta values
        $seoTitle = $post->getMeta('_yoast_wpseo_title');
        $thumbnailId = $post->getMeta('_thumbnail_id');
        $template = $post->getMeta('_wp_page_template', 'default.php');
        
        // Query posts by custom field
        $filteredPosts = Post::whereMeta('sidebar_position', 'left')
            ->whereMeta('_thumbnail_id')
            ->get();
    }
}

/**
 * USAGE IN MODELS
 */

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Metable;

class Post extends Model
{
    use Metable;
    
    // That's it! No configuration needed.
    // All meta methods are now available.
}

class Product extends Model
{
    use Metable;
    
    // Use meta for custom product attributes
    public function getCustomAttributes()
    {
        return $this->getAllMeta();
    }
}

class User extends Model
{
    use Metable;
    
    // Use meta for user preferences
    public function getPreferences()
    {
        return $this->getAllMeta();
    }
    
    public function setPreference($key, $value)
    {
        return $this->setMeta("pref_{$key}", $value);
    }
}
*/

/**
 * MIGRATION
 */

/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelMetaTable extends Migration
{
    public function up()
    {
        Schema::create('model_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('metable');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
            
            $table->unique(['metable_type', 'metable_id', 'key']);
            $table->index(['metable_type', 'metable_id']);
            $table->index('key');
        });
    }

    public function down()
    {
        Schema::dropIfExists('model_meta');
    }
}
*/
