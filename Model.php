<?php

namespace framework;

//数据库操作类
class Model
{
	//主机名
	protected $host;
	//用户名
	protected $user;
	//密码
	protected $pwd;
	//数据库名
	protected $dbname;
	//字符集
	protected $charset;
	//数据表前缀
	protected $prefix;
	
	//数据连接资源
	protected $link;
	//数据表名
	protected $tableName = 'user';
	//sql语句
	protected $sql;
	//缓存字段数组  当你没有给我传递字段的时候我使用缓存字段
	protected $fields;
	
	//options数组  存放查询条件的数组
	protected $options;
	
	//构造方法
	function __construct($config = null)
	{
		if (empty($config)) {
			$config = $GLOBALS['config'];
		}
		//初始化这些成员属性
		$this->host = $config['DB_HOST'];
		$this->user = $config['DB_USER'];
		$this->pwd = $config['DB_PWD'];
		$this->dbname = $config['DB_NAME'];
		$this->charset = $config['DB_CHARSET'];
		$this->prefix = $config['DB_PREFIX'];
		
		//连接数据库，将成功连接后的资源保存起来
		$this->link = $this->connect();
		//得到数据表名函数
		$this->tableName = $this->getTableName();
		//得到缓存字段数组，将其存放到  $this->fields 下面
		$this->fields = $this->getCacheFields();
		
		//初始化options数组  init:初始化
		$this->initOptions();
	}
	
	//连接数据库函数
	protected function connect()
	{
		$link = mysqli_connect($this->host, $this->user, $this->pwd);
		if (!$link) {
			die('数据库连接失败');
		}
		mysqli_select_db($link, $this->dbname);
		mysqli_set_charset($link, $this->charset);
		return $link;
	}
	
	//默认的表名：通过成员属性设置，通过类名获取
	protected function getTableName()
	{
		//如果设置了，以设置的表名为默认
		if (!empty($this->tableName)) {
			return $this->prefix.$this->tableName;
		}
		//获取类名，从类名中将表名得到
		$className = get_class($this);
		//UserModel  GoodsModel  PhoneModel
		$table = strtolower(substr($className, 0, -5));
		return $this->prefix.$table;
	}
	
	//得到缓存字段函数
	protected function getCacheFields()
	{
		//拼接缓存文件路径
		$cacheFile = './cache/'.$this->tableName.'.php';
		//判断该文件是否存在，如果存在，直接include进来，如果不存在，我们要生成这个文件
		if (file_exists($cacheFile)) {
			return include $cacheFile;
		}
		
		//拼接sql语句
		$sql = 'desc '.$this->tableName;
		//调用结果集查询函数,得到结果
		$result = $this->query($sql);
		foreach($result as $value) {
			$fields[] = $value['Field'];
			//得到主键，存放起来
			if ($value['Key'] == 'PRI') {
				$fields['PRI'] = $value['Field'];
			}
		}
		
		//将上面的 数组 变成 数组生成字符串 然后写入到文件中
		$str = var_export($fields, true);
		$str = "<?php \n\n return ".$str.';';
		//写入到文件中
		file_put_contents($cacheFile, $str);
		
		return $fields;
	}
	
	//查询结果集函数，将最终的二维数组返回给你
	function query($sql)
	{
		//在这将options数组还原为初始状态
		$this->initOptions();
		$result = mysqli_query($this->link, $sql);
		if ($result && mysqli_affected_rows($this->link)) {
			while ($data = mysqli_fetch_assoc($result)) {
				$newData[] = $data;
			}
			return $newData;
		}
		return false;
	}
	
	//初始化options数组，将里面的其他的都设置为空   将field设置为缓存字段，将table设置为默认table名
	protected function initOptions()
	{
		$arr = ['where', 'table', 'field', 'order', 'group', 'having', 'limit'];
		foreach ($arr as $value) {
			//将options中的这些键对应的值先设置为空
			$this->options[$value] = '';
			//我要这里面的field默认是我的缓存字段，table默认是我的tableName
			if ($value == 'field') {
				$this->options[$value] = join(',', array_unique($this->fields));
			} else if ($value == 'table') {
				$this->options[$value] = $this->tableName;
			}
		}
	}
	
	//where函数
	function where($where)
	{
		if (!empty($where)) {
			$this->options['where'] = 'where '.$where;
		}
		return $this;
	}
	
	//table函数
	function table($table)
	{
		if (!empty($table)) {
			$this->options['table'] =  $table;
		}
		return $this;
	}
	
	//field函数   id,name,password  [id, name, password]
	function field($field)
	{
		if (!empty($field)) {
			if (is_string($field)) {
				$this->options['field'] = $field;
			} else if (is_array($field)) {
				$this->options['field'] = join(',', $field);
			}
		}
		return $this;
	}
	
	//group函数
	function group($group)
	{
		if (!empty($group)) {
			$this->options['group'] = 'group by '.$group;
		}
		return $this;
	}
	
	//having函数
	function having($having)
	{
		if (!empty($having)) {
			$this->options['having'] = 'having '.$having;
		}
		return $this;
	}
	
