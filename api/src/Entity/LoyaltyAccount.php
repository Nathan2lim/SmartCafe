<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\LoyaltyAccountRepository;
use App\State\MyLoyaltyProvider;
use App\State\UpgradeTierProcessor;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LoyaltyAccountRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(
            uriTemplate: '/auth/me/loyalty',
            provider: MyLoyaltyProvider::class,
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['loyalty:read', 'loyalty:me']],
            name: 'get_my_loyalty',
        ),
        new Post(
            uriTemplate: '/auth/me/loyalty/upgrade',
            processor: UpgradeTierProcessor::class,
            security: "is_granted('ROLE_USER')",
            input: false,
            output: LoyaltyTransaction::class,
            normalizationContext: ['groups' => ['loyalty_transaction:read']],
            name: 'upgrade_loyalty_tier',
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getUser() == user"),
    ],
    normalizationContext: ['groups' => ['loyalty:read']],
)]
class LoyaltyAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['loyalty:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'loyaltyAccount')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Groups(['loyalty:read'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['loyalty:read', 'user:me'])]
    #[ApiProperty(example: 150)]
    private int $points = 0;

    #[ORM\Column]
    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 1500)]
    private int $totalPointsEarned = 0;

    #[ORM\Column]
    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 1350)]
    private int $totalPointsSpent = 0;

    #[ORM\Column(length: 20)]
    #[Groups(['loyalty:read', 'user:me'])]
    #[ApiProperty(example: 'gold')]
    private string $tier = 'bronze';

    private const TIER_CONFIG = [
        'bronze' => ['multiplier' => 1.0, 'upgradePoints' => 50, 'nextTier' => 'silver'],
        'silver' => ['multiplier' => 1.10, 'upgradePoints' => 150, 'nextTier' => 'gold'],
        'gold' => ['multiplier' => 1.25, 'upgradePoints' => 250, 'nextTier' => 'platinum'],
        'platinum' => ['multiplier' => 1.75, 'upgradePoints' => 500, 'nextTier' => 'diamond'],
        'diamond' => ['multiplier' => 2.0, 'upgradePoints' => null, 'nextTier' => null],
    ];

    #[ORM\OneToMany(targetEntity: LoyaltyTransaction::class, mappedBy: 'account', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Groups(['loyalty:me'])]
    private Collection $transactions;

    #[ORM\Column]
    #[Groups(['loyalty:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['loyalty:read'])]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function addPoints(int $points): static
    {
        $this->points += $points;
        $this->totalPointsEarned += $points;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function deductPoints(int $points): static
    {
        $this->points -= $points;
        $this->totalPointsSpent += $points;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function getTotalPointsEarned(): int
    {
        return $this->totalPointsEarned;
    }

    public function setTotalPointsEarned(int $totalPointsEarned): static
    {
        $this->totalPointsEarned = $totalPointsEarned;

        return $this;
    }

    public function getTotalPointsSpent(): int
    {
        return $this->totalPointsSpent;
    }

    public function setTotalPointsSpent(int $totalPointsSpent): static
    {
        $this->totalPointsSpent = $totalPointsSpent;

        return $this;
    }

    public function getTier(): string
    {
        return $this->tier;
    }

    public function setTier(string $tier): static
    {
        $this->tier = $tier;

        return $this;
    }

    public function canUpgrade(): bool
    {
        $config = self::TIER_CONFIG[$this->tier] ?? null;
        if (!$config || null === $config['upgradePoints']) {
            return false;
        }

        return $this->points >= $config['upgradePoints'];
    }

    public function upgrade(): bool
    {
        if (!$this->canUpgrade()) {
            return false;
        }

        $config = self::TIER_CONFIG[$this->tier];
        $this->tier = $config['nextTier'];
        $this->points = 0;
        $this->updatedAt = new DateTimeImmutable();

        return true;
    }

    /**
     * @return Collection<int, LoyaltyTransaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(LoyaltyTransaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setAccount($this);
        }

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

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 50)]
    public function getUpgradeCost(): ?int
    {
        return self::TIER_CONFIG[$this->tier]['upgradePoints'] ?? null;
    }

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 35)]
    public function getPointsToUpgrade(): int
    {
        $cost = $this->getUpgradeCost();
        if (null === $cost) {
            return 0;
        }

        return max(0, $cost - $this->points);
    }

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 'silver')]
    public function getNextTier(): ?string
    {
        return self::TIER_CONFIG[$this->tier]['nextTier'] ?? null;
    }

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: true)]
    public function getCanUpgrade(): bool
    {
        return $this->canUpgrade();
    }

    #[Groups(['loyalty:me'])]
    #[ApiProperty(example: '/api/loyalty/rewards')]
    public function getAvailableRewardsUrl(): string
    {
        return '/api/loyalty/rewards';
    }

    #[Groups(['loyalty:me'])]
    #[ApiProperty(example: '/api/auth/me/loyalty/transactions')]
    public function getTransactionsUrl(): string
    {
        return '/api/auth/me/loyalty/transactions';
    }

    #[Groups(['loyalty:read', 'user:me'])]
    #[ApiProperty(example: 1.25)]
    public function getCurrentMultiplier(): float
    {
        return self::TIER_CONFIG[$this->tier]['multiplier'] ?? 1.0;
    }

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 1.25)]
    public function getNextMultiplier(): ?float
    {
        $nextTier = $this->getNextTier();
        if (null === $nextTier) {
            return null;
        }

        return self::TIER_CONFIG[$nextTier]['multiplier'] ?? null;
    }
}
