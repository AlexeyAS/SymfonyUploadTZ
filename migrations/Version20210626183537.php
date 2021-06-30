<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210626183537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблицы загружаемых файлов';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE reference_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE reference (id INT NOT NULL, uniq_id VARCHAR(255) NOT NULL, filename VARCHAR(255) DEFAULT NULL, error TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AEA34913D1B862B8 ON reference (uniq_id)');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE reference_id_seq CASCADE');
        $this->addSql('DROP TABLE reference');
    }

}
