<?php
/**
 * Created by PhpStorm.
 * User: alexkim
 * Date: 04.08.19
 * Time: 14:16
 */


use PHPUnit\Framework\TestCase;
use \App\Entity\Container;
use \App\Services\ContainerService;
use \App\Entity\Product;

class ContainerServiceTest extends TestCase
{
    public function testGetContainersContainsAllProducts()
    {
        $containerService = new ContainerService();

        /** @var Product[] $products */
        $products = [];
        for ($i = 1; $i <= 100; $i++) {

            $product = new Product();
            $product->setId($i);
            $product->setTitle("product#{$i}");
            $products[] = $product;
        }

        /** @var Container[] $containers */
        $containers = [];
        for ($i = 1; $i <= 100; $i++) {

            $container = new Container();
            $container->setId($i);
            $container->setTitle("container#{$i}");
            $containers[] = $container;
        }

        $uniqueProducts = array_keys($products);

        //Добавление товаров в контейнер
        foreach ($containers as $container) {
            $index = array_shift($uniqueProducts);
            $container->addProduct($products[$index]);
            $inContainer = [$index];

            $i = 0;
            while($i < 9) {
                $index = rand(0, count($products) - 1);
                if (!in_array($index, $inContainer)) {
                    $container->addProduct($products[$index]);
                    $inContainer = [$index];
                    $i++;
                }
            }
        }

        $containers = $containerService->filterContainersContainsAllProducts($containers);


        //ID уникальных товаров содержащихся в контейнерах
        $uniqueProductsInContainers = [];

        foreach ($containers as $index => $container) {
            $this->assertInstanceOf(Container::class, $container, 'Wrong type in item index:' . $index);
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
}
