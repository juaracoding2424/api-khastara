<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
/*
params kurl
$method = post/get
$action = add, getlist, update, delete
$table = table yang akan dieksekusi
$data = data filter atau data update atau data add yang berupa array
$kategori = dari backend ada ListAddItem, ListUpdateItem
$params = untuk penambahan params pada saat req api (pagination dll)
*/

function kurl($method, $action, $table, $data, $kategori, $params = null) { 
    $body = $action == 'getlistraw' ? $data : json_encode($data);
    $form_data = [
        'token' => config('app.internal_api_token'),
        'op' => $action,
        'table' => $table,
        $kategori => $body
    ];

    //page
    if (!empty($params)) {
        $form_data = array_merge($form_data, $params);
    }
    $response = Http::asForm()->$method(config('app.internal_api_url'), $form_data);

    if ($response->successful()) {
        $data = $response->json();
        return $data;

    } else {
        // Handle the error
        $status = $response->status();
        $error = $response->body();
        return $status;
    }
}

function kurl_solr($form_data)
{
    $response = Http::asForm()->get(config('app.solr_url'), $form_data);
    //\Log::info($form_data);
    if ($response->successful()) {
        $data = $response->json();
        return $data;
    } else {
        // Handle the error
        $status = $response->status();
        $error = $response->body();
        return $status;
    }
}

function createKeyFromUrl($request, $functionName) 
{
    $url = $request->fullUrl();
    // Parse URL menjadi komponen-komponen
    $parsedUrl = parse_url($url);
    
    // Jika ada query string, proses lebih lanjut
    if (isset($parsedUrl['query'])) {
        // Parse query string menjadi array
        parse_str($parsedUrl['query'], $queryParams);
        
        // Hapus parameter `_` jika ada
        if (isset($queryParams['_'])) {
            unset($queryParams['_']);
        }
        if (isset($queryParams['searchable'])) {
            unset($queryParams['searchable']);
        }
        if (isset($queryParams['columns'])) {
            unset($queryParams['columns']);
        }
        if (isset($queryParams['order'])) {
            unset($queryParams['order']);
        }
        if (isset($queryParams['draw'])) {
            unset($queryParams['draw']);
        }
        ksort($queryParams);
        // Susun ulang query string tanpa parameter `_`
        $parsedUrl['query'] = http_build_query($queryParams);
    }
    
    // Susun ulang URL
    $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    if (isset($parsedUrl['port'])) {
        $newUrl .= ':' . $parsedUrl['port'];
    }
    if (isset($parsedUrl['path'])) {
        $newUrl .= $parsedUrl['path'];
    }
    if (!empty($parsedUrl['query'])) {
        $newUrl .= '?' . $parsedUrl['query'];
    }
    $rememberKey = md5($functionName .'_'.$newUrl);
    return $rememberKey;
}

function createKeyFromArray($array, $functionName)
{
    $newKey = "";
    ksort($array);
    foreach($array as $key=>$val){
        $newKey .= $key. trim(strtolower($val));
    }
    $rememberKey = md5($functionName . '_' . $newKey);
    return $rememberKey;
}
function cacheSolr($array, $functionName)
{
    $key = createKeyFromArray($array, $functionName);
    try{
        $data = Cache::remember($key, now()->addMinutes(30), function() use($array){
            $response = Http::asForm()->get(config('app.solr_url'), $array);
            if ($response->successful()) {
                $data = $response->json();
                return $data;
            } else {
                $status = $response->status();
                $error = $response->body();
                return $status;
            }
        });
        return $data;
    } catch(\Exception $e){
        \Log::info($e->getMessage());
    }
}
function cacheData($request, $functionName, $sql, $jumlahOrItems = 'items')
{
    $key = createKeyFromUrl($request, $functionName, $penerbit_id);
    try{
        $data = Cache::remember($key, now()->addMinutes(30), function() use($sql, $jumlahOrItems){
            if($jumlahOrItems == 'items'){
                $ret = kurl("post","getlistraw", "", $sql, 'sql', '')["Data"];
                if(isset($ret["Items"])){
                    $ret = $ret["Items"];
                } else {
                    $ret = [];                                                                                                                                                                                                                                                                                   
                }
            } else{
                $q = kurl("post","getlistraw", "", $sql, 'sql', '')["Data"]["Items"];
                if(isset($q[0])){
                    $ret = $q[0]["JUMLAH"];
                } else {
                    $ret = 0;
                }
            }
            return $ret;
        });
        return $data;
    } catch(\Exception $e){
    \Log::info($e->getMessage());
    }
}