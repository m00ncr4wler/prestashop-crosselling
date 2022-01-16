<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname(__FILE__) . '/classes/CrossSellingModel.php';
include_once dirname(__FILE__) . '/classes/CrossSellingGroupModel.php';

class m00ncr4wlerCrossSelling extends Module
{
    public static $adminTabController;
    public static $adminTabIndex;

    public function __construct()
    {
        $this->name = 'm00ncr4wlercrossselling';
        $this->tab = 'front_office_features';
        $this->version = '0.1.1';
        $this->author = 'm00ncr4wler - David Heinz';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Grouped cross selling');
        $this->description = $this->l('Grouped cross selling.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        self::$adminTabController = 'AdminCrossSelling';
        self::$adminTabIndex = 'index.php?controller=' . self::$adminTabController;
    }

    public function install()
    {
        if (!parent::install()
            || !$this->setupTable('add')
            || !$this->setupHooks('add')
            || !$this->setupTab('add')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
            || !$this->setupTable('remove')
            || !$this->setupHooks('remove')
            || !$this->setupTab('remove')
        ) {
            return false;
        }
        return true;
    }

    protected function getHooks()
    {
        return array(
            'actionProductUpdate',
            'displayAdminProductsExtra',
            'displayFooterProduct',
            'displayHeader',
        );
    }

    protected function setupTable($method)
    {
        if ($method === 'add') {
            /** TODO: add key (indexes) to crossselling | crossselling_group */
            $sql = "CREATE TABLE " . _DB_PREFIX_ . "crossselling (
                    id_crossselling int(10) unsigned NOT NULL AUTO_INCREMENT,
                    id_product int(10) unsigned NOT NULL,
                    id_product_cross int(10) NOT NULL,
                    id_crossselling_group int(10) unsigned NOT NULL,
                    position smallint(2) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (id_crossselling),
                    UNIQUE KEY idx_crossselling (id_crossselling_group, id_product, id_product_cross)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            $sql .= "CREATE TABLE " . _DB_PREFIX_ . "crossselling_group (
                    id_crossselling_group int(10) unsigned NOT NULL AUTO_INCREMENT,
                    position smallint(2) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (id_crossselling_group)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            $sql .= "CREATE TABLE " . _DB_PREFIX_ . "crossselling_group_lang (
                    id_crossselling_group INT(10) unsigned NOT NULL,
                    id_lang INT(10) unsigned NOT NULL,
                    name VARCHAR(128) NULL DEFAULT NULL,
                    PRIMARY KEY (id_crossselling_group, id_lang)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        }

        if ($method === 'remove') {
            $sql = "DROP TABLE IF EXISTS " . _DB_PREFIX_ . "crossselling;";
            $sql .= "DROP TABLE IF EXISTS " . _DB_PREFIX_ . "crossselling_group;";
            $sql .= "DROP TABLE IF EXISTS " . _DB_PREFIX_ . "crossselling_group_lang;";
        }

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }
        return true;
    }

    protected function setupHooks($method)
    {
        foreach ($this->getHooks() as $hook) {
            if ($method === 'add')
                if (!$this->registerHook($hook))
                    return false;
            if ($method === 'remove')
                if (!$this->unregisterHook($hook))
                    return false;
        }
        return true;
    }

    protected function setupTab($method)
    {
        if ($method === 'add') {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = self::$adminTabController;
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang)
                $tab->name[$lang['id_lang']] = $this->_translate('Cross-Selling Groups', $lang['iso_code'], 'm00ncr4wlercrossselling');
            //$this->l('Cross-Selling Groups');
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
            $tab->module = $this->name;
            return $tab->add();
        }

