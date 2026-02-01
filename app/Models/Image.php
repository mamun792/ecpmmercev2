<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'path',
        'original_name',
        'format',
        'width',
        'height',
        'size',
        'thumbnail_path',
        'checksum',
    ];

    // Accessor for full URL
    public function getUrlAttribute()
    {
        return url($this->path);
    }

    // Accessor for thumbnail URL
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path ? url($this->thumbnail_path) : $this->url;
    }
}
