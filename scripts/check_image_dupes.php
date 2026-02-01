<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Image;

$all = Image::all();
$grouped = $all->groupBy('checksum')->filter(function($g){ return $g->count() > 1; });
$report = $grouped->map(function($g){ return $g->map(function($r){ return ['id'=>$r->id,'path'=>$r->path,'original'=>$r->original_name]; }); });
print_r($report->toArray());
