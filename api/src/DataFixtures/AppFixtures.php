<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Extra;
use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyReward;
use App\Entity\LoyaltyTransaction;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductExtra;
use App\Entity\User;
use App\Enum\LoyaltyTransactionType;
use App\Enum\OrderStatus;
use App\Enum\RewardType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // Users (30 users)
        $users = $this->createUsers($manager, 30);

        // Extras (50 extras)
        $extras = $this->createExtras($manager);

        // Products (100+ products)
        $products = $this->createProducts($manager);

        // Product-Extra associations
        $this->createProductExtras($manager, $products, $extras);

        // Loyalty Rewards (30 rewards)
        $this->createLoyaltyRewards($manager, $products);

        // Loyalty Accounts for users
        $loyaltyAccounts = $this->createLoyaltyAccounts($manager, $users);

        $manager->flush();

        // Orders (200 orders) - after flush to have IDs
        $this->createOrders($manager, $users, $products, $extras);

        // Loyalty Transactions
        $this->createLoyaltyTransactions($manager, $loyaltyAccounts);

        $manager->flush();
    }

    /**
     * @return array<User>
     */
    private function createUsers(ObjectManager $manager, int $count): array
    {
        $users = [];

        // Admin
        $admin = new User();
        $admin->setEmail('admin@smartcafe.fr');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('Admin');
        $admin->setLastName('SmartCafe');
        $admin->setPhone('+33600000000');
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);
        $users[] = $admin;

        // Test user
        $testUser = new User();
        $testUser->setEmail('user@smartcafe.fr');
        $testUser->setPassword($this->passwordHasher->hashPassword($testUser, 'password123'));
        $testUser->setFirstName('John');
        $testUser->setLastName('Doe');
        $testUser->setPhone('+33612345678');
        $manager->persist($testUser);
        $users[] = $testUser;

        // Random users
        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setEmail($this->faker->unique()->safeEmail());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setFirstName($this->faker->firstName());
            $user->setLastName($this->faker->lastName());
            $user->setPhone($this->faker->optional(0.7)->phoneNumber());
            $user->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-1 year', 'now')));

            $manager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, Extra>
     */
    private function createExtras(ObjectManager $manager): array
    {
        $extrasData = [
            // Sirops
            ['Sirop caramel', 'Sirop de caramel onctueux', '0.60'],
            ['Sirop vanille', 'Sirop de vanille naturelle', '0.60'],
            ['Sirop noisette', 'Sirop de noisette torréfiée', '0.60'],
            ['Sirop amande', 'Sirop d\'amande douce', '0.60'],
            ['Sirop érable', 'Sirop d\'érable du Canada', '0.70'],
            ['Sirop speculoos', 'Sirop saveur speculoos', '0.60'],
            ['Sirop coco', 'Sirop de noix de coco', '0.60'],
            ['Sirop lavande', 'Sirop de lavande bio', '0.70'],
            ['Sirop rose', 'Sirop à la rose', '0.70'],
            ['Sirop menthe', 'Sirop de menthe fraîche', '0.60'],
            ['Sirop chocolat', 'Sirop de chocolat belge', '0.60'],
            ['Sirop caramel salé', 'Sirop caramel beurre salé', '0.70'],

            // Laits
            ['Lait d\'avoine', 'Alternative végétale', '0.70'],
            ['Lait d\'amande', 'Lait d\'amande doux', '0.70'],
            ['Lait de soja', 'Lait de soja bio', '0.60'],
            ['Lait de coco', 'Lait de coco onctueux', '0.80'],
            ['Lait sans lactose', 'Lait de vache sans lactose', '0.50'],
            ['Lait entier', 'Lait entier fermier', '0.30'],

            // Toppings
            ['Crème chantilly', 'Crème fouettée maison', '0.50'],
            ['Crème chantilly vanille', 'Chantilly à la vanille', '0.60'],
            ['Copeaux chocolat noir', 'Chocolat noir 70%', '0.40'],
            ['Copeaux chocolat blanc', 'Chocolat blanc belge', '0.40'],
            ['Pépites chocolat', 'Mini pépites de chocolat', '0.50'],
            ['Cannelle', 'Cannelle de Ceylan', '0.30'],
            ['Cacao en poudre', 'Cacao amer', '0.30'],
            ['Marshmallows', 'Mini marshmallows', '0.50'],
            ['Caramel liquide', 'Coulis de caramel', '0.40'],
            ['Coulis chocolat', 'Coulis chocolat chaud', '0.40'],
            ['Noix de pécan', 'Éclats de noix de pécan', '0.60'],
            ['Noisettes concassées', 'Noisettes torréfiées', '0.50'],
            ['Amandes effilées', 'Amandes grillées', '0.50'],
            ['Perles de sucre', 'Perles de sucre colorées', '0.30'],

            // Shots & extras café
            ['Shot espresso', 'Dose supplémentaire', '0.80'],
            ['Double shot', 'Double dose espresso', '1.50'],
            ['Shot décaféiné', 'Espresso décaféiné', '0.80'],
            ['Mousse de lait', 'Mousse de lait extra', '0.40'],
            ['Mousse végétale', 'Mousse lait d\'avoine', '0.50'],

            // Suppléments food
            ['Beurre', 'Beurre doux AOP', '0.50'],
            ['Confiture fraise', 'Confiture artisanale', '0.60'],
            ['Confiture abricot', 'Confiture maison', '0.60'],
            ['Miel', 'Miel de fleurs bio', '0.70'],
            ['Pâte à tartiner', 'Pâte chocolat-noisette', '0.80'],
            ['Cream cheese', 'Fromage frais', '0.70'],
            ['Avocat', 'Tranche d\'avocat frais', '1.50'],
            ['Saumon fumé', 'Saumon fumé artisanal', '2.50'],
            ['Bacon', 'Bacon croustillant', '1.50'],
            ['Œuf', 'Œuf au plat ou brouillé', '1.20'],
            ['Fromage', 'Emmental ou comté', '1.00'],
        ];

        $extras = [];
        foreach ($extrasData as $index => [$name, $description, $price]) {
            $extra = new Extra();
            $extra->setName($name);
            $extra->setDescription($description);
            $extra->setPrice($price);
            $extra->setStockQuantity($this->faker->numberBetween(20, 200));
            $extra->setLowStockThreshold($this->faker->numberBetween(5, 15));
            $extra->setAvailable($this->faker->boolean(95));

            $manager->persist($extra);
            $extras[$index] = $extra;
        }

        return $extras;
    }

    /**
     * @return array<string, Product>
     */
    private function createProducts(ObjectManager $manager): array
    {
        $productsData = [
            // Cafés classiques
            'Boissons chaudes' => [
                ['Espresso', 'Café court et intense, 100% arabica', '2.50'],
                ['Double Espresso', 'Double dose d\'espresso', '3.50'],
                ['Ristretto', 'Espresso très court et concentré', '2.50'],
                ['Lungo', 'Espresso allongé', '2.80'],
                ['Americano', 'Espresso allongé à l\'eau chaude', '3.00'],
                ['Café filtre', 'Café filtre doux et aromatique', '2.50'],
                ['Café Latte', 'Espresso avec lait crémeux', '4.50'],
                ['Latte Macchiato', 'Lait chaud tacheté d\'espresso', '4.50'],
                ['Cappuccino', 'Espresso, lait et mousse de lait', '4.50'],
                ['Flat White', 'Double espresso et lait micromoussé', '4.80'],
                ['Mocha', 'Café, chocolat et lait', '5.00'],
                ['Mocha blanc', 'Café, chocolat blanc et lait', '5.20'],
                ['Café Viennois', 'Espresso et crème chantilly', '4.80'],
                ['Irish Coffee (sans alcool)', 'Café, crème et sirop whisky', '5.50'],
                ['Affogato', 'Espresso sur glace vanille', '5.50'],
                ['Cortado', 'Espresso coupé au lait', '3.50'],
                ['Macchiato', 'Espresso tacheté de mousse', '3.00'],
                ['Café noisette', 'Espresso et touche de lait', '3.00'],
                ['Café crème', 'Café et crème onctueuse', '3.80'],

                // Thés
                ['Thé vert Sencha', 'Thé vert japonais', '3.50'],
                ['Thé vert Matcha', 'Matcha traditionnel', '4.50'],
                ['Matcha Latte', 'Matcha et lait moussé', '5.00'],
                ['Thé noir Earl Grey', 'Thé noir à la bergamote', '3.50'],
                ['Thé noir English Breakfast', 'Thé noir corsé', '3.50'],
                ['Thé Chai', 'Thé noir aux épices', '4.00'],
                ['Chai Latte', 'Chai et lait moussé', '4.80'],
                ['Thé Oolong', 'Thé semi-fermenté', '4.00'],
                ['Thé blanc', 'Thé blanc délicat', '4.50'],
                ['Rooibos', 'Infusion sud-africaine', '3.50'],
                ['Rooibos vanille', 'Rooibos à la vanille', '3.80'],
                ['Infusion menthe', 'Menthe fraîche', '3.50'],
                ['Infusion verveine', 'Verveine citronnée', '3.50'],
                ['Infusion camomille', 'Camomille apaisante', '3.50'],
                ['Infusion fruits rouges', 'Mélange de fruits rouges', '3.80'],

                // Chocolats
                ['Chocolat chaud', 'Chocolat fondant maison', '4.00'],
                ['Chocolat chaud noir', 'Chocolat 70% cacao', '4.50'],
                ['Chocolat blanc chaud', 'Chocolat blanc onctueux', '4.50'],
                ['Chocolat à l\'ancienne', 'Chocolat épais traditionnel', '5.00'],
                ['Chocolat Viennois', 'Chocolat et chantilly', '5.00'],

                // Autres
                ['Lait chaud', 'Lait entier chaud', '2.50'],
                ['Lait chaud miel', 'Lait et miel bio', '3.00'],
                ['Golden Milk', 'Lait au curcuma', '4.50'],
                ['Cidre chaud', 'Jus de pomme chaud épicé', '4.00'],
            ],

            'Boissons froides' => [
                // Cafés glacés
                ['Espresso glacé', 'Espresso sur glace', '3.00'],
                ['Americano glacé', 'Americano sur glace', '3.50'],
                ['Cold Brew', 'Café infusé à froid 12h', '4.50'],
                ['Cold Brew Nitro', 'Cold brew infusé à l\'azote', '5.50'],
                ['Ice Latte', 'Café latte glacé', '5.00'],
                ['Ice Latte vanille', 'Latte glacé à la vanille', '5.50'],
                ['Ice Latte caramel', 'Latte glacé au caramel', '5.50'],
                ['Ice Cappuccino', 'Cappuccino glacé', '5.00'],
                ['Ice Mocha', 'Mocha glacé', '5.50'],
                ['Frappuccino café', 'Café glacé mixé', '5.50'],
                ['Frappuccino mocha', 'Frappuccino chocolat-café', '6.00'],
                ['Frappuccino caramel', 'Frappuccino au caramel', '6.00'],
                ['Frappuccino vanille', 'Frappuccino à la vanille', '5.80'],
                ['Frappuccino cookie', 'Frappuccino aux cookies', '6.50'],
                ['Shakerato', 'Espresso secoué glacé', '4.50'],

                // Thés glacés
                ['Ice Tea pêche', 'Thé glacé à la pêche', '4.00'],
                ['Ice Tea citron', 'Thé glacé au citron', '4.00'],
                ['Ice Tea menthe', 'Thé glacé à la menthe', '4.00'],
                ['Ice Tea passion', 'Thé glacé fruit de la passion', '4.50'],
                ['Ice Matcha Latte', 'Matcha glacé au lait', '5.50'],
                ['Ice Chai Latte', 'Chai glacé au lait', '5.00'],

                // Smoothies
                ['Smoothie fruits rouges', 'Fraises, framboises, myrtilles', '5.50'],
                ['Smoothie tropical', 'Mangue, ananas, coco', '5.50'],
                ['Smoothie banane', 'Banane, lait, miel', '5.00'],
                ['Smoothie vert', 'Épinards, banane, pomme', '5.50'],
                ['Smoothie açaï', 'Açaï, banane, granola', '6.50'],
                ['Smoothie bowl', 'Smoothie épais et toppings', '7.50'],

                // Jus
                ['Jus d\'orange pressé', 'Oranges fraîches', '4.00'],
                ['Jus pamplemousse', 'Pamplemousse pressé', '4.00'],
                ['Jus pomme', 'Jus de pomme bio', '3.50'],
                ['Jus multifruits', 'Mélange de fruits frais', '4.50'],
                ['Jus carotte-gingembre', 'Carotte et gingembre frais', '4.50'],
                ['Jus détox', 'Céleri, pomme, citron', '5.00'],

                // Limonades
                ['Limonade maison', 'Citron, sucre, eau pétillante', '4.00'],
                ['Limonade menthe', 'Limonade à la menthe', '4.50'],
                ['Limonade gingembre', 'Limonade au gingembre', '4.50'],
                ['Limonade lavande', 'Limonade à la lavande', '4.50'],
                ['Limonade fraise', 'Limonade aux fraises', '5.00'],

                // Autres
                ['Eau plate', 'Eau minérale 50cl', '2.00'],
                ['Eau gazeuse', 'Eau pétillante 50cl', '2.50'],
                ['Soda cola', 'Cola artisanal', '3.50'],
                ['Soda gingembre', 'Ginger beer', '3.50'],
                ['Kombucha', 'Thé fermenté bio', '4.50'],
            ],

            'Viennoiseries' => [
                ['Croissant', 'Croissant pur beurre AOP', '1.80'],
                ['Croissant aux amandes', 'Croissant garni de crème d\'amandes', '2.80'],
                ['Pain au chocolat', 'Chocolatine artisanale', '2.00'],
                ['Pain aux raisins', 'Raisins et crème pâtissière', '2.20'],
                ['Chausson aux pommes', 'Pommes caramélisées', '2.50'],
                ['Brioche', 'Brioche moelleuse', '2.00'],
                ['Brioche suisse', 'Brioche, crème pâtissière, pépites', '2.80'],
                ['Chouquettes (x6)', 'Petits choux au sucre', '2.50'],
                ['Pain au lait', 'Pain au lait moelleux', '1.50'],
                ['Palmier', 'Feuilletage caramélisé', '2.00'],
                ['Oranais', 'Viennoiserie à l\'abricot', '2.30'],
                ['Torsade chocolat', 'Feuilletage torsadé au chocolat', '2.50'],
                ['Kouign-amann', 'Spécialité bretonne caramélisée', '3.00'],
                ['Pain suisse', 'Pain brioché aux pépites', '2.50'],
                ['Escargot raisins', 'Viennoiserie aux raisins', '2.30'],
            ],

            'Pâtisseries' => [
                ['Cookie chocolat', 'Cookie aux pépites de chocolat', '2.50'],
                ['Cookie double chocolat', 'Cookie tout chocolat', '2.80'],
                ['Cookie noix de pécan', 'Cookie aux noix de pécan', '2.80'],
                ['Cookie matcha', 'Cookie au thé matcha', '3.00'],
                ['Brownie', 'Brownie chocolat fondant', '3.50'],
                ['Brownie noix', 'Brownie aux noix', '3.80'],
                ['Blondie', 'Brownie au chocolat blanc', '3.50'],
                ['Muffin myrtille', 'Muffin aux myrtilles', '3.00'],
                ['Muffin chocolat', 'Muffin au chocolat', '3.00'],
                ['Muffin citron-pavot', 'Muffin citronné', '3.00'],
                ['Muffin carotte', 'Muffin aux carottes', '3.00'],
                ['Financier', 'Financier aux amandes', '2.50'],
                ['Madeleine', 'Madeleine pur beurre', '2.00'],
                ['Canelé', 'Canelé bordelais', '2.80'],
                ['Éclair chocolat', 'Éclair au chocolat', '4.00'],
                ['Éclair café', 'Éclair au café', '4.00'],
                ['Éclair vanille', 'Éclair à la vanille', '4.00'],
                ['Paris-Brest', 'Praliné et pâte à choux', '5.00'],
                ['Tarte citron', 'Tarte au citron meringuée', '4.50'],
                ['Tarte fruits rouges', 'Tarte aux fruits rouges', '5.00'],
                ['Tarte pomme', 'Tarte aux pommes', '4.00'],
                ['Cheesecake', 'Cheesecake new-yorkais', '5.00'],
                ['Cheesecake fruits rouges', 'Cheesecake et coulis', '5.50'],
                ['Carrot cake', 'Gâteau à la carotte', '4.50'],
                ['Banana bread', 'Cake à la banane', '3.50'],
                ['Fondant chocolat', 'Cœur coulant au chocolat', '5.50'],
                ['Tiramisu', 'Tiramisu traditionnel', '5.00'],
                ['Panna cotta', 'Panna cotta et coulis', '4.50'],
            ],

            'Sandwichs' => [
                ['Jambon-beurre', 'Jambon de Paris, beurre AOP', '5.50'],
                ['Jambon-fromage', 'Jambon, emmental, beurre', '6.00'],
                ['Poulet crudités', 'Poulet rôti, salade, tomate', '6.50'],
                ['Poulet curry', 'Poulet, sauce curry, crudités', '7.00'],
                ['Thon crudités', 'Thon, mayonnaise, crudités', '6.50'],
                ['Saumon fumé', 'Saumon, cream cheese, aneth', '8.50'],
                ['Végétarien', 'Légumes grillés, houmous', '6.00'],
                ['Mozza-tomate', 'Mozzarella, tomate, basilic', '6.50'],
                ['Club sandwich', 'Poulet, bacon, œuf, crudités', '8.00'],
                ['BLT', 'Bacon, laitue, tomate', '7.00'],
                ['Parisien', 'Jambon, beurre, cornichons', '6.00'],
                ['Nordique', 'Saumon, avocat, cream cheese', '9.00'],
            ],

            'Wraps & Bagels' => [
                ['Wrap poulet caesar', 'Poulet, parmesan, sauce caesar', '7.00'],
                ['Wrap veggie', 'Légumes, houmous, feta', '6.50'],
                ['Wrap saumon', 'Saumon, avocat, crudités', '8.00'],
                ['Wrap falafel', 'Falafels, crudités, sauce yaourt', '7.00'],
                ['Bagel saumon', 'Saumon fumé, cream cheese', '8.50'],
                ['Bagel poulet', 'Poulet, avocat, bacon', '8.00'],
                ['Bagel veggie', 'Avocat, tomate, roquette', '7.00'],
                ['Bagel egg & cheese', 'Œuf brouillé, fromage', '6.50'],
            ],

            'Salades' => [
                ['Salade César', 'Poulet, parmesan, croûtons', '9.00'],
                ['Salade grecque', 'Feta, olives, concombre', '8.50'],
                ['Salade chèvre chaud', 'Chèvre, miel, noix', '9.00'],
                ['Salade quinoa', 'Quinoa, légumes, feta', '9.50'],
                ['Salade saumon', 'Saumon, avocat, agrumes', '10.00'],
                ['Salade italienne', 'Jambon cru, mozza, tomates', '9.50'],
                ['Poke bowl saumon', 'Saumon, riz, avocat, edamame', '11.00'],
                ['Poke bowl poulet', 'Poulet, riz, légumes', '10.00'],
            ],

            'Petit-déjeuner' => [
                ['Formule petit-déj', 'Boisson + viennoiserie', '5.50'],
                ['Formule complète', 'Boisson + viennoiserie + jus', '7.50'],
                ['Granola bowl', 'Granola, yaourt, fruits frais', '6.50'],
                ['Açaï bowl', 'Açaï, granola, fruits, miel', '8.50'],
                ['Avocado toast', 'Avocat, œuf poché, pain complet', '9.00'],
                ['Eggs benedict', 'Œufs pochés, sauce hollandaise', '10.00'],
                ['Pancakes', 'Pancakes, sirop d\'érable, fruits', '8.00'],
                ['French toast', 'Pain perdu, fruits, chantilly', '8.50'],
                ['Omelette', 'Omelette aux herbes, salade', '8.00'],
                ['English breakfast', 'Œufs, bacon, saucisse, beans', '12.00'],
                ['Tartines', 'Pain de campagne, garniture au choix', '6.00'],
            ],
        ];

        $products = [];
        $index = 0;
        foreach ($productsData as $category => $items) {
            foreach ($items as [$name, $description, $price]) {
                $product = new Product();
                $product->setName($name);
                $product->setDescription($description);
                $product->setPrice($price);
                $product->setCategory($category);
                $product->setStockQuantity($this->faker->numberBetween(10, 300));
                $product->setLowStockThreshold($this->faker->numberBetween(5, 20));
                $product->setAlaCarte(in_array($category, ['Viennoiseries', 'Pâtisseries', 'Sandwichs', 'Wraps & Bagels', 'Salades', 'Petit-déjeuner']));
                $product->setAvailable($this->faker->boolean(95));
                $product->setImageUrl($this->faker->optional(0.3)->imageUrl(640, 480, 'food'));

                $manager->persist($product);
                $products[$index] = $product;
                $index++;
            }
        }

        return $products;
    }

    /**
     * @param array<int, Product> $products
     * @param array<int, Extra>   $extras
     */
    private function createProductExtras(ObjectManager $manager, array $products, array $extras): void
    {
        foreach ($products as $product) {
            $category = $product->getCategory();

            // Boissons chaudes: sirops, laits, toppings café
            if ($category === 'Boissons chaudes') {
                $eligibleExtras = array_filter($extras, fn($e) =>
                    str_contains($e->getName(), 'Sirop') ||
                    str_contains($e->getName(), 'Lait') ||
                    str_contains($e->getName(), 'Shot') ||
                    str_contains($e->getName(), 'Crème') ||
                    str_contains($e->getName(), 'Copeaux') ||
                    str_contains($e->getName(), 'Cannelle') ||
                    str_contains($e->getName(), 'Cacao') ||
                    str_contains($e->getName(), 'Mousse') ||
                    str_contains($e->getName(), 'Marshmallow')
                );

                foreach ($this->faker->randomElements($eligibleExtras, $this->faker->numberBetween(5, 15)) as $extra) {
                    $this->createProductExtra($manager, $product, $extra);
                }
            }

            // Boissons froides: sirops, laits, toppings
            if ($category === 'Boissons froides') {
                $eligibleExtras = array_filter($extras, fn($e) =>
                    str_contains($e->getName(), 'Sirop') ||
                    str_contains($e->getName(), 'Lait') ||
                    str_contains($e->getName(), 'Shot') ||
                    str_contains($e->getName(), 'Crème')
                );

                foreach ($this->faker->randomElements($eligibleExtras, $this->faker->numberBetween(3, 10)) as $extra) {
                    $this->createProductExtra($manager, $product, $extra);
                }
            }

            // Viennoiseries: beurre, confitures
            if ($category === 'Viennoiseries') {
                $eligibleExtras = array_filter($extras, fn($e) =>
                    str_contains($e->getName(), 'Beurre') ||
                    str_contains($e->getName(), 'Confiture') ||
                    str_contains($e->getName(), 'Miel') ||
                    str_contains($e->getName(), 'Pâte à tartiner')
                );

                foreach ($eligibleExtras as $extra) {
                    if ($this->faker->boolean(60)) {
                        $this->createProductExtra($manager, $product, $extra, $this->faker->numberBetween(1, 2));
                    }
                }
            }

            // Sandwichs, Wraps: suppléments salés
            if (in_array($category, ['Sandwichs', 'Wraps & Bagels', 'Salades', 'Petit-déjeuner'])) {
                $eligibleExtras = array_filter($extras, fn($e) =>
                    str_contains($e->getName(), 'Avocat') ||
                    str_contains($e->getName(), 'Saumon') ||
                    str_contains($e->getName(), 'Bacon') ||
                    str_contains($e->getName(), 'Œuf') ||
                    str_contains($e->getName(), 'Fromage') ||
                    str_contains($e->getName(), 'Cream cheese')
                );

                foreach ($eligibleExtras as $extra) {
                    if ($this->faker->boolean(50)) {
                        $this->createProductExtra($manager, $product, $extra, $this->faker->numberBetween(1, 3));
                    }
                }
            }
        }
    }

    private function createProductExtra(ObjectManager $manager, Product $product, Extra $extra, int $maxQuantity = 3): void
    {
        $productExtra = new ProductExtra();
        $productExtra->setProduct($product);
        $productExtra->setExtra($extra);
        $productExtra->setMaxQuantity($maxQuantity);

        $manager->persist($productExtra);
    }

    /**
     * @param array<int, Product> $products
     */
    private function createLoyaltyRewards(ObjectManager $manager, array $products): void
    {
        $rewardsData = [
            // Produits offerts
            ['Espresso offert', 'Un espresso offert', 50, RewardType::FREE_PRODUCT],
            ['Café Latte offert', 'Un café latte offert', 100, RewardType::FREE_PRODUCT],
            ['Cappuccino offert', 'Un cappuccino offert', 100, RewardType::FREE_PRODUCT],
            ['Croissant offert', 'Un croissant pur beurre', 40, RewardType::FREE_PRODUCT],
            ['Pain au chocolat offert', 'Un pain au chocolat', 45, RewardType::FREE_PRODUCT],
            ['Cookie offert', 'Un cookie au chocolat', 50, RewardType::FREE_PRODUCT],
            ['Muffin offert', 'Un muffin au choix', 60, RewardType::FREE_PRODUCT],
            ['Smoothie offert', 'Un smoothie au choix', 120, RewardType::FREE_PRODUCT],
            ['Sandwich offert', 'Un sandwich au choix', 150, RewardType::FREE_PRODUCT],
            ['Frappuccino offert', 'Un frappuccino au choix', 130, RewardType::FREE_PRODUCT],

            // Réductions montant
            ['Réduction 1€', '1€ de réduction', 20, RewardType::DISCOUNT_AMOUNT, '1.00'],
            ['Réduction 2€', '2€ de réduction', 40, RewardType::DISCOUNT_AMOUNT, '2.00'],
            ['Réduction 3€', '3€ de réduction', 60, RewardType::DISCOUNT_AMOUNT, '3.00'],
            ['Réduction 5€', '5€ de réduction', 100, RewardType::DISCOUNT_AMOUNT, '5.00'],
            ['Réduction 10€', '10€ de réduction', 200, RewardType::DISCOUNT_AMOUNT, '10.00'],

            // Réductions pourcentage
            ['Réduction 5%', '5% sur la commande', 30, RewardType::DISCOUNT_PERCENT, null, 5],
            ['Réduction 10%', '10% sur la commande', 60, RewardType::DISCOUNT_PERCENT, null, 10],
            ['Réduction 15%', '15% sur la commande', 90, RewardType::DISCOUNT_PERCENT, null, 15],
            ['Réduction 20%', '20% sur la commande', 120, RewardType::DISCOUNT_PERCENT, null, 20],

            // Double points
            ['Points x2', 'Points doublés prochaine commande', 80, RewardType::DOUBLE_POINTS],
            ['Points x2 (semaine)', 'Points doublés pendant 7 jours', 200, RewardType::DOUBLE_POINTS],

            // Silver tier
            ['Boisson chaude offerte (Silver)', 'Une boisson chaude au choix', 80, RewardType::FREE_PRODUCT, null, null, 'silver'],
            ['Réduction 25% (Silver)', '25% sur la commande', 150, RewardType::DISCOUNT_PERCENT, null, 25, 'silver'],

            // Gold tier
            ['Petit-déj offert (Gold)', 'Formule petit-déj complète', 180, RewardType::FREE_PRODUCT, null, null, 'gold'],
            ['Réduction 30% (Gold)', '30% sur la commande', 200, RewardType::DISCOUNT_PERCENT, null, 30, 'gold'],
            ['Menu complet offert (Gold)', 'Sandwich + boisson + dessert', 300, RewardType::FREE_PRODUCT, null, null, 'gold'],
        ];

        $coffeeProducts = array_filter($products, fn($p) =>
            str_contains(strtolower($p->getName()), 'espresso') ||
            str_contains(strtolower($p->getName()), 'latte') ||
            str_contains(strtolower($p->getName()), 'cappuccino') ||
            str_contains(strtolower($p->getName()), 'croissant') ||
            str_contains(strtolower($p->getName()), 'cookie')
        );
        $coffeeProducts = array_values($coffeeProducts);

        foreach ($rewardsData as $data) {
            $reward = new LoyaltyReward();
            $reward->setName($data[0]);
            $reward->setDescription($data[1]);
            $reward->setPointsCost($data[2]);
            $reward->setType($data[3]);

            if (isset($data[4]) && $data[4] !== null) {
                $reward->setDiscountValue($data[4]);
            }
            if (isset($data[5]) && $data[5] !== null) {
                $reward->setDiscountPercent($data[5]);
            }
            if (isset($data[6]) && $data[6] !== null) {
                $reward->setRequiredTier($data[6]);
            }

            if ($data[3] === RewardType::FREE_PRODUCT && !empty($coffeeProducts)) {
                $reward->setFreeProduct($this->faker->randomElement($coffeeProducts));
            }

            $reward->setActive($this->faker->boolean(90));
            $reward->setStockQuantity($this->faker->optional(0.3)->numberBetween(10, 100));

            $manager->persist($reward);
        }
    }

    /**
     * @param array<User> $users
     * @return array<LoyaltyAccount>
     */
    private function createLoyaltyAccounts(ObjectManager $manager, array $users): array
    {
        $accounts = [];
        $tiers = ['bronze', 'bronze', 'bronze', 'bronze', 'silver', 'silver', 'gold'];

        foreach ($users as $user) {
            $account = new LoyaltyAccount();
            $account->setUser($user);
            $account->setPoints($this->faker->numberBetween(0, 500));
            $account->setTotalPointsEarned($this->faker->numberBetween(0, 2000));
            $account->setTotalPointsSpent($this->faker->numberBetween(0, 500));
            $account->setTier($this->faker->randomElement($tiers));

            $manager->persist($account);
            $accounts[] = $account;
        }

        return $accounts;
    }

    /**
     * @param array<User>    $users
     * @param array<Product> $products
     * @param array<Extra>   $extras
     */
    private function createOrders(ObjectManager $manager, array $users, array $products, array $extras): void
    {
        $statuses = [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PREPARING,
            OrderStatus::READY,
            OrderStatus::DELIVERED,
            OrderStatus::DELIVERED,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
        ];

        for ($i = 0; $i < 200; $i++) {
            $order = new Order();
            $order->setCustomer($this->faker->randomElement($users));
            $order->setStatus($this->faker->randomElement($statuses));
            $order->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-6 months', 'now')));
            $order->setNotes($this->faker->optional(0.2)->sentence());
            $order->setTableNumber($this->faker->optional(0.3)->numberBetween(1, 20) ? 'Table ' . $this->faker->numberBetween(1, 20) : null);

            // 1 to 5 items per order
            $itemCount = $this->faker->numberBetween(1, 5);
            $total = 0.0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $this->faker->randomElement($products);
                $quantity = $this->faker->numberBetween(1, 3);

                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setUnitPrice($product->getPrice());

                $total += (float) $product->getPrice() * $quantity;

                $manager->persist($orderItem);
            }

            $order->setTotalAmount(number_format($total, 2, '.', ''));
            $manager->persist($order);
        }
    }

    /**
     * @param array<LoyaltyAccount> $accounts
     */
    private function createLoyaltyTransactions(ObjectManager $manager, array $accounts): void
    {
        $types = [
            LoyaltyTransactionType::EARN,
            LoyaltyTransactionType::EARN,
            LoyaltyTransactionType::EARN,
            LoyaltyTransactionType::REDEEM,
            LoyaltyTransactionType::BONUS,
            LoyaltyTransactionType::EXPIRED,
        ];

        foreach ($accounts as $account) {
            $transactionCount = $this->faker->numberBetween(0, 20);

            for ($i = 0; $i < $transactionCount; $i++) {
                $type = $this->faker->randomElement($types);

                $transaction = new LoyaltyTransaction();
                $transaction->setAccount($account);
                $transaction->setType($type);
                $transaction->setPoints($this->faker->numberBetween(5, 100));
                $transaction->setDescription($this->getTransactionDescription($type));
                $transaction->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-6 months', 'now')));

                $manager->persist($transaction);
            }
        }
    }

    private function getTransactionDescription(LoyaltyTransactionType $type): string
    {
        return match ($type) {
            LoyaltyTransactionType::EARN => 'Points gagnés sur commande #' . $this->faker->numberBetween(1000, 9999),
            LoyaltyTransactionType::REDEEM => 'Récompense échangée',
            LoyaltyTransactionType::BONUS => 'Bonus ' . $this->faker->randomElement(['bienvenue', 'anniversaire', 'parrainage', 'promotion']),
            LoyaltyTransactionType::EXPIRED => 'Points expirés',
            LoyaltyTransactionType::ADJUSTMENT => 'Ajustement manuel',
        };
    }
}
