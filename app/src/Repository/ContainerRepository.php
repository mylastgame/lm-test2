<?php

namespace App\Repository;

use App\Entity\Container;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityNotFoundException;
use App\Entity\Product;

/**
 * @method Container|null find($id, $lockMode = null, $lockVersion = null)
 * @method Container|null findOneBy(array $criteria, array $orderBy = null)
 * @method Container[]    findAll()
 * @method Container[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContainerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Container::class);
    }

    public function purge()
    {
        $cmd = $this->_em->getClassMetadata(Container::class);
        $connection = $this->_em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
            $connection->executeUpdate($q);
            $q = $dbPlatform->getTruncateTableSql('container_product');
            $connection->executeUpdate($q);
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        }
        catch (\Exception $e) {
            $connection->rollback();
        }
    }

    public function createContainer(string $title = '')
    {
        $container = new Container();
        $container->setTitle($title);
        return $container;
    }

    public function addNewContainer(string $title = '')
    {
        $container = $this->createContainer($title);
        $container->setTitle($title);

        $this->_em->persist($container);
        $this->_em->flush();

        if (!$title) {
            $container->setTitle("container#" . $container->getId());
            $this->_em->persist($container);
            $this->_em->flush();
        }

        return $container;
    }

    public function deleteContainer(int $id): bool
    {
        $container = $this->_em->getRepository(Container::class)->find($id);
        if ($container) {
            $this->_em->remove($container);
            $this->_em->flush();
        }

        return true;
    }

    public function addProductsToContainer(int $containerId, array $productIds): Container
    {
        /** @var Container $container */
        $container = $this->_em->getRepository(Container::class)->find($containerId);
        if (!$container) {
            throw new EntityNotFoundException('Container with id '.$containerId.' does not exist!');
        }

        foreach ($productIds as $productId) {
            $product = $this->_em->getRepository(Product::class)->find($productId);
            if (!$product) {
                throw new EntityNotFoundException('Product with id '.$productId.' does not exist!');
            }

            if ($container->haveProduct($productId)) {
                throw new \InvalidArgumentException("Container #{$containerId} already have product #{$productId}");
            }

            $container->addProduct($product);
        }


        $this->_em->persist($container);
        $this->_em->flush();

        return $container;
    }

    // /**
    //  * @return Container[] Returns an array of Container objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Container
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
