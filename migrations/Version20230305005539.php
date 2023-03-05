<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230305005539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE smart_team_conge ADD employe_id INT NOT NULL');
        $this->addSql('ALTER TABLE smart_team_conge ADD CONSTRAINT FK_A901FDD61B65292 FOREIGN KEY (employe_id) REFERENCES smart_team_employe (id)');
        $this->addSql('CREATE INDEX IDX_A901FDD61B65292 ON smart_team_conge (employe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE smart_team_conge DROP FOREIGN KEY FK_A901FDD61B65292');
        $this->addSql('DROP INDEX IDX_A901FDD61B65292 ON smart_team_conge');
        $this->addSql('ALTER TABLE smart_team_conge DROP employe_id');
    }
}
