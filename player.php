<?php

function get_connection(){
	$dsn = "mysql:host=localhost;dbname=mydb";
	$user = "root";
	$passwd = "";
	$conn = new PDO($dsn,$user,$passwd);
	return $conn;
}



function get_random_name($num=6){

	$characters='abcdefghijklmnopqrstuvrxyz0123456789';
	$string='';
	$max=strlen($characters)-1;
	for($i=0; $i<$num; $i++){
		$string .= $characters[mt_rand(0,$max)];
	}
	
	return $string;
}

function save_media($filename,$description,$location){
	$conn = get_connection();
	$sql ="INSERT INTO media(`file`,`description`,`location`) VALUES(?,?,?)";
	$query = $conn->prepare($sql);
	$query->execute([$filename,$description,$location]);
}

function save_playlist($name){
	$conn = get_connection();
	$sql ="INSERT INTO media_playlist(`name`) VALUES(?)";
	$query = $conn->prepare($sql);
	$query->execute([$name]);
}


function save_to_playlist($mediaId,$playlistId){
	$conn = get_connection();
	$sql ="INSERT INTO media_playlist_file(`media`,`playlist`) VALUES(?,?)";
	$query = $conn->prepare($sql);
	$query->execute([$mediaId,$playlistId]);
}
function get_media(){
	$pl = isset($_GET['playlist']) ? $_GET['playlist'] : 'all';
	$results = [];
	try
	{
	$conn = get_connection();
	if($pl && $pl != "all"){
        $query = $conn->prepare("SELECT * from media WHERE id IN(SELECT media from media_playlist_file WHERE playlist=?)");
        $query->execute([$pl]);
        $results=$query->fetchAll();

	}else{
		$results = $conn->query("SELECT * from media");
	}
	
    }catch(Exception $e){
    	echo $e->getMessage();
    }
	return $results;
}

function get_playlists(){
	$results = [];
	try
	{
	$conn = get_connection();
	$results = $conn->query("SELECT * from media_playlist");
    }catch(Exception $e){
    	echo $e->getMessage();
    }
	return $results;
}


if(($_SERVER["REQUEST_METHOD"]=="POST") && (isset($_POST['save-media'])))
{
   $location=$_POST['location'];

	$uploadDir= "uploads/".$location."/";

	echo $uploadDir;
    if(isset($_FILES['file']))
	{

		$filename=$_FILES['file']['name'];
		$filetype=$_FILES['file']['name'];
		$filesize=$_FILES['file']['name'];
		$newFileName=get_random_name().".".pathinfo($filename,PATHINFO_EXTENSION);
		if (file_exists($uploadDir.$newFileName)) 
		{
	        echo $filename . 'is already exist';
	    }
	    else{
			move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir.$newFileName);
			echo  $uploadDir;
			save_media($newFileName,$filename,$location);
			echo "Your file was uploaded successfully";
	    }
	}
	
}

  if($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['save-playlist']))
    {
   	  $playlist =isset($_POST['playlist']) ? $_POST['playlist'] : null ;
   	  if($playlist)
   	   {
   	  	 save_playlist($playlist);
   	  	 echo "Playlist added successfully";
   	   }
   }


   if($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['add-to-playlist']))
    {
   	    $media =isset($_POST['media']) ? $_POST['media'] : [] ;
   	    $playlistId =isset($_POST['addtoplaylist']) ? $_POST['addtoplaylist'] : null ;
   	    if($playlistId)
   	    {
   	   	    if(count($media)>0)
   	   	    {
   	  	        foreach ($media as $mid)
   	  	        {
   	  	 	    save_to_playlist($mid ,$playlistId);
   	  	        }
   	  	    }
   	   }
   }
?>






<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Media Player</title>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" 
	        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" 
	        crossorigin="anonymous"></script>
</head>
<body>
<h1>MY MUSIC LIST</h1>
<form method="post" enctype="multipart/form-data">
	<select name="location">
		<option selected value>----Select a uploadlist----</option>
		<option value="alist">Alist</option>
		<option value="blist">Blist</option>
		<option value="clist">Clist</option>
		<option value="dlist">Dlist</option>
		<option value="elist">Elist</option>
	<input type="file" name="file" />
	<button type="submit" name="save-media">Save Media</button>
  </select>
</form>




<h3>Create Playlist</h3>
<form method="post">
	<input type="text" name="playlist"/>
	<button type="submit" name="save-playlist">Save Playlist</button>
</form>
<br>
<br>

<form>
	Select a playlist:
	<select name="playlist">
		<option selected value>----Select a playlist----</option>
		<option value="all">All Songs</option>
		<?php foreach(get_playlists() as $prow): ?>
		<option value="<?php echo $prow["id"];?>"><?php echo $prow["name"];?></option>
	    <?php endforeach; ?>
	</select>
	<button>Use this Playlist</button>
</form>

<br>
Actions: <button id="pause-button">Pause</button>
<button id="from-start">From Start</button>
<br>
<br>
<br>
<form method="post">
	<select name="addtoplaylist">
		<option selected value>----Select a playlist----</option>
		<?php foreach(get_playlists() as $prow): ?>
		<option value="<?php echo $prow["id"];?>"><?php echo $prow["name"];?></option>
	    <?php endforeach; ?>
	</select>
	<button type="submit" name="add-to-playlist">Add to playlist</button>
<ul>
	<?php foreach(get_media() as $media):  ?>
	<li><input type="checkbox" name="media[]" value="<?php echo $media["id"];?>" />
		<a href="javascript:void(0);" class='play-media' data-file="./uploads/<?php echo $media["file"]; ?>">
		<?php echo $media["description"];?>
		</a>	
	</li>
    <?php endforeach; ?>
</ul>
</form>
</body>
<script>
	var audio= null;
	var currentFile = null;
	var playlist =''
	$(document).ready(function(){


     $('.play-media').on('click',function(){
    var el = $(this);
    var filename =el.attr('data-file');
    if(audio && currentFile === filename){
    	audio.currentTime=0;
    	audio.play();
    }
    else{
         if(audio){
         	audio.pause();
         }
         audio = new Audio(filename);
         currentFile = filename;
         audio.play();
    }
  
    return false;
    });

     $('#pause-button').on('click',function(){
     	if(audio){
     		audio.pause();
     	}
     	return false;
     });

     $('#from-start').on('click',function(){
     	if(audio) {
     	audio.currentTime = 0;
     	audio.play();
     	}
       return false;
     });
      


	});
</script>
</html>