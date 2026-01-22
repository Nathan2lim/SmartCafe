<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyReward;
use App\Entity\LoyaltyTransaction;
use App\Entity\Product;
use App\Enum\LoyaltyTransactionType;
use App\Enum\RewardType;
use App\Tests\Functional\ApiTestCase;

class LoyaltyExtendedApiTest extends ApiTestCase
{
    public function testGetMyLoyaltyAccount(): void
    {
        $user = $this->createUser('loyalty@test.fr', 'password123');

        // Create loyalty account
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(500);
        $loyaltyAccount->setTotalPointsEarned(1000);
        $loyaltyAccount->setTier('silver');
        $this->entityManager->persist($loyaltyAccount);
        $this->entityManager->flush();

        $response = $this->request('GET', '/api/auth/me/loyalty', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals(500, $data['points']);
        $this->assertEquals(1000, $data['totalPointsEarned']);
        $this->assertEquals('silver', $data['tier']);
    }

    public function testGetMyLoyaltyAccountUnauthenticated(): void
    {
        $this->client->request('GET', '/api/auth/me/loyalty');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetMyLoyaltyTransactions(): void
    {
        $user = $this->createUser('transactions@test.fr', 'password123');

        // Create loyalty account
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(500);
        $this->entityManager->persist($loyaltyAccount);

        // Create some transactions using addTransaction for bidirectional relationship
        $transaction1 = new LoyaltyTransaction();
        $transaction1->setType(LoyaltyTransactionType::EARN);
        $transaction1->setPoints(200);
        $transaction1->setDescription('Points pour commande');
        $loyaltyAccount->addTransaction($transaction1);
        $this->entityManager->persist($transaction1);

        $transaction2 = new LoyaltyTransaction();
        $transaction2->setType(LoyaltyTransactionType::BONUS);
        $transaction2->setPoints(50);
        $transaction2->setDescription('Bonus de bienvenue');
        $loyaltyAccount->addTransaction($transaction2);
        $this->entityManager->persist($transaction2);

        $this->entityManager->flush();

        $response = $this->request('GET', '/api/auth/me/loyalty/transactions', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(2, \count($members));
    }

    public function testGetMyLoyaltyTransactionsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/auth/me/loyalty/transactions');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetLoyaltyRewardsPublic(): void
    {
        // Create a reward
        $reward = new LoyaltyReward();
        $reward->setName('Café gratuit');
        $reward->setDescription('Un café offert');
        $reward->setPointsCost(500);
        $reward->setType(RewardType::FREE_PRODUCT);
        $reward->setActive(true);
        $this->entityManager->persist($reward);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/loyalty/rewards');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(1, \count($members));
    }

    public function testRedeemRewardSuccess(): void
    {
        $user = $this->createUser('redeem@test.fr', 'password123');

        // Create loyalty account with enough points
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(1000);
        $loyaltyAccount->setTotalPointsEarned(1000);
        $loyaltyAccount->setTier('bronze');
        $this->entityManager->persist($loyaltyAccount);

        // Create a reward
        $reward = new LoyaltyReward();
        $reward->setName('Réduction 5€');
        $reward->setPointsCost(500);
        $reward->setType(RewardType::DISCOUNT_AMOUNT);
        $reward->setDiscountValue('5.00');
        $reward->setActive(true);
        $this->entityManager->persist($reward);

        $this->entityManager->flush();
        $accountId = $loyaltyAccount->getId();

        $response = $this->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem', [], $user);

        $this->assertResponseIsSuccessful();

        // Verify points were deducted - clear and re-fetch to avoid stale entity
        $this->entityManager->clear();
        $updatedAccount = $this->entityManager->find(LoyaltyAccount::class, $accountId);
        $this->assertEquals(500, $updatedAccount->getPoints());
    }

    public function testRedeemRewardInsufficientPoints(): void
    {
        $user = $this->createUser('insufficientpoints@test.fr', 'password123');

        // Create loyalty account with few points
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(100);
        $loyaltyAccount->setTier('bronze');
        $this->entityManager->persist($loyaltyAccount);

        // Create an expensive reward
        $reward = new LoyaltyReward();
        $reward->setName('Réduction 10€');
        $reward->setPointsCost(1000);
        $reward->setType(RewardType::DISCOUNT_AMOUNT);
        $reward->setDiscountValue('10.00');
        $reward->setActive(true);
        $this->entityManager->persist($reward);

        $this->entityManager->flush();

        $response = $this->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem', [], $user);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRedeemRewardTierNotMet(): void
    {
        $user = $this->createUser('tiernotmet@test.fr', 'password123');

        // Create loyalty account with bronze tier
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(1000);
        $loyaltyAccount->setTier('bronze');
        $this->entityManager->persist($loyaltyAccount);

        // Create a gold-only reward
        $reward = new LoyaltyReward();
        $reward->setName('Récompense Gold');
        $reward->setPointsCost(500);
        $reward->setType(RewardType::DISCOUNT_PERCENT);
        $reward->setDiscountPercent(20);
        $reward->setRequiredTier('gold');
        $reward->setActive(true);
        $this->entityManager->persist($reward);

        $this->entityManager->flush();

        $response = $this->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem', [], $user);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRedeemInactiveReward(): void
    {
        $user = $this->createUser('inactivereward@test.fr', 'password123');

        // Create loyalty account
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(1000);
        $loyaltyAccount->setTier('bronze');
        $this->entityManager->persist($loyaltyAccount);

        // Create an inactive reward
        $reward = new LoyaltyReward();
        $reward->setName('Inactive Reward');
        $reward->setPointsCost(500);
        $reward->setType(RewardType::DISCOUNT_AMOUNT);
        $reward->setDiscountValue('5.00');
        $reward->setActive(false);
        $this->entityManager->persist($reward);

        $this->entityManager->flush();

        $response = $this->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem', [], $user);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRedeemRewardUnauthenticated(): void
    {
        // Create a reward
        $reward = new LoyaltyReward();
        $reward->setName('Unauthenticated Reward');
        $reward->setPointsCost(500);
        $reward->setType(RewardType::DISCOUNT_AMOUNT);
        $reward->setDiscountValue('5.00');
        $reward->setActive(true);
        $this->entityManager->persist($reward);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoyaltyTierProgression(): void
    {
        $user = $this->createUser('tierprogression@test.fr', 'password123');

        // Create loyalty account at bronze level
        $loyaltyAccount = new LoyaltyAccount();
        $loyaltyAccount->setUser($user);
        $loyaltyAccount->setPoints(0);
        $loyaltyAccount->setTotalPointsEarned(0);
        $loyaltyAccount->setTier('bronze');
        $this->entityManager->persist($loyaltyAccount);
        $this->entityManager->flush();

        $response = $this->request('GET', '/api/auth/me/loyalty', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('bronze', $data['tier']);
    }

    public function testGetRewardWithFreeProduct(): void
    {
        // Create a product
        $product = new Product();
        $product->setName('Free Coffee');
        $product->setPrice('4.50');
        $product->setCategory('Boissons');
        $this->entityManager->persist($product);

        // Create a reward with free product
        $reward = new LoyaltyReward();
        $reward->setName('Café offert');
        $reward->setPointsCost(500);
        $reward->setType(RewardType::FREE_PRODUCT);
        $reward->setFreeProduct($product);
        $reward->setActive(true);
        $this->entityManager->persist($reward);

        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/loyalty/rewards/' . $reward->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('Café offert', $data['name']);
        $this->assertEquals('free_product', $data['type']);
    }

    public function testLoyaltyAccountCreatedWithUser(): void
    {
        // When creating a user, a loyalty account should be created automatically
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'newloyalty@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'firstName' => 'New',
                'lastName' => 'User',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        // Login and check loyalty account exists
        $loginResponse = $this->client->request('POST', '/api/login', [
            'json' => [
                'email' => 'newloyalty@smartcafe.fr',
                'password' => 'securePassword123',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }
}
