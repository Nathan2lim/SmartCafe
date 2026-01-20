<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Extra;
use App\Service\Extra\ExtraService;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\RequestStack;

final class ExtraStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ExtraService $extraService,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Extra
    {
        // Restock operation
        if ($operation instanceof Post && str_contains($operation->getUriTemplate() ?? '', '/restock')) {
            $request = $this->requestStack->getCurrentRequest();
            $content = json_decode($request->getContent(), true);
            $quantity = $content['quantity'] ?? 0;

            return $this->extraService->restockExtra($uriVariables['id'], (int) $quantity);
        }

        // Regular create/update operations
        $data->setUpdatedAt(new DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
