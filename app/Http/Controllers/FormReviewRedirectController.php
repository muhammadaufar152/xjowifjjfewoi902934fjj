<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class FormReviewRedirectController extends Controller
{
    public function redirect()
    {
        $user = Auth::user();

        if ($user->hasRole('officer')) {
            return redirect()->route('approval.officer.index');
        } elseif ($user->hasRole('manager')) {
            return redirect()->route('approval.manager.index');
        } elseif ($user->hasRole('avp')) {
            return redirect()->route('approval.avp.index');
        } else {
            return redirect()->route('form_review.raw');
        }
    }
}
