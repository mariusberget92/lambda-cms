<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Seeds a fresh install with 10 themed posts, categories and tags
 * based on the selected genre. Uses picsum.photos for placeholder images.
 */
class GenreSeeder
{
    // ── Genre definitions ─────────────────────────────────────────────────────

    private const GENRES = [
        'food'        => ['label' => 'Food & Cooking',   'emoji' => '🍳', 'color' => '#f97316'],
        'travel'      => ['label' => 'Travel',           'emoji' => '✈️', 'color' => '#3b82f6'],
        'technology'  => ['label' => 'Technology',       'emoji' => '💻', 'color' => '#6366f1'],
        'health'      => ['label' => 'Health & Fitness', 'emoji' => '🏃', 'color' => '#22c55e'],
        'gardening'   => ['label' => 'Gardening',        'emoji' => '🌱', 'color' => '#84cc16'],
        'diy'         => ['label' => 'DIY & Crafts',     'emoji' => '🔨', 'color' => '#eab308'],
        'gaming'      => ['label' => 'Gaming',           'emoji' => '🎮', 'color' => '#8b5cf6'],
        'fashion'     => ['label' => 'Fashion & Style',  'emoji' => '👗', 'color' => '#ec4899'],
        'finance'     => ['label' => 'Finance',          'emoji' => '💰', 'color' => '#14b8a6'],
        'science'     => ['label' => 'Science',          'emoji' => '🔬', 'color' => '#06b6d4'],
        'movies'      => ['label' => 'Movies & TV',      'emoji' => '🎬', 'color' => '#f43f5e'],
        'books'       => ['label' => 'Books & Reading',  'emoji' => '📚', 'color' => '#a78bfa'],
        'music'       => ['label' => 'Music',            'emoji' => '🎵', 'color' => '#fb923c'],
        'sports'      => ['label' => 'Sports',           'emoji' => '⚽', 'color' => '#34d399'],
        'art'         => ['label' => 'Art & Design',     'emoji' => '🎨', 'color' => '#f472b6'],
        'programming' => ['label' => 'Programming',      'emoji' => '⌨️', 'color' => '#60a5fa'],
        'ai'          => ['label' => 'Artificial Intelligence', 'emoji' => '🤖', 'color' => '#a78bfa'],
        'cars'        => ['label' => 'Cars & Motorsport','emoji' => '🚗', 'color' => '#94a3b8'],
        'anime'       => ['label' => 'Anime & Manga',    'emoji' => '⛩️', 'color' => '#fb7185'],
        'empty'       => ['label' => 'None / Start Empty', 'emoji' => '✨', 'color' => '#64748b'],
    ];

