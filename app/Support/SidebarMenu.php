<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\User;

class SidebarMenu
{
    public static function for(User|Authenticatable|null $user): array
    {
        $all = config('menu.sidebar', []);
        $position = null;
        if ($user) {
            // Get position from user
            $position = data_get($user, 'position');
        }
        $flatten = config('menu.flatten_child_when_parent_hidden', true);

        $visible = function (array $item) use ($position, $user) {
            $hasPositions = isset($item['positions']) && $item['positions'] !== null;
            $hasRoles = isset($item['roles']) && $item['roles'] !== null;
            $hasExcludeRoles = isset($item['exclude_roles']) && $item['exclude_roles'] !== null;

            // Check exclude_roles first - if user has any excluded role, hide menu
            if ($hasExcludeRoles && $user instanceof User) {
                $excludedRoles = is_array($item['exclude_roles']) ? $item['exclude_roles'] : explode(',', $item['exclude_roles']);
                $excludedRoles = array_map(fn($r) => strtolower(trim($r)), $excludedRoles);
                if ($user->hasAnyRole($excludedRoles)) {
                    return false;
                }
            }

            // If no restrictions, visible to all
            if (!$hasPositions && !$hasRoles) {
                return true;
            }

            $positionMatch = false;
            $roleMatch = false;

            // Check positions (organizational hierarchy)
            if ($hasPositions) {
                $allowedPositions = is_array($item['positions']) ? $item['positions'] : explode(',', $item['positions']);
                $allowedPositions = array_map(fn($p) => strtolower(trim($p)), $allowedPositions);
                $positionMatch = in_array(strtolower((string) $position), $allowedPositions, true);
            }

            // Check roles (functional roles from user_roles table)
            if ($hasRoles && $user instanceof User) {
                $allowedRoles = is_array($item['roles']) ? $item['roles'] : explode(',', $item['roles']);
                $allowedRoles = array_map(fn($r) => strtolower(trim($r)), $allowedRoles);
                $roleMatch = $user->hasAnyRole($allowedRoles);
            }

            // If both are specified, user must match EITHER positions OR roles
            if ($hasPositions && $hasRoles) {
                return $positionMatch || $roleMatch;
            }

            // If only one is specified, match that one
            return $positionMatch || $roleMatch;
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
