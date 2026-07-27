<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageDataUriService
{
    public function fromUploadedFile(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getPathname());

        if ($contents === false) {
            throw new \RuntimeException('Impossible de lire le fichier image.');
        }

        $mimeType = $file->getMimeType() ?? $file->getClientMimeType() ?? 'application/octet-stream';

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
    }
}