<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
// The purpose of the Mask system is to cover the ugly DB field names
// with human-readable ones.

abstract class KeyHandling
{
	// we're using letter strings for compatibility with the loose-typed switch()
	// structure
	const Delete		= '__BATARANG_MASK_KEYHANDLING_DELETE__';
	const Copy			= '__BATARANG_MASK_KEYHANDLING_COPY__';
	const Beautify		= '__BATARANG_MASK_KEYHANDLING_BEAUTIFY__';
}

abstract class KeyFlags
{
	const Hide		= '__BATARANG_MASK_KEYFLAGS_DELETE__';
	const Skip		= '__BATARANG_MASK_KEYFLAGS_SKIP__';
}

class BatarangMasks {
 
    // Takes an array (probably from a database operation) and applies a mask
    // to it, renaming or removing fields as described by the mask without
    // affecting relative key order.
    public function ApplyToArray($Array, $Mask, $KeyHandling = KeyHandling::Delete)
    {
		if (!is_array($Mask)){
				$Mask = $this->Masks($Mask);
		}
		
		//print_r($Array);die;
		$NewArray = array();
		$Keys = array_keys($Array[0]);
		foreach ($Keys as $Key){
				if (array_key_exists($Key, $Mask) && $Mask[$Key] == true){ //I should check if an array's key can be false
						@$NewArray[$Mask[$Key]] = array();
						@$AddTo = $Mask[$Key];
				} else {
						if ($Mask[$Key] === false) {
							switch ($KeyHandling){
								case KeyHandling::Delete:
									continue 2;
									break;
								case KeyHandling::Copy:
									$NewArray[$Key] = array(); 
									$AddTo = $Key;
									break;
								case KeyHandling::Beautify:
									$Key = $this->BeautifyKey($Key);
									$NewArray[$Key] = array(); 
									$AddTo = $Key;
									break;
								default:
									echo('error'); die;
							}
							
						 } else {
						 	// this looks vestigial but we're leaving it to make sure it isn't to catch some weird edge case
						 	// that I've forgotten about
						 	echo('This should never happen: '.__FILE__.':'.__LINE__);
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
	
	public function UnmaskField($Fieldname, $Mask)
	{
		$Reversed = array_flip($Mask);
		return $Reversed[$Fieldname];
	}
	
	public function MaskField($Fieldname, $Mask, $KeyHandling = KeyHandling::Copy)
	{
		if ($Fieldname === false) { return false; }
		
		if (!isset($Mask[$Fieldname])) {
			switch ($KeyHandling){
				case KeyHandling::Beautify:
					return $this->Beautify($$Fieldname);
					break;
				case KeyHandling::Delete:
					return false;
					break;
				case KeyHandling::Copy:
					return $Fieldname;
					break;			
			}
			
			
		}
		
		if ($Mask[$Fieldname] == KeyFlags::Hide)
			{ return KeyFlags::Hide; }
		
		return $Mask[$Fieldname];
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
	
	private function Beautify($Key) {
		// if it's mixed case already it's probably been beautified already,
		// either by manual intervention, SELECT AS, or some other method
		if (!ctype_upper($Key) && !ctype_lower($Key)){ return $Key;	}
		
		$Key = str_replace('_', ' ', $Key);
		$Key = explode(' ', $Key);
		for ($i = 0; $i < sizeOf($Key); $i++){
			$Key[$i] = strtolower($Key[$i]);
			$Key[$i][0] = strtoupper($Key[$i][0]);
		}
		
	return $Key;
	}
}
