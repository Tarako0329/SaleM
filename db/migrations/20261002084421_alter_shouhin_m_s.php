<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AlterShouhinMS extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `ShouhinMS` ADD `S_lot` INT NOT NULL DEFAULT '0' COMMENT '1製造ロット' AFTER `genka_tanka`, ADD `G_per` DECIMAL(3,2) NOT NULL DEFAULT '0' COMMENT '設定原価率' AFTER `S_lot`, ADD `seizou_genka_tanka` DECIMAL(8,2) NOT NULL DEFAULT '0' COMMENT '製造原価単価' AFTER `G_per`");
        $this->execute("ALTER TABLE `zairyou_zaiko` ADD `unit` VARCHAR(5) NULL COMMENT '単位' AFTER `volum`");
        //$this->execute("");
    }

    public function down(): void
    {
        // ここに元に戻すSQLを書く
    }
}