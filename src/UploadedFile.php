<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Exceptions\UploadedFileException;

class UploadedFile
{
    /**
     * @var array<int, string>
     */
    private const ERROR_MESSAGES = [
        UPLOAD_ERR_OK => 'The file uploaded successfully.',
        UPLOAD_ERR_INI_SIZE => 'The file exceeds the upload_max_filesize setting.',
        UPLOAD_ERR_FORM_SIZE => 'The file exceeds the form MAX_FILE_SIZE setting.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'The temporary upload directory is missing.',
        UPLOAD_ERR_CANT_WRITE => 'The file could not be written to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
    ];

    public function __construct(
        private readonly string $name,
        private readonly string $temporaryPath,
        private readonly int $size,
        private readonly int $error = UPLOAD_ERR_OK,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
    ) {
        if (!array_key_exists($error, self::ERROR_MESSAGES)) {
            throw UploadedFileException::forUpload($name, 'Unknown upload error code ' . $error . '.');
        }

        if ($size < 0) {
            throw UploadedFileException::forUpload($name, 'Upload size cannot be negative.');
        }
    }

    /**
     * @param array{name?: mixed, tmp_name?: mixed, size?: mixed, error?: mixed, type?: mixed} $file
     */
    public static function fromArray(string $fieldName, array $file): self
    {
        if (is_array($file['tmp_name'] ?? null)) {
            throw UploadedFileException::forUpload($fieldName, 'Nested file arrays must be normalized first.');
        }

        return new self(
            $fieldName,
            (string) ($file['tmp_name'] ?? ''),
            (int) ($file['size'] ?? 0),
            (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            isset($file['name']) ? (string) $file['name'] : null,
            isset($file['type']) ? (string) $file['type'] : null,
        );
    }

    /**
     * @param array<string, mixed> $files
     *
     * @return array<string, mixed>
     */
    public static function normalizeArray(array $files): array
    {
        $normalized = [];

        foreach ($files as $fieldName => $file) {
            if (is_array($file) && self::isUploadSpec($file)) {
                $normalized[(string) $fieldName] = self::normalizeSpec((string) $fieldName, $file);
                continue;
            }

            $normalized[(string) $fieldName] = is_array($file) ? self::normalizeArray($file) : $file;
        }

        return $normalized;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function temporaryPath(): string
    {
        return $this->temporaryPath;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function clientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function clientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->temporaryPath !== '';
    }

    public function errorMessage(): string
    {
        return self::ERROR_MESSAGES[$this->error] ?? 'Unknown upload error.';
    }

    public function contents(): string
    {
        if (!$this->isValid()) {
            throw UploadedFileException::forUpload($this->name, $this->errorMessage());
        }

        $contents = @file_get_contents($this->temporaryPath);

        if ($contents === false) {
            throw UploadedFileException::forUpload($this->name, 'Unable to read temporary upload file.');
        }

        return $contents;
    }

    public function moveTo(string $targetPath): void
    {
        if (!$this->isValid()) {
            throw UploadedFileException::forUpload($this->name, $this->errorMessage());
        }

        $directory = dirname($targetPath);

        if (!is_dir($directory) || !is_writable($directory)) {
            throw UploadedFileException::cannotMove($this->name, $targetPath);
        }

        $moved = is_uploaded_file($this->temporaryPath)
            ? @move_uploaded_file($this->temporaryPath, $targetPath)
            : @rename($this->temporaryPath, $targetPath);

        if (!$moved) {
            throw UploadedFileException::cannotMove($this->name, $targetPath);
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function isUploadSpec(array $file): bool
    {
        return array_key_exists('tmp_name', $file)
            && array_key_exists('name', $file)
            && array_key_exists('error', $file);
    }

    /**
     * @param array<string, mixed> $file
     *
     * @return mixed
     */
    private static function normalizeSpec(string $fieldName, array $file): mixed
    {
        if (!is_array($file['tmp_name'] ?? null)) {
            return self::fromArray($fieldName, $file);
        }

        $normalized = [];

        foreach ($file['tmp_name'] as $key => $_) {
            $child = [
                'name' => self::arrayValue($file['name'] ?? null, $key),
                'type' => self::arrayValue($file['type'] ?? null, $key),
                'tmp_name' => self::arrayValue($file['tmp_name'] ?? null, $key),
                'error' => self::arrayValue($file['error'] ?? null, $key),
                'size' => self::arrayValue($file['size'] ?? null, $key),
            ];

            $normalized[$key] = self::normalizeSpec($fieldName . '.' . $key, $child);
        }

        return $normalized;
    }

    private static function arrayValue(mixed $value, int|string $key): mixed
    {
        return is_array($value) ? ($value[$key] ?? null) : null;
    }
}
