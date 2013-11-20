<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BatarangDB {
    var $BatarangConfig;
    var $Batarang;
    function __construct($ConfigReference, $Batarang){
        $this->BatarangConfig   = $ConfigReference;
        $this->Batarang         = $Batarang;
        
        if (!defined('__DELIM__')){
		    switch ($this->BatarangConfig->DBDriver){
				case 'MySQL':
				case 'PostgreSQL':
					define('__DELIM__', "'");
					break;
				case 'MSSQL':
					define('__DELIM__', '');
					break;
			}
		}
        
    }
    
    private function escape_key($data){
    	switch ($this->BatarangConfig->DBDriver){
    		case 'MSSQL':
    			if ( !isset($data) or empty($data) ) return '';
				if ( is_numeric($data) ) return $data;

				$non_displayables = array(
				    '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
				    '/%1[0-9a-f]/',             // url encoded 16-31
				    '/[\x00-\x08]/',            // 00-08
				    '/\x0b/',                   // 11
				    '/\x0c/',                   // 12
				    '/[\x0e-\x1f]/'             // 14-31
				);
				foreach ( $non_displayables as $regex )
				    $data = preg_replace( $regex, '', $data );
				$data = str_replace("'", "''", $data );
				return $data;
				break;
			default:
				return $this->escape_string($data);
				break;
		}
    }
    
    public function escape_string($data){
        switch ($this->BatarangConfig->DBDriver){
            case 'PostgreSQL':
                return pg_escape_string($data);
                break;
            case 'MSSQL':
                if (is_numeric(trim($data)))
				return trim($data);
                $unpacked = unpack('H*hex', $data);
                return '0x' . $unpacked['hex'];
                break;
            case 'MySQL':
            	return mysql_escape_string($data);
            	break;
        }
        return false;
    }
    
    function mssql_qf($query){
	$query = mssql_query($query) or die();
	
	if (is_resource($query)){
		return mssql_fetch_all($query);
	} else {
		return false;
	}
	
    }
    
    public function mssql_fetch_all($resource){
            $i = 0;
            $out = array();
            while ( $record = mssql_fetch_array($resource, MSSQL_ASSOC) )
            {					
                    $keys = array_keys($record);
                    foreach($keys as $key){
                            $out[$i][$key] = $record[$key];
                    }
                    $i++;
            }
            return $out; 
    }
    
    public function mysql_fetch_all($result) {
		$all = array();
		while ($all[] = mysql_fetch_assoc($result)) {}
		return $all;
	}
    
    public function GetEditValues($ActionList, $Action){
	$i = 0;
	
	// Right now this just pulls the first table out of the list, which
	// is really fragile - ideally we need to disable automatic generation
	// on queries that involve multiple tables. I don't think it's even
	// possible to autodetect that except when each table's fieldnames are
	// COMPLETELY unique, and even when that's the case implementing this
	// would make the app break if that ever changed.
	$Table = array_keys($ActionList['ByName']);
	$Column = $ActionList['ByName'][$Table[0]];
	$Query = "SELECT * FROM {$Column} WHERE ";
	
	foreach (array_keys($ActionList['ByKey']) as $Key){
		if (isset($ActionList['ByKey'][$Key]['edit']) && $ActionList['ByKey'][$Key]['edit'] == true){
			if ($i) {$Query .= " AND ";}
			$s_Where = $this->escape_string($_POST[$Action['FieldPrefix'].'field_'.$i]);
			switch ($this->BatarangConfig->DBDriver){
				case 'MySQL':
				case 'PostgreSQL':
					$s_Delim = "'";
					break;
				case 'MSSQL':
					$s_Delim = '';
					break;
			}
			$Query .= "{$Key} = ".__DELIM__."{$s_Where}".__DELIM__;
			$i++;
		}
	}
	$r = $this->ArrayQuery($Query);
	return $r[0];
    }
    
    public function ArrayQuery($Query){
    	$Data = $this->Query($Query);
    	
    	switch ($this->BatarangConfig->DBDriver){
            case 'PostgreSQL':
            	$Response = pg_fetch_all($Data);
                break;
            case 'MSSQL':
          		$Response = mssql_fetch_all($Data);
                break;
            case 'MySQL':
            	$Response = mysql_fetch_all($Data);
            	break;
        }
        
        return($Response);
    }
    
    public function Query($query){
    	switch ($this->BatarangConfig->DBDriver){
            case 'PostgreSQL':
            	$Response = pg_query($query);
                break;
            case 'MSSQL':
          		$Response = mssql_query($query);
                break;
            case 'MySQL':
            	$Response = mysql_query($query);
            	break;
        }
    	return $Response;    
    }
    
    public function ActionQuery($ActionQuery){
            //echo($ActionQuery); //die; //debug
            $this->Query($ActionQuery);
           	//print_r(mssql_get_last_message()); die;
            // this is a very bad hack: in order to keep the batarang library completely modular
            // and autodetect the form/query details without prior config, we have to perform
            // the hook/query here... in the view. Which is evil, obviously. But the alternative
            // is to add another layer of configs, and sacrifice anonymous, autogenerated
            // forms. The quick and dirty solution is simply to stop execution and reload the
            // current page.
            header('Location: '.current_url());
            die;
    }
    
    public function BuildActionQuery($Action, $Type = 'add', $Fields = false){
        // This is built off an array in the Batarang call that defines
        // which field elements are required to add records. This
        // could be generated automatically with the help of a
        // DESCRIBE query but making that a reasonable solution would
        // involve a more intelligent caching solution than I'm capable
        // of right now to avoid doubling the queries needed per Batarang
        // call.
        switch ($this->BatarangConfig->DBDriver){
            case 'MSSQL': // In this case the MS-SQL query should be identical to the PostgreSQL query
            case 'PostgreSQL':
                if (strtolower($Type) == 'add'){
                        foreach($Action['Table'] as $Column => $FieldProps){
                                $FieldsToInsert = Array();
                                $ActionQuery = "INSERT INTO \n";
                                $Keys = array_keys($FieldProps);
                                $ActionQueryTables[] = $Column;
                                foreach($Keys as $Field){
                                		$UIFieldsProperties[$Field] = $this->Batarang->GetFieldProps($FieldProps[$Field]);
                                		//echo("<br>".print_r($UIFieldsProperties[$Field],true).'</pre><br>');
                                		$UIFields[] = $Field;
                                		if ($UIFieldsProperties[$Field]['skip'] == '1' && !$UIFieldsProperties[$Field]['default']) { continue; }
                                		
                                        $FieldsToInsert[] = $Field;
                                        
                                        if (isset($_REQUEST[$Action['FieldPrefix'].$Field])){
                                        	$s_InsertHere = $this->escape_string($_REQUEST[$Action['FieldPrefix'].$Field]);
                                        } else if (isset($UIFieldsProperties[$Field]['default'])) {
                                        	$s_InsertHere = $this->escape_string($UIFieldsProperties[$Field]['default']);
                                        } else {
                                        	continue;
                                        }
                                        
                                        // The following needs to be substantially rewritten
                                        // for interop with non-Postgres systems 
                                        $DataToInsert[] = __DELIM__.$s_InsertHere.__DELIM__; 
                                        
                                        // Check to see if any built-in actions (like deleting/editing onscreen
                                        // records) are enabled on this Batarang form
                                        if (in_array(
                                                $this->Batarang->__BuiltInActions,
                                                $UIFieldsProperties[$Field]
                                        )){
                                                foreach ($UIFieldsProperties[$Field] as $Key => $Value) {
                                                        if (!in_array($Key,$this->Batarang->__BuiltInActions)) { continue; }
                                                        $BuiltInAction[$Field][$Key] = $Value;
                                                        $BuiltInActionsList[$Key] = true; 
                                                }
                                        
                                                
                                        }
                                        
                                }
                                
                                
                                //print_r($DataToInsert);die;
                                $DBFields			= $FieldsToInsert;
                                $FieldsToInsert		= implode(', ', $FieldsToInsert);
                                $DataToInsert		= implode(', ', $DataToInsert);
                                $ActionQueryTables	= implode(', ', $ActionQueryTables);
                                $ActionQuery = $ActionQuery." ".$ActionQueryTables."\n ($FieldsToInsert)\n VALUES ($DataToInsert);";
                                
                        }
                        
                        $return = array(
                                "UIFields"				=>	$UIFields,
                                "DBFields"				=>	$UIFields,
                                "UIFieldsProperties"	=>	$UIFieldsProperties,
                                "ActionQuery"			=>	$ActionQuery
                        );
                        
                } else if (strtolower($Type) == 'delete'){
		  		  // This can cause problems if the Batarang instance involves a
                   // multi-table Delete that has identical field names which
                   // are identical to one used to build the Delete query.
                   //
                   // I don't know if this can be done more intelligently, but
                   // what I am more sure of is that there's no way to get the
                   // information necessary to build a better query without
                   // requiring more setup from whoever implements Batarang. Read:
                   // it's possible, but it breaks with the whole minimal-conf
                   // thing and at that point they should be using custom actions
                   // anyway.
                        $ActionList = $this->Batarang->BuildActionList($Action);
                        $ActionQuery = '';
			//$Action['FieldPrefix']."field_".{$i}
                        foreach (array_unique($ActionList['ByName']) as $Table){ 
                                $ActionQuery .= "DELETE from {$Table}
                                WHERE\n";
                                if (sizeOf($ActionList['ByName']) > 0){
									$i = 0;
									$c = 0;
                                    foreach (array_keys($ActionList['ByKey']) as $WhereField) {
                                    		if (!isset($ActionList['ByKey'][$WhereField]['delete'])){
                                    			$i++;
                                    			continue;
                                    		}
											if ($i) { $ActionQuery .= ' AND '; }
                                            $WhereEquals = $this->escape_string($_POST[$Action['FieldPrefix']."field_".$i]);
                                            $WhereField = $this->escape_key($WhereField);
                                            $ActionQuery .= "{$WhereField}=".__DELIM__."{$WhereEquals}".__DELIM__;
                                            $c++;
											$i++;
                                    }
                                //if there are no excluding criteria, abort to avoid deleting all records
                                if ($c === 0) { return false; }
								                                    
                                }
                        }
                $return = array("ActionQuery"	=>	$ActionQuery);
		
                } else if (strtolower($Type) == 'edit') {
		    $ActionList = $this->Batarang->BuildActionList($Action);
		    $Sets = array();
		    $Table = array_values($ActionList['ByName']);
		    $Column = $Table[0];
		    //print_r($ActionList);die;
		    
		    $ActionQuery = "UPDATE $Column\n SET ";
		    
		    foreach ($Fields as $Field){
		    $Properties = $this->Batarang->GetFieldProps($Action['Table'][$Column][$Field]);
            if ($Properties['skip'] == '1') { continue; }
			if (!isset($_REQUEST[$Action['FieldPrefix'].$Field])){continue;}
			$s_Field = $this->escape_string($_REQUEST[$Action['FieldPrefix'].$Field]);
			$Sets[] .= "$Field = ".__DELIM__.$s_Field.__DELIM__;
		    }
		    
		    $ActionQuery .= implode(', ', $Sets) . ' WHERE ';
		    
		    $i = 0;
		    $c = 0;
		    foreach (array_keys($ActionList['ByKey']) as $WhereField) {
			    if ($i) { $ActionQuery .= ' AND '; }
			    $WhereEquals = $this->escape_string($_REQUEST[$Action['FieldPrefix'].'current_'.$WhereField]);
			    $WhereField = $this->escape_key($WhereField);
			    $ActionQuery .= "{$WhereField}=".__DELIM__."{$WhereEquals}".__DELIM__;
			    $c++;
			    $i++;
		    }
		    
		    // If there are no excluding criteria, abort to avoid editing every record
		    if ($c === 0) { return false; }
		    
		    $ActionQuery .= ';';
		    
		    $return = array("ActionQuery"	=>	$ActionQuery);
		}
		
            break;
        }
	return $return;
    }
}
