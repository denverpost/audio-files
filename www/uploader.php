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
<form action="" id="upLoadImage" name='upLoadImage' method='post' enctype="multipart/form-data">
    <h2>Describe the audio</h2>
   <div id="radios">
    <input type="radio"  name="date"      id="today"      value="today" checked >Today<br />
        <input type="radio"  name="date"      id="tomorrow"   value="tomorrow">Tomorrow<br />
      <input type="radio"  name="date"      id="custom"     value="custom" onchange="populateInput()">Custom date:
   </div>
    <div id="uploadHere">
    Upload Audio: <input name="audio" type="file"><br /><br />
   </div>
   <input type="submit" name="submit" value="upload the File" onclick="populateInput();">
</form>

<form name="chooseDateForm" id="chooseDateForm">
   <input type="hidden" id="datepicker" onchange="populateInput()">
   <input type="text" id="dateField" onchange="dateFieldChange()"></span>
</form>

<script>
$(function(){
    $('#datepicker').datepicker({
        inline: true,
        nextText: '&rarr;',
        prevText: '&larr;',
        showOtherMonths: true,
        dateFormat: 'mm/dd/yy',
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        showOn: "button",
        buttonImage: "http://extras.denverpost.com/frontpages/calendar.svg",
        buttonImageOnly: false,
    });
    $('#datepicker, #dateField').val(sel_month+"/"+sel_day+"/"+sel_year);
    $('#chooseDateForm').submit(function () { return false; });
    $('#today').val(today);
    $('#tomorrow').val(tomorrow);
    $('.ui-datepicker-trigger, #dateField').click(function(){
        $('#today, #tomorrow').prop('checked', false);
        $('#custom').prop('checked', true);
        });
});
</script>
<?php

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

if(isset($_FILES["image"])) {
    echo "<div id='message'>";
    if ($_FILES["image"]["error"] > 0) {
        if ($_FILES["image"]["error"]==4) { echo "<div style='background-color:red'>No Image was chosen to be uploaded</div>"; exit; } // No image file was uploaded
        else { echo "<div style='background-color:red'>Error Code: " . $_FILES["image"]["error"] . "</div>"; exit; } // Another error occurred
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
        $_FILES["image"]["name"] = $year."-".$month."-".$day; // rename the file to today's date

        if (!file_exists($FTP_DIRECTORY."/".$year."/"))            { ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year); }            // if the year folder does not exist, create it
        if (!file_exists($FTP_DIRECTORY."/".$year."/".$month."/")) { ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year."/".$month); } // if the month folder does not exist, create it

        if (($_FILES["image"]["type"]=="application/pdf")||($_FILES["image"]["type"]=="image/jpeg")) {
            $extension = ".pdf";
            if($_FILES["image"]["type"]=="image/jpeg") { $extension = "-original.jpg"; }
            move_uploaded_file($_FILES["image"]["tmp_name"], $_FILES["image"]["name"].$extension); // drop original file in current folder for imagick to use
            $im = new imagick();
            $im->setResolution(72,72);
            $im->readimage($_FILES["image"]["name"].$extension);
            $im->setImageFormat('jpg');
            $im->scaleImage(350,0);
            $im->writeImage($_FILES["image"]["name"].".jpg"); // Create the smaller jpg version in current folder
            $im->clear();

            if (ftp_put($conn_id, $FTP_DIRECTORY.$location.$_FILES["image"]["name"].$extension, $_FILES["image"]["name"].$extension, FTP_BINARY)) {
                echo "<div style='background-color:green'>Original file uploaded!</div>";
                }
            else { echo "<div style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The original file did not upload!</div>"; }
            if (ftp_put($conn_id, $FTP_DIRECTORY.$location.$_FILES["image"]["name"].".jpg", $_FILES["image"]["name"].".jpg", FTP_BINARY)) {

                $filename = '"'.$_FILES["image"]["name"].'"';
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

                    $newString = "frontPages({arr:[";
                    for($i=0;$i<sizeof($json_c);$i++) {
                        $newString .= $json_c[$i];
                        if( $i < sizeof($json_c)-1 ) { $newString .= ","; }
                        }

                    $newString .= "]})";

                    file_put_contents("frontpages.json", $newString);

                    }
                echo "<div style='background-color:green'>.jpg created and uploaded!</div>";
                }
            else { echo "<div style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The .jpg file did not upload!</div>"; }

            unlink($_FILES["image"]["name"].".jpg");     // delete the jpg file in current folder
            unlink($_FILES["image"]["name"].$extension); // delete the pdf file in current folder
            }

        else { echo "<div style='background-color:red'>File must be a pdf or jpg file!<br> This file is: ".$_FILES["image"]["type"]."</div>"; }
        echo "</div>";
        ftp_close($conn_id);
    }
}
?>
