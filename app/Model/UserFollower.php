<?php

namespace App\Model;


use App\User;
use Illuminate\Database\Eloquent\Model;

class UserFollower extends Model
{
    //
    protected $table = 'user_followers';

    public function saveData(array $data = []) : string {
        $status = ['follow', 'un-follow'];

        $datum = static::where(function ($query) use (& $data) {
            $query->where('user_id', $data['user_id'])
                ->where('follower_id', $data['follower_id']);
        })->first();

        if( $datum ) {
            if( !$datum->status ) {
                $datum->status = (bool) 1;
                if( $datum->save() )
                    return $status[0];
            } else {
                $datum->status = (bool) 0;
                if( $datum->save() )
                    return $status[1];
            }
        } else {
            $datum = new static;
            $datum->user_id = $data['user_id'];
            $datum->follower_id = $data['follower_id'];
            $datum->status = (bool) 1;
            if( $datum->save() )
                return $status[0];
        }
    }

    public function follow($data = []) {

        $this->attributes['user_id'] = $data['user_id'];
        $this->attributes['follower_id'] = $data['follower_id'];
        $this->attributes['status'] = 1;
        if($this->save()) {
            $this->checkUpdateUserConnection($data);

            return $this;
        } else {
            return false;
        }

    }

    public function user() {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function userFollowing() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getConnections($user_id){
        $connection_list = [];

        $connections = UserFollower::select(['follower_id'])->where(function($query) use ($user_id) {
            $query->where('user_id', $user_id)
                ->where('status', (bool) 1);
        })->get();

        collect($connections)->each(function($item) use (&$connection_list, $user_id) {
            $followers = UserFollower::select(['user_id'])->where(function($query) use ($item, $user_id) {
                $query->where('user_id', $item->follower_id)
                    ->where('follower_id', $user_id)
                    ->where('status', (bool) 1);
            })->get();

            $followers->each(function($item2) use (&$connection_list, $user_id) {
                if( $item2->user_id !== $user_id ) {
                    $connection_list[] = $item2->user_id;
                    return false;
                }
            });
        });

        return $connection_list;
    }


    protected function checkUpdateUserConnection(array $data) {

        $x = self::where('user_id', $data['follower_id'])->where('follower_id', $data['user_id'])->first();

        if($x) { #create connection
            $kryptonia_connection = KryptoniaConnection::where('user_id', $data['follower_id'])->where('status', 1)->first();

            #other user newly record.
            $x2 = new KryptoniaConnection;

            $arr_x2 = [];

            if($kryptonia_connection) { #has record
                $arr_connection_list = json_decode($kryptonia_connection->connection_list, true);

                if(!in_array($data['user_id'], $arr_connection_list)) {
                    if($x) {
                        $arr_connection_list[] = $data['user_id'];

                        $kryptonia_connection->connection += 1;
                        $kryptonia_connection->connection_list = json_encode( $arr_connection_list, JSON_NUMERIC_CHECK );
                        if($kryptonia_connection->save()) {
                            #persist also the other user
                            $kryptonia_connection2 = KryptoniaConnection::where('user_id', $data['user_id'])->where('status', 1)->first();
                            if($kryptonia_connection2) {
                                $arr_connection_list2 = json_decode( $kryptonia_connection2->connection_list, true );

                                $arr_connection_list2[] = $data['follower_id'];

                                $kryptonia_connection2->connection += 1;
                                $kryptonia_connection2->connection_list = json_encode( $arr_connection_list2, JSON_NUMERIC_CHECK );
                                $kryptonia_connection2->save();
                            } else {
                                $arr_x2[] = $data['follower_id'];

                                $x2->user_id = $data['user_id'];
                                $x2->connection = 1;
                                $x2->connection_list = json_encode( $arr_x2, JSON_NUMERIC_CHECK );
                                $x2->save();
                            }
                        }
                    }
                }
            } else {
                $x3 = new KryptoniaConnection;
                $arr_x3 = [];

                $arr_x3[] = $data['user_id'];

                $x3->user_id = $data['follower_id'];
                $x3->connection = 1;
                $x3->connection_list = json_encode( $arr_x3, JSON_NUMERIC_CHECK );
                if($x3->save()) {
                    #persist also the other user
                    $y2 = KryptoniaConnection::where('user_id', $data['user_id'])->where('status', 1)->first();
                    if($y2) {
                        $arr_y2_list2 = json_decode( $y2->connection_list, true);

                        $arr_y2_list2[] = $data['follower_id'];

                        $y2->connection += 1;
                        $y2->connection_list = json_encode( $arr_y2_list2, JSON_NUMERIC_CHECK );
                        $y2->save();
                    } else {
                        $arr_x2[] = $data['follower_id'];

                        $x2->user_id = $data['user_id'];
                        $x2->connection = 1;
                        $x2->connection_list = json_encode( $arr_x2, JSON_NUMERIC_CHECK );
                        $x2->save();
                    }
                }
            }
        }
    }

}
