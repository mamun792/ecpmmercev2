<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use App\Models\Image;

/**
 * =====================================================
 * BIG TECH STYLE IMAGE HELPER
 * =====================================================
 *
 * Enterprise-grade image handling with:
 * - WebP conversion with fallback support
 * - Multiple format support (JPEG, PNG, GIF, WebP)
 * - Automatic optimization and compression
 * - Graceful error handling
 * - Comprehensive logging
 *
 * @author Enterprise Architecture Team
 */
class ImageHelper
{
    /**
     * Default compression quality
     */
    private const DEFAULT_QUALITY = 80;

    /**
     * WebP compression quality
     */
    private const WEBP_QUALITY = 75;

    /**
     * Upload and optimize image
     *
     * @param UploadedFile $file The uploaded file
     * @param string $path Destination path (relative to public)
     * @return string Full URL to the uploaded image
     */
    public static function uploadImage(UploadedFile $file, string $path): string
    {
        // Set execution time limit for large images
        ini_set('max_execution_time', 120); // 2 minutes

        try {
            // Ensure directory exists
            $fullPath = public_path($path);
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }

            $extension = strtolower($file->getClientOriginalExtension());

            // Check for duplicate using checksum before processing
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath)) {
                $checksum = hash_file('sha256', $realPath);
                if ($checksum) {
                    $existing = Image::where('checksum', $checksum)->first();
                    if ($existing && File::exists(public_path($existing->path))) {
                        return url($existing->path);
                    }
                }
            }

            // Check if we can use WebP
            $canUseWebP = self::supportsWebP();

            // Determine output format and filename
            $outputFormat = $canUseWebP ? 'webp' : self::getFallbackFormat($extension);
            $filename = time() . '_' . uniqid() . '.' . $outputFormat;
            $destination = $fullPath . '/' . $filename;

            // Process image
            $image = self::createImageResource($file, $extension);

            if ($image === false) {
                return self::uploadOriginal($file, $path);
            }

            // Get dimensions before saving
            $width = imagesx($image);
            $height = imagesy($image);

            // Save optimized image
            $saved = self::saveImage($image, $destination, $outputFormat);
            imagedestroy($image);

            if (!$saved) {
                return self::uploadOriginal($file, $path);
            }

            // Optimize with Spatie
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($destination);

            // Create thumbnail
            $thumbnailPath = self::createThumbnail($destination, $path, $filename, $outputFormat);

            // Get file size and checksum
            $fileSize = filesize($destination);
            $checksum = hash_file('sha256', $destination);

            // Ensure destination file exists before creating DB record
            if (!file_exists($destination)) {
                Log::error('📷 [ImageHelper] Destination file missing before DB insert', [
                    'destination' => $destination,
                    'path' => $path,
                    'filename' => $filename,
                ]);
                throw new \RuntimeException('Image processing failed: destination missing');
            }

            // Attempt to store in MySQL with concurrency handling
            try {
                $imageRecord = Image::create([
                    'path' => $path . '/' . $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'format' => $outputFormat,
                    'width' => $width,
                    'height' => $height,
                    'size' => $fileSize,
                    'thumbnail_path' => $thumbnailPath,
                    'checksum' => $checksum,
                ]);

                self::clearCache();
                return url($path . '/' . $filename);

            } catch (\Illuminate\Database\QueryException $qe) {
                // Handle duplicate checksum race condition

                $existing = $checksum ? Image::where('checksum', $checksum)->first() : null;
                if ($existing) {
                    // Delete duplicate files created before DB check
                    @unlink($destination);
                    if ($thumbnailPath) {
                        @unlink(public_path($thumbnailPath));
                    }
                    return url($existing->path);
                }
                throw $qe;
            }

        } catch (\Exception $e) {
            Log::error('❌ [ImageHelper] Upload failed, using fallback', [
                'error' => $e->getMessage(),
            ]);

            // Ultimate fallback: just upload the original
            return self::uploadOriginal($file, $path);
        }
    }

    /**
     * Upload original file without processing
     */
    private static function uploadOriginal(UploadedFile $file, string $path): string
    {
        $fullPath = public_path($path);
        if (!File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        // Check for duplicate before uploading
        $realPath = $file->getRealPath();
        $checksum = $realPath && file_exists($realPath) ? hash_file('sha256', $realPath) : null;
        if ($checksum) {
            $existing = Image::where('checksum', $checksum)->first();
            if ($existing && File::exists(public_path($existing->path))) {
                return url($existing->path);
            }
        }

        $filename = time() . '_' . uniqid() . '.' . $extension;
        $destination = $fullPath . '/' . $filename;
        $file->move($fullPath, $filename);

        // Get image metadata
        $size = filesize($destination);
        $imageInfo = @getimagesize($destination);
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;

        // Create thumbnail
        $thumbPath = self::createThumbnail($destination, $path, $filename, $extension);

        // Compute checksum if not already computed
        $checksum = $checksum ?: hash_file('sha256', $destination);

        // Ensure moved file exists before DB insert
        if (!file_exists($destination)) {
            Log::error('📷 [ImageHelper] Destination file missing before DB insert (uploadOriginal)', [
                'destination' => $destination,
                'path' => $path,
                'filename' => $filename,
            ]);
            throw new \RuntimeException('Image processing failed: destination missing');
        }

        // Store metadata in DB
        try {
            $imageRecord = Image::create([
                'path' => $path . '/' . $filename,
                'original_name' => $file->getClientOriginalName(),
                'format' => $extension,
                'width' => $width,
                'height' => $height,
                'size' => $size,
                'thumbnail_path' => $thumbPath,
                'checksum' => $checksum,
            ]);

            self::clearCache();
        } catch (\Illuminate\Database\QueryException $qe) {
            // Handle race-condition duplicate
            $existing = $checksum ? Image::where('checksum', $checksum)->first() : null;
            if ($existing) {
                @unlink($destination);
                if ($thumbPath) {
                    @unlink(public_path($thumbPath));
                }
                return url($existing->path);
            }
            throw $qe;
        }

        return url($path . '/' . $filename);
    }

    /**
     * Create thumbnail for uploaded image
     */
    private static function createThumbnail(string $sourcePath, string $path, string $filename, string $format): ?string
    {
        try {
            // Use deterministic thumbnail name based on source checksum
            if (!file_exists($sourcePath)) {
                return null;
            }

            $checksum = hash_file('sha256', $sourcePath);
            if (!$checksum) {
                return null;
            }

            $thumbFilename = 'thumb_' . $checksum . '.' . $format;
            $thumbFullPath = public_path($path) . '/' . $thumbFilename;

            // If thumbnail already exists, reuse it
            if (File::exists($thumbFullPath)) {
                return $path . '/' . $thumbFilename;
            }

            $image = self::createImageFromPath($sourcePath);
            if (!$image) {
                return null;
            }

            $origWidth = imagesx($image);
            $origHeight = imagesy($image);

            // Thumbnail size: 150x150
            $thumbWidth = 150;
            $thumbHeight = 150;

            $ratio = min($thumbWidth / $origWidth, $thumbHeight / $origHeight);
            $newWidth = (int) ($origWidth * $ratio);
            $newHeight = (int) ($origHeight * $ratio);

            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

            // Fill with white background
            $white = imagecolorallocate($thumbnail, 255, 255, 255);
            imagefill($thumbnail, 0, 0, $white);

            // Center the resized image
            $x = (int) (($thumbWidth - $newWidth) / 2);
            $y = (int) (($thumbHeight - $newHeight) / 2);

            imagecopyresampled($thumbnail, $image, $x, $y, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            // Save thumbnail
            self::saveImage($thumbnail, $thumbFullPath, $format);

            imagedestroy($image);
            imagedestroy($thumbnail);

            return $path . '/' . $thumbFilename;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create image resource from file path
     */
    private static function createImageFromPath(string $path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return @imagecreatefromjpeg($path);
            case 'png':
                return @imagecreatefrompng($path);
            case 'gif':
                return @imagecreatefromgif($path);
            case 'webp':
                return @imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Check if GD supports WebP
     */
    private static function supportsWebP(): bool
    {
        if (!function_exists('gd_info')) {
            return false;
        }

        $gdInfo = gd_info();
        return isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'];
    }

    /**
     * Get fallback format if WebP is not supported
     */
    private static function getFallbackFormat(string $originalExtension): string
    {
        $supported = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($originalExtension, $supported)) {
            return $originalExtension === 'jpeg' ? 'jpg' : $originalExtension;
        }

        return 'jpg'; // Default fallback
    }

    /**
     * Create GD image resource from uploaded file
     */
    private static function createImageResource(UploadedFile $file, string $extension)
    {
        $realPath = $file->getRealPath();

        if (!$realPath || !file_exists($realPath)) {
            return false;
        }

        $image = false;

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                if (function_exists('imagecreatefromjpeg')) {
                    $image = @imagecreatefromjpeg($realPath);
                }
                break;

            case 'png':
                if (function_exists('imagecreatefrompng')) {
                    $image = @imagecreatefrompng($realPath);
                    if ($image !== false) {
                        // Preserve transparency
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                }
                break;

            case 'gif':
                if (function_exists('imagecreatefromgif')) {
                    $image = @imagecreatefromgif($realPath);
                }
                break;

            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($realPath);
                }
                break;

            default:
                // Try to detect from content
                $image = @imagecreatefromstring(file_get_contents($realPath));
                break;
        }

        return $image;
    }

    /**
     * Save image in specified format with optimization
     */
    private static function saveImage($image, string $destination, string $format): bool
    {
        if (!$image) {
            return false;
        }

        $saved = false;

        switch ($format) {
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saved = @imagewebp($image, $destination, self::WEBP_QUALITY);
                }
                break;

            case 'jpg':
            case 'jpeg':
                if (function_exists('imagejpeg')) {
                    $saved = @imagejpeg($image, $destination, self::DEFAULT_QUALITY);
                }
                break;

            case 'png':
                if (function_exists('imagepng')) {
                    // PNG compression: 0 (no compression) to 9 (max compression)
                    // Quality 80 ≈ compression level 2
                    $saved = @imagepng($image, $destination, 6);
                }
                break;

            case 'gif':
                if (function_exists('imagegif')) {
                    $saved = @imagegif($image, $destination);
                }
                break;
        }

        return $saved;
    }

    /**
     * Delete image from storage
     */
    public static function deleteImage(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        try {
            // Remove domain/base URL
            $relativePath = str_replace(url('/'), '', $url);
            $relativePath = ltrim($relativePath, '/');
            $fullPath = public_path($relativePath);

            // Fetch DB record first
            $imageRecord = Image::where('path', $relativePath)->first();

            // Delete main file if exists
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            // Delete thumbnail if exists (only if no other record references it)
            if ($imageRecord && $imageRecord->thumbnail_path) {
                $thumbPath = $imageRecord->thumbnail_path;
                $thumbFullPath = public_path(ltrim($thumbPath, '/'));

                // Count how many images reference this thumbnail
                $refCount = Image::where('thumbnail_path', $thumbPath)->count();
                if ($refCount <= 1 && File::exists($thumbFullPath)) {
                    File::delete($thumbFullPath);
                }
            }

            // Delete DB record if exists
            if ($imageRecord) {
                $imageRecord->delete();
            }

            // Clear cache
            self::clearCache();

            return true;

        } catch (\Exception $e) {
            Log::error('❌ [ImageHelper] Delete failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get image dimensions
     */
    public static function getDimensions(string $path): ?array
    {
        $fullPath = public_path(ltrim($path, '/'));

        if (!File::exists($fullPath)) {
            return null;
        }

        $size = @getimagesize($fullPath);

        if ($size === false) {
            return null;
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
            'type' => $size[2],
            'mime' => $size['mime'] ?? null,
        ];
    }

    /**
     * Resize image maintaining aspect ratio
     */
    public static function resize(string $sourcePath, int $maxWidth, int $maxHeight): ?string
    {
        try {
            $fullPath = public_path(ltrim($sourcePath, '/'));

            if (!File::exists($fullPath)) {
                return null;
            }

            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo === false) {
                return null;
            }

            [$origWidth, $origHeight, $type] = $imageInfo;

            // Calculate new dimensions
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);

            if ($ratio >= 1) {
                // Image is already smaller than max dimensions
                return $sourcePath;
            }

            $newWidth = (int) ($origWidth * $ratio);
            $newHeight = (int) ($origHeight * $ratio);

            // Create source image
            $source = self::createImageFromPath($fullPath);
            if (!$source) {
                return null;
            }

            // Create new image
            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG/GIF
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize
            imagecopyresampled(
                $resized, $source,
                0, 0, 0, 0,
                $newWidth, $newHeight, $origWidth, $origHeight
            );

            // Save resized image
            $pathInfo = pathinfo($fullPath);
            $resizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $newWidth . 'x' . $newHeight . '.' . $pathInfo['extension'];

            $extension = strtolower($pathInfo['extension']);
            self::saveImage($resized, $resizedPath, $extension);

            imagedestroy($source);
            imagedestroy($resized);

            return str_replace(public_path(), '', $resizedPath);

        } catch (\Exception $e) {
            Log::error('❌ [ImageHelper] Resize failed', [
                'path' => $sourcePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Upload multiple files with request-level dedupe
     * Returns array of URLs in the same order as $files
     */
    public static function uploadMultiple(array $files, string $path): array
    {
        $results = [];
        $seen = [];

        foreach ($files as $file) {
            // Key based on original name + size to detect dupes in same request
            $key = $file->getClientOriginalName() . '|' . $file->getSize();
            if (isset($seen[$key])) {
                $results[] = $seen[$key];
                continue;
            }

            $url = self::uploadImage($file, $path);
            $seen[$key] = $url;
            $results[] = $url;
        }

        return $results;
    }

    /**
     * Get cached list of images
     */
    public static function getImagesList(int $limit = 50)
    {
        return Cache::remember("images.list.{$limit}", 3600, function () use ($limit) {
            return Image::latest()->take($limit)->get();
        });
    }

    /**
     * Ensure deterministic thumbnail exists for given Image model and update record if needed
     * Returns thumbnail path or null
     */
    public static function ensureDeterministicThumbnailForImage(Image $image): ?string
    {
        try {
            $sourceFull = public_path(ltrim($image->path, '/'));
            if (!File::exists($sourceFull)) {
                return null;
            }

            $thumbPath = self::createThumbnail($sourceFull, dirname($image->path), basename($image->path), pathinfo($image->path, PATHINFO_EXTENSION));

            if ($thumbPath && $image->thumbnail_path !== $thumbPath) {
                $image->thumbnail_path = $thumbPath;
                $image->save();
                self::clearCache();
            }

            return $thumbPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear image cache
     */
    public static function clearCache()
    {
        Cache::forget('images.list.50'); // Clear common cache keys
    }
}
