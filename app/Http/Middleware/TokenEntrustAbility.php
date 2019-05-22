<?php

namespace App\Http\Middleware;

//use App\UserUsageLog;
use Carbon\Carbon;
use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Middleware\BaseMiddleware;

class TokenEntrustAbility extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles, $permissions, $validateAll=false)
    {
        if (! $token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'token_not_provided', 400);
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (TokenExpiredException $e) {
            return $this->respond('tymon.jwt.expired', 'token_expired', $e->getStatusCode(), [$e]);
        } catch (JWTException $e) {
            return $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
        }

        if (! $user) {
            return $this->respond('tymon.jwt.user_not_found', 'user_not_found', 404);
        }
        // print_r($roles);
        // exit();
        if (!$request->user()->ability(explode('|', $roles), explode('|', $permissions),
            array('validate_all' => $validateAll))) {
            return $this->respond('tymon.jwt.invalid', 'Unauthorized Access', 401, 'Unauthorized');
        }

        $this->events->fire('tymon.jwt.valid', $user);
//        $new_user_usage_log = new UserUsageLog();
//        $new_user_usage_log->user_id = $user->id;
//        $new_user_usage_log->log_time = Carbon::now()->toDateString();
//        $new_user_usage_log->request_log = $request->fullUrl();
//        $new_user_usage_log->save();
        return $next($request);

    }
}
