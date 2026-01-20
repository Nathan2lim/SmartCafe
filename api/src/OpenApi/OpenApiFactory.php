<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        // Ajouter les schémas personnalisés
        $this->addCustomSchemas($openApi);

        // Ajouter l'endpoint de login
        $openApi = $this->addLoginEndpoint($openApi);

        // Personnaliser les endpoints
        $openApi = $this->customizeEndpoints($openApi);

        return $openApi;
    }

    private function addCustomSchemas(OpenApi $openApi): void
    {
        $schemas = $openApi->getComponents()->getSchemas();

        // Schéma pour créer un utilisateur
        $schemas['UserCreate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['email', 'plainPassword', 'firstName', 'lastName'],
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'example' => 'jean.dupont@smartcafe.fr',
                    'description' => 'Adresse email de l\'utilisateur',
                ],
                'plainPassword' => [
                    'type' => 'string',
                    'format' => 'password',
                    'example' => 'MonMotDePasse123!',
                    'description' => 'Mot de passe (min. 4 caractères)',
                    'minLength' => 4,
                ],
                'firstName' => [
                    'type' => 'string',
                    'example' => 'Jean',
                    'description' => 'Prénom',
                ],
                'lastName' => [
                    'type' => 'string',
                    'example' => 'Dupont',
                    'description' => 'Nom de famille',
                ],
                'phone' => [
                    'type' => 'string',
                    'example' => '+33612345678',
                    'description' => 'Numéro de téléphone (optionnel)',
                    'nullable' => true,
                ],
            ],
        ]);

        // Schéma pour créer un produit
        $schemas['ProductCreate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['name', 'price', 'category'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'example' => 'Cappuccino',
                    'description' => 'Nom du produit',
                ],
                'description' => [
                    'type' => 'string',
                    'example' => 'Espresso avec mousse de lait onctueuse',
                    'description' => 'Description du produit',
                    'nullable' => true,
                ],
                'price' => [
                    'type' => 'string',
                    'example' => '5.50',
                    'description' => 'Prix en euros (ex: 5.50)',
                    'pattern' => '^\d+\.\d{2}$',
                ],
                'category' => [
                    'type' => 'string',
                    'example' => 'Boissons chaudes',
                    'description' => 'Catégorie du produit',
                    'enum' => ['Boissons chaudes', 'Boissons froides', 'Viennoiseries', 'Pâtisseries', 'Snacks', 'Plats'],
                ],
                'available' => [
                    'type' => 'boolean',
                    'example' => true,
                    'description' => 'Produit disponible à la vente',
                    'default' => true,
                ],
                'alaCarte' => [
                    'type' => 'boolean',
                    'example' => true,
                    'description' => 'Produit à la carte',
                    'default' => false,
                ],
                'imageUrl' => [
                    'type' => 'string',
                    'format' => 'uri',
                    'example' => 'https://example.com/cappuccino.jpg',
                    'description' => 'URL de l\'image du produit',
                    'nullable' => true,
                ],
            ],
        ]);

        // Schéma pour créer un extra
        $schemas['ExtraCreate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['name', 'price', 'stockQuantity'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'example' => 'Crème chantilly',
                    'description' => 'Nom de l\'extra',
                ],
                'description' => [
                    'type' => 'string',
                    'example' => 'Délicieuse crème fouettée',
                    'description' => 'Description de l\'extra',
                    'nullable' => true,
                ],
                'price' => [
                    'type' => 'string',
                    'example' => '0.50',
                    'description' => 'Prix en euros (ex: 0.50)',
                    'pattern' => '^\d+\.\d{2}$',
                ],
                'stockQuantity' => [
                    'type' => 'integer',
                    'example' => 100,
                    'description' => 'Quantité en stock',
                    'minimum' => 0,
                ],
                'lowStockThreshold' => [
                    'type' => 'integer',
                    'example' => 10,
                    'description' => 'Seuil d\'alerte stock bas',
                    'minimum' => 1,
                    'default' => 10,
                ],
                'available' => [
                    'type' => 'boolean',
                    'example' => true,
                    'description' => 'Extra disponible à la vente',
                    'default' => true,
                ],
            ],
        ]);

        // Schéma pour restock un extra
        $schemas['ExtraRestock'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['quantity'],
            'properties' => [
                'quantity' => [
                    'type' => 'integer',
                    'example' => 50,
                    'description' => 'Quantité à ajouter au stock',
                    'minimum' => 1,
                ],
            ],
        ]);

        // Schéma pour un extra dans un item de commande
        $schemas['OrderItemExtraCreate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['extraId'],
            'properties' => [
                'extraId' => [
                    'type' => 'integer',
                    'example' => 1,
                    'description' => 'ID de l\'extra',
                    'minimum' => 1,
                ],
                'quantity' => [
                    'type' => 'integer',
                    'example' => 1,
                    'description' => 'Quantité de cet extra',
                    'minimum' => 1,
                    'default' => 1,
                ],
            ],
        ]);

        // Schéma pour un item de commande
        $schemas['OrderItemCreate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['product', 'quantity'],
            'properties' => [
                'product' => [
                    'type' => 'string',
                    'example' => '/api/products/1',
                    'description' => 'IRI du produit (ex: /api/products/1)',
                ],
                'quantity' => [
                    'type' => 'integer',
                    'example' => 2,
                    'description' => 'Quantité commandée',
                    'minimum' => 1,
                    'default' => 1,
                ],
                'specialInstructions' => [
                    'type' => 'string',
                    'example' => 'Sans sucre',
                    'description' => 'Instructions spéciales (optionnel)',
                    'nullable' => true,
                ],
                'extras' => [
                    'type' => 'array',
                    'description' => 'Liste des extras pour cet item',
                    'items' => ['$ref' => '#/components/schemas/OrderItemExtraCreate'],
                ],
            ],
        ]);

        // Schéma pour créer une commande
        $schemas['OrderCreate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['items'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'Liste des articles commandés',
                    'minItems' => 1,
                    'items' => ['$ref' => '#/components/schemas/OrderItemCreate'],
                ],
                'notes' => [
                    'type' => 'string',
                    'example' => 'Commande pour emporter',
                    'description' => 'Notes pour la commande (optionnel)',
                    'nullable' => true,
                ],
                'tableNumber' => [
                    'type' => 'string',
                    'example' => 'Table 5',
                    'description' => 'Numéro de table (optionnel)',
                    'nullable' => true,
                ],
            ],
        ]);

        // Schéma pour modifier le statut d'une commande
        $schemas['OrderStatusUpdate'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['status'],
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'Nouveau statut de la commande',
                    'enum' => ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'],
                    'example' => 'confirmed',
                ],
            ],
        ]);

        // Schéma pour le login
        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['email', 'password'],
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'example' => 'test@smartcafe.fr',
                    'description' => 'Adresse email',
                ],
                'password' => [
                    'type' => 'string',
                    'format' => 'password',
                    'example' => 'test123',
                    'description' => 'Mot de passe',
                ],
            ],
        ]);

        // Schéma pour la réponse token
        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'description' => 'Token JWT à utiliser dans le header Authorization: Bearer {token}',
                    'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                ],
            ],
        ]);
    }

    private function addLoginEndpoint(OpenApi $openApi): OpenApi
    {
        $pathItem = new Model\PathItem(
            post: new Model\Operation(
                operationId: 'postLogin',
                tags: ['Authentification'],
                summary: 'Connexion utilisateur',
                description: 'Authentifie un utilisateur et retourne un token JWT',
                requestBody: new Model\RequestBody(
                    description: 'Identifiants de connexion',
                    content: new \ArrayObject([
                        'application/json' => new Model\MediaType(
                            schema: new \ArrayObject(['$ref' => '#/components/schemas/Credentials'])
                        ),
                    ]),
                    required: true
                ),
                responses: [
                    '200' => new Model\Response(
                        description: 'Connexion réussie',
                        content: new \ArrayObject([
                            'application/json' => new Model\MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/Token'])
                            ),
                        ])
                    ),
                    '401' => new Model\Response(description: 'Identifiants invalides'),
                ]
            )
        );

        $openApi->getPaths()->addPath('/api/login', $pathItem);

        return $openApi;
    }

    private function customizeEndpoints(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths()->getPaths();

        foreach ($paths as $path => $pathItem) {
            // POST /api/users
            if ($path === '/api/users' && $pathItem->getPost()) {
                $post = $pathItem->getPost();
                $pathItem = $pathItem->withPost(
                    $post->withRequestBody(
                        new Model\RequestBody(
                            description: 'Données du nouvel utilisateur',
                            content: new \ArrayObject([
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/UserCreate'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Créer un compte utilisateur')
                      ->withDescription('Crée un nouveau compte utilisateur. Le mot de passe sera automatiquement hashé.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // POST /api/products
            if ($path === '/api/products' && $pathItem->getPost()) {
                $post = $pathItem->getPost();
                $pathItem = $pathItem->withPost(
                    $post->withRequestBody(
                        new Model\RequestBody(
                            description: 'Données du nouveau produit',
                            content: new \ArrayObject([
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/ProductCreate'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Ajouter un produit au menu')
                      ->withDescription('Ajoute un nouveau produit au menu du café. Réservé aux administrateurs.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // PATCH /api/products/{id}
            if (preg_match('#^/api/products/\{id\}$#', $path) && $pathItem->getPatch()) {
                $patch = $pathItem->getPatch();
                $pathItem = $pathItem->withPatch(
                    $patch->withRequestBody(
                        new Model\RequestBody(
                            description: 'Champs à modifier',
                            content: new \ArrayObject([
                                'application/merge-patch+json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/ProductCreate'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Modifier un produit')
                      ->withDescription('Modifie les informations d\'un produit existant. Seuls les champs envoyés seront modifiés.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // POST /api/orders
            if ($path === '/api/orders' && $pathItem->getPost()) {
                $post = $pathItem->getPost();
                $pathItem = $pathItem->withPost(
                    $post->withRequestBody(
                        new Model\RequestBody(
                            description: 'Données de la commande',
                            content: new \ArrayObject([
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/OrderCreate'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Passer une commande')
                      ->withDescription('Crée une nouvelle commande. Le client est automatiquement défini sur l\'utilisateur connecté.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // PATCH /api/orders/{id}
            if (preg_match('#^/api/orders/\{id\}$#', $path) && $pathItem->getPatch()) {
                $patch = $pathItem->getPatch();
                $pathItem = $pathItem->withPatch(
                    $patch->withRequestBody(
                        new Model\RequestBody(
                            description: 'Nouveau statut',
                            content: new \ArrayObject([
                                'application/merge-patch+json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/OrderStatusUpdate'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Changer le statut d\'une commande')
                      ->withDescription('Modifie le statut d\'une commande. Transitions valides : pending → confirmed → preparing → ready → delivered. Annulation possible jusqu\'à "ready".')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // POST /api/extras
            if ($path === '/api/extras' && $pathItem->getPost()) {
                $post = $pathItem->getPost();
                $pathItem = $pathItem->withPost(
                    $post->withRequestBody(
                        new Model\RequestBody(
                            description: 'Données du nouvel extra',
                            content: new \ArrayObject([
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/ExtraCreate'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Ajouter un extra au menu')
                      ->withDescription('Ajoute un nouvel extra (supplément) au menu. Réservé aux administrateurs.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // POST /api/extras/{id}/restock
            if (preg_match('#^/api/extras/\{id\}/restock$#', $path) && $pathItem->getPost()) {
                $post = $pathItem->getPost();
                $pathItem = $pathItem->withPost(
                    $post->withRequestBody(
                        new Model\RequestBody(
                            description: 'Quantité à ajouter au stock',
                            content: new \ArrayObject([
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject(['$ref' => '#/components/schemas/ExtraRestock'])
                                ),
                            ]),
                            required: true
                        )
                    )->withSummary('Réapprovisionner un extra')
                      ->withDescription('Ajoute une quantité au stock d\'un extra. Réservé aux administrateurs.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // GET /api/extras/low-stock
            if ($path === '/api/extras/low-stock' && $pathItem->getGet()) {
                $get = $pathItem->getGet();
                $pathItem = $pathItem->withGet(
                    $get->withSummary('Récupère les extras en stock bas')
                        ->withDescription('Retourne la liste des extras dont le stock est inférieur au seuil défini. Réservé aux administrateurs.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }

            // GET /api/products/low-stock
            if ($path === '/api/products/low-stock' && $pathItem->getGet()) {
                $get = $pathItem->getGet();
                $pathItem = $pathItem->withGet(
                    $get->withSummary('Récupère les produits en stock bas')
                        ->withDescription('Retourne la liste des produits dont le stock est inférieur au seuil défini. Réservé aux administrateurs.')
                );
                $openApi->getPaths()->addPath($path, $pathItem);
            }
        }

        return $openApi;
    }
}
