<?php

namespace app\api\controller;

use app\admin\model\user\Address;
use app\admin\model\user\Bank;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\MoneyLog;
use fast\Random;
use think\Config;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 获取分享链接
     * @return void
     */
    public function getShareUrl()
    {
        $share_url = Config::get('site.share_url');
        $user = $this->auth->getUserinfo();

        $this->success(__('api.common.success'), $share_url . '?invite_code=' . $user['invite_code']);
    }

    /**
     * 实名认证
     * @return void
     */
    public function saveIDCardImages()
    {
        $image1 = $this->request->post('image1');

        if (empty($image1)) {
            $this->error(__('api.user.upload1'));
        }

        $image2 = $this->request->post('image2');

        if (empty($image2)) {
            $this->error(__('api.user.upload2'));
        }

        $id_card = $this->request->post('id_card');
        if (empty($id_card)) {
            $this->error(__('api.user.id_card'));
        }

        $real_name = $this->request->post('real_name');
        if (empty($real_name)) {
            $this->error(__('api.user.real_name'));
        }

        $country = $this->request->post('country');
        if (empty($country)) {
            $this->error(__('api.user.country'));
        }

        $user = $this->auth->getUserinfo();

        $id_card_images = $image1 . ',' . $image2;

        $result = \app\admin\model\User::where('id', $user ['id'])->update([
            'id_card_images' => $id_card_images,
            'real_name' => $real_name,
            'country' => $country,
            'id_card' => $id_card,
            'safe_status' => 1
        ]);

        if ($result) {
            $this->success(__('api.common.success'));
        }

        $this->error(__('api.common.error'));
    }

    /**
     * 获取用户收益
     * @return void
     */
    public function getUserIncome()
    {
        $user = $this->auth->getUserinfo();

        $todayIncome = \app\admin\model\Order::where('user_id', $user ['id'])->whereTime('handletime', 'today')->sum('fact_profits');
        $totalIncome = \app\admin\model\Order::where('user_id', $user ['id'])->whereTime('handletime', 'today')->sum('fact_profits');

        $this->success(__('api.common.success'), ['todayIncome' => $todayIncome, 'totalIncome' => $totalIncome]);
    }

    /**
     * 获取余额日志
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMoneyLog()
    {
        $page = $this->request->get('page', 1);

        $user = $this->auth->getUserinfo();

        $list = MoneyLog::where('user_id', $user ['id'])->page($page, 10)->order('id', 'desc')->select();
        
        $Withdraw = new \app\admin\model\Withdraw();
        $status=0;
        
        foreach ($list as &$item) {
            if(!empty($item['o_id']) && $item['type']==2){
               $status= $Withdraw->where('id',$item['o_id'])->value(['status']);
            }
            $item ['createtime_text'] = date('Y-m-d H:i:s', $item ['createtime']);
            $item ['type_text'] = __('MoneyLog' . $item ['type']);
            $item['status']=$status;
        }

        $this->success(__('api.common.success'), $list);
    }

    /**
     * 获取用户银行卡列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserBankList()
    {
        $user = $this->auth->getUserinfo();

        $list = Bank::where('user_id', $user ['id'])->order('id', 'desc')->select();
        foreach ($list as &$item) {
            $item ['account'] = substr($item ['account'], 0, 4) . '****'.substr($item ['account'], -4);
        }

        $this->success(__('api.common.success'), $list ?? []);
    }

    /**
     * 保存用户银行卡接口
     * @return void
     * @throws \think\exception\DbException
     */
    public function saveUserBankList()
    {
        $user = $this->auth->getUserinfo();

        $id = $this->request->post('id');

        //用户名
        $username = $this->request->post('username');
        if (empty($username)) {
            $this->error(__('api.bank.name'));
        }

        //银行名称
        $bank_name = $this->request->post('bank_name');
        if (empty($bank_name)) {
            $this->error(__('api.bank.bank_name'));
        }

        //银行卡号
        $account = $this->request->post('account');
        if (empty($account)) {
            $this->error(__('api.bank.account'));
        }

        if ($id) {
            $bankModel = Bank::get('id');
        } else {
            $bankModel = new Bank;
        }

        $bankModel->user_id = $user ['id'];
        $bankModel->username = $username;
        $bankModel->bank_name = $bank_name;
        $bankModel->account = $account;
        $result = $bankModel->save();

        if ($result) {
            $this->success(__('api.common.save.success'));
        }
        $this->error(__('api.common.save.error'));
    }

    /**
     * 获取用户提现地址列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserAddressList()
    {
        $user = $this->auth->getUserinfo();

        $list = Address::where('user_id', $user ['id'])->order('id', 'desc')->select();

        $this->success(__('api.common.success'), $list ?? []);
    }

    /**
     * 保存用户提现地址接口
     * @return void
     * @throws \think\exception\DbException
     */
    public function saveUserAddressList()
    {

        $user = $this->auth->getUserinfo();

        $id = $this->request->post('id');

        //名称
        $name = $this->request->post('name');

        //地址
        $address = $this->request->post('address');
        if (empty($address)) {
            $this->error(__('api.address.address'));
        }

        if ($id) {
            $addressModel = Address::get('id');
        } else {
            $addressModel = new Address;
        }

        $addressModel->user_id = $user ['id'];
        $addressModel->name = $name;
        $addressModel->address = $address;
        $result = $addressModel->save();

        if ($result) {
            $this->success(__('api.common.save.success'));
        }
        $this->error(__('api.common.save.error'));
    }

    public function getUserinfo()
    {
        $user = $this->auth->getUserinfo();

        $user ['avatar'] = cdnurl($user ['avatar'], true);
        $user ['pay_password'] = !empty($user ['pay_password']) ? true : $user ['pay_password'];

        $this->success(__('api.common.success'), $user);
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="账号")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        // var_dump($account);
        if (!$account || !$password) {
            $this->error(__('api.common.invalid'));
        }
        $ret = $this->auth->login($account, $password);
        // var_dump($ret);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('api.user.login.success'), $data);
        } else {
            if ($this->auth->getError() == 'api.user.freeze') {
                $this->error(__($this->auth->getError()), null, 2);
            }
            $this->error(__($this->auth->getError()));
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="code", type="string", required=true, description="验证码")
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $pay_password = $this->request->post('pay_password');
        $email = $this->request->post('email');
        $mobile = $this->request->post('mobile');
        $invite_code = $this->request->post('invite_code');
        $code = $this->request->post('code');
        if (!$username || !$password) {
            $this->error(__('api.common.invalid'));
        }
