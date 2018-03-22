<?php
namespace framework;
/*
1、该类对外公开的方法只有一个 outImage,只要调用这个方法，就可以将验证码显示到浏览器，其它的为这个类服务的方法我们搞成protected，供子类来继承和重写
2、有些变量在该类里面会被反复的使用到，我们将其搞成成员属性，将不用公开的成员属性设置为protected
*/
class Code
{
	//验证码个数
	protected $number;
	//验证码类型
	protected $codeType;
	//验证码宽度
	protected $width;
	//验证码高度
	protected $height;
	//图片类型
	protected $imageType;
	//验证码
	protected $code;
	//图像资源
	protected $image;
	
	//初始化成员属性
	function __construct($number = 4, $codeType = 2, $width = 100, $height = 50, $imageType = 'png')
	{
		$this->number = $number;
		$this->codeType = $codeType;
		$this->width = $width;
		$this->height = $height;
		$this->imageType = $imageType;
		
		//调用生成验证码函数
		$this->code = $this->getCode();
	}
	
	//在析构方法中将图像资源销毁
	function __destruct()
	{
		imagedestroy($this->image);
	}
	
	//生成验证码字符串
	protected function getCode()
	{
		switch ($this->codeType) {
			//纯数字类型
			case 0:
				$code = $this->getNumberCode();
				break;
			//纯字母类型
			case 1:
				$code = $this->getCharCode();
				break;
			//字母数字混合型
			case 2:
				$code = $this->getNumCharCode();
				break;
			default:
				exit('不支持这个类型');
		}
		return $code;
	}
	
	//得到纯数字类型字符串函数
	protected function getNumberCode()
	{
		$str = join('', range(0, 9));
		return substr(str_shuffle($str), 0, $this->number);
	}
	
	//得到纯字母类型字符串
	protected function getCharCode()
	{
		$arr = range('a', 'z');
		$str = join('', $arr);
		$str .= strtoupper($str);
		return substr(str_shuffle($str), 0, $this->number);
	}
	
	//得到数字和字母混合字符串
	//0-9   48-57
	//a-z   97-122
	//A-Z   65-90
	protected function getNumCharCode()
	{
		$str = '';
		for ($i = 0; $i < $this->number; $i++) {
			$t = mt_rand(0, 2);
			switch ($t) {
				case 0:
					$str .= chr(mt_rand(48, 57));
					break;
				case 1:
					$str .= chr(mt_rand(97, 122));
					break;
				case 2:
					$str .= chr(mt_rand(65, 90));
					break;
			}
		}
		return $str;
	}
	
	//当外部读取code字符串的时候允许读取
	function __get($name)
	{
		if ($name == 'code') {
			return $this->code;
		}
		return false;
	}
	
	public function outImage()
	{
		//生成画布
		$this->image = $this->createImage();
		//填充背景色
		$this->fillBackground();
		//画验证码
		$this->drawChar();
		//画干扰元素
		//disturb:干扰元素
		$this->drawDisturb();
		//输出显示到浏览器
		$this->show();
	}
	
	protected function createImage()
	{
		return imagecreatetruecolor($this->width, $this->height);
	}
	
	protected function fillBackground()
	{
		imagefill($this->image, 0, 0, $this->lightColor());
	}
	
	protected function lightColor()
	{
		return imagecolorallocate(
				$this->image, 
				mt_rand(130, 255),
				mt_rand(130, 255),
				mt_rand(130, 255)
			);
	}
	
	protected function darkColor()
	{
		return imagecolorallocate(
				$this->image, 
				mt_rand(0, 120),
				mt_rand(0, 120),
				mt_rand(0, 120)
			);
	}
	
	protected function drawChar()
	{
		for ($i = 0; $i < $this->number; $i++) {
			$c = $this->code[$i];
			$width = ceil($this->width / $this->number);
			$x = mt_rand($i * $width + 10, ($i + 1) * $width - 15);
			$y = mt_rand(0, $this->height - 15);
			imagechar($this->image, 5, $x, $y, $c, $this->darkColor());
		}
	}
	
	protected function drawDisturb()
	{
		//画干扰点
		for ($i = 0; $i < 150; $i++) {
			$x = mt_rand(0, $this->width);
			$y = mt_rand(0, $this->height);
			imagesetpixel($this->image, $x, $y, $this->darkColor());
		}
		
		//画干扰弧
		for ($i = 0; $i < 5; $i++) {
			imagearc(
				$this->image,
				mt_rand(0, $this->width),
				mt_rand(0, $this->height),
				mt_rand(0, $this->width),
				mt_rand(0, $this->height),
				mt_rand(30, 120),
				mt_rand(180, 360),
				$this->lightColor()
			);
		}
	}
	
	protected function show()
	{
		header('Content-Type:image/'.$this->imageType);
		//拼接函数名
		$func = 'image'.$this->imageType;
		$func($this->image);
	}
}



















