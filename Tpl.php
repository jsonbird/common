<?php
namespace framework;
class Tpl
{
	//模板路径  view路径
	protected $viewDir = './view/';
	//缓存路径  cache路径
	protected $cacheDir = './cache/';
	//过期时间  
	protected $lifeTime = 3600;
	//用来存放变量的数组
	protected $vars = [];
	
	//构造方法
	function __construct($viewDir = null, $cacheDir = null, $lifeTime = null)
	{
		//检测模板文件夹和缓存文件夹是否存在，权限是否可读写
		if (!empty($viewDir)) {
			if ($this->checkDir($viewDir)) {
				$this->viewDir = $viewDir;
			}
		}
		
		if (!empty($cacheDir)) {
			if ($this->checkDir($cacheDir)) {
				$this->cacheDir = $cacheDir;
			}
		}
		
		//过期是否设置
		if (!empty($lifeTime)) {
			$this->lifeTime = $lifeTime;
		}
	}
	
	//检测文件夹是否存在、可读写
	protected function checkDir($dirPath)
	{
		if (!file_exists($dirPath) || !is_dir($dirPath)) {
			return mkdir($dirPath, 0755, true);
		}
		if (!is_writable($dirPath) || !is_readable($dirPath)) {
			return chmod($dirPath, 0755);
		}
		return true;
	}
	
	//assign方法，分配变量
	//$title = '王麻子';
	//$tpl->assign('title', $title);   compact('title', 'content');
	function assign($name, $value)
	{
		$this->vars[$name] = $value;
	}
	
	//display方法，  显示模板
	/*
	$isInclude: 你给我的模板文件，你是需要编译并且include过来还是仅仅只是编译一下而已
	$uri:  index.php?page=1  就是用来区分你是第几页的，为了让缓存的文件名不重复，我们将文件名和uri拼接起来md5一下，生成缓存的文件名
		index_html.php
	*/
	function display($viewName, $isInclude = true, $uri = null)
	{
		if (empty($viewName)) {
			die('没有传递模板');
		}
		
		//拼接模板的全路径
		$viewPath = rtrim($this->viewDir, '/').'/'.$viewName;
		
		
		if (!file_exists($viewPath)) {
			die('模板文件不存在');
		}
		
		//将缓存文件名拼接起来
		$cacheName = md5($viewName.$uri).'.php';
		//拼接缓存文件全路径
		$cachePath = rtrim($this->cacheDir, '/').'/'.$cacheName;
		
		//判断缓存文件是否存在
		if (!file_exists($cachePath)) {
			//编译模板文件
			$php = $this->compile($viewPath);
			//写入文件，生成缓存
			file_put_contents($cachePath, $php);
		}
		
		//如果缓存文件存在，就要判断是否生成缓存
		/*
		是否过期，过期要重新生成缓存
		虽然不过期，但是html界面被修改，那么也需要重新生成缓存
		*/
		$isTimeout = (filectime($cachePath) + $this->lifeTime) > time() ? false : true;
		$isChange = filemtime($viewPath) > filemtime($cachePath) ? true : false;
		if ($isTimeout || $isChange) {
			$php = $this->compile($viewPath);
			//写入文件，生成缓存
			file_put_contents($cachePath, $php);
		}
		
		//将缓存包含进来
		if ($isInclude) {
			//将数据解析出来
			// ['title' => '你好', 'content' => '打篮球'],
			//  $title = '你好'  $content = '打篮球'
			extract($this->vars);
			include $cachePath;
		}
		
	}  //display函数
	
	//compile方法，   编译html文件
	protected function compile($filePath)
	{
		//将文件读成字符串
		$html = file_get_contents($filePath);
		//正则替换，将里面的模板语法替换为我们的php语法 '#\{\$(.+?)\}#'
		$array = [
			'{$%%}'			=>		'<?=$\1; ?>',
			'{if %%}'		=>		'<?php if (\1): ?>',
			'{else}'		=>		'<?php else: ?>',
			'{/if}'			=>		'<?php endif; ?>',
			'{elseif %%}'	=>		'<?php elseif (\1): ?>',
			'{foreach %%}'	=>		'<?php foreach (\1): ?>',
			'{/foreach}'	=>		'<?php endforeach; ?>',
			'{include %%}'	=>		'',
		];
		//遍历数组，依次的替换数组中的正则表达式
		foreach ($array as $key => $value) {
			//生成正则表达式
			$pattern = '#'.str_replace('%%', '(.+?)', preg_quote($key, '#')).'#';
			//判断正则表达式中有没有include
			if (strstr($pattern, 'include')) {
				$html = preg_replace_callback($pattern, [$this, 'parseInclude'], $html);
			} else {
				//替换
				$html = preg_replace($pattern, $value, $html);
			}
		}
		return $html;
	}
	
	//处理include正则表达式
	protected function parseInclude($data)
	{
		//var_dump($data);
		//将文件两边引号干掉
		$fileName = trim($data[1], '\'"');
		//不包含文件生成缓存 
		$this->display($fileName, false);
		//将缓存文件字符串返回
		$cacheName = md5($fileName).'.php';
		$cachePath = rtrim($this->cacheDir, '/').'/'.$cacheName;
		return '<?php include "'.$cachePath.'"?>';
	}
	
	//删除缓存方法,将缓存文件全部清除
	function clearCache()
	{
		$this->delDir($this->cacheDir);
	}
	
	//删除文件夹中文件   递归删除文件夹中内容
	function delDir($dirPath)
	{
		$dh = opendir($dirPath);
		while ($fileName = readdir($dh)) {
			if ($fileName == '.' || $fileName == '..') {
				continue;
			}
			$filePath = $dirPath.$fileName;
			if (is_dir($filePath)) {
				$this->delDir($filePath);
			} else {
				unlink($filePath);
			}
		}
	}
}














