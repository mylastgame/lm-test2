<?php

namespace App\Controller;

use App\Entity\Container;
use App\Entity\Product;
use App\Services\DataService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiController extends AbstractController
{
    /**
     * @var DataService
     */
    private $_dataService;

    public function __construct(DataService $dataService)
    {

        $this->_dataService = $dataService;
    }

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
     * @param string $title
     * @return JsonResponse
     */
    public function createContainer(string $title = '')
    {
        $container = $this->_dataService->createContainer($title);

        return $this->json([
            'status' => 'Success',
            'container_id' => $container->getId(),
            'container_title' => $container->getTitle(),
        ]);
    }

    /**
     * @Route("/api/create-product/{title}", name="create_product")
     * @param EntityManagerInterface $em
     * @param string $title
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createProduct(string $title = '')
    {
        $product = $this->_dataService->createProduct($title);

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
    public function addProductToContainer(int $containerId, Product $productId)
    {
       if ($this->_dataService->addProductToContainer($containerId, $productId)) {
           return $this->json([
               'status' => 'Success',
           ]);
       }

        return $this->json([
            'status' => 'Error',
        ]);
    }

    /**
     * @Route("/api/container/{containerId}", name="get_container")
     * @ParamConverter("container", options={"mapping": {"containerId" : "id"}})
     */
    public function getContainer(EntityManagerInterface $em, SerializerInterface $serializer, Container $container)
    {
        return JsonResponse::fromJsonString($serializer->serialize($container, 'json', ['circular_reference_handler' => function ($object) {
            return $object->getId();
        }]));

//        return new JsonResponse(
//            ['container' => $serializer->serialize($container, 'json', ['circular_reference_handler' => function ($object) {
//                return $object->getId();
//            }])],
//            JsonResponse::HTTP_OK
//        );
//
//        return $this->json([
//            'container' => $serializer->serialize($container, 'json', ['circular_reference_handler' => function ($object) {
//                return $object->getId();
//            }])
//        ]);

    }

    /**
     * @Route("/api/containers", name="get_containers")
     */
    public function getContainers(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $containers = $em->getRepository(Container::class)->findAll();


        $containersData = [];
        foreach ($containers as $container) {
            $containersData[] = ['id' => $container->getId(), 'title' => $container->getTitle()];
        }

        return new JsonResponse(
            $containersData,
            JsonResponse::HTTP_OK
        );
    }

    /**
     * @Route("/api/generate-data/containers/{containersCount}/products/{uniqueProducts}/capacity/{containerCapacity}", name="generate_data")
     *
     * @param int $containersCount
     * @param int $uniqueProducts
     * @param int $containerCapacity
     * @return JsonResponse
     */
    public function generateData(int $containersCount = 1000, int $uniqueProducts = 100, int $containerCapacity = 10)
    {
        $this->_dataService->generateData($containersCount, $uniqueProducts, $containerCapacity);
        return new JsonResponse(
            'Success',
            JsonResponse::HTTP_OK
        );
    }

    /**
     * @Route("/api/get-containers-with-all-products/", name="get_containers_with_all_products")
     *
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function getContainersWithAllProducts(SerializerInterface $serializer)
    {
        $containers = $this->_dataService->getContainersContainsAllProducts();

        return JsonResponse::fromJsonString($serializer->serialize($containers, 'json', ['circular_reference_handler' => function ($object) {
            return $object->getId();
        }]));
    }
}
