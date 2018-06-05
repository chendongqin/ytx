<?php
namespace app\index\controller;
use think\Db;
use ytxsdk\Timrest;
use think\Controller;
class Index extends Controller
{
    public function index()
    {
        echo 'hello World';

    }


    public function regist(){
        $identifier = $this->request->param('identifier','','string');
        $pwd =  $this->request->param('pwd','','string');
        $surePwd = $this->request->param('surePwd','','string');
//        $type = $this->request->param('IdentifierType',3,'int');
        $nick = $this->request->param('nick','','string');
        $faceUrl = $this->request->param('faceUrl','','string');
        $identifierLen = strlen($identifier);
        if(empty($identifier))
            return $this->returnData('用户名不能为空',7001);
        elseif($this->virefyIdentifier($identifier) or $identifierLen<4 or $identifierLen>24)
            return $this->returnData('用户名不能为纯数字且长度为4-24',7002);
        if($pwd!=$surePwd)
            return $this->returnData('两次密码输入不正确',7003);
        if(strlen($pwd)<8)
            return $this->returnData('密码长度为8-16',7004);
        if(empty($nick))
            return $this->returnData('昵称不能为空',7005);
        $existUser = Db::name('user')->where('Identifier',$identifier)->find();
        if(!empty($existUser))
            return $this->returnData('用户名已注册',7006);
        $password = sha1(md5($pwd.substr($identifier,0,2)));
        $add = ['Identifier'=>$identifier,'Nick'=>$nick,'FaceUrl'=>$faceUrl];
        $timrest = new Timrest();
        $resJson = $timrest->send('im_open_login_svc','account_import',$add);
        $res = json_decode($resJson,true);
        if($res['ActionStatus']!='OK')
            return $this->returnData($res['ErrorInfo'],$res['ErrorCode']);
        $add['password'] = $password;
        $insertRes = Db::name('user')->insert($add);
        if(!$insertRes)
            return $this->returnData('注册失败',7006);
        return $this->returnData('注册成功',1,true);
    }

    public function login(){
        $identifier = $this->request->param('identifier','','string');
        $pwd =  $this->request->param('pwd','','string');
        if(empty($identifier))
            return $this->returnData('用户名不能为空');
        $user = Db::name('user')->where('Identifier',$identifier)->find();
        if(empty($user))
            return $this->returnData('用户不存在',6000);
        $password = sha1(md5($pwd.substr($identifier,0,2)));
        if($user['password']!= $password)
            return $this->returnData('密码错误',6001);
        $timrest = new Timrest();
        $usersig = $timrest->usersig($user['Identifier']);
        return $this->returnData('登陆成功',1,true,array('identifier'=>$user['Identifier'],'usersig'=>$usersig));
    }


    public function test(){
        $add = ['Identifier'=>'test002','Password'=>'abc123456','IdentifierType'=>3];
        $timrest = new Timrest();
        $resJson = $timrest->send('registration_service','register_account_v1',$add);
        $res = json_decode($resJson,true);
        var_dump($res);
    }


    private function returnData($msg='',$code=0,$status=false,array $data=[]){
        $returnData = ['msg'=>$msg,'status'=>$status,'code'=>$code,'data'=>$data];
        return json($returnData);
    }


    private function virefyIdentifier($identifier){
        return (bool)(preg_match('/^[0-9]{1,32}$/', $identifier));
    }

}
