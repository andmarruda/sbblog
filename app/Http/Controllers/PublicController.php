<?php

namespace App\Http\Controllers;

use App\Models\General;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Article;

class PublicController extends Controller
{
    /**
     * Returns the layout of the list of articles into public Blog
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           ?string $category=NULL
     * @param           ?int $id=NULL
     * @return          view
     */
    public function latestPage(Request $request, ?string $category=NULL, ?int $id=NULL)
    {
        $search = $request->input('search');
        if(!is_null($search))
        {
            $articles = Article::premiere()->latests()->search($search)->paginate(config('sbblog.page_limit'));
        } else {
            $articles = is_null($id) 
                ? Article::premiere()->latests()->paginate(config('sbblog.page_limit')) 
                : Article::premiere()->latests()->where('category_id', $id)->paginate(config('sbblog.page_limit'));
        }

        return view('public.main', compact('articles', 'search'));
    }

    /**
     * Returns the layout of the article into public Blog
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           string friendly
     * @param           int $id
     * @return          view
     */
    public function articlePage(string $friendly, int $id)
    {
        $infos = [];
        $article = Article::find($id);
        if(is_null($article))
            return view('public.articleNotFound', $infos);

        $infos['article'] = $article;
        return view('public.article', $infos);
    }

    /**
     * Returns the layout of article with the new comment
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          Object
     */
    public function articlePageComment(Request $req)
    {
        $ac = new ArticleController();
        $ac->articleComment($req);
        return redirect()->route('article', ['friendly' => $req->input('friendly'), 'id' => $req->input('id')]);
    }
}
