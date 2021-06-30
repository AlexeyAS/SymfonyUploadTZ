<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210630075107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавлена коллекция загруженных данных импортируемого файла';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX uniq_aea34913d1b862b8 RENAME TO UNIQ_AEA34913CF63803F');
        $this->addSql('ALTER TABLE upload ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE upload DROP filepath');
        $this->addSql('ALTER TABLE upload ADD CONSTRAINT FK_17BDE61F93CB796C FOREIGN KEY (file_id) REFERENCES reference (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_17BDE61F93CB796C ON upload (file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE upload DROP CONSTRAINT FK_17BDE61F93CB796C');
        $this->addSql('DROP INDEX IDX_17BDE61F93CB796C');
        $this->addSql('ALTER TABLE upload ADD filepath VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE upload DROP file_id');
        $this->addSql('ALTER INDEX uniq_aea34913cf63803f RENAME TO uniq_aea34913d1b862b8');
    }
}
