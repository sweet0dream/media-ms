<?php

declare(strict_types=1);

namespace App\Service;

use CodeBuds\WebPConverter\WebPConverter;
use DateTimeImmutable;
use Exception;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class ImageService
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/media/')] public string $mediaDir,
        private ResizeService $resizeService,
    ) {
        if (!is_dir($this->mediaDir)) {
            mkdir(directory: $this->mediaDir, recursive: true);
        }
    }

    /**
     * @throws Exception
     */
    public function upload(
        int $id,
        Request $request,
    ): string {
        if (!is_dir($this->mediaDir . $id)) {
            mkdir(directory: $this->mediaDir . $id, recursive: true);
        }

        $filename = (new DatetimeImmutable())->format('YmdHis');
        $uploadFile = $this->mediaDir . $id . '/' . $filename;

        file_put_contents($uploadFile, $request->getContent());

        $file = new File($uploadFile);

        if (!in_array($file->guessExtension(), self::SUPPORTED_EXTENSIONS)) {
            $this->dropFile($this->mediaDir . $id . '/', $filename);
            throw new Exception('Unsupported file type');
        }

        if ($file->guessExtension() === 'webp') {
            $file->move($this->mediaDir . $id, $filename . '.webp');
        } else {
            WebPConverter::createWebpImage(
                $file,
                [
                    'saveFile' => true,
                    'filename' => $filename,
                    'force' => true,
                    'savePath' => $this->mediaDir . $id,
                    'quality' => 100,
                ]
            );
            $this->dropFile($this->mediaDir . $id . '/', $filename);
        }

        return $filename;
    }

    public function view(
        int $id,
        string $filename,
    ): SplFileObject
    {
        return new SplFileObject($this->mediaDir . $id . '/' . $filename);
    }

    public function thumbnail(
        int $id,
        string $size,
        string $filename,
    ): SplFileObject
    {
        if (!is_dir($this->mediaDir . $id . '/thumbnails/')) {
            mkdir(directory: $this->mediaDir . $id . '/thumbnails/', recursive: true);
        }

        if (!file_exists($this->mediaDir . $id . '/thumbnails/' . $size . '/' . $filename)) {
            $extractSize = array_combine(['width', 'height'], array_map('intval', explode('x', $size)));
            $this->resizeService->resize(
                source: $this->mediaDir . $id . '/' . $filename,
                save: $this->mediaDir . $id . '/thumbnails/' . $size . '_' . $filename,
                width: $extractSize['width'],
                height: $extractSize['height']
            );
        }

        return new SplFileObject($this->mediaDir . $id . '/thumbnails/' . $size . '_' . $filename);
    }

    public function delete(
        $id,
        $filename
    ): bool {
        $this->dropFile(
            path: $this->mediaDir . $id . '/',
            filename: $filename . '.webp'
        );

        return true;
    }

    private function dropFile(
        string $path,
        string $filename
    ): void {
        if (is_dir($path. 'thumbnails/')) {
            foreach (glob($path . 'thumbnails/*') as $file) {
                if (explode('_', $file)[1] === $filename) {
                    unlink($file);
                }
            }
            $this->dropDirectory($path . 'thumbnails/');
        }
        if (file_exists($path . $filename)) {
            unlink($path . $filename);
        }
        $this->dropDirectory($path);
    }

    private function dropDirectory(
        string $path,
    ): void {
        $handle = opendir($path);
        $empty = true;
        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..') {
                $empty = false;
            }
        }

        if ($empty) {
            rmdir($path);
        }
    }
}
