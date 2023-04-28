<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230424134140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD flux_rss_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E661033B90D FOREIGN KEY (flux_rss_id) REFERENCES flux_rss (id)');
        $this->addSql('CREATE INDEX IDX_23A0E661033B90D ON article (flux_rss_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E661033B90D');
        $this->addSql('DROP INDEX IDX_23A0E661033B90D ON article');
        $this->addSql('ALTER TABLE article DROP flux_rss_id');
    }
}
