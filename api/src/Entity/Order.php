<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\State\MyOrdersProvider;
use App\State\OrderStateProcessor;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(
            uriTemplate: '/auth/me/orders',
            provider: MyOrdersProvider::class,
            security: "is_granted('ROLE_USER')",
            name: 'get_my_orders',
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: OrderStateProcessor::class,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getCustomer() == user"),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            processor: OrderStateProcessor::class,
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']],
    order: ['createdAt' => 'DESC'],
)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['order:read'])]
    private ?string $orderNumber = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read'])]
    private ?User $customer = null;

    #[ORM\Column(enumType: OrderStatus::class)]
    #[Groups(['order:read', 'order:write'])]
    #[ApiProperty(example: 'pending')]
    private OrderStatus $status = OrderStatus::PENDING;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['order:read', 'order:write'])]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'La commande doit contenir au moins un article')]
    private Collection $items;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['order:read'])]
    private ?string $totalAmount = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    #[ApiProperty(example: 'Sans sucre, s\'il vous plaît')]
    private ?string $notes = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    #[ApiProperty(example: 'Table 5')]
    private ?string $tableNumber = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?DateTimeImmutable $readyAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?DateTimeImmutable $deliveredAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->orderNumber = $this->generateOrderNumber();
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function calculateTotal(): void
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += (float) $item->getUnitPrice() * $item->getQuantity();
        }
        $this->totalAmount = number_format($total, 2, '.', '');
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTableNumber(): ?string
    {
        return $this->tableNumber;
    }

    public function setTableNumber(?string $tableNumber): static
    {
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getReadyAt(): ?DateTimeImmutable
    {
        return $this->readyAt;
    }

    public function setReadyAt(?DateTimeImmutable $readyAt): static
    {
        $this->readyAt = $readyAt;

        return $this;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }
}
