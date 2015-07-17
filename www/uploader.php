<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
?>



<?php
if(isset($_FILES["audio"])) {
    echo "<div id='message'>";
    if ($_FILES["audio"]["error"] > 0) {
        if ($_FILES["audio"]["error"]==4) { echo "<div style='background-color:red'>No Image was chosen to be uploaded</div>"; exit; } // No image file was uploaded
        else { echo "<div style='background-color:red'>Error Code: " . $_FILES["audio"]["error"] . "</div>"; exit; } // Another error occurred
        }
    else {
        require('constants.php');
        $conn_id = ftp_connect($FTP_SERVER) or die("Couldn't connect to $ftp_server");
        ftp_login($conn_id,$FTP_USER_NAME,$FTP_USER_PASS);
        ftp_pasv($conn_id, TRUE);
        $selectedDate = explode("/", $_POST["date"]);
        $month    = $selectedDate[0];
        $day      = $selectedDate[1];
        $year     = $selectedDate[2];
        $location = "/".$year."/".$month."/";                 // location the file will be put into
        $_FILES["audio"]["name"] = $year."-".$month."-".$day; // rename the file to today's date

        if (!file_exists($FTP_DIRECTORY."/".$year."/"))            { ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year); }            // if the year folder does not exist, create it
        if (!file_exists($FTP_DIRECTORY."/".$year."/".$month."/")) { ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year."/".$month); } // if the month folder does not exist, create it

        if (($_FILES["audio"]["type"]=="application/pdf")||($_FILES["audio"]["type"]=="image/jpeg")) {
            move_uploaded_file($_FILES["audio"]["tmp_name"], $_FILES["audio"]["name"].$extension); // drop original file in current folder for imagick to use

            if (ftp_put($conn_id, $FTP_DIRECTORY.$location.$_FILES["audio"]["name"].$extension, $_FILES["audio"]["name"].$extension, FTP_BINARY)) {
                echo "<div style='background-color:green'>Original file uploaded!</div>";
                }
            else { echo "<div style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The original file did not upload!</div>"; }
            if (ftp_put($conn_id, $FTP_DIRECTORY.$location.$_FILES["audio"]["name"].".jpg", $_FILES["audio"]["name"].".jpg", FTP_BINARY)) {

                $filename = '"'.$_FILES["audio"]["name"].'"';
                $json_a = explode("[", file_get_contents("frontpages.json"));
                $json_b = explode("]", $json_a[1]);
                $json_c = explode(",", $json_b[0]);

                if (!in_array($filename, $json_c)) {
                    if ($filename > $json_c[0]) { // date is today, add to beginning
                        array_unshift($json_c, $filename);
                        }
                    else {
                        for($i=0;$i<sizeof($json_c);$i++) {
                            $i2 = $i+1;
                            if($i2<sizeof($json_c)) {
                                if(($json_c[$i] > $filename)&&($json_c[$i2] < $filename)) { // date is somewhere in between, find the right spot and drop it in
                                    array_splice($json_c, $i2, 0, $filename);
                                    break;
                                    }
                                }
                            else { // date is older than anything in array, add to end
                                array_push($json_c, $filename);
                                break; // prevents infinite loop
                                }
                            }
                        }
                    }
                echo "<div style='background-color:green'>.jpg created and uploaded!</div>";
                }
            else { echo "<div style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The .jpg file did not upload!</div>"; }

            unlink($_FILES["audio"]["name"].".jpg");     // delete the jpg file in current folder
            unlink($_FILES["audio"]["name"].$extension); // delete the pdf file in current folder
            }

        else { echo "<div style='background-color:red'>File must be a pdf or jpg file!<br> This file is: ".$_FILES["audio"]["type"]."</div>"; }
        echo "</div>";
        ftp_close($conn_id);
    }
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
