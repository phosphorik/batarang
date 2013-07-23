<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require('BatarangConfig.php');
require('BatarangDB.php');
require('BatarangMasks.php');

	/*
	 * Batarang is a small library to simplify implementation requirements 
	 * common in database-driven apps.
	 */

class Batarang {

	public $BatarangConfig;
	public $BatarangDB;
	public $BatarangMasks;
	var $__BuiltInActions;

	function __construct()
	{
		$this->BatarangConfig = new BatarangConfig();
		$this->BatarangDB = new BatarangDB($this->BatarangConfig, $this);
		$this->BatarangMasks = new BatarangMasks();
		$this->__BuiltInActions = array('edit', 'delete');
	}
	
	public function TableHeadersFromDBResults($Results, $Mask = false, $KeyHandling = KeyHandling::Copy)
	{
	$Headers = array_keys($Results[0]);

		$Out = "<tr class='BatarangHeaders'>";
			
			foreach ($Headers as $Label){
				if (is_array($Mask)){
					$Mask[$Label] = $this->BatarangMasks->MaskField($Label, $Mask, $KeyHandling);
					if ($Mask[$Label] === false || $Mask[$Label] == KeyFlags::Hide) { continue; }
					$Label = $Mask[$Label];
				}
				$Out .= "<th>{$Label}</th>";
			}
		$Out .= "</tr>";
	return $Out;
	}

	public function FromTable($Table, $Action = false, $Writable = false, $RecordsPerPage = 40)
	{
		$s_Table = $this->BatarangDB->escape_string($Table);
		$Query = "SELECT * FROM $s_Table;";
		$Out =  $this->FromQuery($Query, $Action = false, $Writable = false, $RecordsPerPage = 40);
		
		return $Out;
	}

	public function FromQuery($Query, $Action = false, $Writable = false, $RecordsPerPage = 40)
	{
		$R = $this->BatarangDB->ArrayQuery($Query);
		$Out = $this->FromArray($R, $Action, $Writable, $RecordsPerPage);
		
		return $Out;
	
	}

