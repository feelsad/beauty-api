<?php


namespace MyApp\V1\Controllers;


use MyApp\V1\Models\Post;

class PostController extends ControllerBase
{

    private $postModel;


    public function initialize()
    {
        parent::initialize();
        $this->postModel = new Post();
    }


    // 发表
    public function indexAction()
    {
        $type = $this->request->get('type', 'alphanum', 'text');
        $content = $this->request->get('content', 'string', '');
        $nobody = $this->request->get('nobody', 'int!', 0);
        $file = $this->request->get('file');
        $location = $this->request->get('location', 'string', '');
        $showLocation = $this->request->get('showLocation', 'int!', 1);

        // check
        if ($type == 'text' && (!$content || $file)) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('parameter error')])->send();
        }
        if ($type != 'text' && !$file) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('parameter error')])->send();
        }
        if (!in_array($type, ['text', 'picture', 'voice', 'video'])) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('parameter error')])->send();
        }

        // attach
        $attach = [];
        if ($location) {
            $attach['location'] = $location;
            $attach['showLocation'] = $showLocation ? true : false;
        }
        if ($file) {
            $attach += [$type => $file];
        }
        if ($nobody) {
            $attach += ['nobody' => 1];
        }

        // post
        if (!$result = $this->postModel->post($this->uid, $content, $attach)) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('post error')])->send();
        }

        return $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success'),
            'data' => $result
        ])->send();
    }


    // 查看
    public function viewAction()
    {
        $postId = $this->request->get('postId', 'alphanum');
        if (!$postId) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('parameter error')])->send();
        }

        // get data
        if (!$data = $this->postModel->getPost($postId)) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('no data')])->send();
        }

        // add viewer
        if ($data->uid != $this->uid) {
            $this->postModel->addViewer($postId, $this->uid);
        }

        // return
        unset($data->_id);
        if (isset($data->commentList)) {
            $data->commentList = $this->component->fillUserByKey($data->commentList, 'uid', ['name']);
        }
        if (isset($data->viewList)) {
            $data->viewList = $this->component->fillUserFromCache($data->viewList, ['name']);
        }
        return $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success'),
            'data' => $data
        ])->send();
    }


    // 删除
    public function deleteAction()
    {
        $postId = $this->request->get('postId', 'alphanum');
        if (!$postId) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('parameter error')])->send();
        }

        if (!$this->postModel->deletePost($this->uid, $postId)) {
            return $this->response->setJsonContent(['code' => 1, 'msg' => _('fail')])->send();
        }

        return $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success')
        ])->send();
    }

}