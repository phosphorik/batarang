<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BatarangConfig {
    var $DBDriver;
    function __construct(){
        $this->DBDriver = 'MSSQL'; //Legal values are PostgreSQL, MSSQL
    }
}
