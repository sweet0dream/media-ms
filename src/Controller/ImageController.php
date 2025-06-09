<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/media/')] public string $mediaDir,
    ) {
        if (!is_dir($this->mediaDir)) {
            mkdir(directory: $this->mediaDir, recursive: true);
        }
    }

    #[Route('/{id}/save', name: 'image_save', methods: ['POST'])]
    public function saveImage(
        int $id,
        Request $request
    ): JsonResponse
    {
        if (!is_dir($this->mediaDir . $id)) {
            mkdir(directory: $this->mediaDir . $id, recursive: true);
        }

        $filename = new DateTimeImmutable()->format('YmdHis') . rand(1000000, 9999999) . '.jpg';

        new Filesystem()->dumpFile($this->mediaDir . $id . '/' . $filename, $request->getContent());

        return $this->json([
            'uploaded' => true,
            'filename' => $filename
        ]);
    }
}
