<?php

namespace MyApp\V1\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use MongoDB\BSON\ObjectId;
use Exception;

class Account extends Model
{


    // 设置昵称
    public function setName($uid = '', $name = '')
    {
        if (!$name) {
            return false;
        }

        $mongodb = $this->di['mongodb'];
        $db = $this->di['config']->mongodb->db;

        // find account
        $account = $mongodb->$db->accounts->findOne(
            ['_id' => new ObjectId($uid)]
        );
        if (!$account) {
            return false;
        }
        if (isset($account->name) && $name == $account->name) {
            return true;
        }

        try {
            $mongodb->$db->nickname->insertOne([
                '_id' => md5($name),
                'uid' => $uid
            ]);
            $mongodb->$db->accounts->updateOne(
                ['_id' => new ObjectId($uid)],
                ['$set' => ['name' => $name]]
            );
            if (isset($account->name)) {
                $mongodb->$db->nickname->deleteOne(['_id' => md5($account->name)]);
            }

            // delete cache
            $this->di['cache']->del('_account|' . $uid);

        } catch (Exception $e) {
            return false;
        }

        return true;
    }


    // 获取账号
    public function getAccountById($id = null)
    {
        if (!is_object($id)) {
            $id = new ObjectId($id);
        }
        $db = $this->di['config']->mongodb->db;
        if (!($result = $this->di['mongodb']->$db->accounts->findOne(['_id' => $id]))) {
            return false;
        }
        return $result;
    }


    // 获取缓存用户信息
    public function _getAccountDataFromCache($uid = '')
    {
        $key = '_account|' . $uid;
        $data = $this->di['cache']->get($key);
        if (!$data) {
            $account = $this->getAccountById($uid);
            $data = json_encode([
                'account' => $account->account,
                'name'    => isset($account->name) ? $account->name : '',
                'desc'    => isset($account->desc) ? $account->desc : '',
            ]);
            $this->di['cache']->set($key, $data, 86400 * 7);
        }
        return json_decode($data, true);
    }

}