	//order函数
	function order($order)
	{
		if (!empty($order)) {
			$this->options['order'] = 'order by '.$order;
		}
		return $this;
	}
	
	//limit函数
	function limit($limit)
	{
		if (!empty($limit)) {
			if (is_string($limit)) {
				$this->options['limit'] = 'limit '.$limit;
			} else if (is_array($limit)) {
				$this->options['limit'] = 'limit '.join(',', $limit);
			}
		}
		return $this;
	}
	
	//查询函数
	function select()
	{
		//带有占位符的sql语句
		$sql = 'select %FIELD% from %TABLE% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT%';
		//将options中对应的值依次的替换上面的占位符
		$sql = str_replace(
			['%FIELD%', '%TABLE%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'], 
			[$this->options['field'], $this->options['table'], $this->options['where'], $this->options['group'], $this->options['having'], $this->options['order'], $this->options['limit']], 
			$sql);
		//保存一份sql语句
		$this->sql = $sql;
		//执行sql语句
		return $this->query($sql);
	}
	
	//增删改语句执行函数
	function exec($sql, $insertId=false)
	{
		//在这将options数组还原为初始状态
		$this->initOptions();
		$result = mysqli_query($this->link, $sql);
		if ($result && mysqli_affected_rows($this->link)) {
			if ($insertId) {
				return mysqli_insert_id($this->link);
			} else {
				return mysqli_affected_rows($this->link);
			}
		}
		return false;
	}
	
	//insert函数  $data:关联数组，键就是字段名  值就是字段值
	function insert($data)
	{
		//处理关联数组中的值如果是字符串，两边加引号
		$data = $this->parseValue($data);
		//提取所有的键，即所有的字段
		$keys = array_keys($data);
		//提取所有的值
		$values = array_values($data);
		$sql = 'insert into %TABLE%(%FIELD%) values(%VALUES%)';
		$sql = str_replace(
			['%TABLE%', '%FIELD%', '%VALUES%'], 
			[$this->options['table'], join(',', $keys), join(',', $values)], 
			$sql);
		$this->sql = $sql;
		return $this->exec($sql, true);
	}
	
	//传递给我一个数组，将数组中的值是字符串的两边加上引号
	protected function parseValue($data)
	{
		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$value = '"'.$value.'"';
			}
			$newData[$key] = $value;
		}
		return $newData;
	}
	
	//删除函数
	function delete()
	{
		$sql = 'delete from %TABLE% %WHERE%';
		$sql = str_replace(
			['%TABLE%', '%WHERE%'], 
			[$this->options['table'], $this->options['where']], 
			$sql);
		$this->sql = $sql;
		return $this->exec($sql);
	}
	
	//更新函数  传入关联数组
	function update($data)
	{
		//处理$data中值为字符串的引号问题
		$data = $this->parseValue($data);
		//将$data拼接为固定的格式   键=值，键=值
		$value = $this->parseUpdate($data);
		$sql = 'update %TABLE% set %VALUE% %WHERE%';
		$sql = str_replace(
			['%TABLE%', '%VALUE%', '%WHERE%'], 
			[$this->options['table'], $value, $this->options['where']], 
			$sql);
		$this->sql = $sql;
		return $this->exec($sql);
	}
	
	//将关联数组拼接为更新格式的字符串   例如  键=值，键=值
	protected function parseUpdate($data)
	{
		foreach ($data as $key => $value) {
			$suibian[] = $key.'='.$value;
		}
		return join(',', $suibian);
	}
	
	function __destruct()
	{
		mysqli_close($this->link);
	}
	
	//获取sql语句
	function __get($name)
	{
		if ($name == 'sql') {
			return $this->sql;
		}
		return false;
	}
	
	//实现类似于  getByUsername()
	function __call($name, $args)
	{
		//获取前5个字符，看是不是getBy
		$str = substr($name, 0, 5);
		//获取后面的所有字符，即字段名
		$field = strtolower(substr($name, 5));
		if ($str == 'getBy') {
			//$sql = "select {$this->options['field']} from {$this->options['table']} where $field = {$args[0]}";
			//return $this->query($sql);
			return $this->where($field.'="'.$args[0].'"')->select();
		}
		return false;
	}
	
	//count  max  min  sum
	function count($field = null)
	{
		if (empty($field)) {
			$field = $this->fields['PRI'];
		}
		$result = $this->field('count('.$field.') as count')->select();
		return $result[0]['count'];
	}
	
	function max($field = null)
	{
		if (empty($field)) {
			$field = $this->fields['PRI'];
		}
		$result = $this->field('max('.$field.') as max')->select();
		return $result[0]['max'];
	}
	
	function min($field = null)
	{
		if (empty($field)) {
			$field = $this->fields['PRI'];
		}
		$result = $this->field('min('.$field.') as min')->select();
		return $result[0]['min'];
	}
	
	function sum($field = null)
	{
		if (empty($field)) {
			$field = $this->fields['PRI'];
		}
		$result = $this->field('sum('.$field.') as sum')->select();
		return $result[0]['sum'];
	}
}
