    private array $data = [
        'food' => [
            'categories' => ['Recipes', 'Meal Prep', 'Baking', 'Healthy Eating'],
            'tags'        => ['breakfast', 'dinner', 'quick-meals', 'vegetarian', 'desserts', 'pasta', 'soups'],
            'posts'       => [
                ['title' => '10 Easy Weeknight Dinner Recipes',         'featured' => true],
                ['title' => 'The Ultimate Guide to Meal Prepping',      'featured' => false],
                ['title' => 'How to Bake the Perfect Sourdough Bread',  'featured' => true],
                ['title' => '5 Healthy Breakfast Ideas to Start Your Day', 'featured' => false],
                ['title' => 'Classic Italian Pasta: Tips and Tricks',   'featured' => false],
                ['title' => 'Vegetarian Soups for Every Season',        'featured' => false],
                ['title' => '30-Minute Meals the Whole Family Will Love', 'featured' => true],
                ['title' => 'Baking Science: Why Your Cakes Rise',      'featured' => false],
                ['title' => 'A Beginner\'s Guide to Knife Skills',      'featured' => false],
                ['title' => 'Seasonal Eating: What to Cook in Spring',  'featured' => false],
            ],
            'image_ids'   => [292, 312, 493, 431, 429, 488, 365, 376, 425, 431],
        ],
        'travel' => [
            'categories' => ['Destinations', 'Travel Tips', 'Budget Travel', 'Adventure'],
            'tags'        => ['europe', 'asia', 'budget', 'solo-travel', 'backpacking', 'hotels', 'food-tourism'],
            'posts'       => [
                ['title' => 'The Ultimate Packing List for a Week Abroad',   'featured' => true],
                ['title' => 'Hidden Gems: 10 Underrated European Cities',    'featured' => true],
                ['title' => 'How to Travel Southeast Asia on $30 a Day',     'featured' => false],
                ['title' => 'Solo Travel Guide for First-Time Adventurers',  'featured' => false],
                ['title' => 'The Best Street Food Cities in the World',      'featured' => true],
                ['title' => 'How to Find Cheap Flights Every Time',          'featured' => false],
                ['title' => 'A Week in Japan: Itinerary and Tips',           'featured' => false],
                ['title' => 'Sustainable Travel: How to Reduce Your Footprint', 'featured' => false],
                ['title' => 'Top 10 Most Scenic Train Journeys in Europe',   'featured' => false],
                ['title' => 'Working Remotely While Traveling the World',    'featured' => false],
            ],
            'image_ids'   => [1041, 1043, 1047, 1049, 1050, 1062, 1069, 1080, 1084, 1096],
        ],
        'technology' => [
            'categories' => ['Reviews', 'How-To Guides', 'Industry News', 'Gadgets'],
            'tags'        => ['smartphones', 'laptops', 'software', 'security', 'cloud', 'open-source', 'ai-tools'],
            'posts'       => [
                ['title' => 'The Best Laptops for Developers in 2026',      'featured' => true],
                ['title' => 'How Quantum Computing Will Change Everything',  'featured' => false],
                ['title' => 'A Beginner\'s Guide to Cybersecurity',         'featured' => true],
                ['title' => 'Open Source Tools You Should Be Using',        'featured' => false],
                ['title' => 'The Rise of Edge Computing Explained',         'featured' => false],
                ['title' => '5G: What It Means for Everyday Users',         'featured' => false],
                ['title' => 'How to Set Up a Home Lab on a Budget',         'featured' => true],
                ['title' => 'The Future of Wearable Technology',            'featured' => false],
                ['title' => 'Linux for Beginners: Getting Started Guide',   'featured' => false],
                ['title' => 'Cloud vs. On-Premise: Which Is Right for You?', 'featured' => false],
            ],
            'image_ids'   => [48, 160, 180, 188, 201, 273, 366, 453, 534, 583],
        ],
        'health' => [
            'categories' => ['Fitness', 'Nutrition', 'Mental Health', 'Sleep'],
            'tags'        => ['workout', 'running', 'yoga', 'meditation', 'diet', 'supplements', 'recovery'],
            'posts'       => [
                ['title' => '30-Day Fitness Challenge for Beginners',        'featured' => true],
                ['title' => 'The Science Behind Intermittent Fasting',       'featured' => false],
                ['title' => 'How to Build a Morning Exercise Routine',       'featured' => true],
                ['title' => 'Yoga for Stress Relief: 10 Poses to Try',       'featured' => false],
                ['title' => 'Why Sleep Is Your Most Powerful Recovery Tool', 'featured' => false],
                ['title' => 'The Best Foods to Eat Before and After a Workout', 'featured' => false],
                ['title' => 'Mindfulness Meditation for Beginners',          'featured' => true],
                ['title' => 'How to Set Realistic Fitness Goals',            'featured' => false],
                ['title' => 'Understanding Macros: Protein, Carbs, and Fat', 'featured' => false],
                ['title' => 'Running Your First 5K: A 6-Week Plan',         'featured' => false],
            ],
            'image_ids'   => [13, 17, 42, 46, 73, 91, 103, 137, 178, 199],
        ],
        'gardening' => [
            'categories' => ['Indoor Plants', 'Vegetable Gardens', 'Landscaping', 'Seasonal'],
            'tags'        => ['succulents', 'vegetables', 'composting', 'raised-beds', 'pruning', 'seeds', 'pests'],
            'posts'       => [
                ['title' => 'Getting Started with Raised Bed Gardening',    'featured' => true],
                ['title' => 'The Easiest Indoor Plants You Can\'t Kill',     'featured' => false],
                ['title' => 'How to Start a Vegetable Garden from Seed',     'featured' => true],
                ['title' => 'Composting 101: Turn Waste into Garden Gold',   'featured' => false],
                ['title' => 'Spring Gardening Tasks for a Beautiful Yard',   'featured' => false],
                ['title' => 'Succulents: Care Guide for Beginners',          'featured' => false],
                ['title' => 'Dealing with Common Garden Pests Naturally',    'featured' => false],
                ['title' => 'Pruning Fruit Trees: When and How to Do It',    'featured' => false],
                ['title' => 'Container Gardening on a Balcony or Patio',     'featured' => true],
                ['title' => 'Companion Planting: Pairing Plants for Success', 'featured' => false],
            ],
            'image_ids'   => [145, 175, 241, 286, 305, 338, 360, 373, 411, 414],
        ],
        'programming' => [
            'categories' => ['Tutorials', 'Best Practices', 'Career', 'Tools'],
            'tags'        => ['javascript', 'python', 'webdev', 'backend', 'testing', 'git', 'devops'],
            'posts'       => [
                ['title' => 'Why Every Developer Should Learn TypeScript',   'featured' => true],
                ['title' => 'Clean Code: Writing Functions That Do One Thing', 'featured' => false],
                ['title' => 'How to Ace Your Next Technical Interview',      'featured' => true],
                ['title' => 'Git Workflows for Teams: A Practical Guide',    'featured' => false],
                ['title' => 'Introduction to Test-Driven Development',       'featured' => false],
                ['title' => 'REST vs. GraphQL: Choosing the Right API',      'featured' => false],
                ['title' => 'Docker for Developers: Getting Started',        'featured' => true],
                ['title' => 'Understanding Async/Await in JavaScript',       'featured' => false],
                ['title' => 'Building a CLI Tool with Python',               'featured' => false],
                ['title' => 'Database Design Patterns You Should Know',      'featured' => false],
            ],
            'image_ids'   => [5, 11, 26, 54, 60, 67, 106, 119, 122, 133],
        ],
        'gaming' => [
            'categories' => ['Reviews', 'Guides', 'Industry News', 'Retro Gaming'],
            'tags'        => ['rpg', 'fps', 'indie', 'nintendo', 'playstation', 'pc-gaming', 'esports'],
            'posts'       => [
                ['title' => 'The 10 Best Open-World Games of the Decade',    'featured' => true],
                ['title' => 'Getting Into Indie Games: Where to Start',      'featured' => false],
                ['title' => 'How to Build a Budget Gaming PC in 2026',       'featured' => true],
                ['title' => 'The History of the RPG Genre',                  'featured' => false],
                ['title' => 'Esports: A Beginner\'s Guide to Competitive Gaming', 'featured' => false],
                ['title' => 'Retro Gaming: 10 Classic Games Worth Revisiting', 'featured' => false],
                ['title' => 'How Video Games Are Made: A Developer\'s Perspective', 'featured' => false],
                ['title' => 'The Best Co-Op Games to Play with Friends',     'featured' => true],
                ['title' => 'Understanding Game Mechanics: What Makes Games Fun', 'featured' => false],
                ['title' => 'The Rise of Mobile Gaming',                     'featured' => false],
            ],
            'image_ids'   => [159, 164, 166, 167, 174, 191, 214, 226, 228, 257],
        ],
        'ai' => [
            'categories' => ['Tutorials', 'Research', 'Ethics', 'Tools & Apps'],
            'tags'        => ['machine-learning', 'chatgpt', 'llm', 'computer-vision', 'automation', 'future', 'prompt-engineering'],
            'posts'       => [
                ['title' => 'A Beginner\'s Guide to Machine Learning',       'featured' => true],
                ['title' => 'How Large Language Models Actually Work',       'featured' => false],
                ['title' => 'Prompt Engineering: Getting the Best from AI',  'featured' => true],
                ['title' => 'AI Ethics: The Questions We Need to Ask',       'featured' => false],
                ['title' => 'The Best AI Tools to Boost Your Productivity',  'featured' => false],
                ['title' => 'Computer Vision Explained Simply',              'featured' => false],
                ['title' => 'Building Your First ML Model with Python',      'featured' => true],
                ['title' => 'AI in Healthcare: Opportunities and Risks',     'featured' => false],
                ['title' => 'The Difference Between AI, ML, and Deep Learning', 'featured' => false],
                ['title' => 'Generative AI: What Can It Really Do?',         'featured' => false],
            ],
            'image_ids'   => [8, 15, 20, 26, 33, 40, 45, 50, 55, 60],
        ],
    ];

