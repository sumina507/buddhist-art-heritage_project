<?php
echo "<h2>Testing eSewa Connection</h2>";

// Test 1: Check if server is reachable
$url = "https://rc-epay.esewa.com.np";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 || $http_code == 302) {
    echo "✅ eSewa server is reachable!<br>";
} else {
    echo "❌ eSewa server not reachable. HTTP Status: $http_code<br>";
    echo "The server might be down. Try again later.<br>";
}

// Test 2: Check if your success/failure URLs are correct
echo "<br><strong>Your Callback URLs:</strong><br>";
$full_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
echo "Success URL: " . $full_url . "/buddhist-art-project/esewa-success.php<br>";
echo "Failure URL: " . $full_url . "/buddhist-art-project/esewa-failure.php<br>";

// Test 3: Check if these files exist
echo "<br><strong>File Check:</strong><br>";
if (file_exists('esewa-success.php')) {
    echo "✅ esewa-success.php exists<br>";
} else {
    echo "❌ esewa-success.php missing!<br>";
}
if (file_exists('esewa-failure.php')) {
    echo "✅ esewa-failure.php exists<br>";
} else {
    echo "❌ esewa-failure.php missing!<br>";
}
?>