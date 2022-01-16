<?php

if (!defined('_PS_VERSION_'))
    exit;

class CrossSellingModel extends ObjectModel
{
    public static $definition = array(
        'table' => 'crossselling',
        'primary' => 'id_crossselling',
        'multilang' => false,
        'fields' => array(
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_product_cross' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_crossselling_group' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
        ),
    );
    public $id;
    public $id_product;
    public $id_product_cross;
    public $id_crossselling_group;
    public $position;

    public static function getAllByGroupAndProductId($id_group, $id_product, $id_lang)
    {
        $sql = '
        SELECT c.id_crossselling, c.id_product, c.id_product_cross, c.position
        FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' c
        LEFT JOIN ' . _DB_PREFIX_ . self::$definition['table'] . '_group cg ON (c.id_crossselling_group = cg.id_crossselling_group)
        LEFT JOIN ' . _DB_PREFIX_ . self::$definition['table'] . '_group_lang cgl ON (c.id_crossselling_group = cgl.id_crossselling_group)
        WHERE c.id_product = ' . (int)$id_product . '
        AND c.id_crossselling_group = ' . (int)$id_group . '
        AND cgl.id_lang = ' . (int)$id_lang . '
        ORDER BY c.position ASC';
        return Db::getInstance()->executeS($sql);
    }

    public static function isExits($id_product, $id_product_cross, $id_group)
    {
        $sql = '
        SELECT c.id_crossselling, c.id_product, c.id_product_cross, c.position
        FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' c
        LEFT JOIN ' . _DB_PREFIX_ . self::$definition['table'] . '_group cg ON (c.id_crossselling_group = cg.id_crossselling_group)
        WHERE c.id_product = ' . (int)$id_product . '
        AND c.id_product_cross = ' . (int)$id_product_cross . '
        AND c.id_crossselling_group = ' . (int)$id_group . '
        ORDER BY c.position';
        Db::getInstance()->executeS($sql);
        return (Db::getInstance()->numRows() > 0) ? true : false;
    }

    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS('
			SELECT ' . self::$definition['primary'] . ', position, id_crossselling_group, id_product
			FROM ' . _DB_PREFIX_ . self::$definition['table'] . '
			ORDER BY position ASC')
        )
            return false;

        foreach ($res as $row)
            if ((int)$row[self::$definition['primary']] == (int)$this->id)
                $moved_row = $row;

        if (!isset($moved_row) || !isset($position))
            return false;

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->execute('
			UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . ' SET position= position ' . ($way ? '- 1' : '+ 1') . ' WHERE position ' . ($way
                    ? '> ' . (int)$moved_row['position'] . ' AND position <= ' . (int)$position
                    : '< ' . (int)$moved_row['position'] . ' AND position >= ' . (int)$position) . '
            AND id_crossselling_group = ' . $moved_row['id_crossselling_group']) . '
            AND id_product = ' . $moved_row['id_product']
            && Db::getInstance()->execute('
			UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . '
			SET position = ' . (int)$position . '
			WHERE ' . self::$definition['primary'] . ' = ' . (int)$moved_row[self::$definition['primary']]));
    }

    public function add($autodate = true, $nullValues = false)
    {
        if ($this->position <= 0)
            $this->position = self::getHigherPosition($this->id_crossselling_group, $this->id_product) + 1;
        return parent::add($autodate, true);
    }

    public static function getHigherPosition($group_id, $product_id)
    {
        $sql = 'SELECT MAX(position) FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' WHERE id_crossselling_group = ' . (int)$group_id . ' AND id_product = ' . (int) $product_id;
        $position = DB::getInstance()->getValue($sql);
        return (is_numeric($position)) ? $position : -1;
    }

    public function delete()
    {
        if ($result = parent::delete()) {
            self::cleanPositions($this->id_crossselling_group);
        }

        return $result;
    }

    public static function cleanPositions($id_group)
    {
        $return = true;

        $sql = 'SELECT ' . self::$definition['primary'] . ' FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' WHERE id_crossselling_group = ' . (int)$id_group . ' ORDER BY position ASC';
        $result = Db::getInstance()->executeS($sql);

        $i = 0;
        foreach ($result as $value)
            $return = Db::getInstance()->execute('
			UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . '
			SET position = ' . (int)$i++ . '
			WHERE ' . self::$definition['primary'] . ' = ' . (int)$value[self::$definition['primary']]);
        return $return;
    }
}