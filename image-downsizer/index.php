<?php
try {
	if (!isset($_GET["url"])) throw new BadRequestError("Parameter \"url\" is required");
	if (!isset($_GET["width"])) throw new BadRequestError("Parameter \"width\" is required");
	
	$url = $_GET["url"];
	$requestWidth = nextPow2((int)$_GET["width"]);

	// normalize path
	$path = realpath($_SERVER["DOCUMENT_ROOT"] . "/" . $url);
	if (!$path) throw new BadRequestError("Image not found");

	// get image type
	switch(end(explode('.', $path))) {
		case "png": $imageType = new PNGImageType; break;
		case "jpeg":
		case "jpg": $imageType = new JPEGImageType; break;
		default: throw new BadRequestError("Image type not supported");
	}

	// get image dimensions
	list($originalWidth) = getimagesize($path);

	$redirectURL = $url;
	if ($requestWidth < $originalWidth) {
		$cacheDirPath = "cache/" . bin2hex($url) . "/";
		$cachePath = $cacheDirPath . $requestWidth . "." . $imageType->getExtension();
	
		if (!file_exists($cachePath) || filemtime($path) > filemtime($cachePath)) {
			mkdir($cacheDirPath, 0777, true);
			$image = imagescale($imageType->read($path), $requestWidth, -1, IMG_BILINEAR_FIXED);
			$imageType->write($image, $cachePath);
		}

		$redirectURL = "./" . $cachePath;
	}

	header("Location: $redirectURL");
	die();
} catch(BadRequestError $e) {
	http_response_code(400);
	echo json_encode([
		"errorName" => "Bad Request",
		"errorDetails" => $e->getMessage(),
	]);
} catch(Error|Exception $e) {
	http_response_code(500);
	echo json_encode([
		"errorName" => "Internal Server Error",
		"errorDetails" => $e->getMessage(),
		"line" => $e->getLine(),
	]);
}

function nextPow2($number) {
    if($number < 2) return 1;
    for($i = 0 ; $number > 1 ; $i++) {
		$number = $number >> 1;
    }
    return 1<<($i+1);
}

class BadRequestError extends Error {

}

class PNGImageType {
	function getExtension() {
		return "png";
	}

	function read($path) {
		return imagecreatefrompng($path);
	}

	function write($image, $path) {
		imagepng($image, $path);
	}
}

class JPEGImageType {
	function getExtension() {
		return "jpg";
	}

	function read($path) {
		return imagecreatefromjpeg($path);
	}

	function write($image, $path) {
		imagejpeg($image, $path);
	}
}