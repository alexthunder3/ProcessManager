<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle;

use Doctrine\DBAL\Connection;
use Elements\Bundle\ProcessManagerBundle\Migrations\Version20210428000000;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Model\User\Permission\Definition;

class Installer extends SettingsStoreAwareInstaller
{
    /**
     * @var array<mixed>
     */
    protected array $permissions = [
        Enums\Permissions::VIEW,
        Enums\Permissions::CONFIGURE,
        Enums\Permissions::EXECUTE,
    ];

    public function install(): void
    {
        $this->createPermissions();
        $this->createTables();
        parent::install();
    }

    protected function createPermissions(): void
    {
        foreach ($this->permissions as $permissionKey) {
            Definition::create($permissionKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    protected function getDb(): Connection
    {
        return \Pimcore\Db::get();
    }

    protected function createTables(): void
    {
        $db = $this->getDb();

        $db->executeQuery(
            'CREATE TABLE IF NOT EXISTS `' . ElementsProcessManagerBundle::TABLE_NAME_CONFIGURATION . "` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `creationDate` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `modificationDate` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `name` VARCHAR(255) NOT NULL,
            `group` VARCHAR(50) NULL DEFAULT NULL,
            `description` VARCHAR(255) NULL DEFAULT NULL,
            `executorClass` VARCHAR(500) NOT NULL,
            `cronJob` TEXT NULL,
            `lastCronJobExecution` INT(10) UNSIGNED NULL DEFAULT NULL,
            `active` TINYINT(4) NULL DEFAULT '1',
            `keepVersions` INT(10) UNSIGNED NULL DEFAULT NULL,
            `executorSettings` TEXT NULL,
            `restrictToRoles` VARCHAR(100) NOT NULL DEFAULT '',
            `action` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `name` (`name`)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");

        $db->executeQuery(
            'CREATE TABLE IF NOT EXISTS `' . ElementsProcessManagerBundle::TABLE_NAME_MONITORING_ITEM . "` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `parentId` INT(11) NULL DEFAULT NULL,
            `creationDate` INT(11) UNSIGNED NOT NULL DEFAULT '0',
            `modificationDate` INT(11) UNSIGNED NOT NULL DEFAULT '0',
            `reportedDate` INT(10) UNSIGNED NULL DEFAULT NULL,
            `currentStep` INT(10) UNSIGNED NULL DEFAULT NULL,
            `totalSteps` INT(10) UNSIGNED NULL DEFAULT NULL,
            `totalWorkload` INT(10) UNSIGNED NULL DEFAULT NULL,
            `currentWorkload` INT(10) UNSIGNED NULL DEFAULT NULL,
            `name` VARCHAR(255) NULL DEFAULT NULL,
            `command` LONGTEXT NULL,
            `status` VARCHAR(20) NULL DEFAULT NULL,
            `updated` INT(11) NULL DEFAULT NULL,
            `message` VARCHAR(1000) NULL DEFAULT NULL,
            `configurationId` INT(11) NULL DEFAULT NULL,
            `pid` INT(11) NULL DEFAULT NULL,
            `callbackSettings` LONGTEXT NULL,
            `executedByUser` INT(11) NULL DEFAULT '0',
            `actions` TEXT NULL,
            `loggers` TEXT NULL,
            `metaData` LONGTEXT NULL,
            `published` TINYINT(4) NOT NULL DEFAULT '1',
            `group` VARCHAR(50) NULL DEFAULT NULL,
            `hasCriticalError` TINYINT(4) NOT NULL DEFAULT '0',
            `deleteAfterHours` INT(10) UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `updated` (`updated`),
            INDEX `status` (`status`),
            INDEX `deleteAfterHours` (`deleteAfterHours`)
            )
           ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $db->executeQuery(
            'CREATE TABLE IF NOT EXISTS `' . ElementsProcessManagerBundle::TABLE_NAME_CALLBACK_SETTING . "` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `creationDate` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `modificationDate` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `name` VARCHAR(255) NOT NULL,
            `description` VARCHAR(255) NULL DEFAULT NULL,
            `settings` TEXT NULL,
            `type` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }

    public function uninstall(): void
    {
        $tables = [
            ElementsProcessManagerBundle::TABLE_NAME_CONFIGURATION,
            ElementsProcessManagerBundle::TABLE_NAME_MONITORING_ITEM,
            ElementsProcessManagerBundle::TABLE_NAME_CALLBACK_SETTING,
        ];
        foreach ($tables as $table) {
            $this->getDb()->executeQuery('DROP TABLE IF EXISTS ' . $table);
        }

        foreach ($this->permissions as $permissionKey) {
            $this->getDb()->executeQuery('DELETE FROM users_permission_definitions WHERE ' . $this->getDb()->quoteIdentifier('key').' = :permission', ['permission' => $permissionKey]);
        }

        parent::uninstall();
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return Version20210428000000::class;
    }
}
