<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class ReplaceView extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("create or REPLACE VIEW vw_shouhinms as SELECT s.* , CONCAT_WS('>', NULLIF(s.bunrui1, ''), NULLIF(s.bunrui2, ''), NULLIF(s.bunrui3, '') ) AS category , CONCAT_WS('>', COALESCE(NULLIF(s.bunrui1, ''), 'NotSet'), COALESCE(NULLIF(s.bunrui2, ''), 'NotSet'), COALESCE(NULLIF(s.bunrui3, ''), 'NotSet') ) AS category123 , CONCAT_WS('>', COALESCE(NULLIF(s.bunrui1, ''), 'NotSet'), COALESCE(NULLIF(s.bunrui2, ''), 'NotSet') ) AS category12 , CONCAT(COALESCE(NULLIF(s.bunrui1, ''), 'NotSet'), '>') AS category1 , 0 AS ordercounter FROM `pcntfsrg_SaleM_test`.`ShouhinMS` AS s");
        //$this->execute("");
    }

    public function down(): void
    {
        // ここに元に戻すSQLを書く
    }
}