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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/{filename}', name: 'image_get', methods: ['GET', 'DELETE'])]
    public function viewImage(
        int $id,
        string $filename,
    ): Response|BinaryFileResponse {
        switch ($this->requestStack->getCurrentRequest()->getMethod()) {
            case Request::METHOD_GET:
                try {
                    return $this->file(
                        file: $this->imageService->view($id, $filename),
                        disposition: ResponseHeaderBag::DISPOSITION_INLINE
                    );
                } catch (NotFoundHttpException $e) {
                    return new Response('', Response::HTTP_NOT_FOUND);
                }
            case Request::METHOD_DELETE:
                return $this->json(
                    data: $this->imageService->delete($id, $filename),
                    status: Response::HTTP_NO_CONTENT
                );
            default:
                return $this->json([], Response::HTTP_METHOD_NOT_ALLOWED);
        }
    }

    #[Route('/{id}/{size}/{filename}', name: 'image_get_thumbnail')]
    public function viewThumbnail(
        int $id,
        string $size,
        string $filename,
    ): Response|BinaryFileResponse {
        try {
            return $this->file(
                file: $this->imageService->thumbnail($id, $size, $filename),
                disposition: ResponseHeaderBag::DISPOSITION_INLINE
            );
        } catch (NotFoundHttpException $e) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

    }
}
