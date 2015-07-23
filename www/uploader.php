<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Audio Uploader - The Denver Post</title>

    <link rel="shortcut icon" href="http://extras.mnginteractive.com/live/media/favIcon/dpo/favicon.ico" type="image/x-icon" />

    <meta name="distribution" content="global" />
    <meta name="robots" content="noindex" />
    <meta name="language" content="en, sv" />
    <meta name="Copyright" content="Copyright &copy; The Denver Post." />

    <meta name="description" content="">
    <meta name="news_keywords" content="">

    <meta name="google-site-verification" content="2bKNvyyGh6DUlOvH1PYsmKN4KRlb-0ZI7TvFtuKLeAc" />
    <style type="text/css">
        footer 
        {
            clear: both; 
            margin: auto;
            text-align: center;
        }
        body { margin: 20px!important; }
    </style>
    <link rel="stylesheet" type="text/css" href="http://extras.mnginteractive.com/live/css/site67/bartertown.css" />
    <script src="http://local.denverpost.com/common/jquery/jquery-min.js"></script>
</head>
<body>


<?php
function slugify($text)
{ 
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

  // trim
  $text = trim($text, '-');

  // transliterate
  //$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // lowercase
  $text = strtolower($text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  if (empty($text))
  {
    return 'n-a';
  }

  return $text;
}

if(isset($_FILES["audio"])) {
    echo "<div id='message'>";
    if ($_FILES["audio"]["error"] > 0):
        if ($_FILES["audio"]["error"]==4) { echo "<div style='background-color:red'>No file was chosen to be uploaded</div>"; exit; } // No image file was uploaded
        else { echo "<div style='background-color:red'>Error Code: " . $_FILES["audio"]["error"] . "</div>"; exit; } // Another error occurred
    else :
        require('constants.php');
        $conn_id = ftp_connect($FTP_SERVER) or die("Couldn't connect to $ftp_server");
        ftp_login($conn_id,$FTP_USER_NAME,$FTP_USER_PASS);
        ftp_pasv($conn_id, TRUE);
        $year = date("Y");
        $location = $year."/";
        $project = '';
        if ( array_key_exists('project', $_GET) ):
            $project = '/' . slugify($_GET['project']);
        endif;

        if (!file_exists($FTP_DIRECTORY."/".$year)) { @ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year); }            // if the year folder does not exist, create it
        if (!file_exists($FTP_DIRECTORY."/".$year.$project)) { @ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year . $project); } // if the project folder does not exist, create it

        if ($_FILES["audio"]["type"]=="audio/mp3"):
            move_uploaded_file($_FILES["audio"]["tmp_name"], $_FILES["audio"]["name"]); // drop original file in current folder for imagick to use

            if (ftp_put($conn_id, $FTP_DIRECTORY.$location.$_FILES["audio"]["name"], $_FILES["audio"]["name"], FTP_BINARY)):
                echo "<div style='background-color:#a2ff96;'>File created and uploaded to: http://extras.denverpost.com/media/mp3/" . $year . $project . "/" . $_FILES["audio"]["name"] . "</div>";
            else:
                echo "<div style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The file did not upload!</div>";
            endif;

            unlink($_FILES["audio"]["name"]);     // delete the file in current folder

        else: 
            echo "<div style='background-color:red'>File must be a mp3 file!<br> This file is: ".$_FILES["audio"]["type"]."</div>";
        endif;
        echo "</div>";
        ftp_close($conn_id);
    endif;
}
?>
<style>
#radioTitle            { float: left; height: 60px; line-height: 3.6; margin-right:15px }
#radios                { margin-bottom: 10px; }
#uploadImage           { float: left; width: 220px; overflow: visible; }
#chooseDateForm        { margin-top: 40px; float: left; }
#chooseDateForm button { width: 23px; height: 25px; float: right; }
#chooseDateForm img    { width: 20px; margin-left: -6px; }
#uploadHere            { float: left; width: 600px; margin-top: 30px; }
#message               { position: absolute; margin-top: 250px; }
#message div           { padding: 0px 10px; }
</style>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/jquery-ui.css">
<script src="js/jquery-latest.min.js"></script>
<script src="js/jquery-ui.js"></script>
<script>

var date       = new Date();
var curr_year  = date.getFullYear();

function populateInput() {
    $('#dateField, #custom').val($('#datepicker').val());
    $('#custom').val($('#datepicker').val());
    $('#message').remove();
    }

</script>

<h1>Audio File Uploader</h1>
<form action="" id="up" name="up" method="post" enctype="multipart/form-data">
    <h2>Describe the audio</h2>
    <p id="project">
        <label for="project">Project Name:</label> <input name="project" type="text" maxlength="50" />
    </p>
    <p id="audio">
        <label for="audio">Audio File:</label> <input name="audio" type="file" />
    </p>
   <input type="submit" name="submit" value="Upload">
</form>
</body>
</html>
