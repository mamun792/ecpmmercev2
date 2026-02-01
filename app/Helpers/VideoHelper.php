<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * =====================================================
 * BIG TECH STYLE VIDEO HELPER
 * =====================================================
 *
 * Enterprise-grade video handling with:
 * - Multiple format support
 * - File validation
 * - Graceful error handling
 * - Comprehensive logging
 *
 * @author Enterprise Architecture Team
 */
class VideoHelper
{
    /**
     * Allowed video extensions
     */
    private const ALLOWED_EXTENSIONS = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];

    /**
     * Maximum file size in bytes (100MB)
     */
    private const MAX_FILE_SIZE = 104857600;

    /**
     * Upload video file
     *
     * @param UploadedFile $file The uploaded video file
     * @param string $path Destination path (relative to public)
     * @return string|null Full URL to the uploaded video, or null on failure
     */
    public static function uploadVideo(UploadedFile $file, string $path): ?string
    {
        try {
            // Validate file
            if (!self::validateVideo($file)) {
                Log::warning('🎬 [VideoHelper] Video validation failed', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]);
                return null;
            }

            // Ensure directory exists
            $fullPath = public_path($path);
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }

            // Generate unique filename
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'mp4';
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Move the file
            $file->move($fullPath, $filename);

            $relativePath = $path . '/' . $filename;

            Log::debug('🎬 [VideoHelper] Video uploaded successfully', [
                'filename' => $filename,
                'path' => $path,
            ]);

            return url($relativePath);

        } catch (\Exception $e) {
            Log::error('❌ [VideoHelper] Upload failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Validate video file
     */
    private static function validateVideo(UploadedFile $file): bool
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return false;
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return false;
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return false;
        }

        return true;
    }

    /**
     * Delete video from storage
     */
    public static function deleteVideo(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        try {
            // Remove domain/base URL
            $relativePath = str_replace(url('/'), '', $url);
            $relativePath = ltrim($relativePath, '/');
            $fullPath = public_path($relativePath);

            if (File::exists($fullPath)) {
                File::delete($fullPath);
                Log::debug('🎬 [VideoHelper] Video deleted', ['path' => $relativePath]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('❌ [VideoHelper] Delete failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get video information
     */
    public static function getVideoInfo(string $path): ?array
    {
        $fullPath = public_path(ltrim($path, '/'));

        if (!File::exists($fullPath)) {
            return null;
        }

        return [
            'size' => File::size($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
            'modified' => File::lastModified($fullPath),
        ];
    }
}
