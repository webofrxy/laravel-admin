<?php
namespace App\Http\Controllers\Home;

use App;
use Cache;
use Agent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Category;
use App\Modess\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\Store;

class IndexController extends Controller
{
    /**
     * 首页
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function index(Article $articleModel)
    {
        // 获取文章列表
        $article = Article::select(
            'id','cateory_id','title',
            'author','description','cover',
            'is_top','created_at'
            )  
            ->orderBy('created_at','desc')
            ->with(['category','tags'])
            ->paginate(10);
        $head = array(
            'title'=>config('admin.head.title'),
            'keywords'=>config('admin.head.keywords'),
            'description'=>config('admin.head.description')
        );
        $assgin = array(
            'category_id'=>'index',
            'article'=>$article,
            'head'=>$head,
            'tagName'=>''
        );
        return view('home.index.index',$assgin);
    }

    /**
     * 文章详情
     *
     * @param         $id
     * @param Request $request
     * @param Comment $commentModel
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \Exception
     */
    public function article($id, Article $article, Comment $commentModel )
    {
        //获取文章详情
        $data = $article->getDataById($id);
        //上一篇
        $prev = $article
            ->select('id','title')
            ->orderBy('created_at','asc')
            ->where('id','>',$id)
            ->limit(1)
            ->first();
        $next = $article
            ->select('id','title')
            ->orderBy('created_at','asc')
            ->where('id','<',$id)
            ->limit(1)
            ->first();
        //评论
        $comment = $commentModel->getDataByArticleId($id);
        $arrgin = array(
            'category_id'=>$data,
            'prev'=>$prev,
            'next'=>$next,
            'comment'=>$comment
        );
        return view('home.index.detail',$assgin);
    }

    /**
     * 获取栏目下的文章
     *
     * @param Article $articleModel
     * @param $id
     * @return mixed
     */

     public function category($id, Article $article, Comment $comment)
     {
         //获取分类数据
        $category = Category::select('id','title','description','keyword')
            ->where('category_id',$id)
            ->first();
        if(is_null($category))
        {
            return abort(404);
        }
        //获取分类下的文章
        $article = $category->articles()
            ->orderBy('created_at','desc')
            ->with('tags')
            ->paginate(10);
        //为了和首页公用HTML，此处手动组合数据
        if($article->isNotEmpty())
        {
            collect(
                $article->items()
            )->map(function ($v) use($category)
            {
                $v->category = $category;
            });
        }
        $head = array(
            'title'=>$category->name,
            'keywords'=>$category->keywords,
            'description'=>$category->description
        );
        $assgin = array(
            'category_id'=>$id,
            'article'=>$article,
            'tagName'=>'',
            'title'=>$category->name,
            'head'=>$head
        );
        return view('home.index.index',$assgin);
     }
     /**
     * 获取标签下的文章
     *
     * @param $id
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public function tag($id)
    {
        $tag = Tag::select('id','name')->where('id',$id)->first();
        if(is_null($tag))
        {
            return abort(404);
        }
        $article = $tag->articles()
            ->orderBy('created_at','desc')
            ->with(['category','tags'])
            ->paginate(10);
        $head = [
            'title'=>$tag->name,
            'description'=>'',
            'keywords'=>''
        ];
        $assgin = [
            'category_id'=>'index',
            'article'=>$article,
            'title'=>$tag->name,
            'tagName'=>$tag->name,
            'head'=>$head
        ];
        return view('home.index.index', $assgin);
    }

    /**
     * 随言碎语
     *
     * @return mixed
     */
    public function chat()
    {
        $chat = Chat::orderBy('created_at','desc')->get();
        $assgin = [
            'category_id'=>'chat',
            'chat'=>$chat,
            'title'=>'随言碎语'
        ];
        return view('home.index.chat', $assgin);
    }

    /**
     * 开源项目
     *
     * @return mixed
     */
    public function git()
    {
        $assgin = [
            'category_id'=>'git',
            'title'=>'开源项目'
        ];
        return view('home.index.git', $assgin);
    }

     /**
     * 文章评论
     *
     * @param Store     $request
     * @param Comment   $commentModel
     * @param OauthUser $oauthUserModel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function comment(Store $request, Comment $commentModel, OauthUser $oauthUserModel)
    {
        $data = $request->only('content', 'article_id', 'pid');
        //获取用户信息
        $userId = session('user.id');
        //如果用户填写邮箱，获取邮箱
        $email = $request->input('email');
        if(filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
        {
            //修改邮箱
            $oauthUserMap = [
                'id'=>$userId
            ];
            $oauthUserData = [
                'email'=>$email
            ];
            $oauthUserModel ->updateData($oauthUserMap, $oauthUserData);
            session(['user.email'=>$email]);
        }
        //存储评论
        $id = $commentModel->storeData($data,false);
        Cache::forget('common:newComment');
        return ajax_return(200, ['id'=>$id]);
    }

    /**
     * 检测是否登录
     */
    public function checkLogin()
    {
        if(empty(session('user.id')))
        {
            return 0;
        }else{
            return 1;
        }
    }

     /**
     * 搜索文章
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search(Request $request, Article $articleModel)
    {
        //禁止蜘蛛抓取搜索页
        if(Agent::isRobot)
        {
            abort(404);
        }
        $wd = clean($request->input('wd'));
        $id = $articleModel->searchArticleById($wd);
        $article = Article::select(
            'id','description','category_id',
            'title','author','cover',
            'is_top','created_at'
        )
        ->orderBy('created_at','desc')
        ->with(['category','tags'])
        ->paginate(10);
        
        $head = [
            'title'=>$wd,
            'keywords'=>'',
            'description'=>''
        ];
        $assgin = [
            'article'=>$article,
            'category_id'=>'index',
            'tagName'=>'',
            'title'=>$wd,
            'head'=>$head
        ];
        return view('home.index.index', $assgin);
    }

    /**
     * feed
     *
     * @return \Illuminate\Support\Facades\View
     */
    public function feed()
    {
        // 获取文章
        $article = Cache::remember('feed:article', 10080, function () {
            return Article::select('id', 'author', 'title', 'description', 'html', 'created_at')
                ->latest()
                ->get();
        });
        $feed = App::make("feed");
        $feed->title = 'admin';
        $feed->description = 'admin博客';
        $feed->logo = '';
        $feed->link = url('feed');
        $feed->setDateFormat('carbon');
        $feed->pubdate = $article->first()->created_at;
        $feed->lang = 'zh-CN';
        $feed->ctype = 'application/xml';

        foreach ($article as $v)
        {
            $feed->add($v->title, $v->author, url('article', $v->id), $v->created_at, $v->description);
        }
        return $feed->render('atom');
    }
}