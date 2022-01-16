<?php

if (!defined('_PS_VERSION_'))
    exit;

class CrossSellingGroupModel extends ObjectModel
{
    public static $definition = array(
        'table' => 'crossselling_group',
        'primary' => 'id_crossselling_group',
        'multilang' => true,
        'fields' => array(
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'name' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
        ),
    );
    public $position;
    public $name;

    public static function getAllGroups($id_lang = 0, $limit = 10)
    {
        if (!$id_lang) {
            $id_lang = (int)Context::getContext()->language->id;
        }

        return self::getGroups($id_lang, $limit);
    }

    public static function getGroups($id_lang, $limit = 10)
    {
        $sql = 'SELECT cs.id_crossselling_group, csl.name, cs.position FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' cs
            LEFT JOIN ' . _DB_PREFIX_ . self::$definition['table'] . '_lang csl ON (cs.' . _DB_PREFIX_ . self::$definition['primary'] . ' = csl.' . _DB_PREFIX_ . self::$definition['primary'] . ')
            WHERE csl.id_lang = ' . (int)$id_lang . '
            ORDER BY cs.position ASC
            LIMIT 0,' . (int)$limit . ';';
        return Db::getInstance()->executeS($sql);
    }

    public static function findByName($search, $id_lang = 0, $limit = 10)
    {
        if (!$id_lang) {
            $id_lang = (int)Context::getContext()->language->id;
        }

        $sql = 'SELECT csl.' . self::$definition['primary'] . ', csl.name FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' cs
            LEFT JOIN ' . _DB_PREFIX_ . self::$definition['table'] . '_lang csl ON (cs.' . self::$definition['primary'] . ' = csl.' . self::$definition['primary'] . ')
            WHERE csl.name LIKE \'%' . pSQL($search) . '%\'
            AND csl.id_lang = ' . (int)$id_lang . '
            ORDER BY csl.name ASC
            LIMIT 0,' . (int)$limit . ';';
        return Db::getInstance()->executeS($sql);
    }

    public function add($autodate = true, $nullValues = false)
    {
        if ($this->position <= 0)
            $this->position = self::getHigherPosition() + 1;
        return parent::add($autodate, true);
    }

    public static function getHigherPosition()
    {
        $sql = 'SELECT MAX(position) FROM ' . _DB_PREFIX_ . self::$definition['table'];
        $position = DB::getInstance()->getValue($sql);
        return (is_numeric($position)) ? $position : -1;
    }

    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS('
			SELECT ' . self::$definition['primary'] . ', position
			FROM ' . _DB_PREFIX_ . self::$definition['table'] . '
			ORDER BY position ASC'
        )
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
                    : '< ' . (int)$moved_row['position'] . ' AND position >= ' . (int)$position))
            && Db::getInstance()->execute('
			UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . '
			SET position = ' . (int)$position . '
			WHERE ' . self::$definition['primary'] . ' = ' . (int)$moved_row[self::$definition['primary']]));
    }

    public function delete()
    {
        if ($result = parent::delete()) {
            self::cleanPositions();
        }

        return $result;
    }

    public static function cleanPositions()
    {
        $return = true;

        $sql = 'SELECT ' . self::$definition['primary'] . ' FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' ORDER BY position ASC';
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