<?php

namespace App;

use App\Model\Bank;
use App\Model\Role;
use App\Model\Task;
use App\Model\TaskUser;
use App\Model\BtcWallet;
use App\Model\SellOption;
use App\Model\TaskHidden;
use App\Model\OptionTrade;
use App\Model\Notification;
use App\Model\UserFollower;
use App\Model\GiftCoinTransaction;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * @OA\Schema(type="object", @OA\Xml(name="User"))
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    // protected $appends = ['avatar'];

    #relation
    public function completed() {
        return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id')->withPivot('approved');
    }

    public function revoked() {
        return $this->belongsToMany(Task::class, 'banned_user_task', 'user_id', 'task_id')->withPivot('revoked');
    }

    public function blocked() {
        return $this->belongsToMany(Task::class, 'block_user_tasks', 'user_id', 'task_user_id')->withPivot('block');
    }

    public function hidden() {
        return $this->hasMany(TaskHidden::class);
    }

    public function bank(){
        return $this->hasOne(Bank::class, 'user_id');
    }

    public function wallet(){
        return $this->hasOne(BtcWallet::class, 'user_id');
    }

    public function optionTrade() {
        return $this->hasMany(OptionTrade::class, 'buyer_id');
    }

    public function optionSell() {
        return $this->hasMany(SellOption::class);
    }

    public function completer() {
        return $this->hasMany(TaskUser::class, 'task_creator');
    }

    public function revokeTask() {
        return $this->hasMany(TaskUser::class, 'task_creator');
    }

    public function followers() {
        return $this->hasMany(UserFollower::class, 'user_id');
    }

    public function giftReceiver() {
        return $this->hasMany(GiftCoinTransaction::class, 'receiver_id');
    }

    public function giftSender() {
        return $this->hasMany(GiftCoinTransaction::class, 'sender_id');
    }

    public function completedTask(){

        return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id')->withPivot('approved');
    }

    public function revokedUser() {
        return $this->belongsToMany(Task::class, 'banned_user_task', 'user_id', 'task_id')->withPivot('revoked');
    }

    public function blockTaskUser() {
        return $this->belongsToMany(Task::class, 'block_user_tasks', 'user_id', 'task_user_id')->withPivot('block');
    }

    public function hiddenTasks() {
        return $this->hasMany(TaskHidden::class);
    }

    public function notifications() {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    #scope
    public function scopeNeedToWizard($query) {
        return $query->where(function($query) {
            $query->where('verified', 1)
                ->where('status', 1)
                ->where('agreed', 0)
                ->whereNotIn('ban', [1, 2]);
        });
    }

    public function status($html=false)
    {
        $status = $this->attributes['status'];
        if ($this->attributes['ban'] > 0){
            if ($this->attributes['ban'] == 1) {
                if ($html == true) {
                    return '<span class="text-danger"><b>Soft Banned</b></span>';
                }
                return 'soft-banned';
            } elseif ($this->attributes['ban'] == 2) {
                if ($html == true) {
                    return '<span class="text-danger"><b>Hard Banned</b></span>';
                }
                return 'hard-banned';
            }
        } else {
            if ($status == 0) {
                if ($this->attributes['verified'] == 1) {
                    if ($this->attributes['agreed'] == 1) {
                        if ($html == true) {
                            return '<span class="text-warning"><b>Disabled</b></span>';
                        }
                        return 'disabled';
                    } else {
                        if ($html == true) {
                            return '<span class="text-warning"><b>Unconfirmed</b></span>';
                        }
                        return 'unconfirmed';
                    }
                } else {
                    if ($html == true) {
                        return '<span class="text-warning"><b>Unverified</b></span>';
                    }
                    return 'unverified';
                }
            } else {
                if ($this->attributes['verified'] == 1) {
                    if ($this->attributes['agreed'] == 1) {
                        if ($html == true) {
                            return '<span class="text-success"><b>Active</b></span>';
                        }
                        return 'active';
                    } else {
                        if ($this->attributes['request_confirmation_at'] == null) {
                            if ($html == true) {
                                return '<span class="text-warning"><b>Unconfirmed</b></span>';
                            }
                            return 'unconfirmed';
                        } else {
                            if ($html == true) {
                                return '<span class="text-warning"><b>Requested Manual Confirmation</b></span>';
                            }
                            return 'requested-manual-confirmation';
                        }
                    }
                } else {
                    if ($html == true) {
                        return '<span class="text-warning"><b>Unverified</b></span>';
                    }
                    return 'unverified';
                }
            }
        }
    }

    public function role()
    {
        return Role::where('user_type', $this->attributes['type'])->first();
    }

    public function withRole()
    {
        return $this->belongsTo(\App\Model\Role::class, 'type', 'user_type');
    }

    public function hasPermission(array $slugs)
    {
        if (count($slugs) > 0) {
            foreach ($slugs as $slug) {
                $role = $this->role();
                if ($role !== null) {
                    $permission = \App\Model\Permission::where('slug', $slug)->first();
                    if ($permission !== null) {
                        $found = \App\Model\RolePermission::where('role_id', $role->id)->where('permission_id', $permission->id)->count();
                        if ($found > 0) {
                            return true;
                        }
                    }
                }
            }
            return false;
        } else {
            return false;
        }
    }
    
    public function getStatusInfoAttribute()
    {
        $status = $this->attributes['status'];
        if ($this->attributes['ban'] > 0) {
            if ($this->attributes['ban'] == 1) {
                return ['text' => 'soft-banned', 'type' => 'danger'];
            } elseif ($this->attributes['ban'] == 2) {
                return ['text' => 'hard-banned', 'type' => 'danger'];
            }
        } else {
            if ($status == 0) {
                if ($this->attributes['verified'] == 1) {
                    if ($this->attributes['agreed'] == 1) {
                        return ['text' => 'disabled', 'type' => 'danger'];
                    } else {
                        return ['text' => 'unconfirmed', 'type' => 'warning'];
                    }
                } else {
                    return ['text' => 'unverified', 'type' => 'warning'];
                }
            } else {
                if ($this->attributes['verified'] == 1) {
                    if ($this->attributes['agreed'] == 1) {
                        return ['text' => 'active', 'type' => 'success'];
                    } else {
                        if ($this->attributes['request_confirmation_at'] == null) {
                            return ['text' => 'unconfirmed', 'type' => 'warning'];
                        } else {
                            return ['text' => 'requested-manual-confirmation', 'type' => 'warning'];
                        }
                    }
                } else {
                    return ['text' => 'unverified', 'type' => 'warning'];
                }
            }
        }
    }

    // public function getAvatarAttribute(){
    //     $user_id = $this->attributes['id'];

    //     $url = env('PROFILE_IMAGE').$user_id.'/avatar.png';
    //     $headers = get_headers($url);
    //     $checker = stripos($headers[0],"200 OK")? true : false;
        
    //     if($checker){
    //         $avatar = env('PROFILE_IMAGE').$user_id.'/avatar.png';
    //     }else{
    //         $avatar = 'https://kimg.io/image/user-avatar-default.png';
    //     }

    //     return $avatar;
    // }

}
