<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\LoyaltyTransactionType;
use App\Repository\LoyaltyTransactionRepository;
use App\State\MyLoyaltyTransactionsProvider;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LoyaltyTransactionRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(
            uriTemplate: '/auth/me/loyalty/transactions',
            provider: MyLoyaltyTransactionsProvider::class,
            security: "is_granted('ROLE_USER')",
            name: 'get_my_loyalty_transactions',
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getAccount().getUser() == user"),
    ],
    normalizationContext: ['groups' => ['loyalty_transaction:read']],
    order: ['createdAt' => 'DESC'],
)]
class LoyaltyTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['loyalty_transaction:read', 'loyalty:me'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LoyaltyAccount::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LoyaltyAccount $account = null;

    #[ORM\Column(enumType: LoyaltyTransactionType::class)]
    #[Groups(['loyalty_transaction:read', 'loyalty:me'])]
    #[ApiProperty(example: 'earn')]
    private LoyaltyTransactionType $type;

    #[ORM\Column]
    #[Groups(['loyalty_transaction:read', 'loyalty:me'])]
    #[ApiProperty(example: 45)]
    private int $points = 0;

    #[ORM\Column(length: 255)]
    #[Groups(['loyalty_transaction:read', 'loyalty:me'])]
    #[ApiProperty(example: 'Points gagnés pour la commande ORD-20260120-ABC123')]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['loyalty_transaction:read'])]
    private ?Order $relatedOrder = null;

    #[ORM\ManyToOne(targetEntity: LoyaltyReward::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['loyalty_transaction:read', 'loyalty:me'])]
    private ?LoyaltyReward $redeemedReward = null;

    #[ORM\Column]
    #[Groups(['loyalty_transaction:read', 'loyalty:me'])]
    private ?DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?LoyaltyAccount
    {
        return $this->account;
    }

    public function setAccount(?LoyaltyAccount $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getType(): LoyaltyTransactionType
    {
        return $this->type;
    }

    public function setType(LoyaltyTransactionType $type): static
    {
        $this->type = $type;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRelatedOrder(): ?Order
    {
        return $this->relatedOrder;
    }

    public function setRelatedOrder(?Order $relatedOrder): static
    {
        $this->relatedOrder = $relatedOrder;

        return $this;
    }

    public function getRedeemedReward(): ?LoyaltyReward
    {
        return $this->redeemedReward;
    }

    public function setRedeemedReward(?LoyaltyReward $redeemedReward): static
    {
        $this->redeemedReward = $redeemedReward;

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
}
