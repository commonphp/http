<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Exceptions\UploadedFileException;
use CommonPHP\HTTP\UploadedFile;
use PHPUnit\Framework\TestCase;

final class UploadedFileTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        $this->tmpRoot = dirname(__DIR__) . '/.tmp';

        if (!is_dir($this->tmpRoot)) {
            mkdir($this->tmpRoot, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpRoot . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testItBuildsUploadFromPhpFileArray(): void
    {
        $file = UploadedFile::fromArray('avatar', [
            'name' => 'profile.png',
            'type' => 'image/png',
            'tmp_name' => $this->createTemporaryFile('image-bytes'),
            'error' => UPLOAD_ERR_OK,
            'size' => 11,
        ]);

        self::assertSame('avatar', $file->name());
        self::assertSame('profile.png', $file->clientFilename());
        self::assertSame('image/png', $file->clientMediaType());
        self::assertSame(11, $file->size());
        self::assertSame(UPLOAD_ERR_OK, $file->error());
        self::assertTrue($file->isValid());
        self::assertSame('The file uploaded successfully.', $file->errorMessage());
        self::assertSame('image-bytes', $file->contents());
    }

    public function testItNormalizesNestedFileArrays(): void
    {
        $firstPath = $this->createTemporaryFile('one');
        $secondPath = $this->createTemporaryFile('two');

        $files = UploadedFile::normalizeArray([
            'documents' => [
                'name' => ['first' => 'one.txt', 'second' => 'two.txt'],
                'type' => ['first' => 'text/plain', 'second' => 'text/plain'],
                'tmp_name' => ['first' => $firstPath, 'second' => $secondPath],
                'error' => ['first' => UPLOAD_ERR_OK, 'second' => UPLOAD_ERR_NO_FILE],
                'size' => ['first' => 3, 'second' => 0],
            ],
        ]);

        self::assertInstanceOf(UploadedFile::class, $files['documents']['first']);
        self::assertInstanceOf(UploadedFile::class, $files['documents']['second']);
        self::assertSame('one.txt', $files['documents']['first']->clientFilename());
        self::assertTrue($files['documents']['first']->isValid());
        self::assertFalse($files['documents']['second']->isValid());
        self::assertSame('No file was uploaded.', $files['documents']['second']->errorMessage());
    }

    public function testItMovesValidUploads(): void
    {
        $source = $this->createTemporaryFile('payload');
        $target = $this->tmpRoot . '/moved.txt';
        $file = new UploadedFile('document', $source, 7);

        $file->moveTo($target);

        self::assertFileDoesNotExist($source);
        self::assertSame('payload', file_get_contents($target));
    }

    public function testItRejectsUnknownUploadErrorCodes(): void
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('Unknown upload error code');

        new UploadedFile('bad', '', 0, 999);
    }

    public function testItRejectsNegativeSizes(): void
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('Upload size cannot be negative');

        new UploadedFile('bad', '', -1);
    }

    public function testItRejectsContentsForInvalidUploads(): void
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('No file was uploaded.');

        (new UploadedFile('empty', '', 0, UPLOAD_ERR_NO_FILE))->contents();
    }

    public function testItRejectsUnreadableTemporaryFiles(): void
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('Unable to read temporary upload file.');

        (new UploadedFile('missing', $this->tmpRoot . '/missing.txt', 1))->contents();
    }

    public function testItRejectsMovesToMissingDirectories(): void
    {
        $source = $this->createTemporaryFile('payload');
        $file = new UploadedFile('document', $source, 7);

        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('Unable to move uploaded file');

        $file->moveTo($this->tmpRoot . '/missing/target.txt');
    }

    public function testItRejectsNestedArraysInDirectFactory(): void
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('Nested file arrays must be normalized first.');

        UploadedFile::fromArray('files', [
            'name' => ['one.txt'],
            'tmp_name' => ['path'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [1],
        ]);
    }

    private function createTemporaryFile(string $contents): string
    {
        $path = tempnam($this->tmpRoot, 'http-upload-');
        self::assertIsString($path);
        file_put_contents($path, $contents);

        return $path;
    }
}
