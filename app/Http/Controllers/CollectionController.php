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
                publish_location,deskripsi_fisik,subject,ddc,catatan_isi,cover_utama,call_number,language_code,language_name,
                aksara,list_entri_tambahan_nama_tak_terkendali,worksheet_name,konten_digital_count,create_date,last_update_date";       
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
        if($response["response"]['numFound']== '0'){ //data katalog tidak ditemukan
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'ID ' . $id . ' not found!',
            ], 500);
        } 
        $sql_catalog_ruas = "SELECT tag, indicator1, indicator2, sequence, value FROM catalog_ruas WHERE CATALOGID='$id' ORDER BY sequence ";
        $catalog_ruas = Http::post(config('app.internal_api_url') . "?token=" . config('app.internal_api_token') . "&op=getlistraw&sql=" . urlencode($sql_catalog_ruas));
        $catalog_ruas_new = [];
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
            publish_location,deskripsi_fisik,subject,ddc,catatan_isi,cover_utama,call_number,language_code,language_name,
            aksara,list_entri_tambahan_nama_tak_terkendali,worksheet_name,konten_digital_count,create_date,last_update_date";  
        $q = "";
        $query = [];
        if($request->input('title')){
            $count = str_word_count($request->input('title'));
            if($count > 1) {
                $q .= ' AND title_text:"' .$request->input('title'). '"';
            } else {
                $q .= " AND title_text:*" .$request->input('title'). "*";
            }
            array_push($query, [
                "field" => "title",
                "value" => $request->input('title')
            ]);
        }
        if($request->input('worksheet_name')){
            $q .= ' AND worksheet_name:"'.trim($request->input('worksheet_name')).'"';
            array_push($query, [
                "field" => "worksheet_name",
                "value" => $request->input('worksheet_name')
            ]);
        }
        if($request->input('aksara')){
            $q .= ' AND aksara:"' .$request->input('aksara'). '"';
            array_push($query, [
                "field" => "aksara",
                "value" => $request->input('aksara')
            ]);
        }
        if($request->input('call_number')){
            $q .= ' AND (call_number_text:"' .$request->input('call_number'). '" OR ddc:*' .$request->input('call_number'). '*)';
            array_push($query, [
                "field" => "call_number",
                "value" => $request->input('call_number')
            ]);
        }
        if($request->input('language_name')){
            $q .= ' AND language_name:"'.trim($request->input('language_name')).'"';
            array_push($query, [
                "field" => "language_name",
                "value" => $request->input('language_name')
            ]);
        }
        if($request->input('list_entri_tambahan_nama_tak_terkendali')){
            $q .= ' AND list_entri_tambahan_nama_tak_terkendali:*'.trim($request->input('list_entri_tambahan_nama_tak_terkendali')).'*';
            array_push($query, [
                "field" => "list_entri_tambahan_nama_tak_terkendali",
                "value" => $request->input('list_entri_tambahan_nama_tak_terkendali')
            ]);
        }
        if($request->input('bib_id')){
            $q .= ' AND bib_id:'.trim($request->input('bib_id'));
            array_push($query, [
                "field" => "bib_id",
                "value" => $request->input('bib_id')
            ]);
        }
        if($request->input('author')){
            $q .= " AND author_text:*".$request->input('author')."*";
            array_push($query, [
                "field" => "author",
                "value" => $request->input('author')
            ]);
        }
        if($request->input('publisher')){
            $q .= " AND publisher_text:*".$request->input('publisher')."*";
            array_push($query, [
                "field" => "publisher",
                "value" => $request->input('publisher')
            ]);
        }
        if($request->input('publish_year')){
            $q .= " AND publish_year:".$request->input('publish_year');
            array_push($query, [
                "field" => "publish_year",
                "value" => $request->input('publish_year')
            ]);
        }
        if($request->input('catatan_isi')){
            $q .= " AND catatan_isi:*".$request->input('catatan_isi')."*";
            array_push($query, [
                "field" => "catatan_isi",
                "value" => $request->input('catatan_isi')
            ]);
        }
        if($request->input('subject')){
            $q .= " AND subject_text:*".$request->input('subject')."*";
            array_push($query, [
                "field" => "subject",
                "value" => $request->input('subject')
            ]);
        }
        if($request->input('konten_digital_count')){
            if($request->input('konten_digital_count') == '0'){
                $q .= " AND konten_digital_count:0";
            } else {
                $q .= " AND konten_digital_count:[0 TO *]";
            }
            array_push($query, [
                "field" => "konten_digital_count",
                "value" => $request->input('konten_digital_count')
            ]);
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
            array_push($query, [
                "field" => "year_start",
                "value" => $request->input('year_start')
            ]);
            array_push($query, [
                "field" => "year_end",
                "value" => $request->input('year_end')
            ]);
        }
        if($request->input('year')){
            $q .= " AND publish_year:".$request->input('year');
            array_push($query, [
                "field" => "year",
                "value" => $request->input('year')
            ]);
        }
        $response = kurl_solr([
            'fl'=> $fl,
            'q' => 'model:catalogs' .$q,
            'rows' => $length,
            'start' => $start,
        ]);
        
        if($response == '400'){
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'Error!',
            ], 500);
        } 
        return response()->json([
            'data' => $response["response"]["docs"],
            'page' => intval($page),
            'length' => intval($length),
            'total' => $response["response"]["numFound"],
            "time" => $response["responseHeader"]["QTime"],
            'query' => $query
        ], 200);
    }

    function getStatistic(Request $request)
    {
        $q = "";
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
        $json_facet = json_encode([
            'subject' => [
                'type' => 'terms',
                'field'=> 'list_subjek_topik',
                'limit' => 10
            ],
            'worksheet_name' => [
                'type' => 'terms',
                'field'=> 'worksheet_name',
                'limit' => 20
            ],
            'language_name' => [
                'type' => 'terms',
                'field'=> 'list_language_name',
                'limit' => 10
            ],
            'aksara' => [
                'type' => 'terms',
                'field'=> 'list_aksara',
                'limit' => 10
            ],
            'author' => [
                'type' => 'terms',
                'field'=> 'list_author',
                'limit' => 10
            ],
            ]);
        $response = kurl_solr([
            'rows'=> 0,
            'q' => 'model:catalogs' . $q,
            'json.facet' => $json_facet,
            'stats' => 'true',
            'stats.facet' => 'worksheet_name',
            'stats.field' => 'konten_digital_count'
        ]);
        if($response == '400'){
            return response()->json([
                    'status' => 'Failed',
                    'message' => 'Error!',
            ], 500);
        } 
        $worksheet_name_stats = $response["stats"]["stats_fields"]["konten_digital_count"]["facets"]["worksheet_name"];
        $worksheets = [];
        foreach($worksheet_name_stats as $key=>$val){
            $worksheets[$key] = [
                "total_koleksi" => $val["count"],
                "total_konten_digital" => $val["sum"]
            ];
        }
        return response()->json([
            'total' => $response["response"]["numFound"],
            'subject' => $response["facets"]["subject"]["buckets"],
            'worksheet_name' => $response["facets"]["worksheet_name"]["buckets"],
            'language_name' => $response["facets"]["language_name"]["buckets"],
            'aksara' => $response["facets"]["aksara"]["buckets"],
            'author' => $response["facets"]["author"]["buckets"],
            'worksheets_name_konten_digital' => $worksheets
        ], 200);
    }

}
