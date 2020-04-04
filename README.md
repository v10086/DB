📃 开源协议 Apache License Version 2.0 see http://www.apache.org/licenses/LICENSE-2.0.html
# 简介

基于PDO开发的数据库操作句柄,简约、高效、可靠 


版本说明
--------------------------------------------------------------------------

PHP7.0+版本

安装教程
--------------------------------------------------------------------------

composer require v10086/db:dev-master

使用示例
--------------------------------------------------------------------------


```php

<?php
        //设置配置信息
        \v10086\DB::$cofing=[
                'default'=>[
                        'dns'=>'mysql:127.0.0.1;dbname=app;charset=utf8mb4;collation=utf8mb4_unicode_ci',
                        'user'=>'dbuser',
                        'password'=>'dbpass'
                ],
        ];

        //查询单个用户
        $user = \v10086\DB::exec('select * from user where id=?',[10086])->fetch();
        
        //查询多个用户 二维数组
        $user_list = \v10086\DB::exec('select * from user where id > ?',[100])->fetchAll();
        
        //查询单字段值
        $username = \v10086\DB::exec('select username from user where id=?',[10086])->fetch(\PDO::FETCH_COLUMN, 0);
        
        //用户编号列表 一维数组
        $uid_list = \v10086\DB::exec('select id from user ')->fetchAll(\PDO::FETCH_COLUMN, 0);
        
        //新增数据
        $user_add['username']='zhongbo';
        $user_add['created_at']= time();
        $user_add['updated_at']= time();
        \v10086\DB::insert('user', $user_add);
        //获取插入后的数据编号
        $uid=\v10086\DB::lastInsertId();
        
        //更新数据
        $upd['username']='钟波';
        \v10086\DB::update('user', $upd,'id =? ',[$uid]);
        
        //删除数据
        \v10086\DB::exec('delete from user where id = ?  ',[$uid]);



```