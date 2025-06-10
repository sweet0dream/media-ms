<?php

namespace App\Controller;

use App\Service\ImageService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {
    }

    #[Route('/{id}/save', name: 'image_save', methods: ['POST'])]
    public function saveImage(
        int $id,
        Request $request
    ): JsonResponse {

        try {
            $uploadedFilename = $this->imageService->upload($id, $request);
        } catch (Exception $e) {
            return $this->json([
                'uploaded' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'uploaded' => true,
            'filename' => $uploadedFilename
        ]);
    }

    #[Route('/{id}/{filename}', name: 'image_get')]
    public function viewImage(
        int $id,
        string $filename,
    ): BinaryFileResponse {
        return $this->file(
            file: $this->imageService->view($id, $filename),
            disposition: ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/{id}/{size}/{filename}', name: 'image_get_thumbnail')]
    public function viewThumbnail(
        int $id,
        string $size,
        string $filename,
    ): BinaryFileResponse {
        return $this->file(
            file: $this->imageService->viewThumbnail($id, $size, $filename),
            disposition: ResponseHeaderBag::DISPOSITION_INLINE
        );
    }
}
