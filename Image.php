<?php
namespace framework;
class Image
{
	//路径
	protected $path;
	//是否启用随机名字
	protected $isRandName;
	//文件类型
	protected $type;
	
	//构造方法初始化成员属性
	function __construct($path = './upload/', $isRandName = true, $type = 'png')
	{
		$this->path = $path;
		$this->isRandName = $isRandName;
		$this->type = $type;
	}
	
	//水印方法
	function water($image, $water, $position, $tmd = 100, $prefix = 'water_')
	{
		//判断图片是否存在
		if ((!file_exists($image)) || (!file_exists($water))) {
			exit('图片资源不存在');
		}
		//得到图片的宽度和高度
		$imageInfo = self::getImageInfo($image);
		$waterInfo = self::getImageInfo($water);
		//判断水印图片是否能忒上去
		if (!$this->checkImage($imageInfo, $waterInfo)) {
			exit('水印图片太大');
		}
		//打开图片
		$imageRes = self::openAnyImage($image);
		$waterRes = self::openAnyImage($water);
		//计算位置
		$pos = $this->getPosition($position, $imageInfo, $waterInfo);
		//忒上来
		imagecopymerge($imageRes, $waterRes, $pos['x'], $pos['y'], 0, 0, $waterInfo['width'], $waterInfo['height'], $tmd);
		//得到新的文件名
		$newName = $this->createNewName($image, $prefix);
		//得到新的文件路径
		$newPath = rtrim($this->path, '/').'/'.$newName;
		//保存文件
		$this->saveImage($imageRes, $newPath);
		//销毁图像资源
		imagedestroy($imageRes);
		imagedestroy($waterRes);
		return $newPath;
	}
	
	//缩放方法
	function suofang($image, $width, $height, $prefix = 'sf_')
	{
		//得到图片宽度和高度
		$info = self::getImageInfo($image);
		//根据原来的宽高和传递进来的宽高计算宽高
		$size = $this->getNewSize($width, $height, $info);
		//打开图片
		$imageRes = self::openAnyImage($image);
		//完成缩放
		$newRes = $this->kidOfImage($imageRes, $size, $info);
		//保存图片
		$newName = $this->createNewName($image, $prefix);
		$newPath = rtrim($this->path, '/').'/'.$newName;
		//保存文件
		$this->saveImage($newRes, $newPath);
		//销毁图片
		imagedestroy($imageRes);
		imagedestroy($newRes);
	}
	
	protected function saveImage($imageRes, $newPath)
	{
		$func = 'image'.$this->type;
		$func($imageRes, $newPath);
	}
	
	//得到新的名字
	protected function createNewName($imagePath, $prefix)
	{
		if ($this->isRandName) {
			$name = $prefix.uniqid().'.'.$this->type;
		} else {
			$name = $prefix.pathinfo($imagePath)['filename'].$this->type;
		}
		return $name;
	}
	
	protected function getPosition($pos, $imageInfo, $waterInfo)
	{
		$arr1 = [0, ($imageInfo['width'] - $waterInfo['width']) / 2, $imageInfo['width'] - $waterInfo['width']];
		$arr2 = [0, ($imageInfo['height'] - $waterInfo['height']) / 2, $imageInfo['height'] - $waterInfo['height']];
		if ($pos) {
			//得到行号和列号
			$row = floor(($pos - 1) / 3);  //1
			$col = ($pos - 1) % 3;         //2
			$x = $arr1[$col];
			$y = $arr2[$row];
		} else {
			$x = mt_rand(0, $imageInfo['width'] - $waterInfo['width']);
			$y = mt_rand(0, $imageInfo['height'] - $waterInfo['height']);
		}
		return ['x' => $x, 'y' => $y];
	}
	
	//判断水印图片大小
	protected function checkImage($imageInfo, $waterInfo)
	{
		if (($waterInfo['width'] > $imageInfo['width']) || ($waterInfo['height'] > $imageInfo['height'])) {
			return false;
		}
		return true;
	}
	
	protected function kidOfImage($srcImg, $size, $imgInfo)
	{
		//传入新的尺寸，创建一个指定尺寸的图片
		$newImg = imagecreatetruecolor($size['old_w'], $size['old_h']);		
		//定义透明色
		$otsc = imagecolortransparent($srcImg);
		if ($otsc >= 0) {
			//取得透明色
			$transparentcolor = imagecolorsforindex($srcImg, $otsc);
			//创建透明色
			$newtransparentcolor = imagecolorallocate(
				$newImg,
				$transparentcolor['red'],
				$transparentcolor['green'],
				$transparentcolor['blue']
			);
		} else {
			//将黑色作为透明色，因为创建图像后在第一次分配颜色时背景默认为黑色
			$newtransparentcolor = imagecolorallocate($newImg, 0, 0, 0);
		}
		//背景填充透明
		imagefill( $newImg, 0, 0, $newtransparentcolor);		 
		imagecolortransparent($newImg, $newtransparentcolor);

		imagecopyresampled( $newImg, $srcImg, $size['x'], $size['y'], 0, 0, $size["new_w"], $size["new_h"], $imgInfo["width"], $imgInfo["height"] );
		return $newImg;
	}


	/*
	$width:最终缩放的宽度
	$height:最终缩放的高度
	$imgInfo:原始图片的宽度和高度
	*/
	protected function getNewSize($width, $height, $imgInfo)
	{
		$size['old_w'] = $width;
		$size['old_h'] = $height;
		
		$scaleWidth = $width / $imgInfo['width'];
		$scaleHeight = $height / $imgInfo['height'];
		$scaleFinal = min($scaleWidth, $scaleHeight);

		$size['new_w'] = round($imgInfo['width'] * $scaleFinal);
		$size['new_h'] = round($imgInfo['height'] * $scaleFinal);
		if ($scaleWidth < $scaleHeight) {
			$size['x'] = 0;
			$size['y'] = round(abs($size['new_h'] - $height) / 2);
		} else {
			$size['y'] = 0;
			$size['x'] = round(abs($size['new_w'] - $width) / 2);
		}
		return $size;
	}
	
	//根据文件路径打开任意图片
	static function openAnyImage($imagePath)
	{
		$mime = self::getImageInfo($imagePath)['mime'];
		switch ($mime) {
			case 'image/png':
				$image = imagecreatefrompng($imagePath);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($imagePath);
				break;
			case 'image/jpeg':
				$image = imagecreatefromjpeg($imagePath);
				break;
			case 'image/wbmp':
				$image = imagecreatefromwbmp($imagePath);
				break;
		}
		return $image;
	}
	
	//根据文件路径获取文件信息
	static function getImageInfo($imagePath)
	{
		$info = getimagesize($imagePath);
		$data['width'] = $info[0];
		$data['height'] = $info[1];
		$data['mime'] = $info['mime'];
		return $data;
	}
}












