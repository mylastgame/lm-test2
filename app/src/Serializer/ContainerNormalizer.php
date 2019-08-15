<?php
/**
 * Created by PhpStorm.
 * User: alexkim
 * Date: 03.08.19
 * Time: 22:11
 */

namespace App\Serializer;


use App\Entity\Container;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


class ContainerNormalizer implements NormalizerInterface
{
    /**
     * @param Container $container
     * @param null $format
     * @param array $context
     * @return array|bool|float|int|string
     */
    public function normalize($container, $format = null, array $context = [])
    {
        $products = [];
        foreach ($container->getContainerProducts() as $containerProduct) {
            $products[] = [
                'id' => $containerProduct->getProduct()->getId(),
                'title' => $containerProduct->getProduct()->getTitle(),
                'amount' => $containerProduct->getAmount(),
            ];
        }

        return [
            'id' => $container->getId(),
            'title' => $container->getTitle(),
            'products' => $products
        ];
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Container;
    }
}