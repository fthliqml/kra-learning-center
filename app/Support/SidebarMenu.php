<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class SidebarMenu
{
    public static function for(?Authenticatable $user): array
    {
        $all = config('menu.sidebar', []);
        $role = null;
        if ($user) {
            $role = data_get($user, 'role');
        }
        $flatten = config('menu.flatten_child_when_parent_hidden', true);

        $visible = function (array $item) use ($role) {
            if (!isset($item['roles']) || $item['roles'] === null)
                return true;
            $allowed = is_array($item['roles']) ? $item['roles'] : explode(',', $item['roles']);
            $allowed = array_map(fn($r) => strtolower(trim($r)), $allowed);
            return in_array(strtolower((string) $role), $allowed, true);
        };

        $result = [];
        foreach ($all as $item) {
            $hasSub = isset($item['submenu']);
            if ($hasSub) {
                $children = array_values(array_filter($item['submenu'], fn($c) => $visible($c)));
                $parentVisible = $visible($item);
                if ($parentVisible) {
                    $item['submenu'] = $children;
                    if (count($children) === 0) {
                        unset($item['submenu']);
                    }
                    $result[] = $item;
                } else {
                    if ($flatten && count($children) > 0) {
                        foreach ($children as $i => $child) {
                            $result[] = [
                                'id' => ($item['id'] ?? 'group') . '-child-' . $i . '-' . ($child['label'] ?? 'item'),
                                'label' => $child['label'] ?? 'Item',
                                'icon' => $item['icon'] ?? 'circle-stack',
                                'href' => $child['href'] ?? '#',
                            ];
                        }
                    }
                }
            } else {
                if ($visible($item)) {
                    $result[] = $item;
                }
            }
        }
        return $result;
    }
}
