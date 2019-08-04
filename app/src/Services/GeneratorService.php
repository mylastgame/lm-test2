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


class GeneratorService
{
    /**
     * Генерация товаров
     * @param int $count
     * @return array
     */
    public function generateProducts(int $count): array
    {
        $products = [];
        for ($i = 1; $i <= $count; $i++) {

            $product = new Product();
            $product->setId($i);
            $product->setTitle("product#{$i}");
            $products[] = $product;
        }

        return $products;
    }

    /**
     * Генерация контейнеров и заполнение товарами
     *
     * @param array $products
     * @param int $containersCount
     * @param int $containerCapacity
     * @return array
     */
    public function generateContainersWithProducts(array $products, int $containersCount = 1000, int $containerCapacity = 10): array
    {
        //Кол-во уникальных товаров
        $uniqueProductsCount = count($products);

        //Если кол-во уникальных товаров больше чем кол-во контейнеров * емкость - Exception
        if ($containersCount * $containerCapacity < $uniqueProductsCount) {
            throw new \InvalidArgumentException('containers * capacity must be more then products count');
        }

        //Минимальное кол-во товаров в контейнере которое нужно разместить явно(без рандома), чтобы все уникальные
        //товары были в контейнерах
        $minExplicitProductsCount = (int)(count($products) / $containersCount) + 1;
        //Список ключей оставшихся уникальных товаров которые ещё не размещены в контейнере
        $uniqueProducts = array_keys($products);

        $containers = [];

        //Генерация контейнеров
        for ($i = 1; $i <= $containersCount; $i++) {
            //Получение нового контейнера
            $container = new Container();
            $container->setTitle("container#{$i}");

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

            $containers[] = $container;
        }

        return $containers;
    }


}