	// This takes a query result in the form of an associative array, and renders it
	// to a grid. 
	public function FromArray($Results, $Action = false, $Writable = false, $RecordsPerPage = 40)
	{
		if (is_null($Results)){
				$Results[0] = array(
					'Results'  => 'There are no records to display.'
				);
		}
		
		// Autodetects the form unit's place in the page and therefore ID,
		// unless the ID is specified
		global $BatarangFormIDCounter;
		@$BatarangFormIDCounter++;
		
		$Out = "";
		
		if (isset($Action['Mask']) && $Action['Mask']) {
			$Mask = $this->BatarangMasks->find($Action['Mask']);
		} else {
			$Mask = false;
		}
		// The FormID variable dictates the name of the HTML form, as well
		// as sets the names (and therefore POST variable names) of the
		// resulting form fields. It is generated from an auto-incrementing
		// number if it isn't specified in the calling context.
		if (!isset($Action['FormID']) && is_array($Action)){
			$Action['FormID'] = 'batarang_form_'.$BatarangFormIDCounter;
			$Action['FieldPrefix'] = 'sf_'.$BatarangFormIDCounter."_";
		} else {
			$Action['FieldPrefix'] = 'sf_'.$Action['FormID']."_";
		}
		
		if (isset($Action['Table'])){
			$ActionList = $this->BuildActionList($Action);
		}
		
		// This isn't very portable, because it's very heavily Postgres-oriented
		// but basically this is a semi-automatic method for adding records
		// to a database using Batarang
		if (isset($Action['Action'])){
			
			if (!$this->ValidateInput($Action)){
				//TODO: rejection logic
			}
			
			$ActionQuery = $this->BatarangDB->BuildActionQuery($Action);
			$UIFields			= $ActionQuery['UIFields'];
			$DBFields			= $ActionQuery['DBFields'];
			$UIFieldsProperties	= $ActionQuery['UIFieldsProperties'];
			$ActionQuery 		= $ActionQuery['ActionQuery'];
			//Process the form, if data's been submitted
			if (isset($_REQUEST[$Action['FieldPrefix'].'submit'])){
				//check our null constraints, etc
				if ($_POST[$Action['FieldPrefix'].'submit'] == 'Insert') {
					$Type = 'add';
					$Fields = false;
				} else {
					$Type = 'edit';
					//echo("BLINGATROO ".$_POST[$Action['FieldPrefix']."action"]); die;
					$Fields = $UIFields;
				}
				$ActionQuery = $this->BatarangDB->BuildActionQuery($Action, $Type, $Fields);
				
				$this->BatarangDB->ActionQuery($ActionQuery['ActionQuery']);					
			}
		} 		// End: // if ($Action['Action'] == 'SQLInsert'){ // //
		
		@$ActionReq = $_POST[$Action['FieldPrefix']."action"];
		
		if ($ActionReq  == 'delete') { //echo('?'); die;
			$Where = $this->GetWhereFields($Action, $ActionList);
			$ActionQuery = $this->BatarangDB->BuildActionQuery($Action, 'delete', $Where);
			$this->BatarangDB->ActionQuery($ActionQuery['ActionQuery']);
			
		} else if ($ActionReq == 'edit') {
			$ActionList = $this->BuildActionList($Action);
			$CurrentValues = $this->BatarangDB->GetEditValues($ActionList, $Action);
			$Out .="<table class='BatarangEdit' cellspacing='0'>";
			$Out .= $this->ActionForm($Action, $UIFields, $UIFieldsProperties, $Mask, $CurrentValues);
			$Out .= "</table>";
			
		} else {
		
			$Out .= "<table class='BatarangLookup' cellspacing='0'>\n";
			
			$Out .= $this->TableHeadersFromDBResults($Results, $Mask);
			$i = 1;
			$j = 1;
				
				foreach ($Results as $Record) {
					$i++;
					$Out .= "<tr class='BatarangRecord BatarangRecord$j'>\n";
						$FirstColumn = false;
						foreach ($Record as $Field => $Column) {
							if ($Mask) {
								if ($this->BatarangMasks->MaskField($Field, $Mask) === false || $this->BatarangMasks->MaskField($Field, $Mask) == KeyFlags::Hide) { continue; }
							}
							if ($Field === false || $Field == KeyFlags::Hide) { continue; }
							$BuiltInActions_Interface = '';
							
							// We add the edit/delete builtins to the column
							// (but only if it's the first column in the
							// sequence)
							if (!$FirstColumn){
								$FirstColumn = true;
								
								if (isset($ActionList) && sizeOf($ActionList['ByName']) > 0){
									$BuiltInActions_WhereBlock = '';
									$Conditions = $this->GetWhereFieldsValues($Action, $ActionList, $Record);
									$i = 0;
									foreach ($Conditions['Values'] as $Values) {
										$BuiltInActions_WhereBlock .= "<input type='hidden' name='{$Action['FieldPrefix']}field_{$i}' value='{$Values}'>\n";
										$i++;
									}
								}
								
								if (isset($ActionList['ByName']['delete'])){
									$BuiltInActions_Interface .= "
										<form action='' method='post'>\n";
											$BuiltInActions_Interface .= $BuiltInActions_WhereBlock;
											$BuiltInActions_Interface .= "<input type='hidden' name='".$Action['FieldPrefix']."action' value='delete'>";
											$BuiltInActions_Interface .= "<input type='submit' class='BatarangForm_Button BatarangForm_Button_Delete' value=''>
										</form>
									";
								}
								
								if (isset($ActionList['ByName']['edit'])){
									$BuiltInActions_Interface .= "
										<form action='' method='post'>\n";
											$BuiltInActions_Interface .= $BuiltInActions_WhereBlock;
											$BuiltInActions_Interface .= "<input type='hidden' name='".$Action['FieldPrefix']."action' value='edit'>";
											$BuiltInActions_Interface .= "
											<input type='submit' class='BatarangForm_Button BatarangForm_Button_Edit' value=''>
										</form>
									";
								}
							}
							if (isset($ActionList['ByKey'][$Field]['clickable'])){
								// clickables are kind of funny, they contain a URL with an arbitrary number
								// of fields from the current record inside, wrapped in %s like so:
								//
								// http://www.foo.com/bar/%barfoo%
								$ClickableLink = $ActionList['ByKey'][$Field]['clickable'];
								$a = 0;
								while (is_numeric(strpos($ClickableLink,'%'))){
									$a = strpos($ClickableLink,'%');
									$b = strpos($ClickableLink,'%',$a+1);
									$Span = $b-$a-1;
									
									if (!$b) {break;} // $b can never be 0 because it's the second match
									$match = substr($ClickableLink, $a+1, $Span);
									
									$ClickableLink = str_replace('%'.$match.'%', $Record[$match], $ClickableLink);
								}
								
								$ClickableLink = "<a href='".$ClickableLink."'>";
								$ClickableLinkEnd = "</a>";
							} else {
								$ClickableLink = "";
								$ClickableLinkEnd = "";
							}
							
							$Out .= "<td>{$BuiltInActions_Interface}{$ClickableLink}{$Column}{$ClickableLinkEnd}</td>\n";
						}
					
					$Out .= "</tr>\n";
					
					if ($i === $RecordsPerPage) { //part of the woefully inadequate pagination scheme
						$i = 0;
						$j++;
						$Out .= "	</table>
								<table class='BatarangLookup BatarangRecord$j' cellspacing='0'>
								<tr>\n";
						$Out .= $this->TableHeadersFromDBResults($Results, $Mask);
						$Out .= "</tr>\n";
					}
					
				}
				
			if (isset($Action['Action']) && strtolower($Action['Action']) == "sqlinsert"){
				$Out .= $this->ActionForm($Action, $UIFields, $UIFieldsProperties, $Mask);
				
			}
			
			$Out .= "</table>\n";
		}
		
		return $Out;
	}
	
