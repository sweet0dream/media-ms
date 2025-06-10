<?php

namespace App\Controller;

use App\Service\ImageService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    public function __construct(
        private ImageService $imageService,
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
}
