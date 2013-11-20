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

    $Output = $this->batarang->FromTable('TableName');
		
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
Batarang and go nuts. All the following methods support MySQL, PostgreSQL, and MS-SQL.

##BatarangDB->escape_string($String)
Escapes a string. 

##BatarangDB->ArrayQuery($String)
Runs a query against the configured database and returns an associative array. Array format
is consistent across RDBMS, so you shouldn't see broken indexes on MS-SQL queries.

##BatarangDB->Query($String)
Runs a query against the configured database and returns a RDBMS-specific result object.


Invocation parameters
---------------------

There are a number of optional parameters for controlling what Batarang allows your users to do.
By default, any UI generated by Batarang does not have editing enabled.

To use these parameters, place them in an array and pass them to Batarang as the second argument.

#Action
The Action parameter accepts a single value, __SQLInsert___. This
tells Batarang that it should add a widget for inserting records
into the database. Unless you're invoking Batarang with the
FromTable() method, you will need to specify the **Table** property
for this to work:

```
$Action = array(
    'Action' => 'SQLInsert'
);
$Output = $this->batarang->FromArray($Data,	$Action); 
```


#Table
If you've enabled SQLInsert or field editing (explained below), you
will need to specify a table for queries to be generated properly.
The value of Table must be an array itself, and may optionally contain
per-field parameters:

```
$Action = array(
    'Action' => 'SQLInsert',
    'Table' => array(
    	'Clients' => array(
    		'ClientID' => 'edit, delete'
    	)
    )
);
$Output = $this->batarang->FromArray($Data,	$Action); 
```
   
##Field parameters
A number of field-level parameters are supported. Multiple parameters per field
are supported, separated by commas and optional whitespace.

###edit
The field will be specified in the WHERE clause of any UPDATE queries.

###delete 
The field will be specified in the WHERE clause of any DELETE queries.

###skip
The field will not be used when generating any queries. This is useful
for auto-increment values, database-level default values, and any other situation
where the value will be pre-filled. 

###default
By specifying `default=foo`, this field will be pre-filled with the
relevant value.

###clickable
The values in this column are hyperlinked, pointing to the path specified.
Other column values may be specified in `%tags%`, so assuming an `id` value of 24234,
the parameter `clickable=/customers/profile/%id` would hyperlink to
`domain.com/customers/profile/24234`. Great for building interactive forms and
applications.

###type
This attribute is partially implemented, and comes in two parts. It is intended to
allow the developer to enforce type checking on the frontend as well as input
length limits; currently, only length limits are enforced. To limit length of a
string input to 80 characters, for example, specify `type=varchar 80`.

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
