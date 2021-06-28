<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210627103227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Импорт начальных данных для таблиц reference, user';
    }

    public function up(Schema $schema): void
    {
        $this->connection->insert('"user"', [
            'id' => 1,
            'username' => 'admin',
            'roles' => '["ROLE_ADMIN"]',
            'password' => '$2y$13$s6cFzCkIsGNhvYQdBU2E4eb83StLaM6uw/I8Gp7nz60Rc6VOjiq1q'
        ]);
        $this->addSql('SELECT setval(\'user_id_seq\', (SELECT MAX(id) FROM "user"))');
        $this->addSql('ALTER TABLE "user" ALTER id SET DEFAULT nextval(\'user_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER id DROP DEFAULT');
        $this->addSql('DELETE FROM "user"');
    }
}
