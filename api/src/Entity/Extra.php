<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ExtraRepository;
use App\State\ExtraStateProcessor;
use App\State\LowStockExtrasProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExtraRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/extras/low-stock',
            security: "is_granted('ROLE_ADMIN')",
            provider: LowStockExtrasProvider::class,
            name: 'get_low_stock_extras'
        ),
        new Post(security: "is_granted('ROLE_ADMIN')", processor: ExtraStateProcessor::class),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')", processor: ExtraStateProcessor::class),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        new Post(
            uriTemplate: '/extras/{id}/restock',
            security: "is_granted('ROLE_ADMIN')",
            processor: ExtraStateProcessor::class,
            name: 'restock_extra'
        ),
    ],
    normalizationContext: ['groups' => ['extra:read']],
    denormalizationContext: ['groups' => ['extra:write']],
)]
class Extra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['extra:read', 'order:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['extra:read', 'extra:write', 'order:read', 'product:read'])]
    #[ApiProperty(example: 'Crème chantilly')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['extra:read', 'extra:write'])]
    #[ApiProperty(example: 'Délicieuse crème fouettée')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['extra:read', 'extra:write', 'order:read', 'product:read'])]
    #[ApiProperty(example: '0.50')]
    private ?string $price = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['extra:read', 'extra:write'])]
    #[ApiProperty(example: 100)]
    private int $stockQuantity = 0;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['extra:read', 'extra:write'])]
    #[ApiProperty(example: 10)]
    private int $lowStockThreshold = 10;

    #[ORM\Column]
    #[Groups(['extra:read', 'extra:write'])]
    private bool $available = true;

    #[ORM\Column]
    #[Groups(['extra:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['extra:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: ProductExtra::class, mappedBy: 'extra', orphanRemoval: true)]
    private Collection $productExtras;

    #[ORM\OneToMany(targetEntity: OrderItemExtra::class, mappedBy: 'extra')]
    private Collection $orderItemExtras;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->productExtras = new ArrayCollection();
        $this->orderItemExtras = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;
        return $this;
    }

    public function getLowStockThreshold(): int
    {
        return $this->lowStockThreshold;
    }

    public function setLowStockThreshold(int $lowStockThreshold): static
    {
        $this->lowStockThreshold = $lowStockThreshold;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, ProductExtra>
     */
    public function getProductExtras(): Collection
    {
        return $this->productExtras;
    }

    /**
     * @return Collection<int, OrderItemExtra>
     */
    public function getOrderItemExtras(): Collection
    {
        return $this->orderItemExtras;
    }

    #[Groups(['extra:read'])]
    public function isLowStock(): bool
    {
        return $this->stockQuantity <= $this->lowStockThreshold;
    }
}
