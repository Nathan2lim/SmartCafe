<?php

declare(strict_types=1);

namespace App\Service\Extra;

use App\DTO\Extra\CreateExtraDTO;
use App\DTO\Extra\UpdateExtraDTO;
use App\Entity\Extra;
use App\Exception\ExtraNotFoundException;
use App\Repository\ExtraRepository;
use DateTimeImmutable;

final class ExtraService
{
    public function __construct(
        private readonly ExtraRepository $extraRepository,
    ) {
    }

    public function createExtra(CreateExtraDTO $dto): Extra
    {
        $extra = new Extra();
        $extra->setName($dto->name);
        $extra->setDescription($dto->description);
        $extra->setPrice($dto->price);
        $extra->setStockQuantity($dto->stockQuantity);
        $extra->setLowStockThreshold($dto->lowStockThreshold);
        $extra->setAvailable($dto->available);

        $this->extraRepository->save($extra);

        return $extra;
    }

    public function updateExtra(int $id, UpdateExtraDTO $dto): Extra
    {
        $extra = $this->getExtraById($id);

        if (null !== $dto->name) {
            $extra->setName($dto->name);
        }
        if (null !== $dto->description) {
            $extra->setDescription($dto->description);
        }
        if (null !== $dto->price) {
            $extra->setPrice($dto->price);
        }
        if (null !== $dto->stockQuantity) {
            $extra->setStockQuantity($dto->stockQuantity);
        }
        if (null !== $dto->lowStockThreshold) {
            $extra->setLowStockThreshold($dto->lowStockThreshold);
        }
        if (null !== $dto->available) {
            $extra->setAvailable($dto->available);
        }

        $extra->setUpdatedAt(new DateTimeImmutable());
        $this->extraRepository->save($extra);

        return $extra;
    }

    public function deleteExtra(int $id): void
    {
        $extra = $this->getExtraById($id);
        $this->extraRepository->remove($extra);
    }

    public function getExtraById(int $id): Extra
    {
        $extra = $this->extraRepository->find($id);

        if (!$extra) {
            throw new ExtraNotFoundException($id);
        }

        return $extra;
    }

    /**
     * @return Extra[]
     */
    public function getAvailableExtras(): array
    {
        return $this->extraRepository->findAvailable();
    }

    /**
     * @return Extra[]
     */
    public function getLowStockExtras(): array
    {
        return $this->extraRepository->findLowStock();
    }

    public function restockExtra(int $id, int $quantity): Extra
    {
        $extra = $this->getExtraById($id);
        $extra->setStockQuantity($extra->getStockQuantity() + $quantity);
        $extra->setUpdatedAt(new DateTimeImmutable());

        $this->extraRepository->save($extra);

        return $extra;
    }

    public function deductStock(Extra $extra, int $quantity): void
    {
        $extra->setStockQuantity($extra->getStockQuantity() - $quantity);
        $extra->setUpdatedAt(new DateTimeImmutable());

        $this->extraRepository->save($extra);
    }
}
