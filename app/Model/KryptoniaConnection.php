<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class KryptoniaConnection extends Model
{
    protected $table = 'kryptonia_connections';
    protected $fillable = ['user_id', 'connection'];
    protected $connection = 'mysql';

    public function saveData(array $data) {
        #connection has been made

        foreach ($data as $datum) {
            #assign
            $arr = [];
            $arr['user_id'] = $datum;
            $arr['connection'] = 1;
            #check
            $a = static::where('user_id', $datum)->first();

            if($a) {
                $n_connection = $a['connection'] + 1;
                $a->connection = $n_connection;
                $a->save();
            } else {
                $kryto_connection = new static($arr);
                $kryto_connection->save();
            }
        }
    }
}
