<?php

namespace core\base\controller;

//use core\base\model\Model;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

abstract class BaseController
{
    use BaseMethods;

    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    protected $content;
    protected $header;
    protected $footer;
    protected $template;
    protected $settings;
    protected $fileArray;
    protected $styles;
    protected $scripts;
    protected $formTemplates;
    protected $name;

    protected $routes;
    protected $page;
    protected $data;
    protected $model;
    protected $columns;
    protected $table;
    protected $action;
    protected $alias;

    protected $pattern;

    public function route()
    {
        $controller = str_replace('/', '\\', $this->controller);

        try{
            $object = new \ReflectionMethod($controller, 'request');

            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ];

            $object->invoke(new $controller, $args);
        }
        catch (\ReflectionException $e) {
            throw new RouteException($e->getError());
        }
    }

    public function request($args)
    {
        $this->parameters = $args['parameters'];

        $inputData = $args['inputMethod'];
        $outputData = $args['outputMethod'];

        $data = $this->$inputData();

        if(method_exists($this, $outputData)) {

            $page = $this->$outputData($data);
            if($page) {
                $this->page = $page;
            }

        }elseif($data){
            $this->page = $data;
        }

        $this->getPage();
    }

    protected function getPage()
    {
        if(is_array($this->page)) {
            foreach ($this->page as $block) echo $block;
        }else{
            echo $this->page;
        }
    }

    protected function render($path = '', $parameters = [])
    {
        extract($parameters);

        if(!$path) {

            $class = new \ReflectionClass($this);

            $space = str_replace('\\','/', $class->getNamespaceName() . '\\');
            $routes = Settings::get('routes');

            if($parameters['template'] === 'admin') $template = ADMIN_TEMPLATE;
            elseif ($space === $routes['user']['path']) $template = TEMPLATE;
            else $template = ADMIN_TEMPLATE;

            $path = $template . explode('controller', strtolower($class->getShortName()))[0];

        }

        ob_start();

        if(!@include_once $path . '.php') throw new RouteException('Такого шаблона не существует - ' . $path);

        return ob_get_clean();
    }

    protected function init($admin = false) {

        if(!$admin) {
            if(USER_CSS_JS['styles']) {
                foreach(USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }

            if(USER_CSS_JS['scripts']) {
                foreach(USER_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . TEMPLATE . trim($item, '/');
            }
        }else{
            if(ADMIN_CSS_JS['styles']) {
                foreach(ADMIN_CSS_JS['styles'] as $item) $this->styles[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }

            if(ADMIN_CSS_JS['scripts']) {
                foreach(ADMIN_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
        }
    }

    protected function createTableData($table = false) {

        if ($table) $this->table = $table;
        elseif(!$this->table) {
            if($this->parameters) $this->table = array_keys($this->parameters)[0];
            else{
                $settings = Settings::instance();
                $this->table = $settings::get('defaultTable');
            }
        }

        $this->columns = $this->model->showColumns($this->table);

        if(!$this->columns) new RouteException('cant find fields in table ' . $this->table, 2);
    }

    protected function createData($tables = [], $where = [])
    {
        if(!$tables) $tables = array($this->table);

        for($i = 0; $i < count($tables); $i++) {
            $this->createTableData($tables[$i]);

            $requiredColumns = $this->settings->get('tableFields')[$tables[$i]];

            if(!$this->columns['id_row']) return $this->data = [];

            if($requiredColumns) {
                for($j = 0; $j < count($requiredColumns); $j++) {
                    if(key_exists($requiredColumns[$j], $this->columns)) $fields[$requiredColumns[$j]] = $requiredColumns[$j];
                }

                $this->data[$this->table] = $this->model->get($this->table, [
                    'fields' => $fields,
                    'where' => $where[$i]
                ]);
                $fields = [];
            }
        }
    }

    protected function editData()
    {
        $method = $this->action;
        if($method === 'add') unset($_POST['id']);

        if($_POST[$this->columns['id_row']]) {
            $id = is_numeric($_POST[$this->columns['id_row']]) ?
                $this->clearNum($_POST[$this->columns['id_row']]) :
                $this->clearStr($_POST[$this->columns['id_row']]);
            if($id) {

                if($this->columns['author_id']) $where = [$this->columns['author_id'] => $id];
                else $where = [$this->columns['id_row'] => $id];
                $method = 'edit';

            }
        }

        $this->createFile();

        $this->createAlias($id);

//        if($this->checkEdit()) {
            $this->model->$method($this->table, [
                'fields' => $_POST,
                'files' => $this->fileArray,
                'where' => $where,
                'return_id' => true
            ]);
//        }else{
//            $this->redirect('login?error=please, log in');
//        }

        $this->redirect();
    }

    protected function createAlias($id = false)
    {
        if($this->columns['alias']) {
            if(!$_POST['alias']) {
                if($_POST['name']) {
                    $alias_str = $this->clearStr($_POST['name']);
                }else{
                    foreach ($_POST as $key => $item) {
                        if(strpos($key, 'name') !== false && $item) {
                            $alias_str = $this->clearStr($item);
                            break;
                        }
                    }
                }
            }else{
                $alias_str = $_POST['alias'] = $this->clearStr($_POST['alias']);
            }

            $textModify = new \libraries\TextModify();
            $alias = $textModify->translit($alias_str);

            $where['alias'] = false;
            $operand[] = '=';

            if($id) {
                $where[$this->columns['id_row']] = $id;
                $operand[] = '<>';
            }

            $res_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'
            ])[0];

            if(!$res_alias) {
                $_POST['alias'] = $alias;
            }else{
                $this->alias = $alias;
                $_POST['alias'] = '';
            }
        }
    }

    protected function createFile() {
        $fileEdit = new FileEdit();
        $this->fileArray = $fileEdit->addFile();
    }

    protected function checkPost()
    {
        if($this->isPost()) {
            $this->clearPostFields();
            $this->table = $this->clearStr($_POST['table']);
            unset($_POST['table']);

            if($this->table) {
                $this->createTableData();
                $this->editData();
            }
        }
    }

    protected function deleteData()
    {
        if (!empty($this->parameters[$this->table])) {
            $id = $this->clearNum($this->parameters[$this->table]);

            if($id) {

                $this->data = $this->model->get($this->table, [
                    'where' => [$this->columns['id_row'] => $id]
                ]);

                if ($this->data) {

                    $this->data = $this->data[0];

                    if($this->table === 'posts') {
                        $commentId = $this->model->get('comments', [
                            'fields' => ['id'],
                            'where' => ['post_id' => $this->table . '/' . $id]
                        ]);
                        $this->model->delete('comments', ['where' => ['post_id' => $this->table . '/' . $id]]);
                    }

                    $this->model->delete($this->table, ['where' => ['id' => $id]]);

                    $this->model->delete('likes', ['where' => ['post_id' => $this->table . '/' . $id]]);

                    for($i = 0; $i < count($commentId); $i++) {
                        $this->model->delete('likes', ['where' => ['post_id' => 'comments' . '/' . $commentId[$i]['id']]]);
                    }


                }
            }
        }
    }

    protected function createOutputForms()
    {
        $forms = $this->settings->get('forms');

        foreach ($forms as $key => $value) {
            foreach ($value as $row) {
                if(key_exists($row, $this->columns)){

                    $this->pattern[$row] = $key;

                }
            }
        }
    }

    protected function checkEdit()
    {
        if(isset($_SESSION['id'])) {

            if ($this->table === 'users' && $_POST['id'] == $_SESSION['id']) return true;
            elseif ($this->table === 'posts' || $this->table === 'comments') {

                $post_id = $this->model->get($this->table, [
                    'fields' => ['id'],
                    'where' => ['author_id' => $_SESSION['id']]
                ]);

                foreach ($post_id as $key => $value) {
                    if($value['id'] == $_SESSION['id']) return true;
                }

            }
        }
    }

}