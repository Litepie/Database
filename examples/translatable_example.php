<?php

namespace App\Examples;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\App;

/**
 * Translatable Trait Examples
 * 
 * This file demonstrates all features of the Translatable trait.
 * The trait provides multi-language content support for models.
 * 
 * Setup:
 * 1. Add trait to model: use Litepie\Database\Traits\Translatable;
 * 2. Define translatable attributes: protected array $translatable = ['title', 'content'];
 * 3. Run migration for model_translations table
 * 4. Start translating content
 */
class TranslatableExamples
{
    /**
     * Example 1: Basic Translation
     * Translate model attributes to different languages.
     */
    public function example1(): void
    {
        $post = Post::find(1);
        
        // Original content (English)
        $post->title = 'Welcome to Laravel';
        $post->content = 'This is an amazing framework';
        $post->save();
        
        // Translate to Spanish
        $post->translate('es', [
            'title' => 'Bienvenido a Laravel',
            'content' => 'Este es un framework increíble',
        ]);
        
        // Translate to French
        $post->translate('fr', [
            'title' => 'Bienvenue sur Laravel',
            'content' => 'Ceci est un framework incroyable',
        ]);
        
        // Translate to German
        $post->translate('de', [
            'title' => 'Willkommen bei Laravel',
            'content' => 'Dies ist ein erstaunliches Framework',
        ]);
    }

    /**
     * Example 2: Get Translations
     * Retrieve translated content for specific locales.
     */
    public function example2(): void
    {
        $post = Post::find(1);
        
        // Get translation for specific attribute and locale
        $spanishTitle = $post->getTranslation('title', 'es');
        echo $spanishTitle; // 'Bienvenido a Laravel'
        
        // Get translation with fallback
        $germanTitle = $post->getTranslation('title', 'de', 'Default Title');
        
        // Get current locale translation (uses App::getLocale())
        App::setLocale('es');
        $currentTitle = $post->getTranslation('title'); // Spanish version
    }

    /**
     * Example 3: Automatic Translation Loading
     * Access translated attributes directly.
     */
    public function example3(): void
    {
        $post = Post::find(1);
        
        // Set locale
        $post->setLocale('es');
        
        // Access attributes - automatically returns translated value
        echo $post->title; // Returns Spanish translation
        echo $post->content; // Returns Spanish translation
        
        // Switch locale
        $post->setLocale('fr');
        echo $post->title; // Returns French translation
        
        // Original attributes still accessible
        $originalTitle = $post->getOriginal('title');
    }

    /**
     * Example 4: Get All Translations for Attribute
     * Retrieve all language versions of an attribute.
     */
    public function example4(): void
    {
        $post = Post::find(1);
        
        // Get all translations for title
        $titleTranslations = $post->getTranslations('title');
        
        /*
        Returns:
        [
            'en' => 'Welcome to Laravel',
            'es' => 'Bienvenido a Laravel',
            'fr' => 'Bienvenue sur Laravel',
            'de' => 'Willkommen bei Laravel',
        ]
        */
        
        foreach ($titleTranslations as $locale => $title) {
            echo "{$locale}: {$title}\n";
        }
    }

    /**
     * Example 5: Get All Translations for Locale
     * Retrieve all translated attributes for a locale.
     */
    public function example5(): void
    {
        $post = Post::find(1);
        
        // Get all Spanish translations
        $spanishTranslations = $post->getAllTranslations('es');
        
        /*
        Returns:
        [
            'title' => 'Bienvenido a Laravel',
            'content' => 'Este es un framework increíble',
            'excerpt' => 'Resumen en español',
        ]
        */
        
        // Get translations for current locale
        App::setLocale('fr');
        $frenchTranslations = $post->getAllTranslations();
    }

