<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AlterTableAny extends AbstractMigration
{
    public function up(): void
    {
        // ここにSQLを書く（例：$this->execute("CREATE TABLE ...");）
        $this->execute("ALTER TABLE `UriageData` CHANGE `Utisu` `Utisu` INT(11) NOT NULL DEFAULT '0' COMMENT '内数'");
        $this->execute("CREATE TABLE `zairyou_zaiko` (
          `hinmei` varchar(255) NOT NULL COMMENT '在庫名',
          `value` decimal(8,0) NOT NULL DEFAULT 0 COMMENT '価格',
          `zeikbn` int(11) NOT NULL COMMENT '税区分',
          `volum` int(11) NOT NULL COMMENT '内容量'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
        $this->execute("ALTER TABLE `zairyou_zaiko` ADD `zairyouCD` INT NOT NULL AUTO_INCREMENT COMMENT '材料CD' FIRST, ADD PRIMARY KEY (`zairyouCD`)");

        $this->execute("CREATE TABLE `ShouhinMS‗Genzairyou` (
          `shouhinCD` int(11) NOT NULL COMMENT '商品CD',
          `zairyouCD` int(11) NOT NULL COMMENT '材料CD',
          `use_vol` decimal(10,0) NOT NULL COMMENT '使用量',
          `genka` decimal(10,0) NOT NULL COMMENT '原価'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;");
        $this->execute("ALTER TABLE `ShouhinMS‗Genzairyou`  ADD KEY `S_Genzai_index1` (`shouhinCD`,`zairyouCD`)");
        $this->execute("ALTER TABLE `zairyou_zaiko` ADD `uid` INT NOT NULL FIRST");
        $this->execute("ALTER TABLE `ShouhinMS‗Genzairyou` ADD `uid` INT NOT NULL FIRST");

        //$this->execute("");
    }

    public function down(): void
    {
        // ここに元に戻すSQLを書く
    }
}