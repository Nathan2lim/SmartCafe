<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ProductRepository;
use App\State\LowStockProductsProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/products/low-stock',
            security: "is_granted('ROLE_ADMIN')",
            provider: LowStockProductsProvider::class,
            name: 'get_low_stock_products'
        ),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']],
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product:read', 'product:write', 'order:read'])]
    #[ApiProperty(example: 'Café Latte')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    #[ApiProperty(example: 'Un délicieux café latte avec du lait crémeux')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['product:read', 'product:write', 'order:read'])]
    #[ApiProperty(example: '4.50')]
    private ?string $price = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['product:read', 'product:write'])]
    #[ApiProperty(example: 'Boissons chaudes')]
    private ?string $category = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    private bool $available = true;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    #[ApiProperty(example: true)]
    private bool $alaCarte = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    #[ApiProperty(example: 'https://example.com/cafe-latte.jpg')]
    private ?string $imageUrl = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['product:read', 'product:write'])]
    #[ApiProperty(example: 100)]
    private ?int $stockQuantity = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['product:read', 'product:write'])]
    #[ApiProperty(example: 10)]
    private int $lowStockThreshold = 10;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    #[ORM\OneToMany(targetEntity: ProductExtra::class, mappedBy: 'product', orphanRemoval: true)]
    #[Groups(['product:read'])]
    private Collection $availableExtras;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orderItems = new ArrayCollection();
        $this->availableExtras = new ArrayCollection();
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
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

    public function isAlaCarte(): bool
    {
        return $this->alaCarte;
    }

    public function setAlaCarte(bool $alaCarte): static
    {
        $this->alaCarte = $alaCarte;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
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
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(?int $stockQuantity): static
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

    /**
     * @return Collection<int, ProductExtra>
     */
    public function getAvailableExtras(): Collection
    {
        return $this->availableExtras;
    }

    public function addAvailableExtra(ProductExtra $productExtra): static
    {
        if (!$this->availableExtras->contains($productExtra)) {
            $this->availableExtras->add($productExtra);
            $productExtra->setProduct($this);
        }
        return $this;
    }

    public function removeAvailableExtra(ProductExtra $productExtra): static
    {
        if ($this->availableExtras->removeElement($productExtra)) {
            if ($productExtra->getProduct() === $this) {
                $productExtra->setProduct(null);
            }
        }
        return $this;
    }

    #[Groups(['product:read'])]
    public function isLowStock(): bool
    {
        if ($this->stockQuantity === null) {
            return false;
        }
        return $this->stockQuantity <= $this->lowStockThreshold;
    }
}
