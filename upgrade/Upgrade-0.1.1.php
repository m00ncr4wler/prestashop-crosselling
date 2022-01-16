<?php

function upgrade_module_0_1_1($module) {
    /* CHANGELOG */
    // [FIX]: wrong position id on adding new products to crossselling group
    $sql = "SELECT * FROM crossselling ORDER BY id_product, id_crossselling_group, position";
    $crosssellings = Db::getInstance()->executeS($sql);
    $crossProducts = array();

    if(is_array($crosssellings)) {
        foreach($crosssellings as $crossselling) {
            $crossProducts[$crossselling['id_product']][$crossselling['id_crossselling_group']][] = array(
                'id_crossselling' => $crossselling['id_crossselling'],
                'position' => $crossselling['position'],
            );
        }

        foreach($crossProducts as $crossGroups) {
            foreach($crossGroups as $crossGroup) {
                foreach ($crossGroup as $position => $crossselling) {
                    Db::getInstance()->execute('UPDATE crossselling set position = ' . (int)$position . ' WHERE id_crossselling = ' . (int)$crossselling['id_crossselling']);
                }
            }
        }
    }
    return true;
}