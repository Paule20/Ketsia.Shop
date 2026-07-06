<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration initiale : cree toutes les tables de Ketsia.shop.
 * Ordre de creation respectant les contraintes de cles etrangeres :
 * user → category → product → order → order_item
 */
final class Version20260619000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation des tables user, category, product, order, order_item';
    }

    public function up(Schema $schema): void
    {
        // Table des utilisateurs (backticks car "user" est un mot reserve MySQL)
        $this->addSql('CREATE TABLE `user` (
            id          INT AUTO_INCREMENT NOT NULL,
            email       VARCHAR(180) NOT NULL,
            roles       JSON NOT NULL,
            password    VARCHAR(255) NOT NULL,
            first_name  VARCHAR(100) NOT NULL,
            last_name   VARCHAR(100) NOT NULL,
            created_at  DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table des categories de produits
        $this->addSql('CREATE TABLE category (
            id    INT AUTO_INCREMENT NOT NULL,
            name  VARCHAR(100) NOT NULL,
            slug  VARCHAR(100) NOT NULL,
            UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table des produits avec cle etrangere vers category
        $this->addSql('CREATE TABLE product (
            id           INT AUTO_INCREMENT NOT NULL,
            category_id  INT NOT NULL,
            name         VARCHAR(255) NOT NULL,
            description  LONGTEXT DEFAULT NULL,
            price        DECIMAL(10, 2) NOT NULL,
            stock        INT NOT NULL,
            sub_category VARCHAR(100) DEFAULT NULL,
            image_url    VARCHAR(500) DEFAULT NULL,
            created_at   DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_D34A04AD12469DE2 (category_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table des commandes (backticks car "order" est un mot reserve MySQL)
        $this->addSql('CREATE TABLE `order` (
            id               INT AUTO_INCREMENT NOT NULL,
            user_id          INT NOT NULL,
            status           VARCHAR(50) NOT NULL,
            total            DECIMAL(10, 2) NOT NULL,
            shipping_address LONGTEXT NOT NULL,
            created_at       DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_F5299398A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Lignes de commande : jointure entre order et product
        $this->addSql('CREATE TABLE order_item (
            id         INT AUTO_INCREMENT NOT NULL,
            order_id   INT NOT NULL,
            product_id INT NOT NULL,
            quantity   INT NOT NULL,
            unit_price DECIMAL(10, 2) NOT NULL,
            INDEX IDX_52EA1F098D9F6D38 (order_id),
            INDEX IDX_52EA1F094584665A (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Cles etrangeres
        $this->addSql('ALTER TABLE product
            ADD CONSTRAINT FK_D34A04AD12469DE2
            FOREIGN KEY (category_id) REFERENCES category (id)');

        $this->addSql('ALTER TABLE `order`
            ADD CONSTRAINT FK_F5299398A76ED395
            FOREIGN KEY (user_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE order_item
            ADD CONSTRAINT FK_52EA1F098D9F6D38
            FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE order_item
            ADD CONSTRAINT FK_52EA1F094584665A
            FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression dans l'ordre inverse pour respecter les contraintes
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE `user`');
    }
}
