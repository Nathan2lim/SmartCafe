<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\Order\CreateOrderDTO;
use App\Entity\Order;
use App\Service\Order\OrderService;
use Symfony\Bundle\SecurityBundle\Security;

final class OrderStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Order
    {
        // Suppression
        if ($operation instanceof DeleteOperationInterface) {
            $this->orderService->deleteOrder($uriVariables['id']);

            return null;
        }

        // Création d'une commande
        if ($operation instanceof Post && $data instanceof Order) {
            $customer = $this->security->getUser();

            // Construire les items depuis les données reçues
            $items = [];
            foreach ($data->getItems() as $item) {
                $items[] = [
                    'productId' => $item->getProduct()?->getId(),
                    'quantity' => $item->getQuantity(),
                    'specialInstructions' => $item->getSpecialInstructions(),
                ];
            }

            $dto = new CreateOrderDTO(
                items: $items,
                notes: $data->getNotes(),
                tableNumber: $data->getTableNumber(),
            );

            return $this->orderService->createOrder($dto, $customer);
        }

        // Mise à jour du statut
        if ($operation instanceof Patch && $data instanceof Order) {
            return $this->orderService->updateOrderStatus($data, $data->getStatus());
        }

        return $data;
    }
}
