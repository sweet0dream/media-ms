<?php

declare(strict_types=1);

namespace App\Service;

use CodeBuds\WebPConverter\WebPConverter;
use DateTimeImmutable;
use Exception;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class ImageService
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/media/')] public string $mediaDir,
        private readonly ImagineInterface $imagine,
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
            $this->dropFile($id, $uploadFile);
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
            $this->dropFile($id, $uploadFile);
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

    public function viewThumbnail(
        int $id,
        string $size,
        string $filename,
    ): SplFileObject
    {
        if (!is_dir($this->mediaDir . $id . '/thumbnails/')) {
            mkdir(directory: $this->mediaDir . $id . '/thumbnails/', recursive: true);
        }

        if (!file_exists($this->mediaDir . $id . '/thumbnails/' . $size . '/' . $filename)) {
            $extractSize = array_combine(['width', 'height'], explode('x', $size));

            $image = $this->imagine->open($this->mediaDir . $id . '/' . $filename);

            switch (true) {
                case $extractSize['width'] == 0:
                    $image->thumbnail(new Box($image->getSize()->getWidth(), $extractSize['height']));
                    break;
                case $extractSize['height'] == 0:
                    $image->thumbnail(new Box($extractSize['width'], $image->getSize()->getHeight()));
                    break;
                default:
                    $image->resize(new Box($extractSize['width'], $extractSize['height']));
            }

            $image->save($this->mediaDir . $id . '/thumbnails/' . $size . '_' . $filename, [
                'quality' => 100,
                'format' => 'webp'
            ]);
        }

        return new SplFileObject($this->mediaDir . $id . '/thumbnails/' . $size . '_' . $filename);
    }

    private function dropFile(
        int $id,
        string $file
    ): void {
        unlink($file);

        if (function() use ($id) {
            $handle = opendir($this->mediaDir . $id);
            while (false !== ($entry = readdir($handle))) {
                if ($entry !== '.' && $entry !== '..') {
                    return false;
                }
            }

            return true;
        }) {
            rmdir($this->mediaDir . $id);
        }
    }
}
