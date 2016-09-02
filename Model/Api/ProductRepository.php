<?php
namespace Styla\Connect2\Model\Api;

use \Styla\Connect2\Model\Api\Converter as DataConverter;

class ProductRepository extends \Magento\Catalog\Model\ProductRepository
{
    const DEFAULT_PAGE_SIZE = 46; //if no limit provided, this will be used
    
    /**
     *
     * @var \Styla\Connect2\Api\Data\StylaProductSearchResultsInterfaceFactory
     */
    protected $stylaSearchResultsFactory;
    
    /**
     *
     * @var \Styla\Connect2\Model\Api\Converter\ConverterFactory
     */
    protected $converterFactory;
    
    /**
     *
     * @var \Styla\Connect2\Model\Api\Converter\ConverterChain
     */
    protected $converters; //data converter chain
    
    /**
     *
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * 
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $resourceModel
     * @param \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks $linkInitializer
     * @param \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Catalog\Model\Product\Option\Converter $optionConverter
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\Api\ImageContentValidatorInterface $contentValidator
     * @param \Magento\Framework\Api\Data\ImageContentInterfaceFactory $contentFactory
     * @param \Magento\Catalog\Model\Product\Gallery\MimeTypeExtensionMap $mimeTypeExtensionMap
     * @param \Magento\Framework\Api\ImageProcessorInterface $imageProcessor
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Styla\Connect2\Api\Data\StylaProductSearchResultsInterfaceFactory $stylaSearchResultsFactory
     * @param \Styla\Connect2\Model\Api\Converter\ConverterFactory $converterFactory
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(\Magento\Catalog\Model\ProductFactory $productFactory, \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper, \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory, \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory, \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder, \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository, \Magento\Catalog\Model\ResourceModel\Product $resourceModel, \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks $linkInitializer, \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Api\FilterBuilder $filterBuilder, \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface, \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter, \Magento\Catalog\Model\Product\Option\Converter $optionConverter, \Magento\Framework\Filesystem $fileSystem, \Magento\Framework\Api\ImageContentValidatorInterface $contentValidator, \Magento\Framework\Api\Data\ImageContentInterfaceFactory $contentFactory, \Magento\Catalog\Model\Product\Gallery\MimeTypeExtensionMap $mimeTypeExtensionMap, \Magento\Framework\Api\ImageProcessorInterface $imageProcessor, \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
            \Styla\Connect2\Api\Data\StylaProductSearchResultsInterfaceFactory $stylaSearchResultsFactory,
            \Styla\Connect2\Model\Api\Converter\ConverterFactory $converterFactory,
            \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->stylaSearchResultsFactory = $stylaSearchResultsFactory;
        $this->converterFactory = $converterFactory;
        $this->filterGroupBuilder = $filterGroupBuilder;
        
        return parent::__construct($productFactory, $initializationHelper, $searchResultsFactory, $collectionFactory, $searchCriteriaBuilder, $attributeRepository, $resourceModel, $linkInitializer, $linkTypeProvider, $storeManager, $filterBuilder, $metadataServiceInterface, $extensibleDataObjectConverter, $optionConverter, $fileSystem, $contentValidator, $contentFactory, $mimeTypeExtensionMap, $imageProcessor, $extensionAttributesJoinProcessor);
    }
    
    /**
     * If no search criteria is provided in the request, use this as default
     * 
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected function _getDefaultSearchCriteria()
    {
        return $this->searchCriteriaBuilder->create()
                    ->setPageSize(self::DEFAULT_PAGE_SIZE)
                    ->setCurrentPage(0);
    }
    
    /**
     * 
     * @param int $productId
     * @return type
     */
    public function getOne($productId) {
        //we'll be loading the normal collection, but with a singled-out entity_id of the product
        //as i need to run the same data converters on the result, as i would have on the product list
        $idFilter = $this->filterBuilder->setField('entity_id')
                ->setValue($productId)
                ->setConditionType('eq')
                ->create();
        
        $filterGroup = $this->filterGroupBuilder->addFilter($idFilter)
                ->create();
        
        $searchCriteria = $this->searchCriteriaBuilder->create()
                ->setFilterGroups([$filterGroup]);
        
        $searchResult = $this->getList($searchCriteria);
        return $searchResult;
    }
    
    /**
     * 
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Styla\Connect2\Api\Data\StylaProductSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        if($searchCriteria === null) {
            $searchCriteria = $this->_getDefaultSearchCriteria();
        }
        
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($collection);

        //load all the product attributes
        foreach ($this->metadataService->getList($this->searchCriteriaBuilder->create())->getItems() as $metadata) {
            $collection->addAttributeToSelect($metadata->getAttributeCode());
        }
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        
        //Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        /** @var SortOrder $sortOrder */
        foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        
        //the data required by styla is different than what our collection returns,
        //so we run "converters" on the result. the converters may need additional joins on the collection, to work
        $this->_addConverterRequirementsToCollection($collection);
        
        $collection->load();
        
        //convert the data to what we need for styla
        $this->_doConvert($collection);
        
        $searchResult = $this->stylaSearchResultsFactory->create();
        
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        
        return $searchResult;
    }
    
    /**
     * Add styla data converters requirements to the product collection.
     * 
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    protected function _addConverterRequirementsToCollection($collection)
    {
        $converterChain = $this->getConverters();
        
        $converterChain->addCollectionRequirements($collection);
    }
    
    /**
     * Get all the styla data converters defined for this store
     * 
     * @return \Styla\Connect2\Model\Api\Converter\ConverterChain
     */
    public function getConverters()
    {
        if(null === $this->converters) {
            $this->converters = $this->converterFactory->createConverterChain(DataConverter\ConverterFactory::TYPE_PRODUCT);
        }
        
        return $this->converters;
    }
    
    /**
     * Do the data conversion to a format accepted by styla
     * 
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    protected function _doConvert($collection)
    {
        $this->getConverters()->doConversion($collection);
    }
}