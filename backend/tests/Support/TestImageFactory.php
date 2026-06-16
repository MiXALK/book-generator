<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

final class TestImageFactory
{
    /**
     * Minimal valid JPEG (10x10) for tests without the GD extension.
     */
    public static function jpeg(string $name = 'child.jpg'): UploadedFile
    {
        $binary = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBUQEBAVFQ8QDxAPEA8QDw8PDw8QDw8QFREWFhURFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMsNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAoACgMBIgACEQEDEQH/xAAXAAADAQAAAAAAAAAAAAAAAAAAAQIG/8QAFhABAQEAAAAAAAAAAAAAAAAAAAEC/9oADAMBAAIQAxAAAAGmP//EABYQAQEBAAAAAAAAAAAAAAAAAAEDAP/aAAgBAQABBQLmP//EABYRAQEBAAAAAAAAAAAAAAAAAAEAAf/aAAgBAwEBPwGSP//EABYRAQEBAAAAAAAAAAAAAAAAAAEAAf/aAAgBAgEBPwGSP//Z',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $binary === false ? '' : $binary);
    }
}
