<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use DateTime;
use DatePeriod;
use DateInterval;

class CollectionController extends Controller
{
    function getById(Request $request, $id)
    {
        //get data katalog
        $fl = "catalog_id,bib_id,title,control_number,author,edition,publisher,publish_year,
                publish_location,deskripsi_fisik,subject,ddc,catatan_isi,call_number,language_code,create_date,last_update_date,list_aksara,
                worksheet_name,konten_digital_count";       
        $response = kurl_solr([
                'fl'=> $fl,
                'q' => 'model:catalogs AND catalog_id:'.$id
        ]);
        if($response == '400'){ //data katalog tidak ditemukan
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'ID ' . $id . ' not found!',
            ], 500);
        } 
        $sql_catalog_ruas = "SELECT tag, indicator1, indicator2, sequence, value FROM catalog_ruas WHERE CATALOGID='$id' ORDER BY sequence ";
        $catalog_ruas = Http::post(config('app.internal_api_url') . "?token=" . config('app.internal_api_token') . "&op=getlistraw&sql=" . urlencode($sql_catalog_ruas));

        foreach($catalog_ruas['Data']['Items'] as $c){
            $c_detail = [];
            foreach($c as $key=>$val){
                $c_detail = array_merge($c_detail, [strtolower($key) => $val]);
            }
            $catalog_ruas_new[] = $c_detail; 
        }
        return response()->json([
            'status' => 'Success',
            'data' => $response["response"]["docs"][0],
            'marc'  => $catalog_ruas_new
        ], 200);
    }

    function getFile(Request $request, $id)
    {
        //get data katalog
        $sql = "SELECT catalog_id, fileurl FROM CATALOGFILES WHERE CATALOG_ID='$id' AND ispublish=1 AND isfileexist=1";
        $data = Http::post(config('app.internal_api_url') . "?token=" . config('app.internal_api_token') . "&op=getlistkontendigital&CatalogId=" . $id);
        
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
        $fl = "catalog_id,bib_id,title,control_number,author,edition,publisher,publish_year,
               publish_location,deskripsi_fisik,subject,ddc,catatan_isi,call_number,language_code,create_date,last_update_date,list_aksara,
               worksheet_name,konten_digital_count";
        $q = "";
        if($request->input('title')){
            $count = str_word_count($request->input('title'));
            if($count > 1) {
                $q .= ' AND title_text:"' .$request->input('title'). '"';
            } else {
                $q .= " AND title_text:*" .$request->input('title'). "*";
            }
        }
        if($request->input('author')){
            $q .= " AND author_text:*".$request->input('author')."*";
        }
        if($request->input('publisher')){
            $q .= " AND publisher_text:*".$request->input('publisher')."*";
        }
        if($request->input('catatan_isi')){
            $q .= " AND catatan_isi:*".$request->input('catatan_isi')."*";
        }
        if($request->input('subject')){
            $q .= " AND subject_text:*".$request->input('subject')."*";
        }

        if($request->input('year_start') && $request->input('year_end')){
            $start = Carbon::createFromFormat('Y-m-d', $request->input('year_start') . '-01-01');
            $end = Carbon::createFromFormat('Y-m-d', $request->input('year_end') .'-12-31');
            $interval = new DateInterval('P1Y');
            $daterange = new DatePeriod($start, $interval ,$end);
            $ranges = [];
            foreach($daterange as $date1){
                $ranges[]= $date1->format('Y');
            }
            $q .= " AND publish_year:(".implode(" ", $ranges) . ")";
        }
        if($request->input('year')){
            $q .= " AND publish_year:".$request->input('year');
        }
        $response = kurl_solr([
            'fl'=> $fl,
            'q' => 'model:catalogs' .$q
        ]);
        
        if($response == '400'){
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'Error!',
            ], 500);
        } 
        return response()->json([
            'data' => $response["response"]["docs"],
            'page' => $page,
            'length' => $length,
            'total' => $response["response"]["numFound"],
        ], 200);
    }
}
