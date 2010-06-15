<?php

class LConf_LDAPFilterModel extends IcingaLConfBaseModel 
{
	protected $key = null;
	protected $value = null;
	protected $filterString = "";
	protected $negated = false;
	
	public function setKey($key) {
		$this->key = $key;
	}
	public function getKey()	{
		return $this->key;
	}
	
	public function setValue($value) {
		$this->value = $value;	
	}
	public function getValue()	{
		return $this->value;
	}
	
	public function setFilterString($str) {
		$this->filterString = $str;
	}
	public function getFilterString() 	{
		return $this->filterString();
	}
	
	public function setNegated($bool) {
		$this->negated = (boolean) $bool;	
	}
	public function isNegated()	{
		return $this->negated;
	}
	
	public function __construct($key, $value,$negated = false) {
		$this->setKey($key);
		$this->setValue($value);		
		$this->setNegated($negated);
	}
	
	public function __toArray() {
		return array($this->getKey(),$this->getValue(),$this->isNegated());
	}
	
	public function buildFilterString() {
		$filterString = ($this->isNegated() ? '(!(' : '(').
							$this->getKey()."=".$this->getValue().
						($this->isNegated() ? '))' : ')');
		$this->setFilterString($filterString);
		
		return $filterString;	
	}

}

?>