<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use RuntimeException;

class CloudinaryUploader
{
    private ?Cloudinary $cloudinary = null;

    public function __construct()
    {
        $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? $_SERVER['CLOUDINARY_CLOUD_NAME'] ?? null;
        $apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? $_SERVER['CLOUDINARY_API_KEY'] ?? null;
        $apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? $_SERVER['CLOUDINARY_API_SECRET'] ?? null;

        if ($cloudName && $apiKey && $apiSecret) {
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
                'url' => [
                    'secure' => true,
                ],
            ]);
        }
    }

    public function uploadImage(string $filePath): string
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Image file not found: %s', $filePath));
        }

        if ($this->cloudinary !== null) {
            try {
                $result = $this->cloudinary->uploadApi()->upload($filePath, [
                    'resource_type' => 'image',
                ]);

                if (is_array($result) && isset($result['secure_url'])) {
                    return (string) $result['secure_url'];
                }
            } catch (\Throwable) {
                // Fall back to local storage if Cloudinary is not configured or upload fails.
            }
        }

        return $this->storeLocally($filePath);
    }

    private function storeLocally(string $filePath): string
    {
        $projectDir = dirname(__DIR__, 2);
        $uploadDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException(sprintf('Unable to create upload directory: %s', $uploadDir));
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $filename = uniqid('upload_', true) . ($extension !== '' ? '.' . $extension : '');
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!copy($filePath, $targetPath)) {
            throw new RuntimeException(sprintf('Unable to store image locally: %s', $filePath));
        }

        return '/uploads/' . $filename;
    }
}