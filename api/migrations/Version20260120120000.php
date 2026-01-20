<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Extras and Stock Management feature.
 */
final class Version20260120120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add extras system and stock management for products';
    }

    public function up(Schema $schema): void
    {
        // Add stock columns to product table
        $this->addSql('ALTER TABLE product ADD stock_quantity INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD low_stock_threshold INT NOT NULL DEFAULT 10');

        // Create extra table
        $this->addSql('CREATE TABLE extra (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            price NUMERIC(10, 2) NOT NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            low_stock_threshold INT NOT NULL DEFAULT 10,
            available BOOLEAN NOT NULL DEFAULT true,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');
        $this->addSql('COMMENT ON COLUMN extra.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN extra.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create product_extra table (join table for Product <-> Extra)
        $this->addSql('CREATE TABLE product_extra (
            id SERIAL PRIMARY KEY,
            product_id INT NOT NULL,
            extra_id INT NOT NULL,
            max_quantity INT NOT NULL DEFAULT 5
        )');
        $this->addSql('CREATE INDEX IDX_product_extra_product ON product_extra (product_id)');
        $this->addSql('CREATE INDEX IDX_product_extra_extra ON product_extra (extra_id)');
        $this->addSql('CREATE UNIQUE INDEX product_extra_unique ON product_extra (product_id, extra_id)');
        $this->addSql('ALTER TABLE product_extra ADD CONSTRAINT FK_product_extra_product FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_extra ADD CONSTRAINT FK_product_extra_extra FOREIGN KEY (extra_id) REFERENCES extra (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Create order_item_extra table
        $this->addSql('CREATE TABLE order_item_extra (
            id SERIAL PRIMARY KEY,
            order_item_id INT NOT NULL,
            extra_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price NUMERIC(10, 2) NOT NULL
        )');
        $this->addSql('CREATE INDEX IDX_order_item_extra_order_item ON order_item_extra (order_item_id)');
        $this->addSql('CREATE INDEX IDX_order_item_extra_extra ON order_item_extra (extra_id)');
        $this->addSql('ALTER TABLE order_item_extra ADD CONSTRAINT FK_order_item_extra_order_item FOREIGN KEY (order_item_id) REFERENCES order_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item_extra ADD CONSTRAINT FK_order_item_extra_extra FOREIGN KEY (extra_id) REFERENCES extra (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // Drop order_item_extra table
        $this->addSql('DROP TABLE order_item_extra');

        // Drop product_extra table
        $this->addSql('DROP TABLE product_extra');

        // Drop extra table
        $this->addSql('DROP TABLE extra');

        // Remove stock columns from product table
        $this->addSql('ALTER TABLE product DROP stock_quantity');
        $this->addSql('ALTER TABLE product DROP low_stock_threshold');
    }
}
