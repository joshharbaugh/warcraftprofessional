<?php
@set_time_limit(0);

function __autoload($class){require_once("includes/$class.class.php");}
$profession = "";

function stopwatch()
{
	static $zero = 0;
	static $limit = 99999;
	
	$args = func_get_args() + Array(null);
	
	// Check for # iterations, assign for future calls
	if (is_integer($args[0])) $max = min(array_shift($args), 999999); else $max = $limit;
	if ($max != $limit)
	{
		$limit = $max;
		$zero = 0;
	}
	if (isset($args[0])) $callback = array_shift($args); else $callback = null;
	
	if (is_null($callback))
	{
		$callback = 'pi';
		$zero = null;
	} 
	
	// Ensure a valid callback
	if (!is_callable($callback, false, $name)) return null;
	if ($name == __FUNCTION__) return null;
	
	// The actual loop
	$st = explode(' ',microtime());
	for($cx=0;  $cx<$max;  $cx++)
	{
		call_user_func_array($callback, $args);
	}
	
	// Final calculations
	$t = explode(' ', microtime());
	$t[0] -= $st[0];
	$t[1] -= $st[1];
	
	if (is_array($zero)) // Use previous reference point
	{
		$t[0] -= $zero[0];
		$t[1] -= $zero[1];
	}
	elseif (is_null($zero)) // or establish a new one
	{
		$zero = $t;
		return;
	}
	
	if ($t[0] < 0) // Ensure microseconds are always positive
	{
		$t[0]++; $t[1]--;
	}
	
	// Done
	return "$t[1] + $t[0]s"; 
}


$db_realms_connect = new mysqli('wowrealms.db.7960285.hostedresource.com', 'wowrealms', 'Sabbath83!', 'wowrealms');
if ($db_realms_connect->errno)
{
	printf("Unable to connect to the database:<br /> %s",
			$db_realms_connect->error);
	exit();
}
else
{
	$date = date("Y-m-d");
	$query_all_realms = "SELECT realm_name FROM realms WHERE last_modified IS NOT NULL ORDER BY realm_id ASC";

	$realm_result = $db_realms_connect->query($query_all_realms, MYSQLI_USE_RESULT);
	if ($realm_result)
	{
		$realms_array = array();
		while(list($realm_name) = $realm_result->fetch_row())
		{
            $_realm = $realm_name;
			$_realm = stripslashes($_realm);
			array_push($realms_array, $_realm);
		}
		print_r($realms_array);
		
		function execute($_realm)
		{
					$db_connect = new mysqli('wowprofessions.db.7960285.hostedresource.com', 'wowprofessions', 'Sabbath83!', 'wowprofessions');
					if ($db_connect->errno)
					{
						printf("Unable to connect to the database:<br /> %s",
								$db_connect->error);
						exit();
					}
					else
					{
					    echo "<h2>".$_realm."</h2>";
		
						$query = "SELECT item_name, item_quantity FROM item, item_profession WHERE item.item_id = item_profession.item_id ORDER BY item.item_id ASC";
		
						$result = $db_connect->query($query, MYSQLI_USE_RESULT);
						if ($result)
						{
							$avg_price_array = array();
							$item_name_array = array();
							while(list($item_name,$item_quantity) = $result->fetch_row())
							{
								echo $item_name."<br />";
								$_realm = str_replace(" ", "%20", $_realm);
								$itemPrices = new current_price("market","A","$_realm","$item_name");
								$item = $itemPrices->getPrices();
								$number_of_items = count($item);
								$total_cost = 0;
								if ($number_of_items > 0) {
									foreach($item as $price) 
									{ 
										if ($price->buyout_each) 
										{
											$total_cost += $price->buyout_each;
										} else 
										{
											$total_cost += $price->buy;
										}
									}
									$average_cost = $total_cost / $number_of_items;
									$avg = round($average_cost);
									echo " > ".$avg."<br />";
		
									// ADD VALUE OF $AVG TO AN ARRAY FOR EACH ITEM
									array_push($avg_price_array, $avg);
									array_push($item_name_array, $item_name);
								}
							}
		
							do
							{
								$_realm = str_replace("%20", "_", $_realm);
								$_realm = str_replace("'", "_", $_realm);
								$_realm = $_realm."_A";
								
								foreach($item_name_array as $i=>$item_name) {
									$item_name = str_replace("'","''",$item_name_array[$i]);
									$x = $i+1;
									$INSERT_query = "INSERT INTO $_realm(item_id,item_name) SELECT item_id, item_name FROM item WHERE item.item_id = $x ON DUPLICATE KEY UPDATE current_price = $avg_price_array[$i], last_modified = '$date'";
									$INSERT_result = $db_connect->query($INSERT_query, MYSQLI_USE_RESULT);
									if ($INSERT_result)
									{
										echo "<br>Added ".$item_name_array[$i]." (".$avg_price_array[$i].") to the ".$_realm." table<br>";
										$UPDATE_query = "UPDATE $_realm SET current_price = $avg_price_array[$i], last_modified = '$date' WHERE item_id = $x";
										$UPDATE_result = $db_connect->query($UPDATE_query, MYSQLI_USE_RESULT);
										if ($UPDATE_result)
										{
											echo "Updated to ".$avg_price_array[$i]."<br /><br />";
										}
									}
								}
							} while ($db_connect->next_result());
							$result->free();
						}
					}
					$db_connect->close(); // close the db connection
					echo "processing complete";			
		} // execute
		
		foreach($realms_array as $n=>$_realm)
		{
			//print_r($realms_array);
			echo "processing... ".$_realm."<br /><br />";
			//execute($_realm);
			stopwatch(3, 'execute', $_realm);
		}
		
		$realm_result->free();
	}
	$db_realms_connect->close();
}
?>