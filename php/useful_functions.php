<?
/**
	* Получаем текущие время в формате сайта
	* @param string $format - формат даты "FULL" или "SHORT"
	* @example date_now($format)
	* @return  <string>
*/
function date_now($format='')
{
	global $DB;
	if ($format=='SHORT')
	{
		return date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), time());
	}
	else
	{
		return date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time());
	}
}

/**
	* Функция склонения числительных в русском языке
	* @example declOfNum(($num),Array(' комментарий', ' комментария',' комментариев','комментариев'));
	* @param int    $number Число которое нужно просклонять
	* @param array  $titles Массив слов для склонения
	* @return string
**/

function declOfNum($number, $titles, $return_num_only=false)
{
    $cases = array (2, 0, 1, 1, 1, 2);
	if($return_num_only)
	{
		return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
	}
	else
	{		
		return $number." ".$titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
	}
}

/**
	* Функия для вывода ошибок на сайте. В качестве входных параметров может принимать любой тип данных
	* @param mixed $message
	* @param string $title
	* @example DebugMessage($message)
	* @return  html string
	*
*/
function DebugMessage($message, $title = false, $color = "#008B8B")
{
	echo '<table border="0" cellpadding="5" cellspacing="0" style="border:1px solid '.$color.';margin:2px;"><tr><td>';
	if (strlen($title)>0)
	{
		
	echo '<p style="color: '.$color.';font-size:11px;font-family:Verdana;">['.$title.']</p>';
	}
	if (is_array($message) || is_object($message))
	{
	   echo '<pre style="color:'.$color.';font-size:11px;font-family:Verdana;">'; print_r($message); echo '</pre>';
	}
	else
	{
	   echo '<p style="color:'.$color.';font-size:11px;font-family:Verdana;">'.$message.'</p>';
	}
	echo '</td></tr></table>';
}

/**
	* Для выборок нескольких картинок удобней всего пользоваться CFile::GetList, который вернет нам базовую директорию и пусть в ней до файла. Функция собирает это все и возвращает нормальный url от корня до файла
	* @param mixed $sourse_array - массив с данными выборки
*/
function makeUrlAfterCFile($sourse_array)
{
	if((!empty($sourse_array["SUBDIR"]))&&(!empty($sourse_array["FILE_NAME"])))
	{
		return '/upload/'.$sourse_array["SUBDIR"].'/'.$sourse_array["FILE_NAME"];
	}	
}

/**
	* конвертация размера картинок 
	* @param mixed $image - путь к картинки
	* @param number $w - ширина картинки
	* @param number $h - высота картинки
	* @param number $add_params - дополнительные атрибуты в теге img		
	* @param boolen $exact_size - Если true то картинка будет подогнана к размеру, если false то картинка будет обрезатся по краям	
	* @example convert_pict('/iamges/pic.jpg',100,200,false,false);
	* @return  <array>
	*
*/

function convert_pict($image,$w,$h,$add_params=false,$exact_size=true)
{
	$image_array=explode("/",$image);
	$image_name=$image_array[count($image_array)-1];
	$image_path=str_replace($image_name,"",$image);
	$pict_size=getimagesize($_SERVER['DOCUMENT_ROOT'].$image);
	$width=$pict_size[0];
	$height=$pict_size[1];
	$new_width=$width;
	$new_height=$height;
	if (!$exact_size)
	{
		if ($width>$w || $height>$h)
		{
			if ($width<$height)
			{
				$ratio=$height/$h;
				$new_height=$h;
				$new_width=intval($width/$ratio);
			}
			else
			{
				$ratio=$width/$w;
				$new_width=$w;
				$new_height=intval($height/$ratio);
			}
		}
		$w=$new_width;
		$h=$new_height;
	}

	$cache_path = $image_path;

	$convert_path = '/usr/bin/convert';

	$cache_name = $w."x".$h."_".$image_name;
	if ($exact_size) $cache_name="exact_".$cache_name;
	$cache = $cache_path.$cache_name;

	if (!file_exists($_SERVER['DOCUMENT_ROOT'].$image))
	{
	    die('ERROR: That image does not exist.');
	}

	$return["IMG"]="<img src='".$cache."' width=".$w." height=".$h." ".$add_params." />";
	$return["SRC"]=$cache;
	$return["WIDTH"]=$w;
	$return["HEIGHT"]=$h;

	if (file_exists($_SERVER['DOCUMENT_ROOT'].$cache)) return $return;

	$commands=" -size ".$w."x".$h." ".$_SERVER['DOCUMENT_ROOT'].$image." -resize ".$w."x".$h.' +profile "*" '.$_SERVER['DOCUMENT_ROOT'].$cache.'';
	$convert = $convert_path.' '.$commands;

    if (!file_exists($_SERVER['DOCUMENT_ROOT'].$cache))
    {
	       $thumb = new Imagick($_SERVER['DOCUMENT_ROOT'].$image);
	       if (!$exact_size)
	       {
				$thumb->resizeImage($w,$h,Imagick::FILTER_LANCZOS,1);
	       }
	       else
	       {
				$thumb->cropThumbnailImage($w,$h);
	       }

           $thumb->writeImage($_SERVER['DOCUMENT_ROOT'].$cache);
           $thumb->destroy();
    }

	if (!file_exists($_SERVER['DOCUMENT_ROOT'].$cache))
	{
		die('ERROR: Image conversion failed.');
	}
	return $return;
}

