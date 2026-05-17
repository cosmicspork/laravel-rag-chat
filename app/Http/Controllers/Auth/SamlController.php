<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OneLogin\Saml2\Auth as SamlAuth;
use OneLogin\Saml2\Error as SamlError;
use OneLogin\Saml2\Utils as SamlUtils;
use App\Http\Controllers\Controller;
use App\Models\User;

class SamlController extends Controller
{
    /**
     * Get certificate from storage and format it
     *
     * @param string $filename The certificate or key filename
     * @param bool $isPrivateKey Whether this is a private key (true) or certificate (false)
     * @return string The formatted certificate or key
     */
    protected function getCertificate($filename, $isPrivateKey = false): string
    {
        // TODO: Use Laravel's Storage facade instead of file_get_contents
        // TODO: Use Laravel's config instead of env
        // TODO: Use Laravel's cache instead of file_get_contents
        if (app()->environment('production')) {
            return $filename;
        }
        $samlDisk = env('SAML_DISK', 'saml');
        $path = storage_path($samlDisk . '/' . $filename);
        if (!file_exists($path)) {
            Log::error('SAML certificate file not found at: ' . $path);
            abort(500, 'SAML certificate file not found.');
        }
        $certContent = file_get_contents($path);
        if ($isPrivateKey) {
            return SamlUtils::formatPrivateKey($certContent);
        }
        return SamlUtils::formatCert($certContent);
    }

    /**
     * SAML authentication instance
     *
     * @return SamlAuth
     */
    protected function getSamlAuth(): SamlAuth
    {
        // TODO: Use Laravel's config instead of env
        config(['saml.sp.assertionConsumerService.url' => route('auth.saml.acs')]);
        config(['saml.sp.singleLogoutService.url' => route('auth.saml.logout')]);
        config(['saml.sp.x509cert' => $this->getCertificate(env('SAML_SP_CERT'))]);
        config(['saml.sp.privateKey' => $this->getCertificate(env('SAML_SP_KEY'), true)]);
        config(['saml.idp.x509cert' => $this->getCertificate(env('SAML_IDP_CERT'))]);
        return new SamlAuth(config('saml'));
    }

    /**
     * Initiate SAML authentication request
     *
     * @return RedirectResponse
     */
    public function login(): RedirectResponse
    {
        try {
            // Get a SAML authentication instance
            $auth = $this->getSamlAuth();

            // Initiate SAML authentication and redirect to IdP
            $ssoBuiltUrl = $auth->login(null, [], false, false, true);

            // Redirect to the IdP
            return redirect($ssoBuiltUrl);
        } catch (SamlError $e) {
            Log::error('SAML login error: ' . $e->getMessage());
            // TODO: Redirect to a custom error page
            return redirect()->route('login')->with('error', 'An error occurred during SSO login.');
        }
    }

    /**
     * Handle SAML assertion response from IdP
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function acs(Request $request): RedirectResponse
    {
        try {
            // Get a SAML authentication instance
            $auth = $this->getSamlAuth();

            // Process the SAML Response from the IdP
            $auth->processResponse();

            // Check for errors
            $errors = $auth->getErrors();
            if (!empty($errors)) {
                Log::error('SAML response errors: ' . implode(', ', $errors));
                // TODO: Redirect to a custom error page
                return redirect()->route('login')
                    ->with('error', 'Error processing SAML response: ' . $auth->getLastErrorReason());
            }

            // Check SAML authentication status
            if (!$auth->isAuthenticated()) {
                Log::error('SAML authentication failed.');
                // TODO: Redirect to a custom error page
                return redirect()->route('login')
                    ->with('error', 'Authentication failed.');
            }

            // Get user attributes from the SAML response
            $attributes = $auth->getAttributes();

            // Find or create the user based on attributes, and update with latest SAML info
            $user = $this->findOrCreateUser($attributes, $auth->getNameId());

            // Log the user in to the Application
            Auth::login($user);

            // Regenerate the session
            $request->session()->regenerate();

            // Store SAML session details for use during logout
            $request->session()->put('samlNameId', $auth->getNameId());
            $request->session()->put('samlSessionIndex', $auth->getSessionIndex());

            // Redirect to intended URL or chat widget
            return redirect()->intended(route('chat.widget'));

        } catch (SamlError $e) {
            Log::error('SAML ACS error: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'An error occurred during SSO authentication.');
        }
    }

    /**
     * Find or create a user based on SAML attributes
     *
     * @param array $attributes
     * @param string $nameId
     * @return User
     */
    protected function findOrCreateUser(array $attributes, string $nameId): User
    {
        // Flexible attribute mapping for email and name
        $email = $attributes['email'][0] ?? $attributes['Email'][0] ?? $nameId;
        $name = $attributes['name'][0] ?? $attributes['Name'][0] ?? explode('@', $email)[0];

        // Find existing user or create a new one
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
            ]
        );

        // Update user record with latest SAML details.
        // Assuming setSamlNameId and setSamlAttributes are defined on the User model.
        $user->setSamlNameId($nameId);
        $user->setSamlAttributes($attributes);
        $user->save();

        return $user;
    }

    /**
     * Initiate SAML logout request
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        try {
            // Collect necessary parameters for SLO
            $returnTo = route('login');
            $parameters = [];
            $nameId = $request->session()->get('samlNameId');
            $sessionIndex = $request->session()->get('samlSessionIndex');

            // Log out and invalidate session
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $auth = $this->getSamlAuth();

            // Initiate SAML logout request to IdP
            $sloUrl = $auth->logout($returnTo, $parameters, $nameId, $sessionIndex, true);

            // If there's no SLO URL, just redirect back to login
            if (empty($sloUrl)) {
                return redirect()->route('login');
            }

            return redirect($sloUrl);
        } catch (SamlError $e) {
            Log::error('SAML logout error: ' . $e->getMessage());
            // Even if there's an error, we should still log out the user
            Auth::logout();
            return redirect()->route('login');
        }
    }

    /**
     * Handle Single Logout Service responses
     *
     * @return RedirectResponse
     */
    public function slo(): RedirectResponse
    {
        try {
            $auth = $this->getSamlAuth();

            // Process the SLO response
            $auth->processSLO();

            // Check for errors
            $errors = $auth->getErrors();
            if (!empty($errors)) {
                Log::error('SAML SLO error: ' . implode(', ', $errors));
            }

            // Always redirect to login after logout
            return redirect()->route('login')
                ->with('status', 'Successfully logged out.');

        } catch (SamlError $e) {
            Log::error('SAML SLO error: ' . $e->getMessage());
            return redirect()->route('login');
        }
    }

    /**
     * Serve SAML metadata for Service Provider
     *
     * @return Response
     */
    public function metadata(): Response
    {
        try {
            // Get the SAML auth instance and settings
            $auth = $this->getSamlAuth();
            $settings = $auth->getSettings();
            $metadata = $settings->getSPMetadata();

            // Validate metadata
            $errors = $settings->validateMetadata($metadata);
            if (!empty($errors)) {
                Log::error('SAML metadata errors: ' . $errors);
                return response('Invalid metadata', 500);
            }

            return response($metadata, 200)
                ->header('Content-Type', 'text/xml');

        } catch (SamlError $e) {
            Log::error('SAML metadata error: ' . $e->getMessage());
            return response('Error generating metadata: ' . $e->getMessage(), 500);
        }
    }
}
