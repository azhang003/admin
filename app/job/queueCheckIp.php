<?php

namespace app\job;

use app\common\controller\member\Account;
use app\common\controller\member\QueueError;
use app\common\model\MemberIndex;
use app\common\model\MemberIpAddress;
use app\common\model\MemberLogin;
use app\common\model\MemberProfile;
use think\queue\Job;

class queueCheckIp
{

    public function fire(Job $job, array $data)
    {
        $isJobDone = $this->doHelloJob($data);
        if ($isJobDone) {
            echo "执行成功>>:" . json_encode($data, true) . '\n';
            $job->delete();
        } else {
            QueueError::setError([
                'title'      => 'memberLoginCheckIp',
                'controller' => self::class,
                'context'    => json_encode($data),
            ]);
            $job->delete();
            echo "执行失败>>:" . json_encode($data, true) . '\n';
        }
    }


    private function doHelloJob(array $data)
    {
        try {
            $MemberProfile = MemberProfile::where('mid', $data['mid'])->find();
            $MemberProfile->account->save([
                'login_ip'   => $data['ip'],
                'login_time' => $data['time']
            ]);
            Account::delMemberCache($data['mid']);
            $address = get_ip_address($data['ip']);
            $login = [
                'mid' => $data['mid'],
                'ip'  => $data['ip'],
            ];
            $MemberIndexData = [
                'login_ip' => $data['ip'],
            ];
            /**更新IP库**/
            if ($address) {
                $login['address'] = $address;
                $MemberIndexData['login_address'] = $address;
            }
            $MemberLogin = new MemberLogin();
            $MemberLogin->save($login);
            $MemberIndex = (new MemberIndex())->where('mid', $data['mid'])->update($MemberIndexData);
            var_dump($MemberIndex);
        } catch (\Exception $exception) {
            var_dump($exception->getTraceAsString());
            return false;
        }
        return true;
    }

}