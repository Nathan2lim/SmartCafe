<?php

namespace App\Entity;

use App\Repository\OrderItemExtraRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemExtraRepository::class)]
class OrderItemExtra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderItem::class, inversedBy: 'extras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrderItem $orderItem = null;

    #[ORM\ManyToOne(targetEntity: Extra::class, inversedBy: 'orderItemExtras')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['order:read', 'order:write'])]
    private ?Extra $extra = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['order:read', 'order:write'])]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['order:read'])]
    private ?string $unitPrice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderItem(): ?OrderItem
    {
        return $this->orderItem;
    }

    public function setOrderItem(?OrderItem $orderItem): static
    {
        $this->orderItem = $orderItem;
        return $this;
    }

    public function getExtra(): ?Extra
    {
        return $this->extra;
    }

    public function setExtra(?Extra $extra): static
    {
        $this->extra = $extra;

        if ($extra !== null) {
            $this->unitPrice = $extra->getPrice();
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

    #[Groups(['order:read'])]
    public function getSubtotal(): string
    {
        $subtotal = (float) ($this->unitPrice ?? 0) * $this->quantity;
        return number_format($subtotal, 2, '.', '');
    }
}
