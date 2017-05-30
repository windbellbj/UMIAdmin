<?php

namespace YM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use YM\Models\Menu;
use YM\Umi\umiMenusBuilder;

class menuController extends Controller
{
    private $menu;

    public function __construct()
    {
        $this->menu = new Menu();
    }

    #加载所有菜单 并管理
    #load all menus and manage
    public function management(Request $request)
    {
        $menu = new umiMenusBuilder();

        $tableName = $request->route()->parameter('table');
        $menuTree = $menu->showDragDropTree($tableName, true);

        return view('umi::menu.sideMenu', [
            'menuTree' => $menuTree,
            'tableName'=> $tableName
            ]);
    }

    #更新菜单顺序 并清除缓存加载最新的菜单顺序
    #update the orders of menus, clear cache and reload the last orders
    public function updateMenuOrder(Request $request)
    {
        $menuJson = $request->input('menuJson');

        $this->recursiveUpdateOrder(json_decode($menuJson), 0);

        $tableName = $request->route()->parameter('table');
        Cache::pull($tableName);

        echo $menuJson;
    }

    #加载分配用户菜单页面
    #load distribution page of user's menus
    public function distribution($table)
    {
        $userTableName = Config::get('umiEnum.system_table_name.umi_users');

        $menu = new umiMenusBuilder();
        $menuTree = $menu->showDragDropTree($table);
        return view('umi::menu.distribution', [
            'menuTree'      => $menuTree,
            'tableName'     => $table,
            'userTableName' =>$userTableName
        ]);
    }

    #重新加载所有菜单 来自ajax请求
    #reload all the menus from ajax request
    public function loadMenuTree($table)
    {
        $menu = new umiMenusBuilder();
        $menuTree = $menu->showDragDropTree($table);

        #必须重新用js启动可拖拽功能
        #must to be restart the drag function with js command
        $js = "<script>$('.dd').nestable();</script>";
        return $menuTree . $js;
    }

    #从用户菜单的json字符串中加载菜单
    #load the menus from string of user's json menu
    public function loadMenuTreeFromJson($userId)
    {

    }
#region Private function
    private function recursiveUpdateOrder($currentMenu, $parentId = 0)
    {
        $order = 0;
        for ($i = 0; $i < count($currentMenu); $i++) {
            $id = $currentMenu[$i]->id;
            $this->checkAndUpdate($id, $parentId, $order);

            if (isset($currentMenu[$i]->children)) {
                $childMenu = $currentMenu[$i]->children;
                $this->recursiveUpdateOrder($childMenu, $id);
            }

            $order++;
        }
    }

    private function checkAndUpdate($id, $parentId, $order)
    {
        $menu = $this->menu->getOneMenu($id);
        if ($menu->menu_id != $parentId || $menu->order != $order) {
            $this->menu->updateOrder($id, $parentId, $order);
        }
    }
#endregion
}