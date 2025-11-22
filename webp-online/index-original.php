<?php
// form for image name and quality
echo "<form method='post' action='index.php'>";
echo "<input type='text' name='name' placeholder='Image Name'>";
echo "<input type='number' name='quality' placeholder='Quality'>";
echo "<input type='submit' name='submit' value='Convert'>";
echo "</form>";

// display image list
$path = "upload/";
$files = scandir($path);
$images = array_diff(scandir($path), array('.', '..'));
// ignore files that are not images
$images = preg_grep('/\.(jpg|jpeg|png)$/i', $images);
foreach ($images as $image) {
    $size = getimagesize("upload/$image");
    $filesize = filesize("upload/$image");
    // convert to KB
    $filesize = round($filesize / 1024, 2);
    echo "<div style='display: inline-block; margin: 10px;'>";
        echo "<img src='upload/$image' width='100'>";
        echo "<p>$size[0] x $size[1]</p>";
        echo "<p>$filesize KB</p>";
    echo "</div>";
}

echo "<div><h2>Converted Images</h2></div>";

// display converted images with variable called $converted
$converted = scandir("convert/");
$converted = array_diff(scandir("convert/"), array('.', '..'));
$converted = preg_grep('/\.(webp)$/i', $converted);
foreach ($converted as $convert) {
    $size = getimagesize("convert/$convert");
    $filesize = filesize("convert/$convert");
    $filesize = round($filesize / 1024, 2);
    echo "<div style='display: inline-block; margin: 10px;'>";
        echo "<img src='convert/$convert' width='100'>";
        echo "<p>$size[0] x $size[1]</p>";
        echo "<p>$filesize KB</p>";
    echo "</div>";
}


// if form submitted
if (isset($_POST['submit'])) {
    $filename = $_POST['name'];
    $quality = $_POST['quality'];
    $number = 0;

    // list files in directory
    $files = scandir($path);
    $files = array_diff(scandir($path), array('.', '..'));

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == "png") {
            $name = $file;
            $newname = $filename . "-".$number.".webp";
            $img = imagecreatefrompng($path.$name);
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            imagewebp($img, 'convert/'.$newname, $quality);
            $number++;
        }
        //if jpg
        if (pathinfo($file, PATHINFO_EXTENSION) == "jpg" || pathinfo($file, PATHINFO_EXTENSION) == "jpeg") {
            $name = $file;
            $newname = $filename . "-".$number. ".webp";
            $img = imagecreatefromjpeg($path.$name);
            imagewebp($img, 'convert/'.$newname, $quality);
            $number++;
        }
    }

}