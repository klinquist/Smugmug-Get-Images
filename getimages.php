<?php

//  getimages.php v1.21
// 
//  (c)2011 Kris Linquist kris@linquist.net
//  Script downloads pictures from smugmug
//
//  To be run from the command line.  PHP & cURL extension required
//  Usage:
//
//  php getimages.php "[smugmug rss2.0 feed url]" [Destination with trailing slash [optional]]
//
//  -or-
//
//  php getimages.php "[gallery URL]" [Destination with trailing slash [optional]]
//
//  -or-
//
//  php getimages.php     
//  You will be prompted for URL/galleryID & size of images to download.  If you just paste the gallery URL on the command line, I will use this default size:
//  [must be Small/Medium/Large/XLarge/X2Large/X3Large/Original, case sensitive]:
$defaultsize = "Original";
//
//
//
//  Example for the "recent photos" feed: 
//  php getimages.php "http://www.smugmug.com/hack/feed.mg?Type=nicknameRecentPhotos&Data=williams&format=rss200&Size=X2Large" ./
//
//
//  Smugmug feeds help:
//  http://help.smugmug.com/customer/portal/articles/84258
//  


function downloadfiles($url, $filename)  {
    global $argv;
    $bd = curl_init();
    curl_setopt($bd, CURLOPT_URL, $url);
    $filename = $argv[2] .  $filename;
    
    if (file_exists($filename)) {
        echo "$filename already exists, skipping\n\n";
    } else {
        echo"downloading $url and writing it to $filename\n\n";
        $fp = fopen($filename, "w+");
         curl_setopt($bd, CURLOPT_BINARYTRANSFER,1);
         curl_setopt($bd, CURLOPT_HEADER, 0);
        curl_setopt($bd, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($bd, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($bd, CURLOPT_FILE, $fp);
         curl_exec($bd);
         curl_close ($bd);
        fclose($fp);
    }

}




function parseRSS($xml)  {
    echo "RSS Feed name: ".$xml->channel->title."\n\n";
    $cnt = count($xml->channel->item);
    for($i=0; $i<$cnt; $i++)  {
        $url     = $xml->channel->item[$i]->link;
        $title     = $xml->channel->item[$i]->title;
        $desc = $xml->channel->item[$i]->description;
        $pattern = "/img src=\"(http:\/\/.+?\.jpg)/";
        preg_match($pattern, $desc, $matches);
        $match = substr($matches[0],9);
        $lastslash = strrpos($matches[0], "/") +1;
        $thefile = substr($matches[0], $lastslash);
        downloadfiles($match, $thefile);
    }
    

}


function getIDfromURL($url) {


	$underscore = strpos($url, "_");
	$probably = substr($url, $underscore-8, 15);
	// if ID is 14 digits, remove the first slash..   v1.2 correction
	if (strpos($probably,"/") === FALSE) {  } else { $probably = substr($probably, strpos($probably, "/")+1, 15); }
	
	echo"Found gallery ID: $probably\n\n";
	
	return $probably;
}



//Check and see if there is a command line argument
if ($argv[1] == "") {
    echo"\n\nDownload pictures from smugmug\n©2011 Kris Linquist kris@linquist.net\n usage: php getimages.php \"[smugmug rss2.0 feed OR regular gallery url]\" [Destination]\n\nExample: php getimages.php \"http://photos.linquist.net/hack/feed.mg?Type=nicknameRecentPhotos&Data=williams&format=rss200&Size=X2Large\" pictures/\n\n";



    while ($galleryID == "") {
        fwrite(STDOUT, "Enter a smugmug 14/15-character galleryID & key combination [example: 14422772_HpR9FH] OR paste the full URL from someone's gallery and I'll try to extract it  OR press Ctrl-C to exit:\n");
        
        $IDInput = trim(fgets(STDIN));
  			
  		if (strlen($IDInput) < 16 && strpos($IDInput, "_") == TRUE) { $galleryID = $IDInput; break; }      

        if (substr($IDInput,0,4) == "http") {
                        
            $galleryID = getIDfromURL($IDInput);
            
        }
    }
    

    
    while (!isset($correctedsize)) {
        fwrite(STDOUT, "Enter a size to download [S/M/L/X/2/3/O] (default = $defaultsize): ");
        $size = strtoupper(trim(fgets(STDIN)));
        switch ($size) {
            case "S":
                $correctedsize = "Small";
                break;
            case "M":
                $correctedsize = "Medium";
                break;
            case "L":
                $correctedsize = "Large";
                break;
            case "X":
                $correctedsize = "XLarge";
                break;
            case "2":
                $correctedsize = "X2Large";
                break;
            case "3":
                $correctedsize = "X3Large";
                break;
            case "O";
                $correctedsize = "Original";
                break;
            case "";
                $correctedsize = $defaultsize;
                break;
            default:
                echo"Incorrect size input\n\n";
        
        }
    }
    
    $feedurl = "http://www.smugmug.com/hack/feed.mg?Type=gallery&Data=" . $galleryID . "&format=rss200&Size=" . $correctedsize . "&ImageCount=999&Paging=0";
    $ch = curl_init($feedurl);
    echo "feed url $feedurl\n\n";


}  else {


    if (substr($argv[1],0,4) != "http") { 
        die("Invalid feed URL! Feed must be in quotes and begin with http://\n\n");
    }

    
    if (strpos($argv[1], "feed.mg") == TRUE) {  
        $ch = curl_init($argv[1]);  
    } else {
        $galleryID = getIDfromURL($argv[1]);
        $feedurl = "http://www.smugmug.com/hack/feed.mg?Type=gallery&Data=" . $galleryID . "&format=rss200&Size=" . $defaultsize . "&ImageCount=999&Paging=0";
        $ch = curl_init($feedurl);

    }
     
    
    
    
}

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    $doc = new SimpleXmlElement($data, LIBXML_NOCDATA);
    parseRSS($doc);





?>