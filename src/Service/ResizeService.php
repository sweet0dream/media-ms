<?php

declare(strict_types=1);

namespace App\Service;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;

readonly class ResizeService
{
    public function __construct(
        private ImagineInterface $imagine,
    ) {
    }

    public function resize(
        string $source,
        string $save,
        int $width,
        int $height
    ): void {
        $image = $this->imagine->open($source);

        $ratio = $image->getSize()->getWidth() / $image->getSize()->getHeight();
        switch (true) {
            case $width == 0:
                $image->resize(new Box((int) round($height * $ratio), $height));
                break;
            case $height == 0:
                $image->resize(new Box($width, (int) round($width / $ratio)));
                break;
            default:
                $targetRatio = $width / $height;
                if ($ratio > $targetRatio) {
                    $scaledHeight = $height;
                    $scaledWidth = (int) round($height * $ratio);
                } else {
                    $scaledWidth = $width;
                    $scaledHeight = (int) round($width / $ratio);

                }
                $image->resize(new Box($scaledWidth, $scaledHeight));
                $image->crop(
                    new Point(
                        (int) round(($scaledWidth - $width) / 2),
                        (int) round(($scaledHeight - $height) / 2)
                    ),
                    new Box($width, $height)
                );
                break;
        }

        $image->save($save,[
            'quality' => 100,
            'format' => 'webp'
        ]);
    }
}
