<?php
/**
 * Created by PhpStorm.
 * User: alexkim
 * Date: 03.08.19
 * Time: 22:11
 */

namespace App\Serializer;


use App\Entity\Product;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductNormalizer implements NormalizerInterface
{
    /**
     * @param Product $product
     * @param null $format
     * @param array $context
     * @return array|bool|float|int|string
     */
    public function normalize($product, $format = null, array $context = [])
    {
        return [
            'id' => $product->getId(),
            'title' => $product->getTitle(),
        ];
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Product;
    }
}