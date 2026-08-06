<?php

namespace App\Service;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3Uploader
{
    private ?S3Client $s3Client = null;
    private ?string $bucket = null;
    private ?LoggerInterface $logger = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->bucket = $_ENV['S3_UPLOADS_BUCKET'] ?? $_SERVER['S3_UPLOADS_BUCKET'] ?? getenv('S3_UPLOADS_BUCKET') ?: null;

        if ($this->bucket) {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => 'eu-west-1',
            ]);
        }
    }

    public function uploadImage(string|UploadedFile $file): string
    {
        $filePath = $file instanceof UploadedFile ? $file->getPathname() : $file;

        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Image file not found: %s', $filePath));
        }

        if ($this->s3Client !== null && $this->bucket !== null) {
            try {
                $extension = $this->resolveExtension(
                    $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
                    $file instanceof UploadedFile ? $file->getClientMimeType() : null,
                    $filePath
                );
                $key = uniqid('upload_', true) . ($extension !== '' ? '.' . $extension : '');

                $result = $this->s3Client->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'SourceFile' => $filePath,
                    'ContentType' => $file instanceof UploadedFile ? $file->getClientMimeType() : 'image/jpeg',
                    'ACL' => 'public-read',
                ]);

                return (string) $result['ObjectURL'];
            } catch (AwsException $e) {
                $this->logger?->error('S3 upload failed, falling back to local storage', [
                    'exception' => $e->getMessage(),
                    'bucket' => $this->bucket,
                ]);
            }
        }

        return $this->storeLocally(
            $filePath,
            $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
            $file instanceof UploadedFile ? $file->getClientMimeType() : null
        );
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
