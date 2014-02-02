<?php	
	$hostUrl = 'http://'.$_SERVER['SERVER_NAME'];
	$arraySrc = $_POST['arraySrc']; // массив путей аппликацый
	$arrayWidth = $_POST['arrayWidth']; // массив ширины аппликаций
	$arrayHeight = $_POST['arrayHeight']; // массив высот аппликаций
	$arrayTop = $_POST['arrayTop']; // массив X координат аппликаций
	$arrayLeft = $_POST['arrayLeft']; // массив  Y координат аппликаций
	$srcImage = $_POST['srcImage']; // путь до фотографии
	
	$workAreaTop = $_POST['workAreaTop']; // отступ сверху до робочей области
	$workAreaLeft = $_POST['workAreaLeft']; // отступ слева до робочей области
 	
	$formatAppl = '.png'; // формат аппликаций
	$pathTmp = './resources/tmp/'; // путь до временной папки
	$pathForReady = './resources/ready_foto/'; // путь до папки с готовыми картинками
	// генерируем случайное имя
	$randName = md5(time().mt_rand(0, 9999));
	$imgReady = $randName.'.jpg';
	// если есть аппликации, то накладываем их
	if(!empty($arraySrc)){
		// ресайз всех картинок для аппликации
		$arrayResizeSrc = array();
		foreach($arraySrc as $k=>$oneSrc){
			$tmpSrcResize = $pathTmp.md5(time().mt_rand(0, 9999)).$formatAppl;
			$imgResize = resizePhotoPNG($hostUrl.$oneSrc, $tmpSrcResize, $arrayHeight[$k], $arrayWidth[$k]);
			$arrayResizeSrc[$k] = $imgResize; // массив путей до отресайзиных картинок
		}		
		// наложение всех картинок
		$resultSrc = addApplicationImage($hostUrl.$srcImage, $workAreaLeft, $workAreaTop, $arrayLeft, $arrayTop,  $arrayResizeSrc, $pathForReady.$imgReady);
		// создание пути для вывода в браузере
		$resultSrc = str_replace('./', $hostUrl.'/', $resultSrc);
	}else{
		// если нет аппликаций, то просто копируем картинку
		copy($hostUrl.$srcImage, $pathForReady.$imgReady);
		$resultSrc = $pathForReady.$imgReady;
	}
	
	$result = array(
		'result' => 'success',
		'imgSrc' => $resultSrc
	);	
	echo json_encode($result);
	exit;
	
	
	/**
	* Наложение массива картинок на фотку
	*
	* @var string $img - фотка
	* @var array $imgX - X координата для фотки
	* @var array $imgY - Y координата для фотки
	* @var array $applX - массив X координат для аппликаций
	* @var array $applY - массив Y координат для аппликаций
	* @var array $appFon - массив путей до аппликаций
	* @var string $pathForImg - путь для сохранения фотки
	* @var string $formatImg - расширение картинки, для сохранения.
	*
	* @return string $pathForImg - путь до созданной фотки
	*/
	function addApplicationImage($img, $imgX, $imgY, $applX, $applY, $appFon, $pathForImg, $formatImg = 'jpeg'){
		$img = current(explode("?", $img)); // если нужно, то отбрасываем ревизию
		$size = getimagesize($img); // получаем размер картинки
		$width = $size[1]; // высота
		$height = $size[0]; // ширина		 
		// cоздаем картинку основу
		$mainImg = imagecreatetruecolor($height, $width); 
		$rgb = 0xffffff; //цвет заливки фона
		imagefill($mainImg, 0, 0, $rgb); //заливаем его белым цветом
		// загружаем картинку(фото)
		//определяем тип (расширение) картинки
		$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
		$icfunc = "imagecreatefrom" . $format;   //определение функции для расшерения файла
		//если нет такой функции, то прекращаем работу скрипта
		if (!function_exists($icfunc)) return false;
		$image = $icfunc($img);				
		// накладываем на основной фон фотографию
		imagecopy($mainImg, $image, 0, 0 , 0, 0, imagesx($image), imagesy($image));
		foreach($appFon as $k=>$oneAppl){
			//Загружаем одну аппликацию и задаем прозрачность
			$imageFon = imagecreatefrompng($oneAppl);
			imagealphablending($imageFon, false); 
			imagesavealpha($imageFon, true);				
			// совмещаем картинки
			imagecopy($mainImg, $imageFon, $applX[$k]-$imgX, $applY[$k]-$imgY, 0, 0, imagesx($imageFon), imagesy($imageFon));
		}		
		// сохраняем картинку
		$func = 'image'.$formatImg;
		$func($mainImg, $pathForImg, 100);		
		// очищаем память
		imagedestroy($mainImg);
		imagedestroy($imageFon);
		imagedestroy($image);
		// возвращаем путь к картинке
		return $pathForImg;
	}
	
	/*
	* Ресайз картинки PNG с сохранением прозрачности
	*
	* @var string $source – исходное изображение
	* @var string $path – путь для сохранения новой картинки
	* @var integer $height – новая высота
	* @var integer $width – новая ширина
	* @var string $formatImg - расширение картинки, для сохранения.
	*/
	function resizePhotoPNG($source, $path, $height, $width, $formatImg = 'png'){
		$rgb = 0xffffff; //цвет заливки фона
		$size = getimagesize($source);//узнаем размеры исходной картинки
		//определяем тип (расширение) картинки
		$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
		$icfunc = "imagecreatefrom" . $format;   //определение функции для расшерения файла
		//если нет такой функции, то прекращаем работу скрипта
		if (!function_exists($icfunc)) return false;
		$x_ratio = $width / $size[0]; //пропорция ширины
		$y_ratio = $height / $size[1]; //пропорция высоты
		$ratio = min($x_ratio, $y_ratio);
		$use_x_ratio = ($x_ratio == $ratio); //соотношения ширины к высоте
		$new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio); //ширина
		$new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio); //высота
		//расхождение с заданными параметрами по ширине
		$new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2);
		//расхождение с заданными параметрами по высоте
		$new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);
		//создаем вспомогательное изображение пропорциональное превью
		$img = imagecreatetruecolor($width, $height);
		//imagefill($img, 0, 0, $rgb); //заливаем его
		imagealphablending($img, false); 
		imagesavealpha($img, true);		
		$photo = $icfunc($source); //достаем наш исходник
		imagecopyresampled($img, $photo, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]); //копируем на него превью с учетом расхождений
		$func = 'image'.$formatImg;
		$func($img, $path); //сохраняем результат
		// Очищаем память после выполнения скрипта
		imagedestroy($img);
		imagedestroy($photo);
		// вернем путь для картинки
		return $path;
	}
	
?>