<?php

namespace Ninjacode\Core\Helpers;

use Illuminate\Support\Facades\Cache;

class Helpers
{
    static function createMenuItem($name = null, $icon = null, $link = null, $active = null, $access = null, $children = null)
    {
        $linkType = $link ? 'link' : 'title';
        $linkName = is_array($link) ? $link[0] : $link;
        $linkActive = $active ?: $linkName;

        $linkStructure = [
            'type' => $linkType,
            'name' => $linkName,
            'active' => $linkActive,
            'open' => $linkActive,
        ];

        if (is_array($link) && count($link) > 1) {
            $linkStructure['params'] = array_slice($link, 1);
        }

        $menuItem = [
            'name' => $name,
            'icon' => $icon,
            'link' => array_filter($linkStructure), // Убираем пустые поля
        ];

        if ($access !== null) {
            $menuItem['access'] = strpos($access, ':') === 0 ? $name . $access : $access;
        }
        if ($children !== null) {
            $menuItem['children'] = $children;
        }

        return $menuItem;
    }

    static function createNotify($icon = 'message', $title = null, $message = null, $actions = null, $time = 10000)
    {
        $obj = (object)['icon' => $icon,];
        if ($title) $obj->title = $title;
        if ($message) $obj->message = $message;
        if ($actions) $obj->actions = $actions;
        if ($time) $obj->time = $time;

        return $obj;
    }

    static function notifyActions($actions)
    {
        $json = json_encode($actions);
        $json = preg_replace('/"cb":"(.*)"/', '"cb":$1', $json);
        echo $json;
    }
}
