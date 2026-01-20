<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\RewardType;
use App\Repository\LoyaltyRewardRepository;
use App\State\RedeemRewardProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoyaltyRewardRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/loyalty/rewards',
            name: 'get_loyalty_rewards'
        ),
        new Post(
            uriTemplate: '/loyalty/rewards',
            security: "is_granted('ROLE_ADMIN')",
            name: 'create_loyalty_reward'
        ),
        new Get(
            uriTemplate: '/loyalty/rewards/{id}',
            name: 'get_loyalty_reward'
        ),
        new Patch(
            uriTemplate: '/loyalty/rewards/{id}',
            security: "is_granted('ROLE_ADMIN')",
            name: 'update_loyalty_reward'
        ),
        new Delete(
            uriTemplate: '/loyalty/rewards/{id}',
            security: "is_granted('ROLE_ADMIN')",
            name: 'delete_loyalty_reward'
        ),
        new Post(
            uriTemplate: '/loyalty/rewards/{id}/redeem',
            security: "is_granted('ROLE_USER')",
            processor: RedeemRewardProcessor::class,
            name: 'redeem_loyalty_reward'
        ),
    ],
    normalizationContext: ['groups' => ['reward:read']],
    denormalizationContext: ['groups' => ['reward:write']],
)]
class LoyaltyReward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reward:read', 'loyalty_transaction:read', 'loyalty:me'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['reward:read', 'reward:write', 'loyalty_transaction:read', 'loyalty:me'])]
    #[ApiProperty(example: 'Café offert')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['reward:read', 'reward:write'])]
    #[ApiProperty(example: 'Un café de votre choix offert')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['reward:read', 'reward:write', 'loyalty_transaction:read', 'loyalty:me'])]
    #[ApiProperty(example: 100)]
    private int $pointsCost = 0;

    #[ORM\Column(enumType: RewardType::class)]
    #[Groups(['reward:read', 'reward:write'])]
    #[ApiProperty(example: 'free_product')]
    private RewardType $type = RewardType::FREE_PRODUCT;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['reward:read', 'reward:write'])]
    #[ApiProperty(example: '5.00')]
    private ?string $discountValue = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['reward:read', 'reward:write'])]
    #[ApiProperty(example: 10)]
    private ?int $discountPercent = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['reward:read', 'reward:write'])]
    private ?Product $freeProduct = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['reward:read', 'reward:write'])]
    #[ApiProperty(example: 'silver')]
    private ?string $requiredTier = null;

    #[ORM\Column]
    #[Groups(['reward:read', 'reward:write'])]
    private bool $active = true;

    #[ORM\Column(nullable: true)]
    #[Groups(['reward:read', 'reward:write'])]
    private ?int $stockQuantity = null;

    #[ORM\Column]
    #[Groups(['reward:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['reward:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getPointsCost(): int
    {
        return $this->pointsCost;
    }

    public function setPointsCost(int $pointsCost): static
    {
        $this->pointsCost = $pointsCost;
        return $this;
    }

    public function getType(): RewardType
    {
        return $this->type;
    }

    public function setType(RewardType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDiscountValue(): ?string
    {
        return $this->discountValue;
    }

    public function setDiscountValue(?string $discountValue): static
    {
        $this->discountValue = $discountValue;
        return $this;
    }

    public function getDiscountPercent(): ?int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(?int $discountPercent): static
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }

    public function getFreeProduct(): ?Product
    {
        return $this->freeProduct;
    }

    public function setFreeProduct(?Product $freeProduct): static
    {
        $this->freeProduct = $freeProduct;
        return $this;
    }

    public function getRequiredTier(): ?string
    {
        return $this->requiredTier;
    }

    public function setRequiredTier(?string $requiredTier): static
    {
        $this->requiredTier = $requiredTier;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
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

    #[Groups(['reward:read'])]
    public function isAvailable(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->stockQuantity !== null && $this->stockQuantity <= 0) {
            return false;
        }

        return true;
    }
}
