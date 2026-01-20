<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyReward;
use App\Enum\RewardType;
use App\Tests\Functional\ApiTestCase;

class LoyaltyApiTest extends ApiTestCase
{
    public function testGetMyLoyaltyAccount(): void
    {
        $user = $this->createUser('loyalty@test.fr');
        $account = $this->createLoyaltyAccount($user, 500);

        $response = $this->request('GET', '/api/auth/me/loyalty', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals(500, $data['points']);
        $this->assertArrayHasKey('tier', $data);
        $this->assertArrayHasKey('availableRewardsUrl', $data);
    }

    public function testGetMyLoyaltyAccountCreatesIfNotExists(): void
    {
        $user = $this->createUser('newloyalty@test.fr');

        $response = $this->request('GET', '/api/auth/me/loyalty', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals(0, $data['points']);
        $this->assertEquals('bronze', $data['tier']);
    }

    public function testGetLoyaltyRewardsPublic(): void
    {
        $this->createReward('Café gratuit', 100);
        $this->createReward('10% de réduction', 200);

        $response = $this->client->request('GET', '/api/loyalty/rewards');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(2, $data['hydra:member']);
    }

    public function testRedeemRewardSuccess(): void
    {
        $user = $this->createUser('redeem@test.fr');
        $account = $this->createLoyaltyAccount($user, 500);
        $reward = $this->createReward('Test Reward', 100);

        $response = $this->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals(100, $data['points']);
        $this->assertEquals('redeem', $data['type']);

        // Verify points were deducted
        $this->entityManager->refresh($account);
        $this->assertEquals(400, $account->getPoints());
    }

    public function testRedeemRewardInsufficientPoints(): void
    {
        $user = $this->createUser('pooruser@test.fr');
        $this->createLoyaltyAccount($user, 50);
        $reward = $this->createReward('Expensive Reward', 100);

        $response = $this->request('POST', '/api/loyalty/rewards/' . $reward->getId() . '/redeem', [], $user);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateRewardAsAdmin(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->request('POST', '/api/loyalty/rewards', [
            'json' => [
                'name' => 'New Reward',
                'pointsCost' => 150,
                'type' => 'discount_amount',
                'discountValue' => '5.00',
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertEquals('New Reward', $data['name']);
        $this->assertEquals(150, $data['pointsCost']);
    }

    public function testCreateRewardAsUserForbidden(): void
    {
        $user = $this->createUser();

        $response = $this->request('POST', '/api/loyalty/rewards', [
            'json' => [
                'name' => 'Hack Reward',
                'pointsCost' => 1,
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createLoyaltyAccount(\App\Entity\User $user, int $points = 0): LoyaltyAccount
    {
        $account = new LoyaltyAccount();
        $account->setUser($user);
        $account->addPoints($points);

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    private function createReward(string $name, int $pointsCost): LoyaltyReward
    {
        $reward = new LoyaltyReward();
        $reward->setName($name);
        $reward->setPointsCost($pointsCost);
        $reward->setType(RewardType::FREE_PRODUCT);
        $reward->setActive(true);

        $this->entityManager->persist($reward);
        $this->entityManager->flush();

        return $reward;
    }
}
