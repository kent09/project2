<?php

use App\Model\Permission;
use Illuminate\Database\Seeder;

class SeedPermissions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'Follow User', 'slug' => 'follow-user']);
        Permission::firstOrCreate(['name' => 'Member Search', 'slug' => 'member-search']);
        Permission::firstOrCreate(['name' => 'Search Tasks', 'slug' => 'search-tasks']);
        Permission::firstOrCreate(['name' => 'View Tasks', 'slug' => 'view-tasks']);
        Permission::firstOrCreate(['name' => 'View Own Tasks', 'slug' => 'view-own-tasks']);
        Permission::firstOrCreate(['name' => 'View Hidden Tasks', 'slug' => 'view-hidden-tasks']);
        Permission::firstOrCreate(['name' => 'View Completed Tasks', 'slug' => 'view-completed-tasks']);
        Permission::firstOrCreate(['name' => 'Edit Task', 'slug' => 'edit-task']);
        Permission::firstOrCreate(['name' => 'View Task', 'slug' => 'view-task']);
        Permission::firstOrCreate(['name' => 'View Task Completers', 'slug' => 'view-task-completers']);
        Permission::firstOrCreate(['name' => 'View Task Revokes', 'slug' => 'view-task-revokes']);
        Permission::firstOrCreate(['name' => 'View Task Histories', 'slug' => 'view-task-histories']);
        Permission::firstOrCreate(['name' => 'View Task Blocked Users', 'slug' => 'view-task-blocked-users']);
        Permission::firstOrCreate(['name' => 'View Task Comments', 'slug' => 'view-task-comments']);
        Permission::firstOrCreate(['name' => 'Upload Task Comment', 'slug' => 'upload-task-comment']);
        Permission::firstOrCreate(['name' => 'Save Task Comment', 'slug' => 'save-task-comment']);
        Permission::firstOrCreate(['name' => 'Edit Comment', 'slug' => 'edit-comment']);
        Permission::firstOrCreate(['name' => 'Delete Comment', 'slug' => 'delete-comment']);
        Permission::firstOrCreate(['name' => 'View Task Comment', 'slug' => 'view-task-comment']);
        Permission::firstOrCreate(['name' => 'Create Task', 'slug' => 'create-task']);
        Permission::firstOrCreate(['name' => 'Hide Task', 'slug' => 'hide-task']);
        Permission::firstOrCreate(['name' => 'Unhide Task', 'slug' => 'unhide-task']);
        Permission::firstOrCreate(['name' => 'Delete Task', 'slug' => 'delete-task']);
        Permission::firstOrCreate(['name' => 'Activate Task', 'slug' => 'activate-task']);
        Permission::firstOrCreate(['name' => 'Deactivate Task', 'slug' => 'deactivate-task']);
        Permission::firstOrCreate(['name' => 'Complete Task', 'slug' => 'complete-task']);
        Permission::firstOrCreate(['name' => 'Revoke Task User', 'slug' => 'revoke-task-user']);
        Permission::firstOrCreate(['name' => 'Block Task User', 'slug' => 'block-task-user']);
        Permission::firstOrCreate(['name' => 'View Bank', 'slug' => 'view-bank']);
        Permission::firstOrCreate(['name' => 'Deposit Bank', 'slug' => 'deposit-bank']);
        Permission::firstOrCreate(['name' => 'Withdraw Bank', 'slug' => 'withdraw-bank']);
        Permission::firstOrCreate(['name' => 'View Ledger', 'slug' => 'view-ledger']);
        Permission::firstOrCreate(['name' => 'View Bank Task', 'slug' => 'view-bank-task']);
        Permission::firstOrCreate(['name' => 'View Bank Referral', 'slug' => 'view-bank-referral']);
        Permission::firstOrCreate(['name' => 'View Bank Gift', 'slug' => 'view-bank-gift']);
        Permission::firstOrCreate(['name' => 'View Bank Bonus', 'slug' => 'view-bank-bonus']);
        Permission::firstOrCreate(['name' => 'View Bank Trade', 'slug' => 'view-bank-trade']);
        Permission::firstOrCreate(['name' => 'View Bank Blog', 'slug' => 'view-bank-blog']);
        Permission::firstOrCreate(['name' => 'Cancel Withdraw Bank', 'slug' => 'cancel-withdraw-bank']);
        Permission::firstOrCreate(['name' => 'Resync Bank', 'slug' => 'resync-bank']);
        Permission::firstOrCreate(['name' => 'View Bank BTC', 'slug' => 'view-bank-btc']);
        Permission::firstOrCreate(['name' => 'Create BTC Wallet', 'slug' => 'create-btc-wallet']);
        Permission::firstOrCreate(['name' => 'Resync Bank BTC', 'slug' => 'resync-bank-btc']);
        Permission::firstOrCreate(['name' => 'View Membership Earnings', 'slug' => 'view-membership-earnings']);
    }
}
