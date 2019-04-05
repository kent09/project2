<?php

namespace App\Repository;

use App\User;
use App\Model\Limitation;
use Illuminate\Support\Facades\Auth;

class LimitationRepository
{
    /**
     * Checking if the user got limitation reached
     *
     * @param string $slug slug of action found in Limitation Table
     * @param integer $user_id
     * @return array
     */
    public function is_limitation_passed(string $slug, int $user_id = 0): array
    {
        if ($user_id === 0) {
            $user_id = Auth::id();
        }
        $user = User::find($user_id);
        if ($user === null) {
            return [
                'data' => null,
                'role' => null,
                'passed' => false,
            ];
        }
        if ($user->role()->slug === 'admin') {
            $top_role_id = 6;
            $limitation = Limitation::where('role_id', $top_role_id)->where('slug', $slug)->where('status', 1)->first();
            if($limitation <> null){
                return [
                    'data' => $limitation,
                    'role' => $user->role()->slug,
                    'passed' => true,
                ];
            }
        }

        $limitation = Limitation::where('role_id', $user->role()->id)->where('slug', $slug)->where('status', 1)->first();
        if ($limitation === null) {
            if ($user->role()->slug === 'founder') {
                // CARRY PLATINUM ATTRIBUTES
                $role = Role::where('slug', 'platinum')->first();
                if ($role === null) {
                    return [
                        'data' => null,
                        'role' => $user->role()->slug,
                        'passed' => false,
                    ];
                }
                $limitation_new = Limitation::where('role_id', $role->id)->where('slug', $slug)->where('status', 1)->first();
                if ($limitation_new === null) {
                    return [
                        'data' => null,
                        'role' => $user->role()->slug,
                        'passed' => false,
                    ];
                }
                return [
                    'data' => $limitation_new,
                    'role' => $user->role()->slug,
                    'passed' => true,
                ];
            }
            return [
                'data' => null,
                'role' => $user->role()->slug,
                'passed' => false,
            ];
        }
        return [
            'data' => $limitation,
            'role' => $user->role()->slug,
            'passed' => true,
        ];
    }

    public function limitation_info(string $slug, int $user_id = 0): array
    {
        if ($user_id === 0) {
            $user_id = Auth::id();
        }
        $user = User::find($user_id);
        if ($user === null) {
            return [
                'value' => null,
                'type' => null,
                'description' => null,
                'status' => null,
            ];
        }
        if ($user->role()->slug === 'admin') {
            $top_role_id = 6;
            for ($i=$top_role_id; $i > 0; $i--) {
                $limitation = Limitation::where('role_id', $i)->where('slug', $slug)->first();
                if ($limitation !== null) {
                    return [
                        'value' => $limitation->value,
                        'type' => $limitation->type,
                        'description' => $limitation->description,
                        'status' => $limitation->status,
                    ];
                }
            }
        }

        $limitation = Limitation::where('role_id', $user->role()->id)->where('slug', $slug)->where('status', 1)->first();
        if ($limitation === null) {
            if ($user->role()->slug === 'founder') {
                // CARRY PLATINUM ATTRIBUTES
                $role = Role::where('slug', 'platinum')->first();
                if ($role === null) {
                    return [
                        'value' => null,
                        'type' => null,
                        'description' => null,
                        'status' => null,
                    ];
                }
                $limitation_new = Limitation::where('role_id', $role->id)->where('slug', $slug)->where('status', 1)->first();
                if ($limitation_new === null) {
                    return [
                        'value' => null,
                        'type' => null,
                        'description' => null,
                        'status' => null,
                    ];
                }
                return [
                    'value' => $limitation_new->value,
                    'type' => $limitation_new->type,
                    'description' => $limitation_new->description,
                    'status' => $limitation_new->status,
                ];
            }
            return [
                'value' => null,
                'type' => null,
                'description' => null,
                'status' => null,
            ];
        }
        return [
            'value' => $limitation->value,
            'type' => $limitation->type,
            'description' => $limitation->description,
            'status' => $limitation->status,
        ];
    }
}