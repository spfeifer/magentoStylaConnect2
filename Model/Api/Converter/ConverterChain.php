<?php
namespace Styla\Connect2\Model\Api\Converter;

class ConverterChain
{
    /**
     *
     * @var array
     */
    protected $_converters;
    
    /**
     * 
     * @param \Styla\Connect2\Model\Api\Converter\Type\AbstractType $converter
     */
    public function addConverter(\Styla\Connect2\Model\Api\Converter\Type\AbstractType $converter)
    {
        $this->_converters[] = $converter;
    }
    
    /**
     * 
     * @return array
     */
    public function getConverters()
    {
        return $this->_converters;
    }
    
    /**
     * 
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
     */
    public function addCollectionRequirements($collection)
    {
        $requirementsIdentifiers = [];
        
        //merge reqs from all converters, add them to the collection
        foreach($this->getConverters() as $converter) {
            $identifier = $converter->getIdentifier();
            if(in_array($identifier, $requirementsIdentifiers)) {
                continue; //one type of converter only adds it's requirements once
            }
            
            $converter->addCollectionRequirements($collection);
            $requirementsIdentifiers[] = $identifier;
        }
    }
    
    /**
     * 
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
     */
    public function doConversion($collection)
    {
        foreach($collection as $item) {
            foreach($this->getConverters() as $converter) {
                $converter->convertItem($item);
            }
        }
    }
}