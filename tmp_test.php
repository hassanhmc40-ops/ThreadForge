<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Register a test user
$user = \App\Models\User::where('email', 'jobtest@test.com')->first();
if (!$user) {
    $user = \App\Models\User::create([
        'name' => 'Job Test',
        'email' => 'jobtest@test.com',
        'password' => bcrypt('password'),
    ]);
    echo "Created user: {$user->id}\n";
} else {
    echo "Found user: {$user->id}\n";
}

// Create a blueprint
$bp = \App\Models\Blueprint::create([
    'user_id' => $user->id,
    'name' => 'Test Blueprint',
    'tone' => 'professional yet relaxed',
    'max_hashtags' => 2,
    'max_characters' => 280,
    'regles_supplementaires' => null,
]);
echo "Created blueprint: {$bp->id}\n";

// Submit content
$rc = \App\Models\RawContent::create([
    'user_id' => $user->id,
    'blueprint_id' => $bp->id,
    'contenu_brut' => "Laravel 13 introduces major performance improvements through its new query optimizer. The framework now supports lazy collection evaluation out of the box, reducing memory usage by up to 40% in data-heavy applications. This is a game-changer for API development with large datasets.",
    'statut' => 'en_attente',
]);
echo "Created raw content: {$rc->id}, status: {$rc->statut}\n";

// Dispatch the job
\App\Jobs\ProcessContentJob::dispatchSync($rc);
echo "Job completed.\n";

// Reload and check
$rc->refresh();
$gp = $rc->generatedPost;
echo "RawContent status: {$rc->statut}\n";
if ($gp) {
    echo "GeneratedPost: {$gp->id}\n";
    echo "  hook: {$gp->hook_propose}\n";
    echo "  body: " . json_encode($gp->body_points) . "\n";
    echo "  score: {$gp->technical_readability_score}\n";
    echo "  hashtags: " . json_encode($gp->suggested_hashtags) . "\n";
} else {
    echo "No GeneratedPost created.\n";
}
