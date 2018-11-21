<?php 
namespace App\Http\Controllers\Home;

use App\Http\Requests\Site\Store;
use App\Models\OauthUser;
use App\Models\Site;
use App\Notifications\ApplySite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Cache;
use Notification;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $site = Cache::remeber('home:site', 10080, function(){
            return Site::select('id','name','description','url')
                ->where('audit',1)
                ->orderBy('sort')
                ->get();

        });
        $head = [
            'title'=>'推荐博客',
            'description'=>'推荐博客',
            'keywords'=>'推荐博客'
        ];
        $assgin = [
            'site'=>$site,
            'head'=>$head,
            'category_id'=>'index',
            'tagName'=>''
        ];
        return view('home.site.index', $assgin);
    }
    /**
     * 新增
     *
     * @param Store     $request
     * @param Site      $siteModel
     * @param OauthUser $oauthUser
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Store $request, Site $siteModel, OauthUser $oauthUser)
    {
        $oauthUserId = Session('user.id');
        $siteData = $request->only('name', 'url', 'description');
        $siteData['oauth_user_id'] = $oauthUserId;
        //获取序列号
        $sort = Site::orderBy('sort','desc')->value('sort');
        $siteData['sort'] = (int)$sort + 1;
        $result = $siteModel->storeData($siteData);

        if ($result) {
            $oauthUserMap = [
                'id' => $oauthUserId
            ];
            $oAuthUserData = [
                'email' => $request->input('email')
            ];
            $oauthUser->updateData($oauthUserMap, $oAuthUserData);

            Notification::route('mail', config('bjyblog.notification_email'))
                ->notify(new ApplySite());
            return ajax_return(200, '提交成功');
        } else {
            return ajax_return(400, '提交失败');
        }
    }
}