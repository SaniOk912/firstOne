<?php

namespace core\admin\controller;

class AddController extends BaseAdmin
{
    protected $action = 'add';

    protected function inputData()
    {
        $this->execBase();

        $this->checkPost();

        $this->createTableData();

//        $this->createData();
        print_arr($this->data);

//        $this->model->get(['table1/\/', 'table2'], ['fields' => ['ConcatTable1"' => ['f1', 'f2'], 'ConcatTable2' => ['f1', 'f2']],
//            'where' => ['qwerty' => '1', 'qwerty2' => '2'],
//            'condition' => ['AND1', 'AND2'],
//            'operand' => ['!=', '='],
//            'concat' => true
//        ]);

//        $this->model->add('users', ['fields' => [
//            'username' => ['sasha12345'],
//            'password' => 'sasha123<a href=#123>',
////            'alias' => 'sasha123'
//        ]]);

//        $this->model->edit('users', [
//            'fields' => ['username' => 'andrey', 'id' => '10'],
//            'files' => ['password' => ['pwd1', 'pwd2']],
//        ]);


    }

}