    // Default fallback for genres without specific data
    private const DEFAULT_CATEGORIES = ['General', 'Tips & Tricks', 'News', 'Reviews'];
    private const DEFAULT_TAGS       = ['featured', 'popular', 'beginner', 'advanced', 'tips', 'guide'];
    private const DEFAULT_POSTS      = [
        ['title' => 'Getting Started: A Beginner\'s Guide',             'featured' => true],
        ['title' => '10 Things You Didn\'t Know About This Topic',       'featured' => false],
        ['title' => 'The Ultimate Resource List',                        'featured' => false],
        ['title' => 'Common Mistakes and How to Avoid Them',             'featured' => false],
        ['title' => 'An Honest Review After One Month',                  'featured' => true],
        ['title' => 'Top Tools and Resources for Beginners',             'featured' => false],
        ['title' => 'How to Make the Most of Your Time',                 'featured' => false],
        ['title' => 'A Deep Dive into the Basics',                       'featured' => false],
        ['title' => 'Community Highlights: Best of the Month',           'featured' => false],
        ['title' => 'What\'s New and What\'s Coming',                   'featured' => false],
    ];

    private const LOREM = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nSed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.\n\nNemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet consectetur.";

    // ── Public API ────────────────────────────────────────────────────────────

    public static function genres(): array
    {
        return self::GENRES;
    }

