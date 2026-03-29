<?php

namespace App\Filament\Tenant\Pages;

use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;

class TenantLogin extends Login
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            $panel = Filament::getCurrentOrDefaultPanel();

            if ($user instanceof FilamentUser && $user->canAccessPanel($panel)) {
                redirect()->intended(Filament::getUrl());
            }
        }

        $this->form->fill();
    }
}
