<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Redirect the user to the SAML login page.
     * @return RedirectResponse
     */
    public function login(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended();
        } else {
            return redirect(
                route('auth.saml.login')
            );
        }
    }

    /**
     * Redirect the user to the SAML logout page.
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        return redirect(
            route('auth.saml.logout')
        );
    }
}
