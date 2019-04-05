<?php

namespace App\Repository\Wizard;


use App\Contracts\Wizard\WizardInterface;
use App\Traits\UtilityTrait;
use App\Traits\WizardTrait;
use Illuminate\Support\Facades\Auth;

class WizardRepository implements WizardInterface
{
    use WizardTrait, UtilityTrait;
    const SOCIAL = ['facebook', 'twitter', 'instagram', 'steemit', 'google-plus'];

    public function checkSocialAuthStatus() {
        // TODO: Implement checkSocialAuthStatus() method.
        $data = static::checkSocialStatus(Auth::id(), static::SOCIAL);
        if( count($data) > 0 )
            return static::response('', $data, 200, 'success');

        return static::response('', null, 400, 'error');
    }

    public function confirmWizard() {
        // TODO: Implement confirmWizard() method.
        if( static::setAgreeData(Auth::id()) )
            return static::response('Account Connected!', null, 200, 'success');

        return static::response('Something went wrong while connecting your account!', null, 400, 'error');
    }
}