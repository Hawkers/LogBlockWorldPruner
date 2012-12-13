<?php

/********************************************************************************/
// LogBlock World Pruner
// License: MIT
// Version: 1.0
// Author: Hawkee (http://twitter.com/Hawkee)

$server = 'localhost:9999';
$login = 'root';
$pass = '';
$database = 'minecraft';

// Comma separated list of playerids representing things like LavaFlow, Creeper, WaterFlow, etc.
//$playerids = "7,9,10,11,14,34";

$world_table = 'lb-world';
$player_table = 'lb-players';

$source_dir = '/home/minecraft/world/region';
$dest_dir = '/home/minecraft/world-small/region';

$db = mysql_connect($server, $login, $pass);
mysql_select_db($database);

global $append;
global $regions;

/********************************************************************************/
// Determines the region file and area of the region given the block coordinates.

function selectBlock($x, $z) {

    $chunkX = $x >> 4;
    $chunkZ = $z >> 4;

    $regionX = floor($chunkX / 32);
    $regionZ = floor($chunkZ / 32);
	
	$region = "r.$regionX.$regionZ.mca";
    
    $minChunkX = $regionX * 32;
    $minChunkZ = $regionZ * 32;
    $maxChunkX = ($regionX + 1) * 32 - 1;
    $maxChunkZ = ($regionZ + 1) * 32 - 1;
    
    $minBlockX = $minChunkX << 4;
    $minBlockZ = $minChunkZ << 4;
    $maxBlockX = ($maxChunkX + 1 << 4) - 1;
    $maxBlockZ = ($maxChunkZ + 1 << 4) - 1;
	
	return array($region, $minBlockX, $minBlockZ, $maxBlockX, $maxBlockZ);
}

/********************************************************************************/
// Gets a single LogBlock activity outside the found regions already.

function get_player_activity($playerid, $region, $minX, $minZ, $maxX, $maxZ) { 
	global $append;
	global $world_table;

	if($minX !== null and !$regions[$region]) $append .= " and ((x not between '$minX' and '$maxX') or (z not between '$minZ' and '$maxZ'))";
	$q = "select * from `$world_table` where playerid = '$playerid' $append limit 0, 1 ";
	$r = mysql_query($q);
	$row = mysql_fetch_assoc($r);

	if($row) {
		$x = $row[x];
		$z = $row[z];
		return array($x, $z);
	}
	else return false ;
}

/********************************************************************************/
// While there are more players keep looking for activity. 

$more_players = true;
while($more_players) {

	$q = "select * from `$world_table` where playerid not in($playerids) order by date desc limit 0, 1";
	$r = mysql_query($q);
	$row = mysql_fetch_assoc($r);
	
	if(!$row) $more_players = false;
	else {
		$x = $row[x];
		$z = $row[z];
		$date = $row[date];
		$playerid = $row[playerid];

		if($playerids) $playerids .= ',';
		$playerids .= $playerid;

		$r = mysql_query("select * from `$player_table` where playerid = '$playerid'");
		$row = mysql_fetch_assoc($r);

		$playername = $row[playername];
		print "Processing $playername (Last seen: $date)\n";

		unset($append);

		$keep_going = true;
		while($keep_going) {

			$result = get_player_activity($playerid, $region, $minX, $minZ, $maxX, $maxZ);

			if(is_array($result)) {
				$x = $result[0];
				$z = $result[1];

				if($region and !$regions[$region]) $regions[$region] = true;

				list($region, $minX, $minZ, $maxX, $maxZ) = selectBlock($x, $z);

				if(!$regions[$region]) {

					if(file_exists($source_dir)) {
						exec("cp $source_dir/$region $dest_dir");
					}	
					print "Saved $region\n";
				}	
			}
			else $keep_going = false;
		}

		if(count($regions) > $region_count) print "Regions Saved: ".count($regions)."\n";
		$region_count = count($regions);
	}
}
?>
