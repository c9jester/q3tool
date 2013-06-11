Q3Tool
======

PHP class for working with ioquake3-based game servers.


Overview
--------

Q3Tool is a simple PHP class that allows developers to quickly and easily retrieve information from, or send remote commands (RCON) to game servers that are based on the Quake 3 (ioq3) engine. It was developed for, and has been extensively used with, Urban Terror servers, but should work for just about anything using ioq3 (or even just the same protocol).


Usage
-----

```php
// Using composer (https://packagist.org/packages/jester/q3tool)
require('vendor/autoload.php');

// Or stand-alone
require('q3tool.php');

// Default port, no RCON
$tool = new q3tool("myclan.org");

// Non-default port, no RCON
$tool = new q3tool("myclan.org", 27961);

// Including RCON
$tool = new q3tool("myclan.org", 27960, "super1337password");

// Get a list of players as an array
$players = $tool->get_info("playerlist");

// Or just how many players are on
$player_num = $tool->get_info("players");

// Sending an RCON command
$response = $tool->send_rcon('bigtext "Hello all!"');
```

See DATA_TYPES for more information on retrieving the information you want.


Known Issues
------------

Getting the console response from sending an RCON command is unreliable at best. This seems to be an issue with ioq3 truncating the reply when sent over the wire. I have not yet figured out a solution. Issuing commands, however, does work.
