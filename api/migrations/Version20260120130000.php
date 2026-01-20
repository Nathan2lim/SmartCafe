<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Loyalty System.
 */
final class Version20260120130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add loyalty system: accounts, transactions, and rewards';
    }

    public function up(Schema $schema): void
    {
        // Create loyalty_account table
        $this->addSql('CREATE TABLE loyalty_account (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            points INT NOT NULL DEFAULT 0,
            total_points_earned INT NOT NULL DEFAULT 0,
            total_points_spent INT NOT NULL DEFAULT 0,
            tier VARCHAR(20) NOT NULL DEFAULT \'bronze\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');
        $this->addSql('CREATE INDEX IDX_loyalty_account_user ON loyalty_account (user_id)');
        $this->addSql('CREATE INDEX IDX_loyalty_account_tier ON loyalty_account (tier)');
        $this->addSql('ALTER TABLE loyalty_account ADD CONSTRAINT FK_loyalty_account_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN loyalty_account.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN loyalty_account.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create loyalty_reward table
        $this->addSql('CREATE TABLE loyalty_reward (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            points_cost INT NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT \'free_product\',
            discount_value NUMERIC(10, 2) DEFAULT NULL,
            discount_percent INT DEFAULT NULL,
            free_product_id INT DEFAULT NULL,
            required_tier VARCHAR(20) DEFAULT NULL,
            active BOOLEAN NOT NULL DEFAULT true,
            stock_quantity INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');
        $this->addSql('CREATE INDEX IDX_loyalty_reward_free_product ON loyalty_reward (free_product_id)');
        $this->addSql('ALTER TABLE loyalty_reward ADD CONSTRAINT FK_loyalty_reward_free_product FOREIGN KEY (free_product_id) REFERENCES product (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN loyalty_reward.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN loyalty_reward.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create loyalty_transaction table
        $this->addSql('CREATE TABLE loyalty_transaction (
            id SERIAL PRIMARY KEY,
            account_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            points INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            related_order_id INT DEFAULT NULL,
            redeemed_reward_id INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE INDEX IDX_loyalty_transaction_account ON loyalty_transaction (account_id)');
        $this->addSql('CREATE INDEX IDX_loyalty_transaction_order ON loyalty_transaction (related_order_id)');
        $this->addSql('CREATE INDEX IDX_loyalty_transaction_reward ON loyalty_transaction (redeemed_reward_id)');
        $this->addSql('CREATE INDEX IDX_loyalty_transaction_type ON loyalty_transaction (type)');
        $this->addSql('ALTER TABLE loyalty_transaction ADD CONSTRAINT FK_loyalty_transaction_account FOREIGN KEY (account_id) REFERENCES loyalty_account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loyalty_transaction ADD CONSTRAINT FK_loyalty_transaction_order FOREIGN KEY (related_order_id) REFERENCES "order" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loyalty_transaction ADD CONSTRAINT FK_loyalty_transaction_reward FOREIGN KEY (redeemed_reward_id) REFERENCES loyalty_reward (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN loyalty_transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE loyalty_transaction');
        $this->addSql('DROP TABLE loyalty_reward');
        $this->addSql('DROP TABLE loyalty_account');
    }
}
