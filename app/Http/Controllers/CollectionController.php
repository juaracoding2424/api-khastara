<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CollectionController extends Controller
{
    function getById(Request $request, $id)
    {
        //get data katalog
        $sql_catalog = "SELECT '' as solr_id, id as catalog_id, bibid as bib_id, title, controlnumber as control_number, author, edition, publisher, publishyear as publish_year,
                        publishlocation as publish_location, description as deskripsi_fisik, subject, deweyno as ddc, LANGUAGES as language_code, 
                        CREATEDATE as create_date, UPDATEDATE as last_update_date FROM CATALOGS WHERE ID='$id'";
        $catalog = Http::post(config('app.internal_api_url') . "?token=" . config('app.internal_api_token') . "&op=getlistraw&sql=" . urlencode($sql_catalog));
        
        $sql_catalog_ruas = "SELECT tag, indicator1, indicator2, sequence, value FROM catalog_ruas WHERE CATALOGID='$id' ORDER BY sequence ";
        $catalog_ruas = Http::post(config('app.internal_api_url') . "?token=" . config('app.internal_api_token') . "&op=getlistraw&sql=" . urlencode($sql_catalog_ruas));
        if(!isset($catalog['Data']["Items"][0])){ //data katalog tidak ditemukan
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'ID ' . $id . ' not found!',
            ], 500);
        } 
        $catalog_new = []; $catalog_ruas_new = [];
        foreach($catalog['Data']['Items'][0] as $key=>$val){
            $catalog_new = array_merge($catalog_new, [strtolower($key) => $val]);
        }

        foreach($catalog_ruas['Data']['Items'] as $c){
            $c_detail = [];
            foreach($c as $key=>$val){
                $c_detail = array_merge($c_detail, [strtolower($key) => $val]);
            }
            $catalog_ruas_new[] = $c_detail; 
        }
        return response()->json([
            'status' => 'Success',
            'data' => $catalog_new,
            'marc'  => $catalog_ruas_new
        ], 200);
    }

    function getFile(Request $request, $id)
    {
        //get data katalog
        $sql = "SELECT catalog_id, fileurl FROM CATALOGFILES WHERE CATALOG_ID='$id' AND ispublish=1 AND isfileexist=1";
        $data = Http::post(config('app.internal_api_url') . "?token=" . config('app.internal_api_token') . "&op=getlistraw&sql=" . urlencode($sql));
        
        if(!isset($data['Data']["Items"][0])){ //data katalog tidak ditemukan
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'ID ' . $id . ' not found!',
            ], 500);
        } 
        $data_new = []; 

        foreach($data['Data']['Items'] as $c){
            $detail = [];
            foreach($c as $key=>$val){
                $detail = array_merge($detail, [strtolower($key) => $val]);
            }
            $data_new[] = $detail; 
        }
        return response()->json([
            'status' => 'Success',
            'data' => $data_new,
        ], 200);
    }

    function getList(Request $request)
    {
        $page = $request->input('page') ? $request->input('page') : 1;
        $length = $request->input('length') ? $request->input('length') : 10;
        $start  = ($page - 1) * $length;
        $end = $start + $length;

        $sql = "SELECT '' as solr_id, id as catalog_id, bibid as bib_id, title, controlnumber as control_number, author, edition, publisher, publishyear as publish_year,
                        publishlocation as publish_location, description as deskripsi_fisik, subject, deweyno as ddc, LANGUAGES as language_code, 
                        CREATEDATE as create_date, UPDATEDATE as last_update_date FROM CATALOGS WHERE ISKHASTARA=1";
        $data = kurl("get","getlistraw", "", "SELECT outer.* FROM (SELECT ROWNUM nomor, inner.* FROM ($sql )  inner WHERE rownum <=$end) outer WHERE nomor >$start", 'sql', '')["Data"]["Items"];
        $totalData = kurl("get","getlistraw", "", "SELECT COUNT(*) JML FROM CATALOGS WHERE ISKHASTARA=1",'sql', '')["Data"]["Items"][0]["JML"];  
        
        if(!isset($data[0])){
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'Error!',
            ], 500);
        } 
        $data_new = []; 

        foreach($data as $c){
            $detail = [];
            foreach($c as $key=>$val){
                $detail = array_merge($detail, [strtolower($key) => $val]);
            }
            $data_new[] = $detail; 
        }

        return response()->json([
            'data' => $data_new,
            'page' => $page,
            'length' => $length,
            'total' => $totalData,
        ], 200);
    }
}
