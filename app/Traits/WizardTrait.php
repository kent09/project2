<?php

namespace App\Traits;


use App\Model\SocialConnect;
use App\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

trait WizardTrait
{

    /**
     * @param int $user_id
     * @param array $social
     *
     * @return array
     */
    public static function checkSocialStatus(int $user_id, array $social) : array {
        $social_collection = [];

        $social_connected = SocialConnect::where(function($query) use ($user_id, $social) {
            $query->where('user_id', $user_id)
                    ->whereIn('social', $social);
        })->get();

        if( $social_connected->count() <= 0 )
            return $social_collection[] = ['label' => 'No social connected', 'status' => 0];

        if($social_connected->count() > 0)
            collect($social_connected)->each(function($item) use (& $social_collection) {
                if($item['social'] === 'facebook') {
                    $data = static::socialLabelStatus($item);
                    $social_collection[ $item['social'] ] = [ 'social' => $item['social'], 'label' => $data['label'], 'status' => $data['status'] ];
                }

                if($item['social'] === 'tweeter') {
                    $data = static::socialLabelStatus($item);
                    $social_collection[ $item['social'] ] = [ 'social' => $item['social'], 'label' => $data['label'], 'status' => $data['status'] ];
                }

                if($item['social'] === 'google-plus') {
                    $data = static::socialLabelStatus($item);
                    $social_collection[ $item['social'] ] = [ 'social' => $item['social'], 'label' => $data['label'], 'status' => $data['status'] ];
                }

                if($item['social'] === 'instagram') {
                    $data = static::socialLabelStatus($item);
                    $social_collection[ $item['social'] ] = [ 'social' => $item['social'], 'label' => $data['label'], 'status' => $data['status'] ];
                }

                if($item['social'] === 'steemit') {
                    $data = static::socialLabelStatus($item);
                    $social_collection[ $item['social'] ] = [ 'social' => $item['social'], 'label' => $data['label'], 'status' => $data['status'] ];
                }
            });

        return $social_collection;
    }


    /**
     * @param string $type
     *
     * @return bool
     */
    public static function socialConnectCallback(string $type) : bool {
        switch ($type) {
            case 'facebook':
                $response = Socialite::with($type)->stateless()->user();

                $data = [ 'social' => $type, 'account_id' => $response->id ];

                if( static::socialTakenAlready([
                    'user_id' => Auth::id(), 'social' => $type, 'account_id' => $response->account_id
                ]) )
                    return 3;

                if( !static::checkSocialConnect($data) )
                    if( (new SocialConnect())->saveData([
                        'user_id' => Auth::id(), 'social' => $type,
                        'account_name' => $response->name, 'account_id' => $response->account_id
                    ]) )
                        return true;
            break;

            case 'google':
                $response = Socialite::with($type)->stateless()->user();

                $data = [ 'social' => $type, 'account_id' => $response->id ];

                if( static::socialTakenAlready([
                    'user_id' => Auth::id(), 'social' => $type, 'account_id' => $response->account_id
                ]) )
                    return 3;

                if( !static::checkSocialConnect($data) )
                    if( (new SocialConnect())->saveData([
                        'user_id' => Auth::id(), 'social' => $type,
                        'account_name' => $response->name, 'account_id' => $response->account_id
                    ]) )
                        return true;
            break;

            case 'linkedin':
                $response = Socialite::with($type)->stateless()->user();

                $data = [ 'social' => $type,'account_id' => $response->id ];

                if( static::socialTakenAlready([
                    'user_id' => Auth::id(), 'social' => $type, 'account_id' => $response->account_id
                ]) )
                    return 3;

                if( !static::checkSocialConnect($data) )
                    if( (new SocialConnect())->saveData([
                        'user_id' => Auth::id(), 'social' => $type,
                        'account_name' => $response->name, 'account_id' => $response->account_id
                    ]) )
                        return true;
            break;

            case 'twitter':
                $response = Socialite::with($type)->stateless()->user();

                $data = [ 'social' => $type,'account_id' => $response->id ];

                if( static::socialTakenAlready([
                    'user_id' => Auth::id(), 'social' => $type, 'account_id' => $response->account_id
                ]) )
                    return 3;

                if( !static::checkSocialConnect($data) )
                    if( (new SocialConnect())->saveData([
                        'user_id' => Auth::id(), 'social' => $type,
                        'account_name' => $response->name, 'account_id' => $response->account_id
                    ]) )
                        return true;
            break;

            default:
                return false;
            break;
        }
    }


    /**
     * @param int $user_id
     *
     * @return bool
     */
    public static function setAgreeData(int $user_id) : bool {
        $user = User::find($user_id);
        if( $user ) {
            $social_connect = SocialConnect::where('user_id', $user_id)->count();
            if( $social_connect > 0 ) {
                $user->agreed = 1;
                $user->status = 1;
                if( $user->save() )
                    return true;
            }
        }
        return false;
    }

    protected static function checkSocialConnect(array $data) : bool {
        $social_connect = SocialConnect::where(function($query) use (& $data) {
            $query->where('social', $data['social'])
                ->where('account_id', $data['account_id'])
                ->where('user_id', Auth::id());
        })->first();
        if($social_connect)
            return true;
        return false;
    }

    protected static function socialTakenAlready(array $data) : bool {
        $taken = SocialConnect::where(function($query) use (& $data) {
            $query->where('user_id', '<>', $data['user_id'])
                ->where('account_id', $data['account_id'])
                ->where('social', $data['social'])
                ->whereNotIn('status', [0, 3]);
        })->first();
        if($taken)
            return true;
        return false;
    }


    /**
     * @param array $item
     *
     * @return array
     */
    protected static function socialLabelStatus(array $item) : array {
        switch ($item['status']) {
            case 1;
                $label = 'Linked';
                $status = 1;
                break;
            case 2;
                $label = 'Soft-Unlinked';
                $status = 2;
                break;
            case 3;
                $label = 'Hard-Unlinked';
                $status = 3;
                break;
        }
        return compact('label', 'status');
    }

}