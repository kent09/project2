<?php

namespace App\Contracts\Wizard;


interface WizardInterface
{
    public function checkSocialAuthStatus();

    public function confirmWizard();
}