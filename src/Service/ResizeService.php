<?php

declare(strict_types=1);

namespace App\Service;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;

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
                $width = $height * $ratio;
            case $height == 0:
                $height = $width / $ratio;
        }

        $image->resize(new Box($width, $height));
        $image->save($save,[
            'quality' => 100,
            'format' => 'webp'
        ]);
    }
}
