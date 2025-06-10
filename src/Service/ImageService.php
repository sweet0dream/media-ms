<?php

declare(strict_types=1);

namespace App\Service;

use CodeBuds\WebPConverter\WebPConverter;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class ImageService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/media/')] public string $mediaDir,
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
        }

        return $filename;
    }
}
