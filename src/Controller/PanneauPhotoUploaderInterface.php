<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface PanneauPhotoUploaderInterface
{
    /**
     * Enregistre le fichier dans le répertoire d'upload et retourne le nouveau nom de fichier.
     */
    public function upload(UploadedFile $file, string $uploadDir, ?string $oldFilename = null): string;
}
