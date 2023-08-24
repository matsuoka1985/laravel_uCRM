<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AnalysisService;
use App\Services\DecileService;
use App\Services\RFMService;

class AnalysisController extends Controller
{
    public function index(Request $request){
        $subQuery=Order::betweenDate($request->startDate,$request->endDate); //購入された商品についての詳細情報が入っている。

        if($request->type==='perDay'){
            list($data, $labels, $totals) = AnalysisService::perDay($subQuery);
        }

        if($request->type==='perMonth'){
            list($data,$labels,$totals)=AnalysisService::perMonth($subQuery);
        }

        if($request->type==="perYear"){
            list($data,$labels,$totals)=AnalysisService::perYear($subQuery);
        }
        if($request->type==='decile'){
            list($data,$labels,$totals)=DecileService::decile($subQuery);
        }
        if($request->type==='rfm'){
            list($data,$totals,$eachCount)=RFMService::rfm($subQuery,$request->rfmPrms);

            return response()->json([
               'data'=>$data, //rとfの5*5のマトリックス
               'type'=>$request->type, //formから送信されたform.typeを加工せずそのまま返却。
               'eachCount'=>$eachCount, //rfmそれぞれの5レベルについて何人いるのか
               'totals'=>$totals //購入者の総人数。
            ]);
        }




        return response()->json([
            'data'=>$data,
            'type'=>$request->type,
            'labels'=>$labels,
            'totals'=>$totals
        ],Response::HTTP_OK );

    }
}