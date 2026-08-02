<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function uploadImage(string|UploadedFile $file): string
    {
        $filePath = $file instanceof UploadedFile ? $file->getPathname() : $file;

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

        return $this->storeLocally(
            $filePath,
            $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
            $file instanceof UploadedFile ? $this->getMimeType($file) : null
        );
    }

    private function getMimeType(UploadedFile $file): ?string
    {
        $mimeType = $file->getClientMimeType();
        if ($mimeType !== null && $mimeType !== '') {
            return $mimeType;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $file->getPathname());
                finfo_close($finfo);
                if ($detected !== false) {
                    return $detected;
                }
            }
        }

        return null;
    }

    private function storeLocally(string $filePath, ?string $originalName = null, ?string $mimeType = null): string
    {
        $projectDir = dirname(__DIR__, 2);
        $uploadDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException(sprintf('Unable to create upload directory: %s', $uploadDir));
        }

        $extension = $this->resolveExtension($originalName, $mimeType, $filePath);
        $filename = uniqid('upload_', true) . ($extension !== '' ? '.' . $extension : '');
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!copy($filePath, $targetPath)) {
            throw new RuntimeException(sprintf('Unable to store image locally: %s', $filePath));
        }

        return '/uploads/' . $filename;
    }

    private function resolveExtension(?string $originalName, ?string $mimeType, string $filePath): string
    {
        if ($originalName !== null && $originalName !== '') {
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== '') {
                return $extension;
            }
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== '' && $extension !== 'tmp') {
            return $extension;
        }

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };
    }
}