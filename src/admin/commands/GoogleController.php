<?php

namespace sitis\seo\google\indexer\admin\commands;

use Google\Exception;
use luya\console\Command;
use sitis\seo\google\indexer\admin\services\GoogleService;
use sitis\seo\google\indexer\admin\services\UrlsService;
use sitis\seo\google\indexer\admin\std\GoogleIndexerMethodEnum;
use sitis\seo\google\indexer\admin\std\Item;
use sitis\seo\google\indexer\models\ngrest\SeoGoogleIndexerLog;
use sitis\shop\core\entities\Shop\Brand;
use sitis\shop\core\entities\Shop\Category;
use sitis\shop\core\entities\Shop\FilterPage;
use sitis\shop\core\entities\Shop\interfaces\ConstProductStatusInterface;
use sitis\shop\core\entities\Shop\Product\Product;
use sitis\shop\core\entities\Shop\Product\queries\ProductQuery;
use sitis\shop\core\entities\Shop\queries\CategoryQuery;
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
use yii\db\ActiveQuery;

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
    )
    {
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

                $entityCounter++;
            }
        }

        (new GoogleService(['items' => $updatedUrls]))->request();

        $this->outputInfo('find ' . count($updatedUrls) . ' items on update from ' . $entityCounter . ' entities');
    }

    protected function getEntities(?array $website): array
    {
        $iteration = $this->getIteration();
        
        return [
            'categories' => [
                'query' => $this->getCategoriesByWebsiteId($website['id'])->exists() ? $this->getCategoriesByWebsiteId($website['id']) : false,
                'mapItemCallback' => function (Category $category) use ($iteration) {
                    return new Item(
                        $category->id,
                        $category->created_at,
                        $category->updated_at,
                        $category->getAbsoluteUrl(),
                        max($category->updated_at, $category->products_updated_at),
                        ($category->status == 1 ? GoogleIndexerMethodEnum::URL_UPDATED : GoogleIndexerMethodEnum::URL_DELETED),
                        Category::class,
                        'category',
                        $iteration
                    );
                },
            ],
            'filter-pages' => [
                'query' => $this->getByWebsiteIdFilterPages($website['id'])->exists() ? $this->getByWebsiteIdFilterPages($website['id']) : false,
                'mapItemCallback' => function (FilterPage $filterPage) use ($iteration) {
                    return new Item(
                        $filterPage->id,
                        $filterPage->created_at,
                        $filterPage->updated_at,
                        $filterPage->getAbsoluteUrl(),
                        $filterPage->updated_at,
                        ($filterPage->status == 1 ? GoogleIndexerMethodEnum::URL_UPDATED : GoogleIndexerMethodEnum::URL_DELETED),
                        FilterPage::class,
                        'filter-page',
                        $iteration
                    );
                },
            ],
            'brands' => [
                'query' => $this->getAllBrand()->exists() ? $this->getAllBrand() : false,
                'mapItemCallback' => function (Brand $brand) use ($iteration) {
                    return new Item(
                        $brand->id,
                        $brand->created_at,
                        $brand->updated_at,
                        $brand->getAbsoluteUrl(),
                        $brand->updated_at,
                        ($brand->status == 1 ? GoogleIndexerMethodEnum::URL_UPDATED : GoogleIndexerMethodEnum::URL_DELETED),
                        Brand::class,
                        'brand',
                        $iteration
                    );
                },
            ],
            'tags' => [
                'query' => $this->getAllTag()->exists() ? $this->getAllTag() : false,
                'mapItemCallback' => function (Tag $tag) use ($iteration) {
                    return new Item(
                        $tag->id,
                        $tag->created_at,
                        $tag->updated_at,
                        $tag->getAbsoluteUrl(),
                        $tag->updated_at,
                        ($tag->is_active == 1 ? GoogleIndexerMethodEnum::URL_UPDATED : GoogleIndexerMethodEnum::URL_DELETED),
                        Tag::class,
                        'tag',
                        $iteration
                    );
                },
            ],
            'products' => [
                'query' => $this->getAllQuery(),
                'mapItemCallback' => function (Product $product) use ($iteration) {
                    return new Item(
                        $product->id,
                        $product->created_at,
                        $product->updated_at,
                        $product->getAbsoluteUrl(),
                        $product->updated_at,
                        (($product->status == 1 && $product->is_not_available == 0) ? GoogleIndexerMethodEnum::URL_UPDATED : GoogleIndexerMethodEnum::URL_DELETED),
                        Product::class,
                        'product',
                        $iteration
                    );
                },
            ],
        ];
    }

    private function getAllQuery(): ProductQuery
    {
        return Product::find()->alias('p')->orderBy(['id' => SORT_ASC]);
    }

    private function getAllTag(): ActiveQuery
    {
        return Tag::find();
    }

    private function getAllBrand(): ActiveQuery
    {
        return Brand::find()
            ->alias('b')
            ->joinWith('products', false)
            ->andWhere(['is not', 'shop_products.category_id', null])
            ->groupBy('b.id');
    }

    public function getCategoriesByWebsiteId(int $websiteId): ?CategoryQuery
    {
        return Category::find()->where(['website_id' => $websiteId])->notRoot()->orderBy(['tree' => SORT_ASC, 'lft' => SORT_ASC]);
    }

    public function getByWebsiteIdFilterPages(int $websiteId): ?ActiveQuery
    {
        return FilterPage::find()->where(['website_id' => $websiteId]);
    }

    private function getIteration(): int
    {
        $iterations = SeoGoogleIndexerLog::find()->orderBy(['id' => SORT_DESC]);
        
        if($iterations->count() >= \Yii::$app->modules['seogoogleindexeradmin']->seoGoogleIndexerLimit){
            return $iterations->one()->iteration + 1;
        }

        return $iterations->one() ? $iterations->one()->iteration : 1;
    }
}