<?php

namespace framework;

class Upload
{
	//成员属性
	//文件上传路径
	protected $path = './upload/';
	//允许上传后缀
	protected $allowSuffix = ['jpg', 'jpeg', 'png', 'gif', 'wbmp', 'bmp'];
	//允许上传的mime
	protected $allowMime = ['image/png', 'image/gif', 'image/jpeg', 'image/wbmp'];
	//允许上传的文件size
	protected $maxSize = 2000000;
	//是否启用随机名
	protected $isRandName = true;
	//是否启用日期目录
	protected $isDatePath = true;
	//加上文件前缀
	protected $prefix = 'up_';
	
	//自定义的错误号码和错误信息
	protected $errorNumber;
	protected $errorInfo;
	
	//要保存的文件信息
	//文件名
	protected $oldName;
	//文件后缀
	protected $suffix;
	//文件大小
	protected $size;
	//文件mime
	protected $mime;
	//文件临时路径
	protected $tmpName;
	
	//文件新名字和新路径
	protected $newName;
	protected $newPath;
	
	//构造方法初始化一批成员属性
	function __construct($arr = [])
	{
		foreach($arr as $key => $value) {
			$this->setOption($key, $value);
		}
	}
	
	//判断该$key是不是我的成员属性，如果是，那么设置之
	protected function setOption($key, $value)
	{
		//得到所有的成员属性
		$keys = array_keys(get_class_vars(__ClASS__));
		//如果$key是成员属性。设置之
		if (in_array($key, $keys)) {
			$this->$key = $value;
		}
	}
	
	//写文件上传函数
	//参数：就是input框的name属性值
	function uploadFile($key)
	{
		//判断有没有设置path
		if (empty($this->path)) {
			$this->setOption('errorNumber', -1);
			return false;
		}
		//判断该路径是否存在、可写
		if (!$this->checkDir()) {
			$this->setOption('errorNumber', -2);
			return false;
		}
		//判断$_FILES里面的error信息是否为0，如果为0，提取信息保存到成员属性中
		$error = $_FILES[$key]['error'];
		if ($error) {
			$this->setOption('errorNumber', $error);
			return false;
		} else {
			//提取文件相关信息保存到成员属性中
			$this->getFileInfo($key);
		}
		
		//判断大小是否符合、mime类型是否符合、后缀是否符合
		if ((!$this->checkSize()) || (!$this->checkMime()) || (!$this->checkSuffix())) {
			return false;
		}
		//得到新的文件名，得到新的文件路径
		$this->newName = $this->createNewName();
		$this->newPath = $this->createNewPath();
		
		//判断是否是上传文件、移动文件
		if (is_uploaded_file($this->tmpName)) {
			if (move_uploaded_file($this->tmpName, $this->newPath.$this->newName)) {
				$this->setOption('errorNumber', 0);
				return $this->newPath;
			} else {
				$this->setOption('errorNumber', -7);
				return false;
			}
		} else {
			$this->setOption('errorNumber', -6);
			return false;
		}
	}
	
	protected function checkDir()
	{
		//文件夹不存在或者不是目录，创建文件夹
		if (!file_exists($this->path) || !is_dir($this->path)) {
			//参数3：是否创建中间目录
			return mkdir($this->path, 0755, true);
		}
		
		//判断文件是否可写
		if (!is_writable($this->path)) {
			return chmod($this->path, 0755);
		}
		
		return true;
	}
	
	//获取文件相关信息
	protected function getFileInfo($key)
	{
		//得到文件名字
		$this->oldName = $_FILES[$key]['name'];
		//得到文件mime
		$this->mime = $_FILES[$key]['type'];
		//得到文件临时路径
		$this->tmpName = $_FILES[$key]['tmp_name'];
		//得到文件大小
		$this->size = $_FILES[$key]['size'];
		//得到文件后缀
		$this->suffix = pathinfo($this->oldName)['extension'];
	}
	
	//判断文件大小函数
	protected function checkSize()
	{
		if ($this->size > $this->maxSize) {
			$this->setOption('errorNumber', -3);
			return false;
		}
		return true;
	}
	
	//判断文件mime类型
	protected function checkMime()
	{
		if (!in_array($this->mime, $this->allowMime)) {
			$this->setOption('errorNumber', -4);
			return false;
		}
		return true;
	}
	
	//判断文件后缀是否符合
	protected function checkSuffix()
	{
		if (!in_array($this->suffix, $this->allowSuffix)) {
			$this->setOption('errorNumber', -5);
			return false;
		}
		return true;
	}
	
	//得到新的文件名
	protected function createNewName()
	{
		if ($this->isRandName) {
			$name = $this->prefix.uniqid().'.'.$this->suffix;
		} else {
			$name = $this->prefix.$this->oldName;
		}
		return $name;
	}
	
	//得到新的路径
	protected function createNewPath()
	{
		if ($this->isDatePath) {
			$path = $this->path.date('y/m/d/');
			if (!file_exists($path)) {
				mkdir($path, 0755, true);
			}
			return $path;
		} else {
			return $this->path;
		}
	}
	
	//写get方法，让外部得到错误号和错误信息
	function __get($name)
	{
		if ($name == 'errorNumber') {
			return $this->errorNumber;
		} else if ($name == 'errorInfo') {
			return $this->getErrorInfo();
		}
	}
	
	//错误号对应的错误信息
	protected function getErrorInfo()
	{
		//-1 =>文件路径没有设置
		//-2 =》文件不是目录或者权限错误
		//-3 => 文件信息量太大
		//-4 => 文件mime类型不符合
		//-5 => 文件后缀不符合
		//-6 => 文件非上传文件
		switch ($this->errorNumber) {
			case 0:
				$str = '文件上传成功';
				break;
			case 1:
				$str = '文件超过php.ini设置';
				break;
			case 2:
				$str = '文件超过html设置';
				break;
			case 3:
				$str = '部分文件上传';
				break;
			case 4:
				$str = '没有文件上传';
				break;
			case 6:
				$str = '找不到临时文件';
				break;
			case 7:
				$str = '文件写入失败';
				break;
			case -1:
				$str = '文件路径没有设置';
				break;
			case -2:
				$str = '文件不是目录或者权限错误';
				break;
			case -3:
				$str = '文件信息量太大';
				break;
			case -4:
				$str = '文件mime类型不符合';
				break;
			case -5:
				$str = '文件后缀不符合';
				break;
			case -6:
				$str = '文件不是上传文件';
				break;
			case -7:
				$str = '文件上传失败';
				break;
		}
		return $str;
	}
}

























