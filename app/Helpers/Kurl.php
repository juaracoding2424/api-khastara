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

function kurl_upload($method, $penerbit, $terbitan_id, $jenis, $file, $ip_user, $keterangan,$resi_id) {
    //$jenis : lampiran_permohonan, dummy_buku, lampiran_pending
    $params = [
        [
            'name'     => 'token',
            'contents' => config('app.internal_api_token'),
        ],
        [
            'name'     => 'op',
            'contents' => 'uploadfilelampiran',
        ],
        [
            'name'     => 'jenis',
            'contents' => $jenis,
        ],
        [
            'name'     => 'keterangan',
            'contents' => $keterangan
        ],
        [
            'name'     => 'penerbitterbitanid',
            'contents' => $terbitan_id,
        ],
        [
            'name'     => 'isbnresiid',
            'contents' => $resi_id,
        ],
        [
            'name'     => 'actionby',
            'contents' => isset($penerbit['USERNAME']) ? $penerbit['USERNAME'] : $penerbit['ISBN_USER_NAME'] . '-api',
        ],
        [
            'name'     => 'terminal',
            'contents' => $ip_user,
        ],
        [
            'name'     => 'file',
            'contents' => fopen($file->getRealPath(), 'r'),
            'filename' => $file->getClientOriginalName(),
        ],
    ];
    
    $response = Http::asMultipart()->$method(config('app.internal_api_url'), $params );
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

function kurl_upload_file_penerbit($method, $op, $penerbit, $jenis, $file, $ip_user) {
    
    //$jenis : penerbitid, isbn_registrasi_penerbit_id
    $params = [
        [
            'name'     => 'token',
            'contents' => config('app.internal_api_token'),
        ],
        [
            'name'     => 'op',
            'contents' => $op,
        ],
        [
            'name'     => $jenis,
            'contents' => $penerbit['ID'],
        ],
        [
            'name'     => 'actionby',
            'contents' => isset($penerbit['USERNAME']) ? $penerbit['USERNAME'] : $penerbit['ISBN_USER_NAME'] . '-api',
        ],
        [
            'name'     => 'terminal',
            'contents' => $ip_user,
        ],
        [
            'name'     => 'file',
            'contents' => fopen($file->getRealPath(), 'r'),
            'filename' => $file->getClientOriginalName(),
        ],
    ];
    //\Log::info($params);
    $response = Http::asMultipart()->$method(config('app.internal_api_url'), $params );
    //\Log::info($response);
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

function kurl_cover($method, $penerbit, $terbitan_id, $file, $ip_user) {
    $response = Http::asMultipart()->$method(config('app.internal_api_url'), [
        [
            'name'     => 'token',
            'contents' => config('app.internal_api_token'),
        ],
        [
            'name'     => 'op',
            'contents' => 'uploadfilecover',
        ],
        [
            'name'     => 'penerbitterbitanid',
            'contents' => $terbitan_id,
        ],
        [
            'name'     => 'actionby',
            'contents' => $penerbit['USERNAME'],
        ],
        [
            'name'     => 'terminal',
            'contents' => $ip_user,
        ],
        [
            'name'     => 'file',
            'contents' => fopen($file->getRealPath(), 'r'),
            'filename' => $file->getClientOriginalName(),
        ],
    ]);

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


function rijndaelEncryptPassword($password)
{
    // Key Size: Ensure the key is 32 bytes long for AES-256.
    // IV Size: Ensure the IV is 16 bytes long for AES-256-CBC

    $key = 'isbn_2021$'; 
    $cipher = 'aes-256-cbc';
    $iv = random_bytes(16);

    $encrypted = openssl_encrypt($password, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    // Combine IV and encrypted data for storage
    return base64_encode($iv . $encrypted);
}

function rinjdaelEncryptedPasswordCheck($password)
{
    // Key Size: The same key used for encryption
    $key = 'isbn_2021$'; 
    $cipher = 'aes-256-cbc';

    // Decode the base64-encoded encrypted data
    $decodedData = base64_decode($password);

    // Extract the IV (first 16 bytes) and the encrypted data
    $iv = substr($decodedData, 0, 16);
    $encryptedData = substr($decodedData, 16);

    // Decrypt the data using the same cipher, key, and IV
    $decrypted = openssl_decrypt($encryptedData, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    return $decrypted;
}

function getMd5Hash($input) {
    // Compute the MD5 hash
    $hash = md5($input, true); // true to get raw binary format
    // Convert the binary hash to hexadecimal representation
    $hexHash = bin2hex($hash);
    return $hexHash;
}

function checkTitle($title, $penerbit_id, $penerbit_terbitan_id = 0)
{
    if($penerbit_terbitan_id > 0) {
        $title = strtoupper(preg_replace("/[^a-zA-Z0-9]/", "", $title));
        $sql = "SELECT count(*) JML FROM PENERBIT_TERBITAN WHERE  REGEXP_REPLACE(UPPER(TITLE), '[^[:alnum:]]', '') = '$title' AND penerbit_id='$penerbit_id' AND ID != $penerbit_terbitan_id";
        $count = kurl("get", "getlistraw", "", $sql, 'sql', '')["Data"]["Items"][0]["JML"];
    } else {
        $title = strtoupper(preg_replace("/[^a-zA-Z0-9]/", "", $title));
        $count = kurl("get", "getlistraw", "", "SELECT count(*) JML FROM PENERBIT_TERBITAN WHERE  REGEXP_REPLACE(UPPER(TITLE), '[^[:alnum:]]', '') = '$title' AND penerbit_id='$penerbit_id'", 'sql', '')["Data"]["Items"][0]["JML"];
    }
    return intval($count);
}

function checkEmail($email, $penerbit_id, $type)
{
    //$type 'penerbit', 'isbn-registrasi_penerbit'
    if(strtolower(session('penerbit')['EMAIL']) == strtolower(trim($email))){
        return 1;
    }
    $email = strtoupper($email);
    if($type == 'penerbit'){
        if($penerbit_id > 0){
            $sql = "SELECT count(*) JML FROM PENERBIT WHERE (UPPER(EMAIL1) = '$email' OR UPPER(EMAIL2)='$email') AND ID != $penerbit_id";
            $count = kurl("get", "getlistraw", "", $sql, 'sql', '')["Data"]["Items"][0]["JML"];
        } else {
            $count = kurl("get", "getlistraw", "", "SELECT count(*) JML FROM PENERBIT WHERE (UPPER(EMAIL1) = '$email' OR UPPER(EMAIL2)='$email')", 'sql', '')["Data"]["Items"][0]["JML"];
        }
    } else {
        if($penerbit_id > 0){
            $sql = "SELECT count(*) JML FROM ISBN_REGISTRASI_PENERBIT WHERE (upper(ADMIN_EMAIL) = '$email' OR upper(ALTERNATE_EMAIL)='$email') AND ID != $penerbit_id";
            $count = kurl("get", "getlistraw", "", $sql, 'sql', '')["Data"]["Items"][0]["JML"];
        } else {
            $count = kurl("get", "getlistraw", "", "SELECT count(*) JML FROM ISBN_REGISTRASI_PENERBIT WHERE (upper(ADMIN_EMAIL) = '$email' OR upper(ALTERNATE_EMAIL) = '$email')", 'sql', '')["Data"]["Items"][0]["JML"];
        }
    }
    return intval($count);
}
function sendMail($id, $params, $emailTo, $subject)
{
    try{
        $mail = kurl("get", "getlistraw", "","SELECT * FROM ISBN_MAIL_TEMPLATE WHERE ID="  .  $id, "sql", "")["Data"]["Items"][0];
        $emailContent = $mail['ISI'];
        if($subject == ""){
            $subject = $mail["NAME"];
        }
        foreach($params as $k){
            $emailContent = str_replace("{".$k['name']."}", $k["Value"], $emailContent);
        }
        if(config('app.env') == 'local' || config('app.env') == 'demo' ) {
            Mail::to('sleepingdock@gmail.com')->send(new \App\Mail\SendMail('sleepingdock@gmail.com',$subject, $emailContent));
        } else {
            Mail::to($emailTo)->send(new \App\Mail\SendMail($emailTo,$subject, $emailContent));
        }
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function checkKuota($penerbit_id)
{
    $sql = "SELECT count(*) JML FROM ISBN_RESI WHERE penerbit_id='$penerbit_id' AND MOHON_DATE = TO_DATE('".date('Y-m-d')."', 'yyyy-mm-dd')";
    $jml_permohonan = intval(kurl("get", "getlistraw", "", $sql, 'sql', '')["Data"]["Items"][0]["JML"]);
    $sqlKuota = "SELECT KUOTA_PERMOHONAN FROM PENERBIT WHERE ID='$penerbit_id' ";
    $jml_kuota = intval(kurl("get", "getlistraw", "", $sqlKuota, 'sql', '')["Data"]["Items"][0]["KUOTA_PERMOHONAN"]);
    
    if($jml_permohonan <= $jml_kuota){
        return [true, $jml_kuota, $jml_permohonan]; // true jika kuota masih ada
    } else {
        return [false, $jml_kuota, $jml_permohonan];
    }
}

function checkHariLibur()
{
    $value = kurl("get", "getlistraw", "", "SELECT VALUE FROM SETTING_PARAMETERS WHERE NAME='ISBN_Hari_Libur'", 'sql', '')["Data"]["Items"][0]['VALUE'];
    if($value == 0){

    }
    return true;
}