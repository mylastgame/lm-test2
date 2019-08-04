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


class ContainerService
{
    /**
     * @param Container[] $containers
     * @return Container[]
     */
    public function filterContainersContainsAllProducts(array $containers)
    {
        //Данные по товарам
        //Ключ массива - ID товара
        //count - кол-во контейнеров в которых размещен данный товар
        //containers[] - список контейнеров(индексов из $containers) в которых размещен данный товар
        $productsData = [];

        foreach ($containers as $index => $container) {
            /** @var Product[] $products */
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

        //Список ID ещё не отобранных товаров
        $uniqueProducts = array_keys($productsData);

        //Получение товаров размещены в контейнерах в единственном экземпляре
        $singleCountProducts = array_filter($productsData, function ($data)
        {
            if ($data['count'] === 1) {
                return true;
            }

            return false;
        });

        //Отобранные контейнеры
        $containersResult = [];


        //Выборка контейнеров в которых содержатся товары в единственном экземпляре
        foreach ($singleCountProducts as $productId => $productData) {
            if (in_array($productId, $uniqueProducts)) {
                $container = $containers[$productData['containers'][0]];

                $containersResult[] = $container;

                //Удаление из списка ещё не отобранных товаров товаров из выбранного контейнера
                $uniqueProducts = array_diff($uniqueProducts, $container->getProductIds());
            }


        }

        //Выборка остальных контейнеров
        while(count($uniqueProducts) > 0) {
            //Получение следующего товара из списка ещё не размещенных товаров
            $productId = array_shift($uniqueProducts);

            //Список контейнеров в которых содержится товар с данным ID
            $containerIndexes = $productsData[$productId]['containers'];

            //Данные контейнеров в которых находится текущий с данным ID
            //index - индекс контейнера из $containers
            //amount - кол-во товаров в контейнере которые ещё не были отобраны
            $containersData = [];
            //Расчет кол-ва товаров в контейнерах которые ещё не были отобраны
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
            //Удаление из списка ещё не отобранных товаров товаров из выбранного контейнера
            $uniqueProducts = array_diff($uniqueProducts, $container->getProductIds());
        }


        return $containersResult;
    }

}