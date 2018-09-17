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

namespace Plugin\ListingAdCsv\Service\ListingAdDataCreator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Product;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\ProductRepository;
use Eccube\Util\FormUtil;
use Plugin\ListingAdCsv\Service\CsvExport\CsvExportService;
use Plugin\ListingAdCsv\Service\ListingAdDataCreator\Campaign\ProductNameCampaign;
use Plugin\ListingAdCsv\Service\ListingAdDataCreator\Rows\Google\GoogleRowCreator;
use Plugin\ListingAdCsv\Service\ListingAdDataCreator\Rows\RowCreatorInterface;
use Plugin\ListingAdCsv\Service\ListingAdDataCreator\Rows\Yahoo\YahooRowCreator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class ListingAdDataCreatorService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CsvExportService
     */
    private $listingAdExport;

    /**
     * @var ProductNameCampaign
     */
    private $productNameCampaign;

    /**
     * ListingAdDataCreatorService constructor.
     *
     * @param EntityManagerInterface $em
     * @param FormFactoryInterface $formFactory
     * @param ProductRepository $productRepository
     * @param CsvExportService $listingAdExport
     * @param ProductNameCampaign $productNameCampaign
     */
    public function __construct(EntityManagerInterface $em, FormFactoryInterface $formFactory, ProductRepository $productRepository, CsvExportService $listingAdExport, ProductNameCampaign $productNameCampaign)
    {
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->productRepository = $productRepository;
        $this->listingAdExport = $listingAdExport;
        $this->productNameCampaign = $productNameCampaign;
    }

    /**
     * リスティング広告データを生成する
     *
     * @param Request $request
     * @param string $type
     */
    public function create(Request $request, $type)
    {
        // 商品データ検索用のクエリビルダを取得
        $this->disableSQLLogger();
        $query = $this->getFilteredProductsQuery($request);
        $products = $query->getResult();

        // 出力
        $creator = $this->getCreator($type);
        $this->exportHeaderData($creator);
        $this->exportProductNameCampaign($creator, $products);

        // メモリの解放
        foreach ($products as $product) {
            $this->em->detach($product);
            $this->em->clear();
            $query->free();
            $this->em->flush();
        }
    }

    /**
     * @param string $type
     *
     * @return RowCreatorInterface
     */
    private function getCreator($type)
    {
        switch ($type) {
            case 'google':
                return new GoogleRowCreator();
            case 'yahoo':
                return new YahooRowCreator();
            default:
                return new GoogleRowCreator();
        }
    }

    /**
     * sql loggerを無効にする
     */
    private function disableSQLLogger()
    {
        $this->em->getConfiguration()->setSQLLogger(null);
    }

    /**
     * 商品データ検索用のクエリビルダを取得
     *
     * クエリの結果を商品マスターの検索結果と一致させたいので、
     * ProductControllerクラスのexport関数と処理を合わせている。
     *
     * @param Request $request
     *
     * @return \Doctrine\ORM\Query
     */
    private function getFilteredProductsQuery(Request $request)
    {
        $qb = $this->getProductQueryBuilder($request);
        $qb->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select('p')
            ->orderBy('p.update_date', 'DESC')
            ->distinct();

        return $qb->getQuery();
    }

    /**
     * 商品検索用のクエリビルダを返す.
     *
     * @param Request $request
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchProductType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.product.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // 商品データのクエリビルダを構築.
        $qb = $this->productRepository
            ->getQueryBuilderBySearchDataForAdmin($searchData);

        return $qb;
    }

    /**
     * セッションでシリアライズされた Doctrine のオブジェクトを取得し直す.
     *
     * ※特定の検索条件で受注CSVダウンロードをするとシステムエラー #1384 の不具合対応のため、
     * 　本体の修正コードを移植する。
     *
     * XXX self::setExportQueryBuilder() をコールする前に EntityManager を取得したいので、引数で渡している
     *
     * @param array $searchData セッションから取得した検索条件の配列
     * @param EntityManager $em
     */
    protected function findDeserializeObjects(array &$searchData, $em)
    {
        foreach ($searchData as &$Conditions) {
            if ($Conditions instanceof ArrayCollection) {
                $Conditions = new ArrayCollection(
                    array_map(
                        function ($Entity) use ($em) {
                            return $em->getRepository(get_class($Entity))->find($Entity->getId());
                        }, $Conditions->toArray()
                    )
                );
            } elseif ($Conditions instanceof \Eccube\Entity\AbstractEntity) {
                $Conditions = $em->getRepository(get_class($Conditions))->find($Conditions->getId());
            }
        }
    }

    /**
     * ヘッダーを生成して出力
     *
     * @param RowCreatorInterface $creator
     */
    private function exportHeaderData(RowCreatorInterface $creator)
    {
        $header = $creator->GetHeaderRow();
        $this->listingAdExport->exportData($header);
    }

    /**
     * 商品キャンペーンを生成して出力
     *
     * @param RowCreatorInterface $creator
     * @param Product[] $products
     */
    private function exportProductNameCampaign(RowCreatorInterface $creator, $products)
    {
        $campaign = $this->productNameCampaign;
        $campaign->buildProduct($products);
        $rows = $creator->GetRows($campaign);
        foreach ($rows as $row) {
            $this->listingAdExport->exportData($row);
        }
    }
}