    /**
     * Example 6: Check Translation Existence
     * Verify if translations exist.
     */
    public function example6(): void
    {
        $post = Post::find(1);
        
        // Check if specific translation exists
        if ($post->hasTranslation('title', 'es')) {
            echo "Spanish translation exists\n";
        }
        
        // Check for current locale
        App::setLocale('de');
        if ($post->hasTranslation('title')) {
            echo "German translation exists\n";
        }
        
        // Check if model has any translations
        if ($post->hasTranslations()) {
            echo "Post has translations\n";
        }
        
        // Check if has translations for specific locale
        if ($post->hasTranslations('es')) {
            echo "Post has Spanish translations\n";
        }
    }

    /**
     * Example 7: Delete Translations
     * Remove translations from database.
     */
    public function example7(): void
    {
        $post = Post::find(1);
        
        // Delete specific translation
        $post->deleteTranslation('title', 'es');
        
        // Delete all translations for a locale
        $post->deleteTranslationsForLocale('de');
        
        // Delete all translations
        $post->deleteAllTranslations();
    }

    /**
     * Example 8: Fallback to Default Locale
     * Automatic fallback when translation is missing.
     */
    public function example8(): void
    {
        $post = Post::find(1);
        
        // Only has English and Spanish translations
        $post->title = 'English Title';
        $post->translate('es', ['title' => 'Título en Español']);
        
        // Try to get Italian translation (doesn't exist)
        $post->setLocale('it');
        $title = $post->getTranslation('title');
        // Returns 'English Title' (falls back to default locale)
        
        // Disable fallback
        $post->useTranslatableFallback = false;
        $title = $post->getTranslation('title');
        // Returns original attribute value or null
    }

    /**
     * Example 9: Available Locales
     * Get list of languages available for a model.
     */
    public function example9(): void
    {
        $post = Post::find(1);
        
        // Get available locales
        $locales = $post->getAvailableLocales();
        // Returns: ['en', 'es', 'fr', 'de']
        
        foreach ($locales as $locale) {
            $title = $post->getTranslation('title', $locale);
            echo "{$locale}: {$title}\n";
        }
    }

    /**
     * Example 10: Translation Completeness
     * Check translation progress.
     */
    public function example10(): void
    {
        $post = Post::find(1);
        
        // Get translation completeness percentage
        $spanishCompletion = $post->getTranslationCompleteness('es');
        echo "Spanish translation: {$spanishCompletion}% complete\n";
        
        // Get missing translations
        $missing = $post->getMissingTranslations('es');
        /*
        Returns: ['excerpt', 'meta_description']
        (if these translatable attributes haven't been translated)
        */
        
        foreach ($missing as $attribute) {
            echo "Missing Spanish translation for: {$attribute}\n";
        }
    }

    /**
     * Example 11: Copy Translations Between Models
     * Duplicate translations to another model.
     */
    public function example11(): void
    {
        $originalPost = Post::find(1);
        $newPost = Post::find(2);
        
        // Copy all translations
        $originalPost->copyTranslationsTo($newPost);
        
        // Copy specific locales only
        $originalPost->copyTranslationsTo($newPost, ['es', 'fr']);
    }

    /**
     * Example 12: Duplicate Translations
     * Copy translations from one locale to another.
     */
    public function example12(): void
    {
        $post = Post::find(1);
        
        // Duplicate English to Italian (as starting point)
        $post->duplicateTranslations('en', 'it');
        
        // Duplicate without overwriting existing
        $post->duplicateTranslations('en', 'es', overwrite: false);
        
        // Duplicate and overwrite
        $post->duplicateTranslations('en', 'es', overwrite: true);
    }

    /**
     * Example 13: Query Models by Translation
     * Find models with specific translated content.
     */
    public function example13(): void
    {
        // Find posts with Spanish title "Laravel"
        $posts = Post::whereTranslation('title', 'Laravel', 'es')->get();
        
        // Find posts that have French translations
        $frenchPosts = Post::whereLocale('fr')->get();
        
        // Find posts translated in Spanish
        $spanishPosts = Post::translatedIn('es')->get();
        
        // Eager load translations
        $posts = Post::withTranslations('es')->get();
        
        // Load all translations
        $posts = Post::withTranslations()->get();
    }

