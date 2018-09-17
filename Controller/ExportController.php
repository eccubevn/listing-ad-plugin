<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ListingAdCsv\Controller;

use Eccube\Controller\AbstractController;
use Plugin\ListingAdCsv\Service\ListingAdDataCreator\ListingAdDataCreatorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    /**
     * CSV出力の共通処理
     *
     * @param Request $request
     * @param string $type CSV出力形式の種類
     *
     * @return StreamedResponse
     *
     * @Route("/%eccube_admin_route%/listing_ad_csv/export/{type}", name="ListingAdCsv_export")
     */
    public function export(Request $request, $type, ListingAdDataCreatorService $adDataCreatorService)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request, $type, $adDataCreatorService) {
            $adDataCreatorService->create($request, $type);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$this->createFileName($type));
        $response->send();

        return $response;
    }

    /**
     * 現在時刻から出力ファイル名を生成
     *
     * @param $type
     *
     * @return string
     */
    private function createFileName($type)
    {
        $now = new \DateTime();
        $filename = 'listing_ad_'.$type.$now->format('_YmdHis').'.csv';

        return $filename;
    }
}
