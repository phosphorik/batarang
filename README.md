batarang
========

A lightweight framework for building database applications in PHP, without the
mess.

installation
============

Batarang plays well with existing software, and uses your existing database
connection. If you're building without a framework, setting your connection
up in BatarangDB.php is a reasonable choice.


integration with CodeIgniter
============================

You are suggested to place the four PHP files in application/libraries:

1. ./Batarang.php
2. ./BatarangConfig.php
3. ./BatarangDB.php
4. ./BatarangMasks.php

The three client content directories should go in your web root:

1. ./css/
2. ./img/
3. ./js/

Then, add Batarang.php to your CodeIgniter autoload section. **Do not autoload
the other script files.**


implementation
==============

    $Data = $this->DataModel->MyData();		// This is a placeholder for the method in your app that returns data
    	$Action = array(  					// An array contains our arguments to Batarang
    		'Action' => 'SQLInsert'			// Tells Batarang to build an SQL insert action from our table definition
			'Table'	=>	array(			
				'clients'	=> array(		// The name of our primary table
					'Username'			=>	'clickable=/account/profile/%Username%/', // Make the UserID clickable, and hyperlink to a URL including our UserID.
					'AccountID'		=> 'edit, delete', // tells Batarang to use this field in WHERE clauses, for updates and deletes.
					'name_first'			=>	'type=varchar 80', // Forcing field type for validation
					'name_last'			=>	'',
					'rate'			=>	'type=money, default='.$this->system->default_rate(), //Prefilling the UI with a return value elsewhere in the application
					'active'			=>	'skip', // Skip this field when generating insert or edit UI
				)
			),
			'Mask'		=> 'DataLookupGrid'	// A mask we've defined to give our database column names nicer
		);
		
		$Output = $this->batarang->TableFromDBResults($Data,	$Action); // Generate our HTML and save it to a variable!
		
Of course, this is a complicated example. At its simplest, all you need is:

    $Output = $this->batarang->TableFromDBResults($Data);
		
Batarang will do some clever stuff and display your data array attractively. Everything else is optional.
	
