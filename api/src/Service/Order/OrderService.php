<?php

namespace App\Service\Order;

use App\DTO\Order\CreateOrderDTO;
use App\DTO\Order\OrderItemDTO;
use App\DTO\Order\OrderItemExtraDTO;
use App\DTO\Order\UpdateOrderStatusDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderItemExtra;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Exception\ExtraNotAllowedForProductException;
use App\Exception\ExtraNotAvailableException;
use App\Exception\ExtraNotFoundException;
use App\Exception\InsufficientStockException;
use App\Exception\InvalidOrderStatusTransitionException;
use App\Exception\OrderNotFoundException;
use App\Exception\ProductNotAvailableException;
use App\Exception\ProductNotFoundException;
use App\Repository\ExtraRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductExtraRepository;
use App\Repository\ProductRepository;
use App\Service\Loyalty\LoyaltyService;
use App\Service\Stock\StockService;
use Doctrine\ORM\EntityManagerInterface;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly ExtraRepository $extraRepository,
        private readonly ProductExtraRepository $productExtraRepository,
        private readonly StockService $stockService,
        private readonly LoyaltyService $loyaltyService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Crée une nouvelle commande
     */
    public function createOrder(CreateOrderDTO $dto, User $customer): Order
    {
        $order = new Order();
        $order->setCustomer($customer);
        $order->setNotes($dto->notes);
        $order->setTableNumber($dto->tableNumber);

        // Ajouter les items
        foreach ($dto->items as $itemData) {
            $itemDto = $itemData instanceof OrderItemDTO
                ? $itemData
                : new OrderItemDTO(
                    productId: $itemData['productId'] ?? $itemData['product_id'],
                    quantity: $itemData['quantity'] ?? 1,
                    specialInstructions: $itemData['specialInstructions'] ?? $itemData['special_instructions'] ?? null,
                );

            $orderItem = $this->createOrderItem($itemDto);
            $order->addItem($orderItem);
        }

        // Calculer le total
        $order->calculateTotal();

        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * Crée un item de commande
     */
    private function createOrderItem(OrderItemDTO $dto): OrderItem
    {
        $product = $this->productRepository->find($dto->productId);

        if (!$product) {
            throw new ProductNotFoundException($dto->productId);
        }

        if (!$product->isAvailable()) {
            throw new ProductNotAvailableException($product->getName());
        }

        // Vérifier le stock du produit
        if (!$this->stockService->checkProductAvailability($product, $dto->quantity)) {
            throw new InsufficientStockException(
                $product->getName(),
                $dto->quantity,
                $product->getStockQuantity() ?? 0
            );
        }

        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity($dto->quantity);
        $orderItem->setSpecialInstructions($dto->specialInstructions);

        // Ajouter les extras
        foreach ($dto->extras as $extraData) {
            $extraDto = $extraData instanceof OrderItemExtraDTO
                ? $extraData
                : new OrderItemExtraDTO(
                    extraId: $extraData['extraId'] ?? $extraData['extra_id'],
                    quantity: $extraData['quantity'] ?? 1,
                );

            $orderItemExtra = $this->createOrderItemExtra($extraDto, $product, $dto->quantity);
            $orderItem->addExtra($orderItemExtra);
        }

        return $orderItem;
    }

    /**
     * Crée un extra pour un item de commande
     */
    private function createOrderItemExtra(OrderItemExtraDTO $dto, \App\Entity\Product $product, int $itemQuantity): OrderItemExtra
    {
        $extra = $this->extraRepository->find($dto->extraId);

        if (!$extra) {
            throw new ExtraNotFoundException($dto->extraId);
        }

        if (!$extra->isAvailable()) {
            throw new ExtraNotAvailableException($extra->getName());
        }

        // Vérifier que l'extra est autorisé pour ce produit
        $productExtra = $this->productExtraRepository->findByProductAndExtra($product, $extra);
        if (!$productExtra) {
            throw new ExtraNotAllowedForProductException($extra->getName(), $product->getName());
        }

        // Vérifier que la quantité ne dépasse pas le max autorisé
        if ($dto->quantity > $productExtra->getMaxQuantity()) {
            throw new \InvalidArgumentException(sprintf(
                'La quantité de l\'extra "%s" ne peut pas dépasser %d par item',
                $extra->getName(),
                $productExtra->getMaxQuantity()
            ));
        }

        // Calculer la quantité totale d'extras nécessaire
        $totalExtraQuantity = $dto->quantity * $itemQuantity;

        // Vérifier le stock de l'extra
        if (!$this->stockService->checkExtraAvailability($extra, $totalExtraQuantity)) {
            throw new InsufficientStockException(
                $extra->getName(),
                $totalExtraQuantity,
                $extra->getStockQuantity()
            );
        }

        $orderItemExtra = new OrderItemExtra();
        $orderItemExtra->setExtra($extra);
        $orderItemExtra->setQuantity($dto->quantity);

        return $orderItemExtra;
    }

    /**
     * Met à jour le statut d'une commande
     */
    public function updateOrderStatus(Order $order, OrderStatus $newStatus): Order
    {
        // Récupérer le statut actuel depuis la base de données
        $currentStatusQuery = $this->entityManager->createQuery(
            'SELECT o.status FROM App\Entity\Order o WHERE o.id = :id'
        )->setParameter('id', $order->getId());

        $result = $currentStatusQuery->getSingleResult();
        $currentStatus = $result['status'];

        // Vérifier si la transition est valide
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidOrderStatusTransitionException($currentStatus, $newStatus);
        }

        // Déduire le stock lors de la confirmation
        if ($newStatus === OrderStatus::CONFIRMED) {
            $this->deductStockForOrder($order);
        }

        // Restaurer le stock lors de l'annulation
        if ($newStatus === OrderStatus::CANCELLED && $currentStatus === OrderStatus::CONFIRMED) {
            $this->restoreStockForOrder($order);
        }

        // Attribuer les points de fidélité lors de la livraison
        if ($newStatus === OrderStatus::DELIVERED) {
            $this->loyaltyService->awardPointsForOrder($order);
        }

        $order->setStatus($newStatus);
        $order->setUpdatedAt(new \DateTimeImmutable());

        // Mettre à jour les timestamps selon le statut
        match ($newStatus) {
            OrderStatus::CONFIRMED => $order->setConfirmedAt(new \DateTimeImmutable()),
            OrderStatus::READY => $order->setReadyAt(new \DateTimeImmutable()),
            OrderStatus::DELIVERED => $order->setDeliveredAt(new \DateTimeImmutable()),
            default => null,
        };

        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * Déduit le stock pour une commande
     */
    private function deductStockForOrder(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            // Déduire le stock du produit
            $product = $item->getProduct();
            $this->stockService->deductProductStock($product, $item->getQuantity());

            // Déduire le stock des extras
            foreach ($item->getExtras() as $orderItemExtra) {
                $totalExtraQuantity = $orderItemExtra->getQuantity() * $item->getQuantity();
                $this->stockService->deductExtraStock($orderItemExtra->getExtra(), $totalExtraQuantity);
            }
        }
    }

    /**
     * Restaure le stock pour une commande annulée
     */
    private function restoreStockForOrder(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            // Restaurer le stock du produit
            $product = $item->getProduct();
            $this->stockService->restoreProductStock($product, $item->getQuantity());

            // Restaurer le stock des extras
            foreach ($item->getExtras() as $orderItemExtra) {
                $totalExtraQuantity = $orderItemExtra->getQuantity() * $item->getQuantity();
                $this->stockService->restoreExtraStock($orderItemExtra->getExtra(), $totalExtraQuantity);
            }
        }
    }

    /**
     * Récupère une commande par son ID
     */
    public function getOrderById(int $id): Order
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw new OrderNotFoundException($id);
        }

        return $order;
    }

    /**
     * Récupère les commandes d'un client
     * @return Order[]
     */
    public function getOrdersByCustomer(User $customer): array
    {
        return $this->orderRepository->findByCustomer($customer);
    }

    /**
     * Récupère les commandes par statut
     * @return Order[]
     */
    public function getOrdersByStatus(OrderStatus $status): array
    {
        return $this->orderRepository->findByStatus($status);
    }

    /**
     * Récupère les commandes actives (non terminées)
     * @return Order[]
     */
    public function getActiveOrders(): array
    {
        return $this->orderRepository->findActiveOrders();
    }

    /**
     * Récupère les commandes du jour
     * @return Order[]
     */
    public function getTodayOrders(): array
    {
        return $this->orderRepository->findTodayOrders();
    }

    /**
     * Annule une commande
     */
    public function cancelOrder(Order $order): Order
    {
        return $this->updateOrderStatus($order, OrderStatus::CANCELLED);
    }

    /**
     * Supprime une commande
     */
    public function deleteOrder(int $id): void
    {
        $order = $this->getOrderById($id);
        $this->orderRepository->remove($order);
    }
}
