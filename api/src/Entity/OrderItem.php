<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Repository\OrderItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read', 'order:write'])]
    #[Assert\NotNull]
    private ?Product $product = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['order:read', 'order:write'])]
    #[ApiProperty(example: 2)]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['order:read'])]
    private ?string $unitPrice = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    #[ApiProperty(example: 'Avec du lait d\'avoine')]
    private ?string $specialInstructions = null;

    #[ORM\OneToMany(targetEntity: OrderItemExtra::class, mappedBy: 'orderItem', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['order:read'])]
    private Collection $extras;

    public function __construct()
    {
        $this->extras = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        // Copier le prix du produit au moment de la commande
        if ($product !== null) {
            $this->unitPrice = $product->getPrice();
        }

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getSpecialInstructions(): ?string
    {
        return $this->specialInstructions;
    }

    public function setSpecialInstructions(?string $specialInstructions): static
    {
        $this->specialInstructions = $specialInstructions;
        return $this;
    }

    #[Groups(['order:read'])]
    public function getSubtotal(): string
    {
        $productSubtotal = (float) ($this->unitPrice ?? 0) * $this->quantity;

        $extrasSubtotal = 0.0;
        foreach ($this->extras as $extra) {
            $extrasSubtotal += (float) $extra->getSubtotal();
        }

        $total = $productSubtotal + $extrasSubtotal;
        return number_format($total, 2, '.', '');
    }

    /**
     * @return Collection<int, OrderItemExtra>
     */
    public function getExtras(): Collection
    {
        return $this->extras;
    }

    public function addExtra(OrderItemExtra $extra): static
    {
        if (!$this->extras->contains($extra)) {
            $this->extras->add($extra);
            $extra->setOrderItem($this);
        }
        return $this;
    }

    public function removeExtra(OrderItemExtra $extra): static
    {
        if ($this->extras->removeElement($extra)) {
            if ($extra->getOrderItem() === $this) {
                $extra->setOrderItem(null);
            }
        }
        return $this;
    }
}
