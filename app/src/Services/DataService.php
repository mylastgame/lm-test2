<?php
/**
 * Created by PhpStorm.
 * User: alexkim
 * Date: 03.08.19
 * Time: 11:09
 */

namespace App\Services;

use App\Entity\Container;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class DataService
{
    /**
     * @var EntityManagerInterface
     */
    private $_em;

    public function __construct(EntityManagerInterface $em)
    {

        $this->_em = $em;
    }

    public function generateData(int $containersCount = 1000, int $uniqueProductsCount = 100, int $containerCapacity = 10)
    {
        //Если кол-во уникальных товаров больше чем кол-во контейнеров * емкость - Exception
        if ($containersCount * $containerCapacity < $uniqueProductsCount) {
            throw new \InvalidArgumentException('containers * capacity must be more then products count');
        }

        $containerRepository = $this->_em->getRepository(Container::class);
        $productRepository = $this->_em->getRepository(Product::class);

        //Удаление существующих данных
        $containerRepository->purge();
        $productRepository->purge();

        //Генерация товаров
        $products = [];
        for ($i = 1; $i <= $uniqueProductsCount; $i++) {
            $product = $productRepository->createProduct("product#{$i}");
            $this->_em->persist($product);
            $products[] = $product;
        }

        $this->_em->flush();

        //Минимальное кол-во товаров в контейнере которое нужно разместить явно(без рандома), чтобы все уникальные
        //товары были в контейнерах
        $minExplicitProductsCount = (int)($uniqueProductsCount / $containersCount) + 1;
        //Список ключей оставшихся уникальных товаров которые ещё не размещены в контейнере
        $uniqueProducts = array_keys($products);


        //Генерация контейнеров
        for ($i = 1; $i <= $containersCount; $i++) {
            //Получение нового контейнера
            $container = $containerRepository->createContainer("container#{$i}");

            //Счетчик кол-ва товаров размещенных в контейнере явно(не рандомно)
            $explicitProductsCounter = 0;


            //Заполнение контейнера товарами
            $j = 0;
            //Список товаров в контейнере(индекс $products)
            $productsInContainer = [];

            do {
                //Явное размещение товаров в контейнере
                if (count($uniqueProducts) > 0 && $explicitProductsCounter < $minExplicitProductsCount) {
                    $productIndex = array_shift($uniqueProducts);
                    $product = $products[$productIndex];
                    $container->addProduct($product);
                    $productsInContainer[] = $productIndex;

                    $explicitProductsCounter++;
                    $j++;
                    continue;
                }

                //Случайное размещение товара в контейнере
                $index = rand(0, $uniqueProductsCount - 1);

                if (!in_array($index, $productsInContainer)) {
                    $container->addProduct($products[$index]);
                    $productsInContainer[] = $index;
                    //Удаление из списка ещё не размещенных уникальных товаров
                    if (in_array($index, $uniqueProducts)) {
                        $uniqueProducts = array_diff($uniqueProducts, [$index]);
                    }
                    $j++;
                }

            } while ($j < $containerCapacity);

            $this->_em->persist($container);
        }

        $this->_em->flush();
    }

    public function getContainersContainsAllProducts()
    {
        $containers = $this->_em->getRepository(Container::class)->findAll();

        $productsData = [];

        foreach ($containers as $index => $container) {
            $products = $container->getProducts();
            foreach ($products as $product) {
                if (isset($productsData[$product->getId()])) {
                    $productsData[$product->getId()]['count']++;
                    $productsData[$product->getId()]['containers'][] = $index;
                } else {
                    $productsData[$product->getId()] = ['count' => 1, 'containers' => [$index]];
                }
            }
        }

        //Список уникальных товаров
        $uniqueProducts = array_keys($productsData);

        //Получение товаров размещены в контейнерах в единственном экземпляре
        $singleCountProducts = array_filter($productsData, function ($data)
        {
            if ($data['count'] === 1) {
                return true;
            }

            return false;
        });

        $containersResult = [];


        //Выборка контейнеров в которых содержатся товары в единственном экземпляре
        foreach ($singleCountProducts as $productId => $productData) {
            if (in_array($productId, $uniqueProducts)) {
                $container = $containers[$productData['containers'][0]];

                $containersResult[] = $container;
                $uniqueProducts = array_diff($uniqueProducts, $container->getProductIds());
            }


        }

        //Выборка контейнеров
        while(count($uniqueProducts) > 0) {
            //$productId = $uniqueProducts[0];
            $productId = array_shift($uniqueProducts);

            //Список индексов контейнеров в которых содержится товар с данным ID
            $containerIndexes = $productsData[$productId]['containers'];

            $containersData = [];
            //Проверка на кол-во товаров в контейнерах которые ещё не были отобраны
            foreach ($containerIndexes as $containerIndex) {
                $productIds = $containers[$containerIndex]->getProductIds();
                $containersData[] = [
                    'index' => $containerIndex,
                    'amount' =>  array_intersect($productIds, $uniqueProducts)
                ];
            }

            //Получение контейнера с максимальным кол-вом товаров которые ещё не были отобраны
            $containerData = array_reduce($containersData, function ($result, $data)
            {
                if (!$result || $result['amount'] < $data['amount']) {
                    $result = $data;
                }

                return $result;
            });

            $container = $containers[$containerData['index']];
            $containersResult[] = $container;
            $uniqueProducts = array_diff($uniqueProducts, $container->getProductIds());
        }


        return $containersResult;
    }
}