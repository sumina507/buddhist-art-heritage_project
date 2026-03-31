<?php
// Create a default artwork image if it doesn't exist
$artwork_dir = 'uploads/artworks/';
if (!file_exists($artwork_dir)) {
    mkdir($artwork_dir, 0777, true);
}

// Create a simple colored image
function createDefaultImage($filename, $text) {
    $img = imagecreate(400, 300);
    $bg = imagecolorallocate($img, 52, 152, 219); // Blue
    $text_color = imagecolorallocate($img, 255, 255, 255);
    
    // Add some pattern
    for ($i = 0; $i < 10; $i++) {
        $color = imagecolorallocate($img, rand(200, 255), rand(200, 255), rand(200, 255));
        imageline($img, rand(0, 400), rand(0, 300), rand(0, 400), rand(0, 300), $color);
    }
    
    imagestring($img, 5, 150, 140, $text, $text_color);
    imagejpeg($img, $filename, 90);
    imagedestroy($img);
}

// Create sample images
$images = [
    'buddha-mandala.jpg' => 'Buddha Mandala',
    'green-tara.jpg' => 'Green Tara',
    'meditating-buddha.jpg' => 'Meditating Buddha',
    'thangka1.jpg' => 'Thangka Painting',
    'mandala1.jpg' => 'Mandala Art',
    'sculpture1.jpg' => 'Buddha Sculpture'
];

foreach ($images as $filename => $text) {
    $path = $artwork_dir . $filename;
    if (!file_exists($path)) {
        createDefaultImage($path, $text);
        echo "Created: $filename<br>";
    }
}

echo "<p>Default images created! <a href='gallery.php'>View Gallery</a></p>";
?>