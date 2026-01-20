<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LoyaltyAccountRepository;
use App\State\MyLoyaltyProvider;
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
            name: 'get_my_loyalty'
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

    #[ORM\OneToMany(targetEntity: LoyaltyTransaction::class, mappedBy: 'account', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Groups(['loyalty:me'])]
    private Collection $transactions;

    #[ORM\Column]
    #[Groups(['loyalty:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['loyalty:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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
        $this->updatedAt = new \DateTimeImmutable();
        $this->updateTier();
        return $this;
    }

    public function deductPoints(int $points): static
    {
        $this->points -= $points;
        $this->totalPointsSpent += $points;
        $this->updatedAt = new \DateTimeImmutable();
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

    private function updateTier(): void
    {
        $this->tier = match (true) {
            $this->totalPointsEarned >= 5000 => 'platinum',
            $this->totalPointsEarned >= 2000 => 'gold',
            $this->totalPointsEarned >= 500 => 'silver',
            default => 'bronze',
        };
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

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 350)]
    public function getPointsToNextTier(): int
    {
        return match ($this->tier) {
            'bronze' => 500 - $this->totalPointsEarned,
            'silver' => 2000 - $this->totalPointsEarned,
            'gold' => 5000 - $this->totalPointsEarned,
            'platinum' => 0,
            default => 0,
        };
    }

    #[Groups(['loyalty:read'])]
    #[ApiProperty(example: 'silver')]
    public function getNextTier(): ?string
    {
        return match ($this->tier) {
            'bronze' => 'silver',
            'silver' => 'gold',
            'gold' => 'platinum',
            'platinum' => null,
            default => 'silver',
        };
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
}