//        if ($email && !Validate::is($email, "email")) {
//            $this->error(__('Email is incorrect'));
//        }
//        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
//            $this->error(__('Mobile is incorrect'));
//        }
//        $ret = Sms::check($mobile, $code, 'register');
//        if (!$ret) {
//            $this->error(__('Captcha is incorrect'));
//        }$result = \think\Validate::is($username, 'alphaNum');
        $result = \think\Validate::regex($username, '/^[A-Za-z0-9._\-@]+$/');
        if (!$result) {
            $this->error(__( 'api.user.username.alphanum'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, ['pay_password' => $pay_password, 'invite_code' => $invite_code]);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('api.user.register.success'), $data);
        } else {
            $this->error(__($this->auth->getError()));
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('api.common.invalid'));
        }
        $this->auth->logout();
        $this->success(__('api.user.logout'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @ApiParams (name="avatar", type="string", required=true, description="头像地址")
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="nickname", type="string", required=true, description="昵称")
     * @ApiParams (name="bio", type="string", required=true, description="个人简介")
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="platform", type="string", required=true, description="平台名称")
     * @ApiParams (name="code", type="string", required=true, description="Code码")
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="newpassword", type="string", required=true, description="新密码")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function resetpwd()
    {

        $user = $this->auth->getUser();
        $type = $this->request->post("type", 1);
        $new_password = $this->request->post("new_password");
        $old_password = $this->request->post("old_password");
        $confirm_password = $this->request->post("confirm_password");


        if ($new_password != $confirm_password) {
            $this->error(__('api.user.password.match'));
        }

        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($new_password, $type, $old_password, false);
        if ($ret) {
            $this->success(__('api.user.password.success'));
        } else {
            $this->error(__($this->auth->getError()));
        }
    }

    public function resetpwdcopy()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
}
