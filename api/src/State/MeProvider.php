<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class MeProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'Vous devez être connecté pour accéder à cette ressource');
        }

        return $user;
    }
}
