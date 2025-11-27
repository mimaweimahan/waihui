<?php

namespace addons\qiniu\controller;

use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Attachment;
use Qiniu\Auth;
use Qiniu\Storage\ResumeUploader;
use Qiniu\Storage\UploadManager;
use think\addons\Controller;
use think\Config;

/**
 * 七牛管理
 *
 */
class Index extends Controller
{

    public function _initialize()
    {
        //跨域检测
        check_cors_request();

        parent::_initialize();
        Config::set('default_return_type', 'json');
    }

    public function index()
    {
        Config::set('default_return_type', 'html');
        $this->error("当前插件暂无前台页面");
    }

    /**
     * 获取签名
     */
    public function params()
    {
        $this->check();
        $name = $this->request->post('name');
        $md5 = $this->request->post('md5');
        $chunk = $this->request->post('chunk');

        $config = get_addon_config('qiniu');

        $name = xss_clean($name);
        $config['savekey'] = (new Upload())->getSavekey($config['savekey'], $name, '$(etag)');

        preg_match('/(\d+)(\w+)/', $config['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$config['maxsize'] * pow(1024, $typeDict[$type] ?? 0);

        // 构建鉴权对象
        $auth = new Auth($config['accessKey'], $config['secretKey']);
        // 生成上传 Token
        $token = $auth->uploadToken($config['bucket'], null, 3600, ['saveKey' => ltrim($config['savekey'], '/'), 'fsizeLimit' => $size]);

        $params['qiniutoken'] = $token;

        $this->success('', null, $params);
        return;
    }

    /**
     * 中转上传文件
     * 上传分片
     * 合并分片
     * @param bool $isApi
     * @throws \Exception
     */
    public function upload($isApi = false)
    {
        Config::set('default_return_type', 'json');

        if ($isApi === true) {
            if (!\addons\qiniu\library\Auth::isModuleAllow()) {
                $this->error("请登录后再进行操作");
            }
        } else {
            $this->check();
        }

        $config = get_addon_config('qiniu');
        $config['savekey'] = str_replace(
            ['$(year)', '$(mon)', '$(day)', '$(hour)', '$(min)', '$(sec)', '$(etag)', '$(ext)', '$(fname)', '$(fprefix)'],
            ['{year}', '{mon}', '{day}', '{hour}', '{min}', '{sec}', '{filemd5}', '{.suffix}', '{filename}', '{fileprefix}'],
            $config['savekey']
        );
        $file = $this->request->file('file');

        $chunkid = $this->request->post("chunkid");
        $md5 = $chunkid && $this->request->post("action") == 'merge' ? md5(str_replace('-', '', $chunkid)) : null;
        $config['savekey'] = (new Upload($file))->getSavekey($config['savekey'], null, $md5);

        preg_match('/(\d+)(\w+)/', $config['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$config['maxsize'] * pow(1024, $typeDict[$type] ?? 0);

        // 构建鉴权对象
        $auth = new Auth($config['accessKey'], $config['secretKey']);
        // 生成上传 Token
        $token = $auth->uploadToken($config['bucket'], null, 3600, ['saveKey' => ltrim($config['savekey'], '/'), 'fsizeLimit' => $size]);
        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();

        //检测删除文件或附件
        $checkDeleteFile = function ($attachment, $upload, $force = false) use ($config) {
            //如果设定为不备份则删除文件和记录 或 强制删除
            if ((isset($config['serverbackup']) && !$config['serverbackup']) || $force) {
                if ($attachment && !empty($attachment['id'])) {
                    $attachment->delete();
                }
                if ($upload) {
                    //文件绝对路径
                    $filePath = $upload->getFile()->getRealPath() ?: $upload->getFile()->getPathname();
                    @unlink($filePath);
                }
            }
        };

        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filesize = $this->request->post("filesize");
            $filename = $this->request->post("filename");
            if ($action == 'merge') {
                $attachment = null;
                $upload = null;
                if ($config['uploadmode'] == 'server') {
                    //合并分片文件
                    try {
                        $upload = new Upload();
                        $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                    } catch (UploadException $e) {
                        $this->error($e->getMessage());
                    }
                }

                $config = get_addon_config('qiniu');

                $name = xss_clean($filename);
                $config['savekey'] = (new Upload())->getSavekey($config['savekey'], $name, $md5);

                // 重新生成上传 Token
                $token = $auth->uploadToken($config['bucket'], null, 3600, ['saveKey' => ltrim($config['savekey'], '/'), 'fsizeLimit' => $size]);

                $etags = $this->request->post("etags/a", []);
                $uploader = new ResumeUploader($token, null, null, $filesize);
                list($ret, $err) = $uploader->setContexts($etags)->makeFile($filename);
                if ($err !== null) {
                    $checkDeleteFile($attachment, $upload, true);
                    $this->error("上传失败");
                } else {
                    $checkDeleteFile($attachment, $upload);
                    $this->success("上传成功", '', ['url' => '/' . $ret['key'], 'fullurl' => cdnurl('/' . $ret['key'], true), 'hash' => $ret['hash']]);
                }
            } else {
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $file = $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }

                //上传分片文件
                //$file = $this->request->file('file');
                $filesize = $file->getSize();
                //合并分片文件
                $uploader = new ResumeUploader($token, null, fopen($file->getRealPath(), 'rb'), $filesize);
                $ret = $uploader->uploadChunk($chunkindex, $file, $filesize);
                $this->success("上传成功", "", $ret);
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');

            try {
                if (empty($file)) {
                    throw new UploadException(__('No file upload or server upload limit exceeded'));
                }
                $upload = new Upload($file);

                $suffix = $upload->getSuffix();
                $md5 = md5_file($file->getRealPath());
                $filename = $file->getFilename();
                $fileprefix = substr($filename, 0, strripos($filename, '.'));
                $search = ['$(year)', '$(mon)', '$(day)', '$(hour)', '$(min)', '$(sec)', '$(etag)', '$(ext)', '$(fname)', '$(fprefix)'];
                $replace = [date("Y"), date("m"), date("d"), date("H"), date("i"), date("s"), $md5, '.' . $suffix, $filename, $fileprefix];
                $savekey = ltrim(str_replace($search, $replace, $config['savekey']), '/');

                $attachment = $upload->upload($savekey);
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            //文件绝对路径
            $filePath = $upload->getFile()->getRealPath() ?: $upload->getFile()->getPathname();

            //上传到七牛后保存的文件名
            $saveKey = ltrim($attachment->url, '/');

            $url = $attachment->url;

            try {
                // 调用 UploadManager 的 putFile 方法进行文件的上传。
                list($ret, $err) = $uploadMgr->putFile($token, $saveKey, $filePath);

                if ($err !== null) {
                    throw new \Exception("上传失败");
                }
                //成功不做任何操作
            } catch (\Exception $e) {
                $checkDeleteFile($attachment, $upload, true);
                $this->error("上传失败");
            }
            $hash = @md5_file($filePath);
            $checkDeleteFile($attachment, $upload);

            // 记录云存储记录
            $data = $attachment->toArray();
            unset($data['id']);
            $data['storage'] = 'qiniu';
            Attachment::create($data, true);

            $this->success("上传成功", '', ['url' => $url, 'fullurl' => cdnurl($url, true), 'hash' => $hash]);
        }
    }

    /**
     * 通知回调
     */
    public function notify()
    {
        Config::set('default_return_type', 'json');

        $this->check();
        $config = get_addon_config('qiniu');
        if ($config['uploadmode'] != 'client') {
            $this->error("无需执行该操作");
        }
        $this->request->filter('trim,strip_tags,htmlspecialchars,xss_clean');

        $size = $this->request->post('size/d');
        $name = $this->request->post('name', '');
        $hash = $this->request->post('hash', '');
        $type = $this->request->post('type', '');
        $url = $this->request->post('url', '');
        $width = $this->request->post('width/d');
        $height = $this->request->post('height/d');
        $category = $this->request->post('category', '');
        $suffix = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';
        $attachment = Attachment::where('url', $url)->where('storage', 'qiniu')->find();
        if (!$attachment) {
            $params = array(
                'category'    => $category,
                'admin_id'    => (int)session('admin.id'),
                'user_id'     => (int)cookie('uid'),
                'filename'    => $name,
                'filesize'    => $size,
                'imagewidth'  => $width,
                'imageheight' => $height,
                'imagetype'   => $suffix,
                'imageframes' => 0,
                'mimetype'    => $type,
                'url'         => $url,
                'uploadtime'  => time(),
                'storage'     => 'qiniu',
                'sha1'        => $hash,
            );
            Attachment::create($params, true);
        }
        $this->success();
    }

    /**
     * 检查签名是否正确或过期
     */
    protected function check()
    {
        $qiniutoken = $this->request->post('qiniutoken', $this->request->server('AUTHORIZATION'), 'trim');
        if (!$qiniutoken) {
            $this->error("参数不正确(code:1)");
        }
        $config = get_addon_config('qiniu');
        $auth = new Auth($config['accessKey'], $config['secretKey']);
        list($accessKey, $sign, $data) = explode(':', $qiniutoken);
        if (!$accessKey || !$sign || !$data) {
            $this->error("参数不正确(code:2)");
        }
        if ($accessKey !== $config['accessKey']) {
            $this->error("参数不正确(code:3)");
        }
        if ($accessKey . ':' . $sign !== $auth->sign($data)) {
            $this->error("签名不正确");
        }
        $json = json_decode(\Qiniu\base64_urlSafeDecode($data), true);
        if ($json['deadline'] < time()) {
            $this->error("请求已经超时");
        }
    }
}
