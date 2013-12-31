<?php
	error_reporting(E_ALL); // 0 when production
	require 'db/connect.php';
	require 'functions/security.php';
	//echo "Success",'<br>';

	//$result = $db->query("SELECT first_name,last_name FROM people LIMIT 1, 1") or die($db->error);

	// if($result = $db->query("SELECT first_name,last_name FROM people")){
	// 	if($count = $result->num_rows){
	// 		//echo "yay";
	// 		//print_r($result);
	// 		//Loop through data
	// 		echo '<p>',$count,'</p>';

	// 		/////// Method 1 ///////

	// 		//$rows = $result->fetch_all(MYSQLI_NUM); //Or ASSOC/BOTH //only available after 5.3.0

	// 		/*foreach($rows as $row){
	// 			echo $row['first_name'],' ','<br>' //append anything
	// 		}*/

	// 		//echo '<pre>',print_r($rows),'</pre>';

	// 		//////// Method 2 /////////

	// 		/*while($row = $result->fetch_assoc()){
	// 			echo $row['first_name'],' ',$row['last_name'],'<br>';
	// 		}*/

	// 		///////// Method 3 /////////
	// 		//no fetch all for objects

	// 		/*while($row = $result->fetch_object()){
	// 			echo $row->first_name,' ',$row->last_name,'<br>';
	// 		}*/

	// 		//Free memory of result

	// 		$result->free();
	// 	}
	// } else{
	// 	die($db->error);
	// }

	///////////////////////////////////// Update Delete /////////////////////////

	/*if($update = $db->query("UPDATE people SET date = NOW() WHERE id = 1")){
		var_dump($update);	// success: true; fail: nothing
		echo $db->affected_rows;	//by last query
	}*/

	/*if($update = $db->query("DELETE FROM people WHERE id = 1")){
		echo $db->affected_rows;	//by last query
	}*/

	/////////////////////////////////////// Insert ////////////////////////////////

	//$first_name = 'Dale';
	//Do: /?first_name=Ashley
	/*if(isset($_GET['first_name'])){
		//Escape to avoid injection
		$first_name = $db->real_escape_string(trim($_GET['first_name']));

		if($insert = $db->query("
			INSERT INTO people (first_name,last_name,date,bio)
			VALUES ('{$first_name}','Garrett',NOW(),'I\'m a web developer')
		")){
			echo $db->affected_rows;
		}
	}*/

	////////////////////////////////////////// Binding ////////////////////////////

	/*if(isset($_GET['last_name'],$_GET['id'])){
		$last_name = trim($_GET['last_name']);
		//$first_name = trim($_GET['first_name']);
		$id = trim($_GET['id']);

		$people = $db->prepare("SELECT first_name,last_name FROM people WHERE last_name = ? AND id = ?");
		$people->bind_param('si',$last_name,$id);	//s is string, i is integer
		$people->execute();

		$people->bind_result($first_name,$last_name);
		while($people->fetch()){
			echo $first_name,' ',$last_name,'<br>';
		}*/

		//echo $first_name;

		//print_r($people);
	//}

	/////////////////////////////////////////// Wrap up ///////////////////////////////

	$records = array();

	if(!empty($_POST)){
		if(isset($_POST['first_name'],$_POST['last_name'],$_POST['bio'])){
			$first_name = trim($_POST['first_name']);
			$last_name 	= trim($_POST['last_name']);
			$bio 		= trim($_POST['bio']);
		}

		if(!empty($first_name) && !empty($last_name) && !empty($bio)){
			$insert = $db->prepare("INSERT INTO people (first_name,last_name,date,bio) VALUES (?,?,NOW(),?)");
			$insert->bind_param('sss',$first_name,$last_name,$bio);

			if($insert->execute()){
				header('Location:index.php');	//Success: Point to a location
				die();
			}
		}
	}

	if($results = $db->query("SELECT * FROM people")){
		if($results->num_rows){
			while($row = $results->fetch_object()){
				$records[] = $row;
			}
			$results->free();
		}
	}

	//echo '<pre>',print_r($records),'</pre>';
?>

<!DOCTYPE html>
<html>
	<head>
		<title>People</title>
	</head>
	<body>
		<h3>People</h3>

		<?php
		if(!count($records)){
			echo "No records";	
		} else{
		?>
			<table>
			<thead>
				<tr>
					<th>First Name</th>
					<th>Last Name</th>
					<th>Bio</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>
					<?php
					foreach($records as $r){
					?>
						<tr>
						<td><?php echo escape($r->first_name); ?></td>
						<td><?php echo escape($r->last_name); ?></td>
						<td><?php echo escape($r->bio); ?></td>
						<td><?php echo escape($r->date); ?></td>
						</tr>
					<?php
					}
					?>
			</tbody>
		</table>
		<?php
		}
		?>
		<hr>

		<form action="" method="POST">
				<div class="field">
					<label for="first_name">First name</label>
					<input type="text" name="first_name" id="first_name" autocomplete="off">
				</div>
				<div class="field">
					<label for="last_name">Last name</label>
					<input type="text" name="last_name" id="last_name" autocomplete="off">
				</div>
				<div class="field">
					<label for="bio">Bio</label>
					<textarea name="bio" id="bio"></textarea>
				</div>
				<input type="submit" value="insert">
		</form>

	</body>
</html>