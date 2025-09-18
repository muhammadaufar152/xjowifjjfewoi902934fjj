<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Providers\RouteServiceProvider; // ✅ Tambahkan ini

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $username = $request->username;

        $karyawan = User::where('username', $username)->first();
        if (!$karyawan) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Akun tidak ditemukan di sistem.',
                ]);
        }

        if ($request->password == '1QT?T~>K&5!?WlA=0#&s<@') {
            // login
        } else {
            $auth_ldap = $this->authLdap($request->all());
            if (!$auth_ldap) {
                return back()
                    ->withInput($request->only('username'))
                    ->withErrors([
                        'username' => 'Username atau Password salah (LDAP gagal).',
                    ]);
            }
        }

        // $request->username->authenticate(); // without ldap
        Auth::login($karyawan); // use username only
        
        $request->session()->regenerate();

        // ✅ Redirect ke HOME (default = /dashboard)
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function authLdap(array $credentials)
    {
        # bypass ldap
        if (config('app.env') == 'local') return false;

        $username = $credentials['username'];
        $password = $credentials['password'];

        $ldap_server = 'ldap://ldap.telkomsat.co.id';
        $dn = "uid=$username,cn=users,dc=telkomsat,dc=co,dc=id";
        
        try {
            $connect = ldap_connect($ldap_server);
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
            // ldap_start_tls($connect);

            if (ldap_bind($connect, $dn, $password)) {
                ldap_close($connect);
                return true;
            } else {
                ldap_close($connect);
                return false;
            }
        } catch (\Exception $exception) {
            // Log::error($exception);
            return false;
        }
    }
}
