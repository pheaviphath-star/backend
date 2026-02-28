<?php

namespace App\Services;

use Cloudinary\Cloudinary;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $config = config('services.cloudinary');

        if (empty($config['cloud_name']) || empty($config['api_key']) || empty($config['api_secret'])) {
            throw new \RuntimeException('Cloudinary credentials are not configured. Please set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET in .env');
        }

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $config['cloud_name'] ?? null,
                'api_key' => $config['api_key'] ?? null,
                'api_secret' => $config['api_secret'] ?? null,
            ],
        ]);
    }

    public function uploadImage(string $localFilePath, string $folder = 'profiles'): array
    {
        $result = $this->cloudinary->uploadApi()->upload($localFilePath, [
            'folder' => $folder,
            'resource_type' => 'image',
        ]);

        return [
            'url' => $result['secure_url'] ?? null,
            'public_id' => $result['public_id'] ?? null,
        ];
    }

    public function deleteImage(?string $publicId): void
    {
        if (!$publicId) {
            return;
        }

        $this->cloudinary->uploadApi()->destroy($publicId, [
            'resource_type' => 'image',
            'invalidate' => true,
        ]);
    }
}
