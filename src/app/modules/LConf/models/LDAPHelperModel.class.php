<?php

class LConf_LDAPHelperModel extends IcingaLConfBaseModel
{
	static public function formatToPropertyArray(array $arr) {
		$returnArray = array();
		foreach($arr as $attribute=>$value) {
			if(!is_array($value)) 
				continue;
			
			$valueCount = $value["count"];
			if($valueCount == 1) {
				$returnArray[$attribute] = $value[0];
			} else {
				$returnArray[$attribute] = array();
				for($i=0;$i<$valueCount;$i++) {
					$returnArray[$attribute][] = $value[$i];
				}
			}			
 		}
 		return $returnArray;
	}
	
	/**
	 * Method that tries to guess the baseDN if none is set in the options
	 * @param string $dn
	 * @return string
	 */
	static public function suggestBaseDNFromName($dn) {
		$explodedDN = ldap_explode_dn($dn,0);
		$dn = "";
		foreach($explodedDN as $val) {
			$splitted = explode("=",$val);
			if($splitted[0] == "dc") {
				$dn .= ($dn ? "," : "").$val;
			}
		}
		return $dn;
	}
	
	static public function cleanResult(array &$result,$firstLevel = true) {
		if(isset($result["count"]))
			unset($result["count"]);
		
		// recursively clean array
		foreach($result as $name=>&$elem) {
			if(is_array($elem) && !empty($elem)) {
				self::cleanResult($elem,false);
			}
		}
		// remove reference garbage and reset pointer 
		unset($elem);
		reset($result);
		
		//  reset firstlevel values (aren't needed)
		if($firstLevel) {
			foreach($result as $key=>$name) {
				if(!is_array($name)) {
					unset($result[$key]);
				}
			}
		}
		
		//remove empty values
		foreach($result as $nr=>$elem)
			if(empty($elem) && is_array($elem))
				unset($result[$nr]);
				
	}
	
	static public function resolveAliases($resultset) {

		foreach($resultset as &$result) {
			if(!is_array($result))
				continue;
				
			$isAlias = false;
			foreach($result["objectclass"] as $type) {
				if($type == "alias") {
					$isAlias = true;
					break;
				}
			}
			if(!$isAlias)
				continue;
			$result["aliasdn"] = $result["dn"];
			$result["dn"] = "ALIAS=Alias of:".$result["aliasedobjectname"][0];
		}
		return $resultset;
	}
}

?>