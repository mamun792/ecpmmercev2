<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\ImageHelper;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class ImageHelperTest extends TestCase
{
    public function test_image_upload_and_mysql_storage()
    {
        // Create a fake image
        $fakeImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // Upload image
        $url = ImageHelper::uploadImage($fakeImage, 'uploads/test');

        // Assert URL is returned
        $this->assertStringStartsWith(url('/'), $url);

        // Assert MySQL record exists
        $imageRecord = Image::where('original_name', 'test.jpg')->first();
        $this->assertNotNull($imageRecord);
        $this->assertEquals(800, $imageRecord->width);
        $this->assertEquals(600, $imageRecord->height);
        $this->assertNotNull($imageRecord->thumbnail_path);

        // Assert file exists
        $path = str_replace(url('/'), '', $url);
        $this->assertTrue(File::exists(public_path($path)));

        // Test cache
        $images = ImageHelper::getImagesList(10);
        $this->assertGreaterThan(0, $images->count());

        // Test delete
        $deleted = ImageHelper::deleteImage($url);
        $this->assertTrue($deleted);

        // Assert MySQL record deleted
        $imageRecord = Image::where('original_name', 'test.jpg')->first();
        $this->assertNull($imageRecord);

        // Assert file deleted
        $this->assertFalse(File::exists(public_path($path)));
    }

    public function test_duplicate_upload_is_skipped()
    {
        $fakeImage = UploadedFile::fake()->image('dup.jpg', 400, 300);

        $url1 = ImageHelper::uploadImage($fakeImage, 'uploads/dup');
        $url2 = ImageHelper::uploadImage($fakeImage, 'uploads/dup');

        $this->assertEquals($url1, $url2);
        $this->assertEquals(1, Image::where('original_name', 'dup.jpg')->count());

        ImageHelper::deleteImage($url1);
    }

    public function test_uploadoriginal_fallback_dedupe()
    {
        // Create a non-image fake file to force fallback to uploadOriginal
        $fake = UploadedFile::fake()->create('notanimage.txt', 10, 'text/plain');

        $url1 = ImageHelper::uploadImage($fake, 'uploads/fallback');
        $url2 = ImageHelper::uploadImage($fake, 'uploads/fallback');

        $this->assertEquals($url1, $url2);
        $this->assertEquals(1, Image::where('original_name', 'notanimage.txt')->count());

        ImageHelper::deleteImage($url1);
    }

    public function test_upload_multiple_request_dedupe()
    {
        $file = UploadedFile::fake()->image('multi.jpg', 200, 200);

        $results = ImageHelper::uploadMultiple([$file, $file], 'uploads/multi');

        $this->assertCount(2, $results);
        $this->assertEquals($results[0], $results[1]);
        $this->assertEquals(1, Image::where('original_name', 'multi.jpg')->count());

        ImageHelper::deleteImage($results[0]);
    }

    public function test_thumbnail_is_deterministic_and_single()
    {
        $file = UploadedFile::fake()->image('thumbme.jpg', 300, 300);

        $url1 = ImageHelper::uploadImage($file, 'uploads/thumbs');
        $url2 = ImageHelper::uploadImage($file, 'uploads/thumbs');

        $this->assertEquals($url1, $url2);

        $record = Image::where('original_name', 'thumbme.jpg')->first();
        $this->assertNotNull($record->thumbnail_path);

        $thumbFull = public_path(ltrim($record->thumbnail_path, '/'));
        $this->assertTrue(File::exists($thumbFull));

        // Thumbnail filename should include checksum
        $destPath = public_path(str_replace(url('/'), '', $url1));
        $checksum = hash_file('sha256', $destPath);
        $expectedThumb = 'uploads/thumbs/thumb_' . $checksum . '.jpg';
        $this->assertEquals($expectedThumb, $record->thumbnail_path);

        ImageHelper::deleteImage($url1);
    }
}

