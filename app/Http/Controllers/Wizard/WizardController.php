<?php

namespace App\Http\Controllers\Wizard;

use App\Contracts\Wizard\WizardInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WizardController extends Controller
{
    //
    protected $wizard;
    public function __construct(WizardInterface $wizard) {
        $this->wizard = $wizard;
    }

    public function socialAuthStatus() {

        return $this->wizard->checkSocialAuthStatus();
    }

    public function confirmWizardAccount() {

        return $this->wizard->confirmWizard();
    }
}
