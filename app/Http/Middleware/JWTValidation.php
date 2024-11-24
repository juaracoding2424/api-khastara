<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JWTValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,  $guard = null): Response
    {
        $authorization = $request->bearerToken();
        if(!$authorization){
            return response()->json([
                'message'   => 'Token is required!',
                'status'    => 'Failed'
            ], 401);
        }
        $authapi = kurl("get","getlistraw", "", "SELECT * FROM API_MEMBERS WHERE APITOKEN='$authorization'", 'sql', '')["Data"]["Items"];
        if(!isset($authapi[0])){
            return response()->json([
                'message'   => 'Token not valid!',
                'status'    => 'Failed'
            ], 401);
        } 
        if($authorization != $authapi[0]['APITOKEN']){
            return response()->json([
                'message'   => 'Token not valid.',
                'status'    => 'Failed'
            ], 401);
        } 
        if($authapi[0]['APITOKEN'] == '123ABC-demoonly' && $request->ip() != '127.0.0.1'){
            return response()->json([
                'message'   => 'Application Key for demo only.',
                'status'    => 'Failed'
            ], 401);
        } 
        if($authapi[0]['ISACTIVE'] == '0 '&& $authapi->APITOKEN != '123ABC-demoonly'){
            return response()->json([
                'message'   => 'Application Key is disabled. Contact administrator.',
                'status'    => 'Failed'
            ], 401);
        } 
        if((strtotime(date('Y-m-d H:i:s') . ' +7 hours') > strtotime($authapi[0]["APITOKEN_EXPIRED"])) && $authapi[0]['APITOKEN'] != '123ABC-demoonly'){
            return response()->json([
                'message'   => 'Token expired. Please request new token',
                'status'    => 'Failed'
            ], 401);
        }

        return $next($request);
    }
}
