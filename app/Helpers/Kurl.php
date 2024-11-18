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