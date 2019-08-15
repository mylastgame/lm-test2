<?php

namespace App\Controller\Rest;

use App\Entity\Container;

use App\Entity\ContainerProduct;
use App\Entity\Product;
use App\Services\ContainerService;
use App\Services\GeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Doctrine\ORM\EntityNotFoundException;

class ApiController extends FOSRestController
{
    /**
     * @var GeneratorService
     */
    private $_generatorService;

    /**
     * @var EntityManagerInterface
     */
    private $_em;


    public function __construct(EntityManagerInterface $em, GeneratorService $generatorService)
    {
        $this->_em = $em;
        $this->_generatorService = $generatorService;
    }

    /**
     * @Route("/", name="api")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php',
        ]);
    }

    /**
     * @Rest\Post("/create-container")
     * @param Request $request
     * @return View
     */
    public function createContainer(Request $request)
    {
        $title = $request->get('title') ? $request->get('title') : '';
        $container = $this->_em->getRepository(Container::class)->addNewContainer($title);
        return View::create($container, Response::HTTP_CREATED);
    }

    /**
     * @Rest\Delete("/container/{containerId}")
     * @param int $containerId
     * @return View
     */
    public function deleteContainer(int $containerId)
    {
        $this->_em->getRepository(Container::class)->deleteContainer($containerId);
        return View::create('Success', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Post("/create-product")
     * @param Request $request
     * @return View
     */
    public function createProduct(Request $request)
    {
        $title = $request->get('title') ? $request->get('title') : '';
        $product = $this->_em->getRepository(Product::class)->addNewProduct($title);
        return View::create($product, Response::HTTP_CREATED);
    }

    /**
     * Добавление товаров в контейнер
     *
     * @Rest\Put("/add-products-to-container/{containerId}")
     * @param int $containerId
     * @param Request $request
     * @return View
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function addProductsToContainer(int $containerId, Request $request)
    {
        $container = $this->_em->getRepository(Container::class)->addProductsToContainer($containerId, $request->get('products'));
        return View::create($container, Response::HTTP_CREATED);
    }

    /**
     * @Rest\Get("/product/{productId}")
     * @param int $productId
     * @return View
     * @throws EntityNotFoundException
     */
    public function getProduct(int $productId)
    {
        $product = $this->_em->getRepository(Product::class)->find($productId);

        if (!$product) {
            throw new EntityNotFoundException('Product with id '.$productId.' does not exist!');
        }

        return View::create($product, Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/container/{containerId}")
     * @param int $containerId
     * @return View
     * @throws EntityNotFoundException
     */
    public function getContainer(int $containerId)
    {
        $container = $this->_em->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new EntityNotFoundException('Container with id '.$containerId.' does not exist!');
        }

        return View::create($container, Response::HTTP_OK);
    }

    /**
     * Получение всех контейнеров
     *
     * @Rest\Get("/containers")
     * @return View
     */
    public function getContainers()
    {
        $containers = $this->_em->getRepository(Container::class)->findAll();


        $containersData = [];
        foreach ($containers as $container) {
            $containersData[] = ['id' => $container->getId(), 'title' => $container->getTitle()];
        }

        return View::create($containersData, Response::HTTP_OK);
    }

    /**
     * Получение всех товаров
     *
     * @Rest\Get("/products")
     * @return View
     */
    public function getProducts()
    {
        $products = $this->_em->getRepository(Product::class)->findAll();
        return View::create($products, Response::HTTP_OK);
    }

//    /**
//     * @Route("/api/generate-data/containers/{containersCount}/products/{uniqueProducts}/capacity/{containerCapacity}", name="generate_data")
//     *
//     * @param int $containersCount
//     * @param int $uniqueProducts
//     * @param int $containerCapacity
//     * @return JsonResponse
//     */
//    public function generateData(int $containersCount = 1000, int $uniqueProducts = 100, int $containerCapacity = 10)
//    {
//        $this->_dataService->generateData($containersCount, $uniqueProducts, $containerCapacity);
//        return new JsonResponse(
//            'Success',
//            JsonResponse::HTTP_OK
//        );
//    }

    /**
     * Генерация данных
     *
     * @Rest\Post("/generate-data")
     * @param Request $request
     * @return View
     */
    public function generateData(Request $request)
    {
        if ((int)$request->get('products') <= 0) {
            throw new \InvalidArgumentException('Products count must be greater then 0');
        }

        //Если кол-во уникальных товаров больше чем кол-во контейнеров * емкость - Exception
        if ($request->get('containers') * $request->get('capacity') < $request->get('products')) {
            throw new \InvalidArgumentException('containers * capacity must be more then products count');
        }

        $productRepository = $this->_em->getRepository(Product::class);
        $containerRepository = $this->_em->getRepository(Container::class);
        $containerProductsRepository = $this->_em->getRepository(ContainerProduct::class);

        $productRepository->purge();
        $products = $this->_generatorService->generateProducts((int)$request->get('products'));

        foreach ($products as $product) {
            $this->_em->persist($product);
        }
        $this->_em->flush();

        $containerRepository->purge();
        //$containerProductsRepository->purge();
        $containers = $this->_generatorService->generateContainersWithProducts($products, $request->get('containers'), $request->get('capacity'));
        foreach ($containers as $container) {
            $this->_em->persist($container);
        }
        $this->_em->flush();


        //$this->_dataService->generateData($request->get('containers'), $request->get('products'), $request->get('capacity'));
        return View::create('Success', Response::HTTP_CREATED);
    }

    /**
     * Генерация данных по GET запросу
     *
     * @Rest\Get("/generate-data-from-get/containers/{containersCount}/products/{uniqueProducts}/capacity/{containerCapacity}")
     * @param int $containersCount
     * @param int $uniqueProducts
     * @param int $containerCapacity
     * @return View
     */
    public function generateDataFromGet(int $containersCount, int $uniqueProducts, int $containerCapacity)
    {
        if ($uniqueProducts <= 0) {
            throw new \InvalidArgumentException('Products count must be greater then 0');
        }

        //Если кол-во уникальных товаров больше чем кол-во контейнеров * емкость - Exception
        if ($containersCount * $containerCapacity < $uniqueProducts) {
            throw new \InvalidArgumentException('containers * capacity must be more then products count');
        }

        $productRepository = $this->_em->getRepository(Product::class);
        $containerRepository = $this->_em->getRepository(Container::class);

        $productRepository->purge();
        $products = $this->_generatorService->generateProducts($uniqueProducts);

        foreach ($products as $product) {
            $this->_em->persist($product);
        }
        $this->_em->flush();

        $containerRepository->purge();
        $containers = $this->_generatorService->generateContainersWithProducts($products, $containersCount, $containerCapacity);
        foreach ($containers as $container) {
            $this->_em->persist($container);
        }
        $this->_em->flush();


        return View::create('Success', Response::HTTP_CREATED);
    }

    /**
     * Выборка контейнеров со всеми уникальными товарами
     *
     * @Rest\Get("/get-containers-with-all-products")
     * @param ContainerService $containerService
     * @return View
     */
    public function getContainersWithAllProducts(ContainerService $containerService)
    {
        $containers = $this->_em->getRepository(Container::class)->findAll();
        $result = $containerService->filterContainersContainsAllProducts($containers);

        return View::create(['containers_count' => count($result), 'containers' => $result], Response::HTTP_OK);
    }
}
