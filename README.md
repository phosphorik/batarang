batarang
========

A lightweight framework for building database applications in PHP, without the
mess.

installation
============

Batarang plays well with existing software, and uses your existing database
connection. You can specify


integration with CodeIgniter
============================

You are suggested to place the four PHP files in application/libraries:

# ./Batarang.php
# ./BatarangConfig.php
# ./BatarangDB.php
# ./BatarangMasks.php

The three client content directories should go in your web root:

# ./css/
# ./img/
# ./js/

Then, add Batarang.php to your CodeIgniter autoload section. **Do not autoload
the other script files.**
