<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RFMService
{
    //全てのテーブルを結合し、期間指定して一部のレコードのみに絞ったサブクエリと
    //formから送信されたrfm分析のランク付けとなる基準数字
    //を引数として受け取り、以下の３つの戻り値を返す関数。

    // ・rとfのそれぞれの人数 r1 r2 r3 r4 r5 f1 f2 f3 f4 f5のマトリックス。つまり5*5。
    //  ・購入者の総人数
    //  ・どのランクのどのレベルに何人ずついるか、
    public static function rfm($subQuery, $rfmPrms)
    {
        //RFM分析
        //1.購買IDごとにまとめる。
        $subQuery = $subQuery
            ->groupBy('id')
            ->selectRaw('id,customer_id,customer_name,SUM(subtotal) as totalPerPurchase,created_at');

        $subQuery = DB::table($subQuery)
            ->groupBy('customer_id')
            ->selectRaw('customer_id,customer_name,max(created_at) as recentDate,datediff(now(),max(created_at)) as recency,
        count(customer_id) as frequency,sum(totalPerPurchase) as monetary'); //全てのテーブルを連結し、それを購買idごとにレコードをまとめた

        // dd($subQuery);

        //4.会員ごとのRFMランクを計算
        // $rfmPrms = [
        //     14, 28, 60, 90, 7, 5, 3, 2, 300000, 200000, 100000, 30000
        // ];

        //4.会員ごとのRFMランクを計算
        $subQuery = DB::table($subQuery)
            ->selectRaw('customer_id,customer_name,recentDate,recency,frequency,monetary,
        case
            when recency < ? then 5
            when recency < ? then 4
            when recency < ? then 3
            when recency < ? then 2
            else 1 end as r,
        case
            when ? <= frequency  then 5
            when ? <= frequency  then 4
            when ? <= frequency  then 3
            when ? <= frequency then 2
            else 1 end as f,
        case
            when ? <= monetary  then 5
            when ? <= monetary  then 4
            when ? <= monetary  then 3
            when  ? <= monetary  then 2
            else 1 end as m
        ', $rfmPrms); //各々の会員のRFMのそれぞれ３つのランクを確定し、カラムとして会員のレコードに追加。RFMのそれぞれのランクを確定するための基準値はユーザーによってformから送信されたもの。

        // dd($subQuery);

        //5.ランク毎の数を計算する
        /**
         *
         *
         * 購買者の総数。
         *   */

        $totals = DB::table($subQuery)->count(); //購買者の総数。
        // $rCount = DB::table($subQuery)->groupBy('r')->selectRaw('r,count(r)')->orderBy('r', 'desc')->pluck('count(r)'); //購入者をrランクごとにまとめて、rランクについてそれぞれのレベルごとに何人の購入者が存在するかをのデータを取得し、ランクの高い順に並べ、その人数だけを抽出した配列。
        $rCount = DB::table($subQuery)->groupBy('r')->selectRaw('r,count(r) as cr')->get();


        // Log::debug($rCount);
        $rCount->each(function($item,$index){
            // Log::debug($item);
            Log::debug($index);
        });
        die;


        //[
        //    [5ランク->６人],
        //    [4ランク->3人],
        //    [3ランク->4人],
        //    [2ランク->4人],
        //    [1ランク->3人],
        // ]

        $fCount = DB::table($subQuery)->groupBy('f')->selectRaw('f,count(f)')->orderBy('f', 'desc')->pluck('count(f)');//購入者をfランクごとにまとめて、fランクについてそれぞれのレベルごとに何人の購入者が存在するかをのデータを取得し、ランクの高い順に並べ、その人数だけを抽出した配列。



        $mCount = DB::table($subQuery)->groupBy('m')->selectRaw('m,count(m)')->orderBy('m', 'desc')->pluck('count(m)'); //購入者をmランクごとにまとめて、mランクについてそれぞれのレベルごとに何人の購入者が存在するかをのデータを取得し、ランクの高い順に並べ、その人数だけを抽出した配列。



        $eachCount = []; //Vue側に渡す用の空の配列。 最終的に['rank'=>5,'r'=>r分析についてレベル5の購入者の人数、'f'=>f分析についてレベル5の購入者の人数,'m'=>m分析についてレベル5の購入者の人数...]と続く。


        $rank = 5; //初期値 5

        for ($i = 0; $i < 5; $i++) {
            $eachCount[] = [
                'rank' => $rank,
                'r' => $rCount[$i],
                'f' => $fCount[$i],
                'm' => $mCount[$i]
            ];
            $rank--; //rankを一つずつ減らす。
        }
        Log::info($eachCount);


        // dd($total,$eachCount, $rCount,$fCount,$mCount);





        $data = DB::table($subQuery)->groupBy('r')->selectRaw('concat("r_",r) as rRank,
        count(case when f=5 then 1 end) as f_5,
        count(case when f=4 then 1 end) as f_4,
        count(case when f=3 then 1 end) as f_3,
        count(case when f=2 then 1 end) as f_2,
        count(case when f=1 then 1 end) as f_1
        ')->orderBy('rRank', 'desc')->get();

        // dd($data);
        // ['r_5','r_5かつfが5の購入者の人数','r_5かつfが4の購入者の人数'...]
        // ['r_4','r_4かつfが5の購入者の人数','r_5かつfが4の購入者の人数'...]
        // ...
        Log::info("津々浦々");
        return [$data, $totals, $eachCount];
    }
    //全てのテーブルを連結したテーブルのレコードを購入idごとにまとめる。
}





// $$subtotalというカラムのエイリアスは購入した商品の金額(itemsテーブルにあるカラム)とitemsテーブルとpurchasesテーブルをつなげる
//中間テーブルに存在するquantityカラムの積。

//$$totalPerPurchaseというカラムのエイリアスは購買ごとにレコードをまとめ、その購買での合計金額を出したもの。
//つまり、$$subtotalの合計。

//$$recentDateというカラムのエイリアスは購入者ごとにレコードをまとめたもので、その購入者が最後に商品を買った日付を表す。

//$$recencyというカラムのエイリアスは、その購入者が最後に商品を購入した日と現在日時の差分を表す。つまり最後に商品を購入してから何日経過したかを表す。

//$$frequencyというカラムのエイリアスはその購入者が何回購入をしたかを表す。

//$$monetaryというカラムのエイリアスはその購入者がこれまでの購入金額の総額。

//$$rというカラムのエイリアスは当該購入者のrランクの値が入るカラムのエイリアス。

//$$fというカラムのエイリアスは当該購入者のfランクの値が入るカラムのエイリアス。

//$$mというカラムのエイリアスは当該購入者のmランクの値が入るカラムのエイリアス。