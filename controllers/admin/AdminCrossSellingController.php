<?php
if (!defined('_PS_VERSION_'))
    exit;

class AdminCrossSellingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'crossselling_group';
        $this->className = 'CrossSellingGroupModel';
        $this->lang = true;
        $this->bootstrap = true;
        $this->list_no_link = true;
        $this->fields_list = $this->getFieldsList();
        $this->bulk_actions = $this->getBulkActions();
        $this->fields_form = $this->getFieldsForm();

        $this->position_identifier = 'id_crossselling_group';
        $this->_defaultOrderBy = 'position';

        parent::__construct();
        if (!$this->module->active)
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
    }

    protected function getFieldsList()
    {
        return array(
            /** ONLY FOR DEVELOPMENT
            'id_crossselling_group' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            */
            'name' => array(
                'title' => $this->l('Name'),
                'filter_key' => 'b!name',
            ),
            'position' => array(
                'title' => $this->l('Position'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'position' => 'position',
                'filter_key' => 'a!position',
            ),
        );
    }

    protected function getBulkActions()
    {
        return array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ),
        );
    }

    protected function getFieldsForm()
    {
        return array(
            'legend' => array(
                'title' => $this->l('Group name'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'lang' => true,
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'maxchar' => 128,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
            $this->page_header_toolbar_btn['new_tag'] = array(
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Add new group', null, null, false),
                'icon' => 'process-icon-new'
            );

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

        foreach ($this->_list as $key => $list)
            if ($list['name'] == '0')
                $this->_list[$key]['name'] = Configuration::get('PS_SHOP_NAME');
    }

    public function ajaxProcessUpdatePositions()
    {
        $way = (int)(Tools::getValue('way'));
        $id = (int)(Tools::getValue('id'));

        $model = null;
        if (is_array($positions = Tools::getValue('crossselling'))) {
            $model = 'CrossSellingModel';
        } else if (is_array($positions = Tools::getValue('crossselling_group'))) {
            $model = 'CrossSellingGroupModel';
        }

        if (!$model) {
            die();
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int)$pos[2] === $id) {
                if ($modelClass = new $model((int)$pos[2])) {
                    if (isset($position) && $modelClass->updatePosition($way, $position))
                        die();
                    else
                        echo '{"hasError" : true, "errors" : "Can not update ' . $id . ' to position ' . $position . ' "}';
                } else
                    echo '{"hasError" : true, "errors" : "This update (' . $id . ') can\'t be loaded"}';

                break;
            }
        }
    }

    /**
     * @deprecated
     */
    public function ajaxProcessGetGroups()
    {
        $search = Tools::getValue('q', false);
        $id_lang = Tools::getValue('id_lang');
        $limit = Tools::getValue('limit');

        if (!$search OR $search == '' OR strlen($search) < 1)
            die();

        $items = CrossSellingGroupModel::findByName($search, $id_lang, $limit);

        // packs
        $results = array();
        foreach ($items AS $item) {
            $group = array(
                'id' => (int)($item['id_crossselling_group']),
                'name' => $item['name'],
                'position' => $item['position'],
            );
            array_push($results, $group);
        }
        die(Tools::jsonEncode($results));
    }

    public function ajaxProcessDelete()
    {
        if ($id_crossselling = (int)Tools::getValue('id_crossselling')) {
            $crossselling = new CrossSellingModel($id_crossselling);
            if ($crossselling->delete()) {
                $results = array(
                    'success' => 1,
                    'text' => $this->l('Successfully removed.'),
                );
            } else {
                $results = array(
                    'success' => 0,
                    'text' => $this->l('Error on removing!'),
                );
            }

        }
        die(Tools::jsonEncode($results));
    }
}