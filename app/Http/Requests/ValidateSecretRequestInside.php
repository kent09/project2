<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use SMSkin\LumenMake\Requests\FormRequest; 
use Illuminate\Validation\Factory as ValidatonFactory;
use Illuminate\Support\Facades\Auth;
use App\Model\UserGoogleAuth;
use App\User;

class ValidateSecretRequestInside extends FormRequest
{
    /**
     *
     * @var \App\User
     */
    private $user;
    private $user_2fa_update;
    /**
     * Create a new FormRequest instance.
     *
     * @param \Illuminate\Validation\Factory $factory
     * @return void
     */
    public function __construct(ValidatonFactory $factory)
    {
        $this->user = User::find(Auth::id());
        $this->user_2fa_update = UserGoogleAuth::where('user_id',$this->user->id)->first();

        $factory->extend(
            'valid_token',
            function ($attribute, $value, $parameters, $validator) {
                $secret = Crypt::decrypt($this->user_2fa_update->google2fa_secret);

                return Google2FA::verifyKey($secret, $value);
            },
            'Not a valid token'
        );

        $factory->extend(
            'used_token',
            function ($attribute, $value, $parameters, $validator) {
                $key = $this->user->id . ':' . $value;

                return !Cache::has($key);
            },
            'Cannot reuse token'
        );
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {   
        $user = User::find(Auth::id());

        try {
            $this->user = User::findOrFail(
               $user->id

            );
        } catch (Exception $exc) {

            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'totp' => 'bail|required|digits:6|valid_token|used_token',
        ];
    }
}