<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Gère l'upload des photos de panneaux (sauvegarde, suppression de l'ancienne, nom unique).
 */
class PanneauPhotoUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    /**
     * Enregistre le fichier dans le répertoire d'upload et retourne le nouveau nom de fichier.
     * Supprime l'ancien fichier si $oldFilename est fourni.
     *
     * @throws FileException en cas d'échec du déplacement du fichier
     */
    public function upload(UploadedFile $file, string $uploadDir, ?string $oldFilename = null): string
    {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($oldFilename) {
            $oldPath = $uploadDir . '/' . $oldFilename;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($uploadDir, $newFilename);

        return $newFilename;
    }
}
