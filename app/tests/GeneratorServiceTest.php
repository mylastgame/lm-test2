<?php
/**
 * Created by PhpStorm.
 * User: alexkim
 * Date: 04.08.19
 * Time: 14:16
 */

use PHPUnit\Framework\TestCase;
use \App\Entity\Container;
use \App\Services\GeneratorService;
use \App\Entity\Product;

class GeneratorServiceTest extends TestCase
{
    public function testGenerateProducts()
    {
        $generatorService = new GeneratorService();

        $productsCount = 10;

        $products = $generatorService->generateProducts(10);

        $this->assertEquals($productsCount, count($products), 'Wrong number of products');

        foreach ($products as $index => $product) {
            $this->assertInstanceOf(Product::class, $product, 'Wrong type in item index: ' . $index);
        }

    }

    public function testGenerateContainersWithProducts()
    {
        $generatorService = new GeneratorService();

        /** @var Product[] $products */
        $products = [];
        for ($i = 1; $i <= 100; $i++) {

            $product = new Product();
            $product->setId($i);
            $product->setTitle("product#{$i}");
            $products[] = $product;
        }

        //Кол-во контейнеров
        $containersNumber = 50;
        //Кол-во товаров в контейнере
        $containersCapacity = 10;

        /** @var Container[] $containers */
        $containers = $generatorService->generateContainersWithProducts($products, $containersNumber, $containersCapacity);

        $this->assertEquals($containersNumber, count($containers), 'Wrong number of containers');

        //ID уникальных товаров содержащихся в контейнерах
        $uniqueProductsInContainers = [];

        foreach ($containers as $index => $container) {
            $this->assertInstanceOf(Container::class, $container, 'Wrong type in item index:' . $index);
            $this->assertEquals($containersCapacity, count($container->getProducts()), 'Wrong number of products in container#' . $index);

            $uniqueProductsInContainers = array_unique(array_merge($uniqueProductsInContainers, $container->getProductIds()));
        }

        //Проверка что все товары были добавлены в контейнеры хотя бы один раз
        $productIds = [];
        foreach ($products as $product) {
            $productIds[] = $product->getId();
        }

        sort($productIds);
        sort($uniqueProductsInContainers);

        $this->assertEquals(count($productIds), count(array_intersect($productIds, $uniqueProductsInContainers)));
    }

    public function testGenerateContainersWithProductsException()
    {
        $this->expectException('InvalidArgumentException');

        $generatorService = new GeneratorService();

        /** @var Product[] $products */
        $products = [];
        for ($i = 1; $i <= 100; $i++) {

            $product = new Product();
            $product->setId($i);
            $product->setTitle("product#{$i}");
            $products[] = $product;
        }

        $generatorService->generateContainersWithProducts($products, 9, 10);
    }
}
