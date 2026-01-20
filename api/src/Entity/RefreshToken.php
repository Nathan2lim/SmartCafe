<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(columns: ['token'], name: 'idx_refresh_token')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_refresh_token_expires')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    private string $token;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $revoked = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(User $user, int $ttlSeconds = 2592000): self // 30 days default
    {
        $refreshToken = new self();
        $refreshToken->token = bin2hex(random_bytes(64));
        $refreshToken->user = $user;
        $refreshToken->expiresAt = new \DateTimeImmutable("+{$ttlSeconds} seconds");

        return $refreshToken;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): self
    {
        $this->revoked = true;

        return $this;
    }

    public function isValid(): bool
    {
        return !$this->revoked && $this->expiresAt > new \DateTimeImmutable();
    }
}
