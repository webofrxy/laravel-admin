<?php
namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App;
use Cache;
use Agent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Category;

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
}