    /**
     * Example 14: Multi-Language Blog Example
     * Complete blog post workflow.
     */
    public function example14(): void
    {
        // Create post in default language (English)
        $post = Post::create([
            'title' => 'Getting Started with Laravel',
            'content' => 'Laravel is a web application framework...',
            'excerpt' => 'Learn Laravel basics',
        ]);
        
        // Add Spanish translation
        $post->translate('es', [
            'title' => 'Comenzando con Laravel',
            'content' => 'Laravel es un framework de aplicaciones web...',
            'excerpt' => 'Aprende los conceptos básicos de Laravel',
        ]);
        
        // Add French translation
        $post->translate('fr', [
            'title' => 'Débuter avec Laravel',
            'content' => 'Laravel est un framework d\'application web...',
            'excerpt' => 'Apprenez les bases de Laravel',
        ]);
        
        // Display in user's language
        App::setLocale(request('lang', 'en'));
        
        $data = [
            'title' => $post->title, // Auto-translated
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'locale' => $post->getLocale(),
            'available_locales' => $post->getAvailableLocales(),
        ];
        
        // Use $data in view or return as response
    }

    /**
     * Example 15: E-commerce Product Translations
     * Translate product information.
     */
    public function example15(): void
    {
        $product = Product::find(1);
        
        // English (default)
        $product->name = 'Wireless Headphones';
        $product->description = 'High-quality wireless headphones with noise cancellation';
        $product->save();
        
        // Spanish
        $product->translate('es', [
            'name' => 'Auriculares Inalámbricos',
            'description' => 'Auriculares inalámbricos de alta calidad con cancelación de ruido',
        ]);
        
        // German
        $product->translate('de', [
            'name' => 'Kabellose Kopfhörer',
            'description' => 'Hochwertige kabellose Kopfhörer mit Geräuschunterdrückung',
        ]);
        
        // Display in customer's language
        $userLocale = auth()->user()->locale ?? 'en';
        $product->setLocale($userLocale);
        
        echo $product->name; // Translated name
        echo $product->description; // Translated description
    }

    /**
     * Example 16: Partial Translations
     * Not all attributes need translation.
     */
    public function example16(): void
    {
        $post = Post::find(1);
        
        // Only translate title and excerpt, not content
        $post->translate('es', [
            'title' => 'Título en Español',
            'excerpt' => 'Resumen en Español',
            // content not translated
        ]);
        
        $post->setLocale('es');
        echo $post->title; // Spanish
        echo $post->excerpt; // Spanish
        echo $post->content; // Falls back to English
    }

    /**
     * Example 17: Translation Management Dashboard
     * Admin interface for managing translations.
     */
    public function example17(): void
    {
        $post = Post::find(1);
        
        // Get translation status for all locales
        $supportedLocales = ['en', 'es', 'fr', 'de', 'it'];
        $status = [];
        
        foreach ($supportedLocales as $locale) {
            $status[$locale] = [
                'completion' => $post->getTranslationCompleteness($locale),
                'missing' => $post->getMissingTranslations($locale),
                'exists' => $post->hasTranslations($locale),
            ];
        }
        
        /*
        Returns:
        [
            'en' => ['completion' => 100, 'missing' => [], 'exists' => true],
            'es' => ['completion' => 66.67, 'missing' => ['content'], 'exists' => true],
            'fr' => ['completion' => 100, 'missing' => [], 'exists' => true],
            'de' => ['completion' => 33.33, 'missing' => ['content', 'excerpt'], 'exists' => true],
            'it' => ['completion' => 0, 'missing' => ['title', 'content', 'excerpt'], 'exists' => false],
        ]
        */
    }

