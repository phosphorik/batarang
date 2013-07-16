<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
// The purpose of the Mask system is to cover the ugly DB field names
// with human-readable ones.

class BatarangMasks {
 
    // Takes an array (probably from a database operation) and applies a mask
    // to it, renaming or removing fields as described by the mask without
    // affecting relative key order.
    public function ApplyToArray($Array, $Mask, $DeleteMissing = true)
    {
		if (!is_array($Mask)){
				$Mask = $this->Masks($Mask);
		}
		
		//print_r($Array);die;
		$NewArray = array();
		$Keys = array_keys($Array[0]);
		//print_r($Keys); die;
		foreach ($Keys as $Key){
				//echo("<br>".$Mask[$Key]." ");
				//echo($Key." ".$Mask[$Key]."<br>");print_r($Mask);die;
				if (array_key_exists($Key, $Mask) && $Mask[$Key] == true){
						//echo("(Masked)");
						@$NewArray[$Mask[$Key]] = array();
						@$AddTo = $Mask[$Key];
				} else {
						if ($DeleteMissing || $Mask[$Key] === false) { 
							//echo("(Deleted)");
							continue;
						 } else { 
							//echo("(Passed)");
							$NewArray[$Key] = array(); 
							$AddTo = $Key;
						}
				}
				
				//echo("AddTo: $AddTo, Mask: $Mask, Key: $Key<br><br>"); print_r($Array);die;
				
				
				$Size = sizeOf($Array);
				for ($i = 0; $i < $Size; $i++) {
					$NewArray[$i][$AddTo] = $Array[$i][$Key];
				}
		}
		//print_r($NewArray);
		return $NewArray;
	}
    
    public function Find($maskname)
    {
			return $this->Masks($maskname);
		
	}
	
	private function Masks($FindMask = false)
	{
		$Path = dirname(__FILE__).'/masks/';
		$MaskFiles = glob($Path."*.php");
		foreach($MaskFiles as $MaskFile) {
			$MaskFile = basename($MaskFile);
			include($Path.$MaskFile);
			$MaskName = substr($MaskFile, 0, strlen($MaskFile)-4);
			$Masks[$MaskName] = $Mask;
		}
		if ($FindMask && is_array($Masks[$FindMask])){
			return $Masks[$FindMask];
		} else if ($FindMask){
			return false;
		} else {
			return $Masks;
		}
			
	}
    
}