/**
	* конвертация размера картинок работает по аналогии с convert_pict
	* @param mixed $image - путь к картинки
	* @param number $w - ширина картинки
	* @param number $h - высота картинки
	* @param number $add_params - дополнительные атрибуты в теге img		
	* @param boolen $exact_size - Если true то картинка будет подогнана к размеру, если false то картинка будет обрезатся по краям	
	* @example convert_pict_full('/iamges/pic.jpg',100,200,false,false);
	* @return  html  код
	*
*/
function convert_pict_full($src, $width, $height, $style=false, $exact_size=true)
{
	if(!strlen($src))
	{
		return false;
	}
	else
	{
		if(intval($src))
			$src = CFile::GetPath($src);

		if(!file_exists($_SERVER['DOCUMENT_ROOT'].$src))
			return false;

		$converted_image_url = convert_pict($src, $width, $height, $style, $exact_size);
		$converted_image = $converted_image_url["IMG"];

		return $converted_image;
	}
}

function convert_pict_full_path($src, $width, $height, $style=false, $exact_size=true)
{
	if(!strlen($src))
	{
		return false;
	}
	else
	{
		if(intval($src))
			$src = CFile::GetPath($src);

		if(!file_exists($_SERVER['DOCUMENT_ROOT'].$src))
			return false;

		$converted_image_url = convert_pict($src, $width, $height, $style, $exact_size);
		if(strlen($converted_image_url['SRC']) > 0){
			$converted_image = $converted_image_url["SRC"];			
		}else return false;
		
		return $converted_image;
	}
}

function is_index()
{
	global $APPLICATION;
	$dir = $APPLICATION->GetCurDir();
	if(($dir == '/')||($dir == '/index.php')){
		return true;
	}else{
		return false;
	}
}

