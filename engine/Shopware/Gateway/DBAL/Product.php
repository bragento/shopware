<?php

namespace Shopware\Gateway\DBAL;


use Shopware\Components\Model\ModelManager;
use Shopware\Gateway\DBAL\Hydrator as Hydrator;
use Shopware\Struct;

/**
 * Class Product gateway.
 *
 * @package Shopware\Gateway\DBAL
 */
class Product implements \Shopware\Gateway\Product
{
    /**
     * @var \Shopware\Gateway\DBAL\Hydrator\Product
     */
    private $hydrator;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $entityManager;

    /**
     * Contains the selection for the s_articles_attributes table.
     * This table contains dynamically columns.
     *
     * @var array
     */
    private $attributeFields = array();

    /**
     * @param $hydrator
     * @param ModelManager $entityManager
     */
    function __construct(ModelManager $entityManager, Hydrator\Product $hydrator)
    {
        $this->hydrator = $hydrator;
        $this->entityManager = $entityManager;
    }

    /**
     * The get function returns a full product struct.
     *
     * This function should only be used if all product data
     * are required, like on the article detail page.
     *
     * @param $number
     * @return Struct\Product
     */
    public function get($number)
    {
        // TODO: Implement get() method.
    }

    /**
     * Returns a list of ProductMini structs which can be used for listings
     * or sliders.
     *
     * A mini product contains only the minified product data.
     * The mini data contains data sources:
     *  - article
     *  - variant
     *  - unit
     *  - attribute
     *  - tax
     *  - manufacturer
     *  - price group
     *
     * @param array $numbers
     * @return Struct\ProductMini[]
     */
    public function getMinis(array $numbers)
    {
        $query = $this->entityManager->getDBALQueryBuilder();
        $query->select($this->getArticleFields())
            ->addSelect($this->getVariantFields())
            ->addSelect($this->getUnitFields())
            ->addSelect($this->getTaxFields())
            ->addSelect($this->getPriceGroupFields())
            ->addSelect($this->getManufacturerFields())
            ->addSelect($this->getTableFields('s_articles_attributes', 'attribute'))
            ->addSelect($this->getTableFields('s_articles_supplier_attributes', 'manufacturerAttribute'));

        $query->from('s_articles_details', 'variant')
            ->innerJoin('variant', 's_articles', 'product', 'product.id = variant.articleID')
            ->innerJoin('product', 's_core_tax', 'tax', 'tax.id = product.taxID')
            ->leftJoin('variant', 's_articles_attributes', 'attribute', 'attribute.articledetailsID = variant.id')
            ->leftJoin('variant', 's_core_units', 'unit', 'unit.id = variant.unitID')
            ->leftJoin('product', 's_articles_supplier', 'manufacturer', 'manufacturer.id = product.supplierID')
            ->leftJoin('product', 's_articles_supplier_attributes', 'manufacturerAttribute',  'manufacturerAttribute.id = product.supplierID')
            ->leftJoin('product', 's_core_pricegroups', 'priceGroup', 'priceGroup.id = product.pricegroupID')
            ->where('variant.ordernumber IN (:numbers)')
            ->setParameter(':numbers', implode(',', $numbers));

        /**@var $statement \Doctrine\DBAL\Driver\ResultStatement */
        $statement = $query->execute();

        $data = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $products = array();
        foreach($data as $product) {
            $products[] = $this->hydrator->hydrateMini($product);
        }

        return $products;
    }

    /**
     * Returns a single of ProductMini struct which can be used for listings
     * or sliders.
     *
     * A mini product contains only the minified product data.
     * The mini data contains data sources:
     *  - article
     *  - variant
     *  - unit
     *  - attribute
     *  - tax
     *  - manufacturer
     *  - price group
     *
     * @param $number
     * @return Struct\ProductMini
     */
    public function getMini($number)
    {
        $products = $this->getMinis(array($number));

        return array_shift($products);
    }

    /**
     * Defines which s_articles fields should be selected.
     * @return array
     */
    private function getArticleFields()
    {
        return array(
            'product.id',
            'product.supplierID',
            'product.name',
            'product.description',
            'product.description_long',
            'product.shippingtime',
            'product.datum',
            'product.active',
            'product.taxID',
            'product.pseudosales',
            'product.topseller',
            'product.metaTitle',
            'product.keywords',
            'product.changetime',
            'product.pricegroupID',
            'product.pricegroupActive',
            'product.filtergroupID',
            'product.laststock',
            'product.crossbundlelook',
            'product.notification',
            'product.template',
            'product.mode',
            'product.main_detail_id',
            'product.available_from',
            'product.available_to',
            'product.configurator_set_id'
        );
    }

    /**
     * Defines which s_articles_details fields should be selected.
     * @return array
     */
    private function getVariantFields()
    {
        return array(
            'variant.id as variantId',
            'variant.ordernumber',
            'variant.suppliernumber',
            'variant.kind',
            'variant.additionaltext',
            'variant.impressions',
            'variant.sales',
            'variant.active',
            'variant.instock',
            'variant.stockmin',
            'variant.weight',
            'variant.position',
            'variant.width',
            'variant.height',
            'variant.length',
            'variant.ean',
            'variant.unitID',
            'variant.purchasesteps',
            'variant.maxpurchase',
            'variant.minpurchase',
            'variant.purchaseunit',
            'variant.referenceunit',
            'variant.packunit',
            'variant.releasedate',
            'variant.shippingfree',
            'variant.shippingtime'
        );
    }

    /**
     * Defines which s_core_units fields should be selected
     * @return array
     */
    private function getUnitFields()
    {
        return array(
            'unit.id as __unit_id',
            'unit.unit as __unit_unit',
            'unit.description as __unit_description'
        );
    }

    /**
     * Defines which s_core_tax fields should be selected
     * @return array
     */
    private function getTaxFields()
    {
        return array(
            'tax.id as __tax_id',
            'tax.tax as __tax_tax',
            'tax.description as __tax_description'
        );
    }

    /**
     * Defines which s_core_pricegroups fields should be selected
     * @return array
     */
    private function getPriceGroupFields()
    {
        return array(
            'priceGroup.id as __priceGroup_id',
            'priceGroup.description as __priceGroup_description'
        );
    }

    /**
     * Defines which s_articles_suppliers fields should be selected
     * @return array
     */
    private function getManufacturerFields()
    {
        return array(
            'manufacturer.id as __manufacturer_id',
            'manufacturer.name as __manufacturer_name',
            'manufacturer.img as __manufacturer_img',
            'manufacturer.link as __manufacturer_link',
            'manufacturer.description as __manufacturer_description',
            'manufacturer.meta_title as __manufacturer_meta_title',
            'manufacturer.meta_description as __manufacturer_description',
            'manufacturer.meta_keywords as __manufacturer_keywords'
        );
    }

    /**
     * Helper function which generates an array with table column selections
     * for the passed table.
     *
     * @param $table
     * @param $alias
     * @return array
     */
    private function getTableFields($table, $alias)
    {
        $key = $table . '_' . $alias;

        if ($this->attributeFields[$key] !== null) {
            return $this->attributeFields[$key];
        }

        $schemaManager = $this->entityManager->getConnection()->getSchemaManager();

        $tableColumns = $schemaManager->listTableColumns($table);
        $columns = array();

        foreach ($tableColumns as $column) {
            $columns[] = $alias . '.' . $column->getName() . ' as __' . $alias . '_' . $column->getName();
        }

        $this->attributeFields[$key] = $columns;

        return $this->attributeFields[$key];
    }

}