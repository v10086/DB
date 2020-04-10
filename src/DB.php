<?php
namespace v10086;
// @desc 数据库操作句柄
// @author zhongbo
class DB{
    static $instance;//PDO实例组
    static $handler; //当前PDO实例句柄
    static $config;//数据库配置
    
    //实例化pdo  采用单利例模式
    public static function connection($conn='default') {
        $conn && self::$handler=$conn;  !self::$handler && self::$handler ='default';
        if (!isset(self::$instance[self::$handler]) || !self::$instance[self::$handler]){
            if(!self::$config){
                throw new \Exception('请先设置数据库配置信息');
            }
            $config=self::$config[self::$handler];
            self::$instance[self::$handler] =  new \PDO($config['dsn'], $config['user'], $config['password'],[\PDO::ATTR_PERSISTENT=> true]);
            self::$instance[self::$handler]->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            self::$instance[self::$handler]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$instance[self::$handler]->setAttribute(\PDO::ATTR_EMULATE_PREPARES , FALSE);//数据库使用真正的预编译
            
        }
        return self::$instance[self::$handler];
    }

    //    @desc 以static 方式编码 执行pdo方法 例如 DB::prepare();
    //    @param method 需要执行的方法
    //    @param arguments method所需参数
    //    @return pdo执行结果
    public static function __callStatic($method, $arguments){
        if (!isset(self::$instance[self::$handler]) || !self::$instance[self::$handler]) self::connection(self::$handler);
        try{
           $res = call_user_func_array([self::$instance[self::$handler], $method], $arguments);
        }catch(\PDOException $e){
                // 服务端断开时重连一次 mysqlnd 抛出的异常
                if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                    self::$instance[self::$handler]=null;
                    self::connection(self::$handler);
                    $res =  call_user_func_array([self::$instance[self::$handler], $method], $arguments);
                }else{
                    throw new \Exception($e->getMessage());
                }
        }
        return $res;
        
    }

    //    @desc pdo prepare操作后执行execute操作 并返回sth对象
    //    @param sql 需要预编译的sql语句
    //    @param params 执行预编译绑定的参数
    //    @param conn 需要连接的pdo实例 （可选，默认当前）
    //    return pdo预编译对象
    public static function exec($sql,$params=[]) {
        try{
            if (!isset(self::$instance[self::$handler]) || !self::$instance[self::$handler]) self::connection(self::$handler);
            $sth = @self::$instance[self::$handler]->prepare($sql);
            $sth->execute($params);
        }catch(\PDOException $e){
            // 服务端断开时重连一次
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                self::$instance[self::$handler]=null;
                self::connection(self::$handler);
                $sth = self::$instance[self::$handler]->prepare($sql);
                $sth->execute($params);
            }else{
                throw new \Exception($e->getMessage());
            }
        }
        return $sth;
    } 

    //    @desc 插入数据
    //    @param table 数据表
    //    @param values 数据值
    //    return 结果
    public static function insert($table,$values){
            $keys = array_keys($values); 
            $fields = '`'.implode('`, `',$keys).'`'; 
            $placeholder = substr(str_repeat('?,',count($keys)),0,-1);
            $res = self::exec("INSERT INTO `".$table."` ($fields) VALUES($placeholder)",array_values($values));
            return $res;
    }
    
    //    @desc 批量插入数据
    //    @param table 数据表
    //    @param values 数据值
    //    return 结果
    public static function insertMulti($table,$data){
            if(!$data && !is_array($data)){
                return false;
            }
            $keys = array_keys($data[0]); 
            $placeholder = substr(str_repeat('?,',count($data[0])),0,-1);
            $fields = '`'.implode('`, `',$keys).'`';
            $placeholder_list='';
            $values=[];
            foreach ($data as $ka => $va) {
                $placeholder_list.="($placeholder),";
                foreach ($va as $kb => $vb) {
                    $values[]=$vb;
                }
            }
            $placeholder_list = trim($placeholder_list,',');
            $res = self::exec("INSERT INTO `".$table."` ($fields) VALUES ".$placeholder_list,$values);
            return $res;
    }
    
    //    @desc更新数据
    //    @param table 数据表
    //    @param update_values 所要更新的值
    //    @param where 条件 必须有 ?
    //    @param where_values 条件值
    //    return 结果
    public static function update($table,$update_values,$where='',$where_values=array()){
        $set_vaule=array();
        foreach ($update_values as $key => $value) {
            $set_key[$key] = '`'.trim($key).'`'. ' = ? ';
            $set_vaule[]=$value;
        }
        foreach ($where_values as $key => $value) {
            $set_vaule[]=$value;
        }
        $set_sql =implode(',', $set_key);
        return self::exec('UPDATE  `'.$table.'`  SET  '.$set_sql.' WHERE   '.$where,$set_vaule);
    }
}