    /**
     * Seed categories, tags, and posts for the chosen genre.
     * Skips if genre is 'empty'.
     */
    public function seed(string $genre, User $admin): void
    {
        if ($genre === 'empty') {
            return;
        }

        $data = $this->data[$genre] ?? null;

        $categoryNames = $data['categories'] ?? self::DEFAULT_CATEGORIES;
        $tagNames      = $data['tags']       ?? self::DEFAULT_TAGS;
        $posts         = $data['posts']      ?? self::DEFAULT_POSTS;
        $imageIds      = $data['image_ids']  ?? range(1, 10);

        // Create categories
        $categories = collect($categoryNames)->map(fn ($name) => Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => '']
        ));

        // Create tags
        $tags = collect($tagNames)->map(fn ($name) => Tag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        ));

        // Create 10 posts
        foreach ($posts as $i => $postDef) {
            $title     = $postDef['title'];
            $featured  = $postDef['featured'];
            $imageId   = $imageIds[$i % count($imageIds)];
            $imageUrl  = "https://picsum.photos/id/{$imageId}/800/450";

            // Create a placeholder media record for the featured image
            $media = Media::create([
                'user_id'           => $admin->id,
                'filename'          => "seed-{$genre}-{$i}.jpg",
                'original_filename' => Str::slug($title) . '.jpg',
                'path'              => $imageUrl,
                'disk'              => 'external',
                'mime_type'         => 'image/jpeg',
                'type'              => 'image',
                'size'              => 0,
                'width'             => 800,
                'height'            => 450,
                'alt'               => $title,
            ]);

            $post = Post::create([
                'user_id'          => $admin->id,
                'title'            => $title,
                'slug'             => Str::slug($title),
                'body'             => self::LOREM,
                'excerpt'          => Str::limit(self::LOREM, 160),
                'status'           => 'published',
                'published_at'     => now()->subDays(rand(1, 60)),
                'featured'         => $featured,
                'featured_image_id' => $media->id,
            ]);

            // Attach random 1-2 categories
            $post->categories()->attach(
                $categories->random(min(2, $categories->count()))->pluck('id')
            );

            // Attach random 2-4 tags
            $post->tags()->attach(
                $tags->random(min(4, $tags->count()))->pluck('id')
            );
        }
    }
}