        if ($method === 'remove') {
            $id_tab = (int)Tab::getIdFromClassName(self::$adminTabController);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                return $tab->delete();
            } else
                return false;
        }

        return true;
    }

    /**
     * Return the translation for a string given a language iso code 'en' 'fr' ..
     *
     * @public
     * @param $string string to translate
     * @param $iso_lang language iso code
     * @param $source source file without extension
     * @param $js if it's inside a js string
     * @return string translation
     */
    protected function _translate($string, $iso_lang, $source, $js = false)
    {
        $file = dirname(__FILE__) . '/translations/' . $iso_lang . '.php';
        if (!file_exists($file)) return $string;
        include($file);
        $key = md5(str_replace('\'', '\\\'', $string));
        $current_key = strtolower('<{' . $this->name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
        $default_key = strtolower('<{' . $this->name . '}prestashop>' . $source) . '_' . $key;
        $ret = $string;
        if (isset($_MODULE[$current_key]))
            $ret = stripslashes($_MODULE[$current_key]);
        elseif (isset($_MODULE[$default_key]))
            $ret = stripslashes($_MODULE[$default_key]);
        if ($js)
            $ret = addslashes($ret);
        return $ret;
    }

    protected function renderCrossSellingGroupLists($id_product)
    {
        $html = null;
        $groups = CrossSellingGroupModel::getAllGroups($this->context->language->id);
        if (is_array($groups)) {
            $init = true;
            foreach ($groups as $group) {
                $helper = new HelperList();
                $helper->module = $this;
                $helper->shopLinkType = '';
                $helper->simple_header = true;
                $helper->no_link = true;
                $helper->actions = array('Delete');
                $helper->table = CrossSellingModel::$definition['table'];
                $helper->title = $group['name'];
                $helper->imageType = 'jpg';
                $helper->orderBy = 'position';
                $helper->identifier = CrossSellingModel::$definition['primary'];
                $helper->position_identifier = CrossSellingModel::$definition['primary'];
                $helper->currentIndex = $helper->tpl_vars['currentIndex'] = self::$adminTabIndex;
                $helper->token = $helper->tpl_vars['token'] = Tools::getAdminTokenLite(self::$adminTabController);
                $helper->tpl_vars['init'] = $init;

                $html .= $helper->generateList(
                    $this->getCrossSellingDataList($group['id_crossselling_group'], $id_product, $this->context->language->id),
                    $this->getCrossSellingFieldsList()
                );

                $init = false;
            }
        }
        return $html;
    }

    protected function getCrossSellingDataList($id_group, $id_product, $id_lang)
    {
        $data = array();
        $crossSellings = CrossSellingModel::getAllByGroupAndProductId($id_group, $id_product, $id_lang);
        if (is_array($crossSellings)) {
            foreach ($crossSellings as $crossSelling) {
                $productCross = new Product($crossSelling['id_product_cross']);
                $productCrossCover = Product::getCover($productCross->id);
                $productCrossQuantity = StockAvailable::getQuantityAvailableByProduct($productCross->id);

                $tmp = array(
                    'id_crossselling' => $crossSelling['id_crossselling'],
                    'id_image' => $productCrossCover['id_image'],
                    'name' => $productCross->name[$id_lang],
                    'reference' => $productCross->reference,
                    'quantity' => $productCrossQuantity,
                    'position' => $crossSelling['position'],
                );

                if ($productCrossQuantity <= 0)
                    $tmp['badge_danger'] = true;
                else
                    $tmp['badge_success'] = true;

                array_push($data, $tmp);
            }
        }
        return $data;
    }

    protected function getCrossSellingFieldsList()
    {
        /** ONLY FOR DEVELOPMENT
        $fieldList['id_crossselling'] = array(
            'title' => 'ID',
            'align' => 'center',
            'class' => 'fixed-width-xs',
        );
        */
        $fieldList['image'] = array(
            'title' => $this->l('Photo'),
            'align' => 'center',
            'image' => 'p',
            'class' => 'fixed-width-md',
        );
        $fieldList['name'] = array(
            'title' => $this->l('Name'),
        );
        $fieldList['reference'] = array(
            'title' => $this->l('Reference'),
            'align' => 'left',
            'class' => 'fixed-width-sm',
        );
        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $fieldList['quantity'] = array(
                'title' => $this->l('Quantity'),
                'type' => 'int',
                'align' => 'text-right',
                'class' => 'fixed-width-sm',
                'badge_danger' => true,
                'badge_success' => true,
            );
        }
        $fieldList['position'] = array(
            'title' => $this->l('Position'),
            'align' => 'center',
            'position' => 'position',
            'class' => 'fixed-width-xs',
        );

        return $fieldList;
    }

    protected function getAccessories($id_product, $id_group, $id_lang, $active = true, Context $context = null)
    {
        if (!$context)
            $context = Context::getContext();

        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`,
					pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
					MAX(image_shop.`id_image`) id_image, il.`legend`, m.`name` as manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(
						p.`date_add`,
						DATE_SUB(
							NOW(),
							INTERVAL ' . (Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
						)
					) > 0 AS new
				FROM `' . _DB_PREFIX_ . 'crossselling` cs
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cs.`id_product_cross`
				' . Shop::addSqlAssociation('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int)$id_lang . Shop::addSqlRestrictionOnLang('pl') . '
				)
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (
					product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . (int)$id_lang . Shop::addSqlRestrictionOnLang('cl') . '
				)
				LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_product` = p.`id_product`)' .
            Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (p.`id_manufacturer`= m.`id_manufacturer`)
				' . Product::sqlStock('p', 0) . '
				WHERE cs.`id_product` = ' . (int)$id_product . ' AND cs.`id_crossselling_group` = ' . (int)$id_group .
            ($active ? ' AND product_shop.`active` = 1 AND product_shop.`visibility` != \'none\'' : '') . '
				GROUP BY product_shop.id_product
				ORDER BY cs.position ASC';

        if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql))
            return false;
        foreach ($result as &$row)
            $row['id_product_attribute'] = Product::getDefaultAttribute((int)$row['id_product']);
        return $this->getProductsProperties($id_lang, $result);
    }

    protected function getProductsProperties($id_lang, $query_result)
    {
        $results_array = array();

        if (is_array($query_result))
            foreach ($query_result as $row)
                if ($row2 = Product::getProductProperties($id_lang, $row))
                    $results_array[] = $row2;

        return $results_array;
    }

    public function displayDeleteLink($token = null, $id, $name = null)
    {
        if (!array_key_exists('Delete', HelperList::$cache_lang))
            HelperList::$cache_lang['Delete'] = $this->l('Delete', 'Helper');

        if (!array_key_exists('Name', HelperList::$cache_lang))
            HelperList::$cache_lang['Name'] = $this->l('Name:', 'Helper', true, false);

        if (!array_key_exists('DeleteItem', HelperList::$cache_lang))
            HelperList::$cache_lang['DeleteItem'] = $this->l('Delete selected item?', 'Helper', true, false);

        if (!is_null($name))
            $name = addcslashes('\n\n' . HelperList::$cache_lang['Name'] . ' ' . $name, '\'');

        $this->context->smarty->assign(array(
            'id_crossselling' => $id,
            'href' => Tools::safeOutput(self::$adminTabIndex . '&id_crossselling=' . $id . '&action=delete&token=' . Tools::getAdminTokenLite(self::$adminTabController) . '&ajax=1'),
            'action' => HelperList::$cache_lang['Delete'],
            'confirm' => Tools::safeOutput(HelperList::$cache_lang['DeleteItem'] . ' ' . $name),
        ));

        return $this->display(__FILE__, 'views/templates/admin/_configure/helpers/list/list_action_delete.tpl');
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::$adminTabController));
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)Tools::getValue('id_product');

        if (Validate::isLoadedObject($product = new Product($id_product))) {
            $this->context->smarty->assign(array(
                'languages' => Language::getLanguages(true),
                'id_lang' => $this->context->language->id,
            ));

            return $this->display(__FILE__, 'views/templates/admin/' . $this->name . '.tpl') . $this->renderCrossSellingGroupLists($id_product);
        }
    }

    public function hookActionProductUpdate($params)
    {
        if ((int)array_search("CrossSelling", Tools::getValue('submitted_tabs')) != null && Tools::getValue('inputCrossSellingProducts') != '') {
            $errors = array();
            $id_product = (int)Tools::getValue('id_product');
            $productIds = array_unique(explode('-', substr(Tools::getValue('inputCrossSellingProducts'), 0, -1)));
            $groupIds = array_unique(explode('-', substr(Tools::getValue('inputCrossSellingGroups'), 0, -1)));

            if (Validate::isLoadedObject($product = new Product($id_product))) {
                if (!is_array($productIds)) {
                    $errors[] = Tools::displayError($this->l('You forgot to add products!'));
                }

                if (!is_array($groupIds)) {
                    $errors[] = Tools::displayError($this->l('No groups specified!'));
                }

                if (empty($errors)) {
                    foreach ($groupIds as $id_crossselling_group) {
                        if (Validate::isLoadedObject($crossSellingGroup = new CrossSellingGroupModel($id_crossselling_group))) {
                            foreach ($productIds as $id_product_cross) {
                                if (Validate::isLoadedObject($productCross = new Product($id_product_cross))) {
                                    if (!CrossSellingModel::isExits($product->id, $productCross->id, $crossSellingGroup->id, $this->context->language->id)) {
                                        $crossSelling = new CrossSellingModel();
                                        $crossSelling->id_product = $product->id;
                                        $crossSelling->id_product_cross = $productCross->id;
                                        $crossSelling->id_crossselling_group = $crossSellingGroup->id;
                                        if (!$crossSelling->save()) {
                                            $this->context->controller->errors[] = Tools::displayError(sprintf(
                                                $this->l('Coud not save cross-selling entry! (Group: %s, Product: %s)'),
                                                $crossSellingGroup->name,
                                                $productCross->name
                                            ));
                                        }
                                    } else {
                                        //TODO: what we want to do with already extits entries?
                                    }
                                } else {
                                    $errors[] = Tools::displayError($this->l('Cross-Product do not exits!'));
                                }
                            }
                        } else {
                            $errors[] = Tools::displayError($this->l('Cross-Selling-Group do not exits!'));
                        }
                    }
                }

                $this->context->controller->errors = array_merge($this->context->controller->errors, $errors);
            }
        }
    }

    public function hookDisplayHeader($params)
    {
        $allowedControllers = array('product');
        $c = $this->context->controller;
        if (isset($c->php_self) && in_array($c->php_self, $allowedControllers)) {
            $this->context->controller->addCSS($this->_path . 'views/templates/css/' . $this->name . '.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/templates/js/' . $this->name . '.js');
        }
    }

    public function hookDisplayFooterProduct($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        $crossSellingGroups = CrossSellingGroupModel::getAllGroups($this->context->language->id);

        if (is_array($crossSellingGroups)) {
            foreach ($crossSellingGroups as $k => $crossSellingGroup) {
                if ($accessories = $this->getAccessories($id_product, $crossSellingGroup['id_crossselling_group'], $this->context->language->id)) {
                    $crossSellingGroups[$k]['accessories'] = $accessories;
                } else {
                    unset($crossSellingGroups[$k]);
                }
            }

            $this->context->smarty->assign(array(
                'crossSellingGroups' => $crossSellingGroups,
                'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
            ));

            return $this->display(__FILE__, 'views/templates/front/' . $this->name . '.tpl');
        }
    }
}
