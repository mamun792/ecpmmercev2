<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Image;

$imgs = Image::where('path', 'like', 'storage/products%')->get();
if ($imgs->isEmpty()) {
    echo "No images found with path storage/products\n";
    exit;
}
foreach ($imgs as $img) {
    $p = public_path(ltrim($img->path, '/'));
    echo "ID: {$img->id} | path: {$img->path} | exists: " . (file_exists($p) ? 'yes' : 'no') . " | thumb: {$img->thumbnail_path}\n";
}
