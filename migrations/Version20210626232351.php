<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210626232351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Связь данных файла со списком';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE upload_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE upload (id INT NOT NULL, hash VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, error TEXT DEFAULT NULL, filepath VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_17BDE61FD1B862B8 ON upload (hash)');
        $this->addSql('ALTER TABLE reference ADD filepath VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE upload_id_seq CASCADE');
        $this->addSql('DROP TABLE upload');
        $this->addSql('ALTER TABLE reference DROP filepath');
        $this->addSql('ALTER INDEX uniq_aea34913cf63803f RENAME TO uniq_aea34913d1b862b8');
    }
}
