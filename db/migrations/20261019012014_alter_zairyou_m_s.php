<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AlterZairyouMS extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `zairyou_zaiko` ADD `bunrui` VARCHAR(100) NULL COMMENT '材料の分類' AFTER `uid`;");
        //$this->execute("CREATE TABLE ...");
        //$this->execute("");
    }

    public function down(): void
    {
        // ここに元に戻すSQLを書く
    }
}