function checkPhone($string, $template = '/^[( ]{0,1}([0-9]{0,3})[) ]{0,}(([0-9]{2}[- ]{0,}[0-9]{2}[- ]{0,}[0-9]{3})|([0-9]{3}[- ]{0,}[0-9]{2}[- ]{0,}[0-9]{2})|([0-9]{3}[- ]{0,}[0-9]{1}[- ]{0,}[0-9]{3})|([0-9]{2}[- ]{0,}[0-9]{3}[- ]{0,}[0-9]{2}))/')
{
	/*
		// '/((8\+7)-?)?\(|?\d{3,5}\)?-?\d{1}-?\d{1}-?\d{1}-?\d{1}-?\d{1}((-?\d{1})?-?\d{1})?/'
			// '/(8|7|\+7){0,1}[- \\\\(]{0,}([9][0-9]{2})[- \\\\)]{0,}(([0-9]{2}[-
		]{0,}[0-9]{2}[- ]{0,}[0-9]{3})|([0-9]{3}[- ]{0,}[0-9]{2}[- ]{0,}[0-9]{2})|([0-9]{3}[-
		]{0,}[0-9]{1}[- ]{0,}[0-9]{3})|([0-9]{2}[- ]{0,}[0-9]{3}[- ]{0,}[0-9]{2}))/'
		Разберем регулярное выражение
			Посимвольное задание
				\d - цифра 0-9
				\D - Не цифра (любой символ кроме 0-9)
				\s - Пустой символ (пробел или таб)
				\S - не устой символ (все кроме пробела и таба)
				\w - Все буквы, цифры и _
				\W - все кроме указанного в \w
			Задание подмножеств символов
				^ - если этот симпол стоит первым, сразу после [ то выступает как отрицание
				[0-9]
				[02468] - четная цифра

			{2,4} - символ долен повториться минимум 2 раза, но не более 4.
			{,5} - символ может отсутствовать (т.к. не задано минимальное количество повторений), но если присутствует, то не должен повторяться более 5 раз.
			{3,} - символ должен повторяться минимум 3 раза, но может быть и больше.
			{4} - символ должен повторяться ровно 4 раза
	*/
	if (preg_match($template, $string))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function nowToDB()
{
	$nowTimeStamp = mktime();
	$now = ConvertTimeStamp($nowTimeStamp, "FULL");
	return $now;
}

function getPageContent($query = '', $url = 'http://test.ru/export/index.php', $auth = false)
{
	if(!empty($query))
	{
		$url .= $query;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if($auth)
	{
		curl_setopt($ch, CURLOPT_USERPWD, "login:password");
	}
	curl_setopt($ch, CURLOPT_HEADER, 0);
	//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$str = curl_exec($ch);
	curl_close($ch);
	return $str;
}

/*	Функция проверки принадлежит ли браузер к мобильным устройствам
	Возвращает 0 - браузер стационарный или определить его не удалось
	1-4 - браузер запущен на мобильном устройстве
*/
function is_mobile()
{
    $user_agent = strtolower(getenv('HTTP_USER_AGENT'));
    $accept = strtolower(getenv('HTTP_ACCEPT'));
     
    if ((strpos($accept,'text/vnd.wap.wml') !== false) ||
        (strpos($accept,'application/vnd.wap.xhtml+xml') !== false)) {
      return true; // Мобильный браузер обнаружен по HTTP-заголовкам
    }
     
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) ||
        isset($_SERVER['HTTP_PROFILE']))
    {
      return true; // Мобильный браузер обнаружен по установкам сервера
    }
     
	if (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|'.
		'wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|'.
		'lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|'.
		'mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|'.
		'm881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|'.
		'r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|'.
		'i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|'.
		'htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|'.
		'sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|'.
		'p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|'.
		'_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|'.
		's800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|'.
		'd736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |'.
		'sonyericsson|samsung|240x|x320vx10|nokia|sony cmd|motorola|'.
		'up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|'.
		'pocket|kindle|mobile|psp|treo|android|iphone|ipod|webos|wp7|wp8|'.
		'fennec|blackberry|htc_|opera m|windowsphone)/', $user_agent))
	{
		return true; // Мобильный браузер обнаружен по сигнатуре User Agent
	}
     
    if (in_array(substr($user_agent,0,4),
    	array("1207", "3gso", "4thp", "501i", "502i", "503i", "504i", "505i", "506i",
              "6310", "6590", "770s", "802s", "a wa", "abac", "acer", "acoo", "acs-",
              "aiko", "airn", "alav", "alca", "alco", "amoi", "anex", "anny", "anyw",
              "aptu", "arch", "argo", "aste", "asus", "attw", "au-m", "audi", "aur ",
              "aus ", "avan", "beck", "bell", "benq", "bilb", "bird", "blac", "blaz",
              "brew", "brvw", "bumb", "bw-n", "bw-u", "c55/", "capi", "ccwa", "cdm-",
              "cell", "chtm", "cldc", "cmd-", "cond", "craw", "dait", "dall", "dang",
              "dbte", "dc-s", "devi", "dica", "dmob", "doco", "dopo", "ds-d", "ds12",
              "el49", "elai", "eml2", "emul", "eric", "erk0", "esl8", "ez40", "ez60",
              "ez70", "ezos", "ezwa", "ezze", "fake", "fetc", "fly-", "fly_", "g-mo",
              "g1 u", "g560", "gene", "gf-5", "go.w", "good", "grad", "grun", "haie",
              "hcit", "hd-m", "hd-p", "hd-t", "hei-", "hiba", "hipt", "hita", "hp i",
              "hpip", "hs-c", "htc ", "htc-", "htc_", "htca", "htcg", "htcp", "htcs",
              "htct", "http", "huaw", "hutc", "i-20", "i-go", "i-ma", "i230", "iac",
              "iac-", "iac/", "ibro", "idea", "ig01", "ikom", "im1k", "inno", "ipaq",
              "iris", "jata", "java", "jbro", "jemu", "jigs", "kddi", "keji", "kgt",
              "kgt/", "klon", "kpt ", "kwc-", "kyoc", "kyok", "leno", "lexi", "lg g",
              "lg-a", "lg-b", "lg-c", "lg-d", "lg-f", "lg-g", "lg-k", "lg-l", "lg-m",
              "lg-o", "lg-p", "lg-s", "lg-t", "lg-u", "lg-w", "lg/k", "lg/l", "lg/u",
              "lg50", "lg54", "lge-", "lge/", "libw", "lynx", "m-cr", "m1-w", "m3ga",
              "m50/", "mate", "maui", "maxo", "mc01", "mc21", "mcca", "medi", "merc",
              "meri", "midp", "mio8", "mioa", "mits", "mmef", "mo01", "mo02", "mobi",
              "mode", "modo", "mot ", "mot-", "moto", "motv", "mozz", "mt50", "mtp1",
              "mtv ", "mwbp", "mywa", "n100", "n101", "n102", "n202", "n203", "n300",
              "n302", "n500", "n502", "n505", "n700", "n701", "n710", "nec-", "nem-",
              "neon", "netf", "newg", "newt", "nok6", "noki", "nzph", "o2 x", "o2-x",
              "o2im", "opti", "opwv", "oran", "owg1", "p800", "palm", "pana", "pand",
              "pant", "pdxg", "pg-1", "pg-2", "pg-3", "pg-6", "pg-8", "pg-c", "pg13",
              "phil", "pire", "play", "pluc", "pn-2", "pock", "port", "pose", "prox",
              "psio", "pt-g", "qa-a", "qc-2", "qc-3", "qc-5", "qc-7", "qc07", "qc12",
              "qc21", "qc32", "qc60", "qci-", "qtek", "qwap", "r380", "r600", "raks",
              "rim9", "rove", "rozo", "s55/", "sage", "sama", "samm", "sams", "sany",
              "sava", "sc01", "sch-", "scoo", "scp-", "sdk/", "se47", "sec-", "sec0",
              "sec1", "semc", "send", "seri", "sgh-", "shar", "sie-", "siem", "sk-0",
              "sl45", "slid", "smal", "smar", "smb3", "smit", "smt5", "soft", "sony",
              "sp01", "sph-", "spv ", "spv-", "sy01", "symb", "t-mo", "t218", "t250",
              "t600", "t610", "t618", "tagt", "talk", "tcl-", "tdg-", "teli", "telm",
              "tim-", "topl", "tosh", "treo", "ts70", "tsm-", "tsm3", "tsm5", "tx-9",
              "up.b", "upg1", "upsi", "utst", "v400", "v750", "veri", "virg", "vite",
              "vk-v", "vk40", "vk50", "vk52", "vk53", "vm40", "voda", "vulc", "vx52",
              "vx53", "vx60", "vx61", "vx70", "vx80", "vx81", "vx83", "vx85", "vx98",
              "w3c ", "w3c-", "wap-", "wapa", "wapi", "wapj", "wapm", "wapp", "wapr",
              "waps", "wapt", "wapu", "wapv", "wapy", "webc", "whit", "wig ", "winc",
              "winw", "wmlb", "wonu", "x700", "xda-", "xda2", "xdag", "yas-", "your",
              "zeto", "zte-")))
	{
    	return true; // Мобильный браузер обнаружен по сигнатуре User Agent
    }
     
    return false; // Мобильный браузер не обнаружен
}
?>