    /**
     * Example 18: API Responses with Translations
     * Return translated content in API.
     */
    public function example18(): void
    {
        // Get locale from request
        $locale = request('locale', 'en');
        
        $posts = Post::withTranslations($locale)->get();
        
        $translatedPosts = $posts->map(function ($post) use ($locale) {
            $post->setLocale($locale);
            
            return [
                'id' => $post->id,
                'title' => $post->title, // Translated
                'content' => $post->content, // Translated
                'locale' => $locale,
                'available_translations' => $post->getAvailableLocales(),
            ];
        });
        
        // Use $translatedPosts in response
    }

    /**
     * Example 19: Middleware for Locale Detection
     * Automatically set locale based on request.
     */
    public function example19(): void
    {
        // In middleware:
        /*
        public function handle($request, Closure $next)
        {
            $locale = $request->header('Accept-Language', 'en');
            App::setLocale($locale);
            
            return $next($request);
        }
        */
        
        // Models automatically use the set locale
        $post = Post::find(1);
        echo $post->title; // Uses App::getLocale()
    }

    /**
     * Example 20: Complete Multi-Language CMS Example
     * Full content management workflow.
     */
    public function example20(): void
    {
        // Create content in default language
        $post = Post::create([
            'title' => 'Laravel 11 Released',
            'content' => 'The Laravel team has released version 11...',
            'excerpt' => 'New features and improvements',
            'status' => 'published',
        ]);
        
        // Translate to multiple languages
        $translations = [
            'es' => [
                'title' => 'Laravel 11 Lanzado',
                'content' => 'El equipo de Laravel ha lanzado la versión 11...',
                'excerpt' => 'Nuevas características y mejoras',
            ],
            'fr' => [
                'title' => 'Laravel 11 Publié',
                'content' => 'L\'équipe Laravel a publié la version 11...',
                'excerpt' => 'Nouvelles fonctionnalités et améliorations',
            ],
            'de' => [
                'title' => 'Laravel 11 Veröffentlicht',
                'content' => 'Das Laravel-Team hat Version 11 veröffentlicht...',
                'excerpt' => 'Neue Funktionen und Verbesserungen',
            ],
        ];
        
        foreach ($translations as $locale => $content) {
            $post->translate($locale, $content);
        }
        
        // Frontend: Display in user's language
        $userLocale = session('locale', 'en');
        
        $posts = Post::where('status', 'published')
            ->translatedIn($userLocale)
            ->withTranslations($userLocale)
            ->get();
        
        foreach ($posts as $post) {
            $post->setLocale($userLocale);
            
            echo "Title: {$post->title}\n";
            echo "Content: {$post->content}\n";
            echo "Available in: " . implode(', ', $post->getAvailableLocales()) . "\n\n";
        }
        
        // Admin: Translation progress report
        foreach ($posts as $post) {
            echo "Post: {$post->id}\n";
            foreach (['en', 'es', 'fr', 'de'] as $locale) {
                $completion = $post->getTranslationCompleteness($locale);
                echo "  {$locale}: {$completion}%\n";
            }
        }
    }
}

/**
 * USAGE IN MODELS
 */

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Translatable;

class Post extends Model
{
    use Translatable;
    
    // Define which attributes are translatable
    protected array $translatable = [
        'title',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
    ];
    
    // Optional: Enable/disable fallback
    protected bool $useTranslatableFallback = true;
}

class Product extends Model
{
    use Translatable;
    
    protected array $translatable = [
        'name',
        'description',
        'short_description',
        'features',
    ];
}

class Category extends Model
{
    use Translatable;
    
    protected array $translatable = [
        'name',
        'description',
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

class CreateModelTranslationsTable extends Migration
{
    public function up()
    {
        Schema::create('model_translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 10);
            $table->string('attribute');
            $table->text('value')->nullable();
            $table->timestamps();
            
            $table->unique(
                ['translatable_type', 'translatable_id', 'locale', 'attribute'],
                'translations_unique'
            );
            $table->index(['translatable_type', 'translatable_id']);
            $table->index('locale');
        });
    }

    public function down()
    {
        Schema::dropIfExists('model_translations');
    }
}
*/
