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

namespace Plugin\ListingAdCsv\Service\CsvExport;

use Eccube\Common\EccubeConfig;

class CsvExportService
{
    /**
     * @var
     */
    protected $fp;

    /**
     * @var
     */
    protected $closed = false;

    /**
     * @var \Closure
     */
    protected $convertEncodingCallBack;

    /**
     * @var string CSV出力エンコード
     */
    private $output_encode;

    /**
     * CsvExportService constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param $output_encode
     */
    public function __construct(EccubeConfig $eccubeConfig, $output_encode = null)
    {
        if (is_null($output_encode)) {
            $output_encode = $eccubeConfig->get('eccube_csv_export_encoding');
        }

        $this->output_encode = $output_encode;
    }

    /**
     * @param array $row 出力するデータの配列
     */
    public function exportData(array $row)
    {
        $this->fopen();
        $this->fputcsv($row);
        $this->fclose();
    }

    /**
     * 文字エンコーディングの変換を行うコールバック関数を返す.
     *
     * @return \Closure
     */
    private function getConvertEncodingCallback()
    {
        $encode = $this->output_encode;

        return function ($value) use ($encode) {
            return mb_convert_encoding(
                (string) $value, $encode, 'UTF-8'
            );
        };
    }

    private function fopen()
    {
        if (is_null($this->fp) || $this->closed) {
            $this->fp = fopen('php://output', 'w');
        }
    }

    private function fputcsv($row)
    {
        if (is_null($this->convertEncodingCallBack)) {
            $this->convertEncodingCallBack = $this->getConvertEncodingCallback();
        }

        fputcsv($this->fp, array_map($this->convertEncodingCallBack, $row), ',');
    }

    private function fclose()
    {
        if (!$this->closed) {
            fclose($this->fp);
            $this->closed = true;
        }
    }
}