	private function ValidateInput($Action){
		// stub
		// TODO: this
	}
	
	private function GetWhereFieldsValues($Action, $ActionList, $Record){
		$Where = $this->GetWhereFields($Action, $ActionList);
		$Values = $this->GetWhereValues($Action, $ActionList, $Record);
		return array('Values' => $Values, 'Where' => $Where);
	}
	
	private function GetWhereFields($Action, $ActionList){
		foreach (array_keys($ActionList['ByKey']) as $WhereField) {
			$Where[] = $Action['FieldPrefix'].$WhereField;
		}
		return $Where;
	}
	
	private function GetWhereValues($Action, $ActionList, $Record){
		
		foreach (array_keys($ActionList['ByKey']) as $WhereField) {
			$Equals[] = $Record[$WhereField];
		}
		return $Equals;
	}
	
	private function ActionForm($Action, $UIFields, $UIFieldsProperties, $Mask, $CurrentValues = false){
		if (!is_array($CurrentValues)) { $Task = 'Insert'; } else { $Task = 'Edit'; }
		$k = 0;
		$Out = '';
				
		// We only add the form code if there's an action set
		$Out .= "<form method='post' action=''>
		<script type='text/javascript'></script>
		<input name='".$Action['FieldPrefix']."submit' value='1' type='hidden'>";
		
		// Rendering a new set of headers for the insert block. This
		// is a little bit unintuitive, because unlike above we're
		// going to render based on the contents of the Batarang call's
		// Action block instead of the query output. This can result
		// in different numbers of columns between the insert and
		// the display rows.
		$Out .= "<tr class='BatarangAction Divide'>";
					foreach ($UIFields as $Field) {
						// A field that has the "skip" property will
						// still take up space, but cannot be filled.
						// This is separate from masks, which affect
						// presentation of the output: the reason
						// for this schism is that a mandatory field
						// may have a predictable value that needs
						// to be preset, but the logic for which is
						// too complex to rely on a default value
						// in the DB.
						if ($UIFieldsProperties[$Field]['skip']) {$LabelStyle = 'disabled';} else {$LabelStyle = '';}
						if (is_array($Mask)){
							$Field = $this->BatarangMasks->MaskField($Field, $Mask);
							if ($Field == KeyFlags::Hide) { continue; }
						}
						
						$Out .= "<th class='{$LabelStyle}'>{$Field}</th>\n";
					}
		$Out .="	</tr>";
		
		$Out .="	<tr class='BatarangAction'>\n";
					foreach ($UIFields as $Field) {
						if ($UIFieldsProperties[$Field]['skip']) { $Out .= "<td class='disabled'></td>"; $k++; continue; }
						if ($this->BatarangMasks->MaskField($Field, $Mask) == KeyFlags::Hide) { continue; }
						if ($Task == 'Insert') {
							// unless we're editing an existing field, load the default
							// value into the table
							$Default = $UIFieldsProperties[$Field]['default'];
						} else {
							$Default = $CurrentValues[$Field];							
						}
						$k++; // So that we can put some dummy columns
							  // in the next row and position the
						$Out .= "<td><input type='text' value='{$Default}' name='".$Action['FieldPrefix'].$Field."'>\n";
						$Out .= "</td>\n";
						
					}
		$Out .="	</tr>\n";
		$Out .="	<tr class='BatarangAction'>\n";
		for ($i = 1; $i < $k; $i++) {
				$Out .=	"<td>&nbsp;</td>";
		}
		if (is_array($CurrentValues) && sizeOf($CurrentValues) > 0){
			foreach ($CurrentValues as $Name => $Value) {
				if (sizeOf($Value) < 1) {continue;}
				$Out .= "<input type='hidden' name='".$Action['FieldPrefix']."current_".$Name."' value='$Value'>";
			}
		}
		$Out .= "<td><input type='submit' value='$Task' name='{$Action['FieldPrefix']}submit' class='BatarangAction BatarangControls'></td>";
		$Out .="	</tr>\n";
		$Out .= "</form>\n";
		return $Out;
	}
	
