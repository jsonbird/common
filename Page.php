<?php
namespace framework;

class Page
{
	//一共多少个
	protected $totalCount;
	//每页显示个数
	protected $number;

	//一共多少页
	protected $totalPage;
	//当前页
	protected $page;
	//url
	protected $url;
	
	function __construct($number = 5, $totalCount = 60)
	{
		//初始化成员属性了
		$this->number = $number;
		$this->totalCount = $totalCount;
		
		//得到总页数
		$this->totalPage = $this->getTotalPage();
		//得到当前页
		$this->page = $this->getPage();
		//得到url
		$this->url = $this->getUrl();
		echo $this->url;
	}
	

    //获得总页数
	protected function getTotalPage()
	{
		return ceil($this->totalCount / $this->number);
	}
	

    //获取当前页
	protected function getPage()
	{
		if (empty($_GET['page'])) {
			$page = 1;
		} else {
			$page = $_GET['page'];
		}
		return $page;
	}
	
    //得到url
	protected function getUrl()
	{
		//得到协议
		$scheme = $_SERVER['REQUEST_SCHEME'];
		//得到主机名
		$host = $_SERVER['SERVER_NAME'];
		//得到端口号
		$port = $_SERVER['SERVER_PORT'];
		//得到文件路径和参数
		$pathData = $_SERVER['REQUEST_URI'];
		
		// index.php?username=goudan&page=3
		//对url进行处理，有page参数，将page参数干掉
		$data = parse_url($pathData);
		//得到文件路径
		$path = $data['path'];
		//判断有没有query，如果有，将里面的page干掉
		if (!empty($data['query'])) {
			//将query中page干掉
			parse_str($data['query'], $arr);
			unset($arr['page']);
			//将其他的参数再次拼接
			$query = http_build_query($arr);
			//将其拼接到path后面
			$path = $path.'?'.$query;

		}
		$path = trim($path, '?');
		
		//根据上面的所有信息拼接你的url  hebe
		$url = $scheme.'://'.$host.':'.$port.$path;
		return $url;
	}
	


	protected function setUrl($str)
	{
		if (strstr($this->url, '?')) {
			return $this->url.'&'.$str;
		} else {
			return $this->url.'?'.$str;
		}
	}
	


	//首页url
	function first()
	{
		return $this->setUrl('page=1');
	}


	//上一页url
	function prev()
	{
		if ($this->page - 1 < 1) {
			$page = 1;
		} else {
			$page = $this->page - 1;
		}
		return $this->setUrl('page='.$page);
	}


	//下一页url
	function next()
	{
		if ($this->page + 1 > $this->totalPage) {
			$page = $this->totalPage;
		} else {
			$page = $this->page + 1;
		}
		return $this->setUrl('page='.$page);
	}


	//尾页url
	function end()
	{
		return $this->setUrl('page='.$this->totalPage);
	}
	


	function allPage()
	{
		return [
			'first' => $this->first(),
			'prev' => $this->prev(),
			'next' => $this->next(),
			'end' => $this->end(),
		];
	}
	


	function limit()
	{
		$offset = ($this->page - 1) * $this->number;
		return $offset.','.$this->number;
	}
}












