<?php

namespace sitis\seo\google\indexer\admin\commands;

use Google\Exception;
use luya\console\Command;
use sitis\seo\google\indexer\admin\services\GoogleService;
use sitis\seo\google\indexer\admin\services\UrlsService;
use sitis\shop\core\entities\Shop\Brand;
use sitis\shop\core\entities\Shop\Category;
use sitis\shop\core\entities\Shop\FilterPage;
use sitis\shop\core\entities\Shop\Product\Product;
use sitis\shop\core\entities\Shop\Tag;
use sitis\shop\core\readModels\Shop\BrandReadRepository;
use sitis\shop\core\readModels\Shop\CategoryReadRepository;
use sitis\shop\core\readModels\Shop\FilterPageReadRepository;
use sitis\shop\core\readModels\Shop\ProductReadRepository;
use sitis\shop\core\readModels\Shop\TagReadRepository;
use sitis\shop\core\readModels\Shop\WebsiteReadRepository;
use sitis\shop\core\services\sitemap\MapItem;
use sitis\shop\core\traits\ShopModuleTrait;
use yii\base\InvalidConfigException;
use Yii;

class GoogleController extends Command
{
    use ShopModuleTrait;

    public $defaultAction = 'start-index';

    private WebsiteReadRepository $websiteReadRepository;
    private CategoryReadRepository $shopCategories;
    private FilterPageReadRepository $shopFilterPages;
    private BrandReadRepository $brands;
    private TagReadRepository $tags;
    private ProductReadRepository $products;
    private UrlsService $urlsService;

    public function __construct(
        $id,
        $module,
        WebsiteReadRepository $websiteReadRepository,
        CategoryReadRepository $shopCategories,
        ProductReadRepository $products,
        FilterPageReadRepository $shopFilterPages,
        BrandReadRepository $brands,
        TagReadRepository $tags,
        UrlsService $urlsService,
        $config = []
    ){
        $this->shopCategories = $shopCategories;
        $this->shopFilterPages = $shopFilterPages;
        $this->products = $products;
        $this->brands = $brands;
        $this->tags = $tags;
        $this->websiteReadRepository = $websiteReadRepository;
        $this->urlsService = $urlsService;
        parent::__construct($id, $module, $config);
    }

    public function init()
    {
        Yii::$app->urlManager->setBaseUrl('');
        Yii::$app->urlManager->setHostInfo('{baseUrl}');

        parent::init();
    }

    /**
     * @return void
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionGenerate(): void
    {
        $websites = $this->websiteReadRepository->getAllWebsites();
        $updatedUrls = [];

        $entityCounter = 0;

        foreach ($websites as $website) {
            $entities = $this->getEntities($website);

            foreach ($entities as $key => $entity) {
                if (!isset($entity['query']) || !isset($entity['mapItemCallback'])) {
                    throw new InvalidConfigException('Entity must contain "query", "mapItemCallback" config keys.');
                }

                $query = is_callable($entity['query']) ? call_user_func($entity['query']) : $entity['query'];
                $mapItemCallback = $entity['mapItemCallback'];

                if (!$query) {
                    $this->outputInfo(sprintf('Entity "%s" has no query and was skipped.', $key));
                    $entityCounter++;
                    continue;
                }

                $updatedUrls = array_merge($updatedUrls, $this->urlsService->getUpdatedUrls($key, $query, $mapItemCallback));

                $entityCounter ++;
            }
        }

        (new GoogleService(['items' => $updatedUrls]))->request();

        $this->outputInfo('find '. count($updatedUrls). ' items on update from ' . $entityCounter . ' entities');
    }

    protected function getEntities(?array $website): array
    {
        return [
            'categories' => [
                'query' => $this->shopCategories->getByWebsiteId($website['id']),
                'mapItemCallback' => function (Category $category) {
                    return new MapItem(
                        $category->getAbsoluteUrl(),
                        max($category->updated_at, $category->products_updated_at),
                        MapItem::WEEKLY,
                        0.6
                    );
                },
            ],
            'filter-pages' => [
                'query' => $this->shopFilterPages->checkExistByWebsiteId($website['id']) ? $this->shopFilterPages->getByWebsiteIdActiveQuery($website['id']) : false,
                'mapItemCallback' => function (FilterPage $filterPage) {
                    return new MapItem(
                        $filterPage->getAbsoluteUrl(),
                        $filterPage->updated_at,
                        MapItem::WEEKLY,
                        0.6
                    );
                },
            ],
            'brands' => [
                'query' => $this->brands->checkExist() ? $this->brands->getAllActiveQuery() : false,
                'mapItemCallback' => function (Brand $brand) {
                    return new MapItem(
                        $brand->getAbsoluteUrl(),
                        $brand->updated_at,
                        MapItem::WEEKLY,
                        0.6
                    );
                },
            ],
            'tags' => [
                'query' => $this->tags->getAllActiveQuery()->exists() ? $this->tags->getAllActiveQuery() : false,
                'mapItemCallback' => function (Tag $tag) {
                    return new MapItem(
                        $tag->getAbsoluteUrl(),
                        $tag->updated_at,
                        MapItem::WEEKLY,
                        0.6
                    );
                },
            ],
            'products' => [
                'query' => $this->products->getAllQuery(),
                'mapItemCallback' => function (Product $product) {
                    return new MapItem(
                        $product->getAbsoluteUrl(),
                        $product->updated_at,
                        MapItem::DAILY,
                        0.6
                    );
                },
            ],
        ];
    }
}