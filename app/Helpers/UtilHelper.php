<?php
use App\Repository\UtilRepository;

function request()
{
    return app('request');
}

function b_crypt($plain_text)
{
    return app('hash')->make($plain_text);
}

function hash_check($plain_text, $hashed)
{
    return app('hash')->check($plain_text, $hashed);
}

function paginate($collection, int $parts, int $page)
{
    return \App\Repository\UtilRepository::paginate($collection, $parts, $page);
}

function return_data($data, $status = 200) {
    $return['status'] = $status;
    $return['data'] = $data;
    return $return;
}

function res(string $message = '', $data = null, int $code = 200) {
    return response()->json([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ]);
}

function to_obj($data){
    $data = json_encode($data);
    return json_decode($data);
}

function record_activity($user_id, $field, $action, $table=null, $row_id=0, $request_status='success')
{
    $Utility = new UtilRepository;
    return $Utility->record_activity($user_id, $field, $action, $table, $row_id, $request_status);
}


function record_admin_activity($admin_id, $type, $action, $field, $status, $category=null, $affected_user_id=0)
{   
    $Utility = new UtilRepository;
    return $Utility->record_admin_activity($admin_id, $type, $action, $field, $status, $category, $affected_user_id);
}

function error(int $code, string $message = '', string $redirect_uri = '')
{
    return view('errors.' . $code, compact('message', 'redirect_uri'));
}

function settings($key)
{
    $Utility = new UtilRepository;
    return $Utility->settings($key);
}

function set_referral_reward($user_id=0, $referral_id, $reward, $type)
{
    $Utility = new UtilRepository;
    return $Utility->set_referral_reward($user_id, $referral_id, $reward, $type);
}

function online_users_list($user_id)
{
    $Utility = new UtilRepository;
    return $Utility->online_users_list($user_id);
}

function emoji($type=null)
{
    $Utility = new UtilRepository;
    return $Utility->emoji($type);
}

function remove_element($array,$value) {
    return array_diff($array, (is_array($value) ? $value : array($value)));
}

function public_path($path){
    return rtrim(app()->basePath('public/'.$path), '/');
}

function is_limitation_passed(string $slug, int $user_id = 0): array
{
    return (new \App\Repository\LimitationRepository)->is_limitation_passed($slug, $user_id);
}

function limitation_info(string $slug, int $user_id = 0): array
{
    return (new \App\Repository\LimitationRepository)->limitation_info($slug, $user_id);
}