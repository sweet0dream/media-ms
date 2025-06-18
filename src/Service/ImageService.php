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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageService
{
    private const NOT_FOUND_FILE_MESSAGE = 'File not found';
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/media')] public string $mediaDir,
        private readonly ResizeService $resizeService,
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
        $path = $this->generatePath([
            $this->mediaDir,
            $id
        ]);

        if (!is_dir($path)) {
            mkdir(
                directory: $path,
                recursive: true
            );
        }

        $filename = (new DatetimeImmutable())->format('YmdHis') . rand(1000000, 9999999);
        $uploadFile = $path . $filename;

        file_put_contents($uploadFile, $request->getContent());

        $file = new File($uploadFile);

        if (!in_array($file->guessExtension(), self::SUPPORTED_EXTENSIONS)) {
            $this->dropFile($path, $filename);
            throw new Exception('Unsupported file type');
        }

        if ($file->guessExtension() === 'webp') {
            $file->move($path, $filename . '.webp');
        } else {
            WebPConverter::createWebpImage(
                $file,
                [
                    'saveFile' => true,
                    'filename' => $filename,
                    'force' => true,
                    'savePath' => $path,
                    'quality' => 100,
                ]
            );
            $this->dropFile($path, $filename);
        }

        return $filename;
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function view(
        int $id,
        string $filename,
    ): SplFileObject {
        $path = $this->generatePath([
            $this->mediaDir,
            $id
        ]);

        $this->handleExistFile(
            path: $path,
            filename: $filename,
        );

        return new SplFileObject($path . $filename);
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function thumbnail(
        int $id,
        string $size,
        string $filename,
    ): SplFileObject {
        $path = $this->generatePath([
            $this->mediaDir,
            $id
        ]);

        $this->handleExistFile(
            path: $path,
            filename: $filename,
        );

        if (!is_dir($path . 'thumbnails/')) {
            mkdir(directory: $path . 'thumbnails/', recursive: true);
        }

        if (!file_exists($path . 'thumbnails/' . $size . '_' . $filename)) {
            $extractSize = array_combine(['width', 'height'], array_map('intval', explode('x', $size)));
            $this->resizeService->resize(
                source: $path . $filename,
                save: $path . 'thumbnails/' . $size . '_' . $filename,
                width: $extractSize['width'],
                height: $extractSize['height']
            );
        }

        return new SplFileObject($path . 'thumbnails/' . $size . '_' . $filename);
    }

    public function delete(
        $id,
        $filename
    ): bool {
        $this->dropFile(
            path: $this->generatePath([
                $this->mediaDir,
                $id
            ]),
            filename: $filename . '.webp'
        );

        return true;
    }

    private function generatePath(array $data): string
    {
        return implode('/', $data) . '/';
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    private function handleExistFile(
        string $path,
        string $filename
    ): void {
        $this->convertImage($path, $filename);

        if (!file_exists($path . $filename)) {
            throw new NotFoundHttpException(self::NOT_FOUND_FILE_MESSAGE);
        }
    }

    /**
     * @throws Exception
     */
    private function convertImage(
        string $path,
        string $filename
    ): void {
        if (file_exists($path . explode('.', $filename)[0] . '.jpg')) {
            $filename = explode('.', $filename)[0];
            $file = new File($path . $filename . '.jpg');

            WebPConverter::createWebpImage(
                $file,
                [
                    'saveFile' => true,
                    'filename' => $filename,
                    'force' => true,
                    'savePath' => $path,
                    'quality' => 100,
                ]
            );
            $this->dropFile($path, $filename . '.jpg');
        }
    }

    private function dropFile(
        string $path,
        string $filename
    ): void {
        if (is_dir($path . 'thumbnails/')) {
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
