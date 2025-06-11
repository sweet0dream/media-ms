<?php

namespace App\Controller;

use App\Service\ImageService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly RequestStack $requestStack,
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

    #[Route('/{id}/{filename}', name: 'image_get', methods: ['GET', 'DELETE'])]
    public function viewImage(
        int $id,
        string $filename,
    ): JsonResponse|BinaryFileResponse {
        if ($this->requestStack->getCurrentRequest()->getMethod() === 'DELETE') {
            $this->json([], Response::HTTP_NO_CONTENT);
        }

        return match ($this->requestStack->getCurrentRequest()->getMethod()) {
            Request::METHOD_GET => $this->file(
                file: $this->imageService->view($id, $filename),
                disposition: ResponseHeaderBag::DISPOSITION_INLINE
            ),
            Request::METHOD_DELETE => $this->json(
                $this->imageService->delete($id, $filename), Response::HTTP_NO_CONTENT),
            default => $this->json([], Response::HTTP_METHOD_NOT_ALLOWED),
        };
    }

    #[Route('/{id}/{size}/{filename}', name: 'image_get_thumbnail')]
    public function viewThumbnail(
        int $id,
        string $size,
        string $filename,
    ): BinaryFileResponse {
        return $this->file(
            file: $this->imageService->thumbnail($id, $size, $filename),
            disposition: ResponseHeaderBag::DISPOSITION_INLINE
        );
    }
}
