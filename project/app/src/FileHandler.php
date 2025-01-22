<?php

namespace src;

class FileHandler
{
    private string $uploadDir;

    public function __construct(string $serverUploadDir)
    {
        $this->uploadDir = $serverUploadDir;
    }

    public function getRelativeUploadDir(): string
    {
        $baseDir = dirname(__DIR__);
        return str_replace($baseDir, '', $this->uploadDir);
    }

    public function isFile(mixed $file): bool
    {
        return isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    public function upload(mixed $file): string | false
    {
        if (!$this->isFile($file)) {
            return false;
        }

        $uniqueFileName = $this->getUniqueFileName($file);
        $destination = $this->uploadDir . $uniqueFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return false;
        }

        return $uniqueFileName;
    }

    private function getUniqueFileName(array $file): string
    {
        return uniqid() . '_' . basename($file['name']);
    }

    public function delete(string $filename): bool
    {
        $filePath = $this->uploadDir . basename($filename);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}
