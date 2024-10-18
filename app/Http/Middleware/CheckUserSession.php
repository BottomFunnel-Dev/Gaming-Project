<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = session()->getId();
            \Log::info('Checking user session', [
                'user_id' => $user->id,
                'session_id' => $user->session_id,
                'current_session_id' => $currentSessionId,
            ]);

            // Check if the session ID matches
            if ($user->session_id !== $currentSessionId) {
                \Log::warning('Session mismatch, logging out user', [
                    'user_id' => $user->id,
                    'expected_session_id' => $user->session_id,
                    'actual_session_id' => $currentSessionId,
                ]);
                Auth::logout();
                return redirect()->route('login')->with('message', 'You have been logged out due to a new login from another device.');
            }
        }

        return $next($request);
    }

}
