<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\ContainerProduct;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ContainerRepository")
 */
class Container
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

//    /**
//     * @ORM\ManyToMany(targetEntity="App\Entity\Product", inversedBy="containers")
//     */
//    private $products;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ContainerProduct", mappedBy="container", cascade={"persist", "remove"})
     * @var ContainerProduct[]
     */
    private $containerProducts;


    public function __construct()
    {
        //$this->products = new ArrayCollection();
        $this->containerProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        $products = new ArrayCollection();

        foreach ($this->containerProducts as $containerProduct) {
            if (!$products->contains($containerProduct->getProduct())) {
                $products[] = $containerProduct->getProduct();
            }
        }

        return $products;
    }

    public function addProduct(Product $product): self
    {
        $containerProduct = $this->getContainerProductByProductId($product->getId());

        if ($containerProduct) {
            $containerProduct->incrAmount();
        } else {
            $containerProduct = new ContainerProduct();
            $containerProduct->setProduct($product);
            $containerProduct->setAmount(1);
            $containerProduct->setContainer($this);
            $this->containerProducts[] = $containerProduct;
        }

        return $this;
    }
//
//    public function removeProduct(Product $product): self
//    {
//        if ($this->products->contains($product)) {
//            $this->products->removeElement($product);
//        }
//
//        return $this;
//    }
//
    public function getProductIds(): array
    {
        $ids = [];

        foreach ($this->containerProducts as $containerProduct) {
            if (!in_array($containerProduct->getProduct()->getId(), $ids)) {
                $ids[] = $containerProduct->getProduct()->getId();
            }
        }

        return $ids;
    }
//
    public function haveProduct(int $productId)
    {
        foreach ($this->containerProducts as $containerProduct) {
            if ($containerProduct->getProduct()->getId() === $productId) {
                return true;
            }
        }

        return false;
    }

    public function getContainerProductByProductId(int $productId)
    {
        foreach ($this->containerProducts as $containerProduct) {
            if ($containerProduct->getProduct()->getId() === $productId) {
                return $containerProduct;
            }
        }

        return false;
    }

    /**
     * @return Collection|ContainerProduct[]
     */
    public function getContainerProducts(): Collection
    {
        return $this->containerProducts;
    }

    public function addContainerProduct(ContainerProduct $containerProduct): self
    {
        if (!$this->containerProducts->contains($containerProduct)) {
            $this->containerProducts[] = $containerProduct;
            $containerProduct->setContainer($this);
        }

        return $this;
    }

    public function removeContainerProduct(ContainerProduct $containerProduct): self
    {
        if ($this->containerProducts->contains($containerProduct)) {
            $this->containerProducts->removeElement($containerProduct);
            // set the owning side to null (unless already changed)
            if ($containerProduct->getContainer() === $this) {
                $containerProduct->setContainer(null);
            }
        }

        return $this;
    }
}
