<?php

use App\Model\Role;
use App\Model\Permission;
use App\Model\RolePermission;
use Illuminate\Database\Seeder;

class SeedRolePermissions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rp_count = RolePermission::count();
        // if ($rp_count === 0) {
            $permissions = Permission::where('status', 1)->get();
            $roles = Role::where('status', 1)->get();
            if (count($roles) > 0) {
                foreach ($roles as $role) {
                    if (count($permissions) > 0) {
                        foreach ($permissions as $permission) {
                            $rp_check = RolePermission::where('role_id',$role->id)->where('permission_id',$permission->id)->first();
                            if($rp_check == null){
                                $rp = new RolePermission;
                                $rp->role_id = $role->id;
                                $rp->permission_id = $permission->id;
                                $rp->save();
                            }
                        }
                    }
                }
            }
        // }
    }
}