	public function GetFieldProps($Serialized){
		$Array = array_map('trim', explode(',', $Serialized));
		foreach ($Array as $Field) {
			$splode = explode('=',$Field);
			$r[$splode[0]] = isset($splode[1]) ? $splode[1] : true;
		}
		
		// IMPLICIT SETTINGS
		//
		// if null or skip aren't set, explicitly set them to false. If default (the
		// pre-filled value for a field) isn't set, prefill an empty string instead
		$r['default']	=	isset($r['default'])	? $r['default'] : '';
		$r['skip']	=	isset($r['skip'])	? $r['skip'] : false;
		$r['null']	=	isset($r['null'])	? $r['null'] : false;
		
		return $r;
	}
	
	public function BuildActionList($Action){
		$BuiltInAction = '';
		$BuiltInActionsList = '';
		
		foreach($Action['Table'] as $Table => $FieldProps){
				$Keys = array_keys($FieldProps);
				foreach($Keys as $Field){
					$UIFieldsProperties[$Field] = $this->GetFieldProps($FieldProps[$Field]);
					
					
					// Check to see if any built-in actions (like deleting/editing onscreen
					// records) are enabled on this Batarang form
					if (in_array(
						$this->__BuiltInActions,
						$UIFieldsProperties[$Field]
					)){
						foreach ($UIFieldsProperties[$Field] as $Key => $Value) {
							if (!in_array($Key,$this->__BuiltInActions)) { continue; }
							$BuiltInAction[$Field][$Key] = $Value;
							$BuiltInActionsList[$Key] = $Table; 
						}
					}
				}
			}
			
			if (is_array($BuiltInAction)) {
				$UIFieldsProperties = array_merge($UIFieldsProperties, $BuiltInAction);
			}
			
			$return['ByKey'] = $UIFieldsProperties;
			
			if (is_array($BuiltInActionsList)){
				$return['ByName'] = $BuiltInActionsList;
			} else {
				$return['ByName'] = null;
			}
			
			return $return;
	}
	
}
