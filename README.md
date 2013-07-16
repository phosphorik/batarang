batarang
========

A lightweight framework for building database applications in PHP, without the
mess.

Installation
------------

Batarang plays well with existing software, and uses your existing database
connection. If you're building without a framework, setting your connection
up in BatarangDB.php is a reasonable choice.

To install, simply include Batarang.php at the beginning of your application and edit
BatarangConfig.php so that it knows what database to use. You're ready to go!


Integration with CodeIgniter
----------------------------

You are suggested to place the four PHP files and the masks directory in application/libraries:

1. ./Batarang.php
2. ./BatarangConfig.php
3. ./BatarangDB.php
4. ./BatarangMasks.php
5. ./masks/

The three client content directories should go in your web root:

1. ./css/
2. ./img/
3. ./js/

Then, add Batarang.php to your CodeIgniter autoload section. **Do not autoload
the other script files.**


Integration with some other framework
-------------------------------------

Let us know how it works out for you. Batarang is self-contained enough that it should
be pretty simple.


Implementation
--------------

    $Data = $this->DataModel->MyData();															// This is a placeholder for the method in your app that returns data
    	$Action = array(  																		// An array contains our arguments to Batarang
    		'Action' => 'SQLInsert'																// Tells Batarang to build an SQL insert action from our table definition
			'Table'	=>	array(			
				'clients'	=> array(															// The name of our primary table
					'Username'			=>	'clickable=/account/profile/%Username%/',			// Make the UserID clickable, and hyperlink to a URL including our UserID.
					'AccountID'		=> 'edit, delete', 											// tells Batarang to use this field in WHERE clauses, for updates and deletes.
					'name_first'			=>	'type=varchar 80', 								// Forcing field type for validation purposes
					'name_last'			=>	'',
					'rate'			=>	'type=money, default='.$this->system->default_rate(), 	//Prefilling the UI with a return value elsewhere in the application
					'active'			=>	'skip', 											// Skip this field when generating insert or edit UI
				)
			),
			'Mask'		=> 'DataLookupGrid'														// A mask we've defined to give our database column names nicer
		);
		
		$Output = $this->batarang->FromArray($Data,	$Action); 						// Generate our HTML and save it to a variable!
		
Of course, this is a complicated example. At its simplest, all you need is:

    $Output = $this->batarang->FromArray($Data);
		
Batarang will do some clever stuff and display your data array attractively. Everything else is optional.


Masks
-----

A mask is just an array that is used to programmatically replace ugly field names with
attractive ones. This has benefits over using AS for beautification in your queries; consider:

    SELECT first_name AS "First name", last_name AS "Last name";
    
Though this will result in an attractive table, it will be less consistent, because
* Performing logic on the array post-transformation will need to address each key by the new keyname
* There is no systematic means for mapping each key to the same beautified keyname each time
* Processing the data later means reversing the transformation, or explicitly saving the original array

Batarang abstracts these problems away by only performing the substitution during the rendering
phase. Masked data is never saved or exposed to your application's internals, so you don't
need to worry about how it'd displayed to the user.

To create a mask, create a file in ./masks/ named after your mask, with the .php extension. For
instance, to create a mask called FooBar, your file should be "./masks/FooBar.php".

The contents are simply a PHP array of key => name pairs, in a string called $Mask. The opening <?
is essential!

Example:

    <? $Mask = array(
    	'FOOBAR'			=>	'Foo Bar',
    	'MEMO'				=>	'Notes'
    );

You can apply a mask to a Batarang lookup by specifying its name in the Mask field of the
invocation. You can also apply the mask to an array using the $BatarangMasks->ApplyToArray($array)
method.

Database stuff
--------------

Batarang doesn't pretend to be an ORM or even an abstraction layer, but it does
have some related perks. For one thing, BatarangDB has a set of DB-agnostic query and
string escape methods, so if that's something you need just instance it outside of
Batarang and go nuts.


Todo
----

There are a number of features we'd like which aren't done yet. That list includes
(but is not necessarily limited to) the following:

* Fully automatic conf for basic table update/delete code via RDBMS __describe__
* Related $Batarang->TableFromQuery() method would be nice :)
* Database-backed Mask storage
* Autodetect existing DB connection and switch DB functions to match rather than relying on BatarangConfig value
* Pre-insert/pre-update function callbacks for field data
* Pre-query data consistency & typing validation
* Get some coffee that isn't stale
