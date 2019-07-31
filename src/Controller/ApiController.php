<?php

namespace App\Controller;

use App\Entity\Container;
use App\Entity\Product;
use App\Repository\ContainerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiController extends AbstractController
{
    /**
     * @Route("/api", name="api")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php',
        ]);
    }

    /**
     * @Route("/api/create-container/{title}", name="create_container")
     * @param EntityManagerInterface $em
     * @param string $title
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createContainer(EntityManagerInterface $em, string $title = '')
    {
        $container = new Container();

        if ($title) {
            $container->setTitle($title);
        }

        $em->persist($container);
        $em->flush();

        if (!$title) {
            $container->setTitle("container#" . $container->getId());
        }

        $em->persist($container);
        $em->flush();

        return $this->json([
            'status' => 'Success',
            'container_id' => $container->getId(),
            'container_title' => $container->getTitle(),
        ]);
    }

    /**
     * @Route("/api/create-product/{title}", name="create_container")
     * @param EntityManagerInterface $em
     * @param string $title
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createProduct(EntityManagerInterface $em, string $title = '')
    {
        $product = new Product();

        if ($title) {
            $product->setTitle($title);
        }

        $em->persist($product);
        $em->flush();

        if (!$title) {
            $product->setTitle("product#" . $product->getId());
        }

        $em->persist($product);
        $em->flush();

        return $this->json([
            'status' => 'Success',
            'product_id' => $product->getId(),
            'product_title' => $product->getTitle(),
        ]);
    }

    /**
     * @Route("/api/add-product-to-container/{containerId}/{productId}", name="add_product_to_container")
     *
     * @param EntityManagerInterface $em
     * @param int $containerId
     * @param Product $productId
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addProductToContainer(EntityManagerInterface $em, int $containerId, Product $productId)
    {
        /** @var Container $container */
        $container = $em->getRepository(Container::class)->find($containerId);

        /** @var Product $product */
        $product = $em->getRepository(Product::class)->find($productId);

        $container->addProduct($product);
        $em->persist($container);
        $em->flush();

        return $this->json([
            'status' => 'Success',
        ]);
    }

    /**
     * @Route("/api/get-container/{containerId}", name="get_container")
     * @ParamConverter("container", options={"mapping": {"containerId" : "id"}})
     */
    public function getContainer(EntityManagerInterface $em, SerializerInterface $serializer, Container $container)
    {
        dump($container);
        dump($serializer);

        return $this->json([
            'container' => $serializer->serialize($container, 'json')
        ]);
    }
}
