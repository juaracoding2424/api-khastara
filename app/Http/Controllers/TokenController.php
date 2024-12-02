<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Str;

class TokenController extends Controller
{
    function getToken(Request $request)
    {
        $data = kurl("get","getlistraw", "", "SELECT * FROM API_MEMBERS WHERE APIKEY='".$request->input('x-api-key')."'", 'sql', '')["Data"]["Items"];
        if(isset($data[0]["NAMA"])){
            $token = Str::random(45);
            $expired_at = Date('Y-m-d H:i:s', strtotime(config('app.expires')));
            $send_data = [
                ["name"=> "APITOKEN", "Value" => $token],
                ["name" =>"APITOKEN_EXPIRED", "Value" => $expired_at]
            ];
            $params = [
                "issavehistory" => 0,
                "id" => $data[0]["ID"]
            ];
            $res = kurl('post','update', 'API_MEMBERS', $send_data, 'ListUpdateItem', $params);
            if($res["Status"] == "Success"){
                return response()->json([
                "token" => $token,
                "expired_at" => $expired_at
                ], 200);
            } else {
                return response()->json([
                    'status' => "Failed",
                    'message' => $res["Message"]
                ], 500);
            }
        } else {
            return response()->json([
                'message' => "Application Key not found!",
                'status' => "Failed"
            ], 401);
        }
    }
}
