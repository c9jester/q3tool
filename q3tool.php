<?php

// Create the class
class q3tool
{
    // A couple of local vars to use
    private $password;
    public $player_data = array();
    public $server_data = array();

    // Class constructor
    public function __construct($server, $port = 27960, $password = "")
    {
        // Construction ahead.
        // Set the password and cache the server info
        $this->password = $password;
        $this->cache_info($server, $port);
    }

    // Pulls the data from the server and stores it
    // This means fewer actual queries
    // This is a good thing.
    private function cache_info($server, $port)
    {
        // Socket to read/write
        $socket = fsockopen("udp://$server", $port, $errno, $errstr, 1);
        // Send the special message
        fwrite($socket, "\xFF\xFF\xFF\xFFgetstatus");
        // And retrieve the server's response
        $response = fread($socket, 2048);
        // Explode the response into an array containing header, server info, players, footer
        $sections = explode("\n", $response);
        // Remove the header/footer (they're useless)
        array_shift($sections);
        array_pop($sections);
        // Remove the server info from the player list and store it
        $server_chunk = array_shift($sections);
        // Explode the server info into an array and remove the first (empty) element
        $server_bits = explode("\\", $server_chunk);
        array_shift($server_bits);

        // Every other element is a key or value
        // ex. "g_gametype\4\sv_maxclients\40" becomes $server_data["g_gametype"] = 4 & $server_data["sv_maxclients"] = 40
        for($i = 0; $i <= count($server_bits) - 1; $i = $i + 2)
        {
            $key = $server_bits[$i];
            $key = str_replace(' ', '', $key);
            $value = $server_bits[$i+1];
            $value = preg_replace('/\^[0-9]{1}/', '', $value);
            $this->server_data[$key] = $value;
        }

        // Turn the player list string into an array
        // ex. [0] ( "name" => "{C9}Jester", "ping" => "50", score => "9001" )
        foreach($sections as $raw_player)
        {
            // Create an array from the player string
            // The player string is "<score> <ping> <name>"
            // So the array is: [0] = <score>, [1] = <ping>, [2] = <name>
            $player = explode(" ", $raw_player);
            // Remove any color codes from names
            // They just make things hard to read if you leave them in
            $name = preg_replace('/\^[0-9]{1}/', '', $player[2]);
            // Push the player's info into the $playerdata array
            // So each element in $playerdata is an array with the player's name, ping
            // So the first element could be something like this:
            // $playerdata[0]['name'] = "{C9}Jester", $playerdata[0]['ping'] = 57, $playerdata[0]['score'] = -1
            array_push($this->player_data, array("name"=>$name, "ping"=>$player[1], "score"=>$player[0]));
        }


    }

    // Retrives various bits of information about the server (map, hostname, etc)
    public function get_info($info)
    {
        $server_data = &$this->server_data;
        $player_data = &$this->player_data;

        // Figure out what info we're looking to retrieve, then return it
        switch($info)
        {
            case "players":
                // Gives the number of elements in $playerdata (eg. the number of players)
                return count($player_data);
                break;
            case "playerlist":
                // Gives the list of players (with each player's name, ping, and score)
                return $player_data;
                break;
            case "map":
                return $server_data["mapname"];
                break;
            case "maxplayers":
                return $server_data["sv_maxclients"];
                break;
            // For convenience, we parse the gametype int into a string
            // The g_gametype key can still be used for a raw gametype int
            case "gametype":
                switch($server_data["g_gametype"])
                {
                    case 0:
                        $gametype = "Free For All";
                        break;
                    case 3:
                        $gametype = "Team Deathmatch";
                        break;
                    case 4:
                        $gametype = "Team Survivor";
                        break;
                    case 5:
                        $gametype = "Follow The Leader";
                        break;
                    case 6:
                        $gametype = "Capture & Hold";
                        break;
                    case 7:
                        $gametype = "Capture The Flag";
                        break;
                    case 8:
                        $gametype = "Bomb Mode";
                        break;
                    default:
                        $gametype = "Unreconized gametype";
                        break;
                }
                return $gametype;
                break;
            // This is just a shorter gametype string
            case "sgametype":
                switch($server_data["g_gametype"])
                {
                    case 0:
                        $gametype = "FFA";
                        break;
                    case 3:
                        $gametype = "TDM";
                        break;
                    case 4:
                        $gametype = "TS";
                        break;
                    case 5:
                        $gametype = "FTL";
                        break;
                    case 6:
                        $gametype = "C&H";
                        break;
                    case 7:
                        $gametype = "CTF";
                        break;
                    case 8:
                        $gametype = "BOMB";
                        break;
                    default:
                        $gametype = "Unrecognized gametype";
                        break;
                }
                return $gametype;
                break;
            case "name":
                return $server_data["sv_hostname"];
                break;
            case "gear":
                return $server_data["g_gear"];
                break;
            case "redname":
                return $server_data["g_teamnamered"];
                break;
            case "bluename":
                return $server_data["g_teamnameblue"];
                break;
            // If none of the above, check to see that the provided key exists
            // If it does, return the info, if not, return an error message
            default:
                if(array_key_exists($info, $server_data))
                {
                    return $server_data[$info];
                }
                else
                {
                    return "UNRECOGNIZED KEY";
                }
                break;
        }
    }

    // Check to see if an RCON password was given
    // And if it was, send a command to the server
    // If not, remind the user
    public function send_rcon($command)
    {
        if(!empty($this->password))
        {
            $socket = fsockopen("udp://".$this->server, $this->port, $errno, $errstr, 1);
            fwrite($socket, "\xFF\xFF\xFF\xFFrcon ".$this->password." $command");
            $response = fread($socket, 2048);
            return $response;
        }
        else
        {
            return "RCON password not supplied";
        }
    }
}

?>
