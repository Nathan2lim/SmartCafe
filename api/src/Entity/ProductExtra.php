<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ProductExtraRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductExtraRepository::class)]
#[ORM\UniqueConstraint(name: 'product_extra_unique', columns: ['product_id', 'extra_id'])]
#[UniqueEntity(fields: ['product', 'extra'], message: 'Cet extra est déjà associé à ce produit')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['product_extra:read']],
    denormalizationContext: ['groups' => ['product_extra:write']],
)]
class ProductExtra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product_extra:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'availableExtras')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['product_extra:read', 'product_extra:write'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Extra::class, inversedBy: 'productExtras')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['product_extra:read', 'product_extra:write', 'product:read'])]
    private ?Extra $extra = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['product_extra:read', 'product_extra:write', 'product:read'])]
    private int $maxQuantity = 5;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getExtra(): ?Extra
    {
        return $this->extra;
    }

    public function setExtra(?Extra $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function getMaxQuantity(): int
    {
        return $this->maxQuantity;
    }

    public function setMaxQuantity(int $maxQuantity): static
    {
        $this->maxQuantity = $maxQuantity;
        return $this;
    }
}
