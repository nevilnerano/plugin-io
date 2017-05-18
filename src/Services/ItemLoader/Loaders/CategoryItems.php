<?php

namespace IO\Services\ItemLoader\Loaders;

use IO\Services\SessionStorageService;
use IO\Services\ItemLoader\Contracts\ItemLoaderContract;
use IO\Services\ItemLoader\Contracts\ItemLoaderPaginationContract;
use IO\Services\ItemLoader\Contracts\ItemLoaderSortingContract;
use IO\Builder\Sorting\SortingBuilder;
use IO\Services\TemplateConfigService;
use Plenty\Modules\Cloud\ElasticSearch\Lib\ElasticSearch;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Processor\DocumentProcessor;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Query\Type\TypeInterface;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Search\Document\DocumentSearch;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Search\SearchInterface;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Source\Mutator\BuiltIn\LanguageMutator;
use Plenty\Modules\Item\Search\Filter\CategoryFilter;
use Plenty\Modules\Item\Search\Filter\ClientFilter;
use Plenty\Modules\Item\Search\Filter\VariationBaseFilter;
use Plenty\Modules\Item\Search\Filter\TextFilter;
use Plenty\Plugin\Application;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Sorting\SortingInterface;

/**
 * Created by ptopczewski, 09.01.17 11:15
 * Class CategoryItems
 * @package IO\Services\ItemLoader\Loaders
 */
class CategoryItems implements ItemLoaderContract, ItemLoaderPaginationContract, ItemLoaderSortingContract
{
	/**
	 * @return SearchInterface
	 */
	public function getSearch()
	{
        $languageMutator = pluginApp(LanguageMutator::class, ["languages" => [pluginApp(SessionStorageService::class)->getLang()]]);

        $documentProcessor = pluginApp(DocumentProcessor::class);
        $documentProcessor->addMutator($languageMutator);

		return pluginApp(DocumentSearch::class, [$documentProcessor]);
	}
    
    /**
     * @return array
     */
    public function getAggregations()
    {
        return [];
    }

	/**
	 * @param array $options
	 * @return TypeInterface[]
	 */
	public function getFilterStack($options = [])
	{
		/** @var ClientFilter $clientFilter */
		$clientFilter = pluginApp(ClientFilter::class);
		$clientFilter->isVisibleForClient(pluginApp(Application::class)->getPlentyId());

		/** @var VariationBaseFilter $variationFilter */
		$variationFilter = pluginApp(VariationBaseFilter::class);
		$variationFilter->isActive();
		
		if(isset($options['variationShowType']) && $options['variationShowType'] == 'main')
        {
            $variationFilter->isMain();
        }
        elseif(isset($options['variationShowType']) && $options['variationShowType'] == 'child')
        {
            $variationFilter->isChild();
        }
        
		/** @var CategoryFilter $categoryFilter */
		$categoryFilter = pluginApp(CategoryFilter::class);
		$categoryFilter->isInCategory($options['categoryId']);
        
        /**
         * @var TemplateConfigService $templateConfigService
         */
		$templateConfigService = pluginApp(TemplateConfigService::class);
		$usedItemName = $templateConfigService->get('item.name');
        
        $textFilterType = TextFilter::FILTER_ANY_NAME;
		if(strlen($usedItemName))
        {
            if($usedItemName == 'name1')
            {
                $textFilterType = TextFilter::FILTER_NAME_1;
            }
            elseif($usedItemName == 'name2')
            {
                $textFilterType = TextFilter::FILTER_NAME_2;
            }
            elseif($usedItemName == 'name3')
            {
                $textFilterType = TextFilter::FILTER_NAME_3;
            }
        }
        
        /**
         * @var TextFilter $textFilter
         */
        $textFilter = pluginApp(TextFilter::class);
        $textFilter->hasNameInLanguage(pluginApp(SessionStorageService::class)->getLang(), $textFilterType);
        
        return [
            $clientFilter,
            $variationFilter,
            $categoryFilter,
            $textFilter
        ];
	}
	
	/**
	 * @param array $options
	 * @return int
	 */
	public function getCurrentPage($options = [])
	{
		return (INT)$options['page'];
	}

	/**
	 * @param array $options
	 * @return int
	 */
	public function getItemsPerPage($options = [])
	{
		return (INT)$options['items'];
	}
	
	public function getSorting($options = [])
    {
        $sortingInterface = null;
        
        if(isset($options['sorting']) && strlen($options['sorting']))
        {
            $sortingInterface = SortingBuilder::buildSorting($options['sorting']);
        }
       
        return $sortingInterface;
    }
}