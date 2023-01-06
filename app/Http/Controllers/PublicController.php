<?php

namespace App\Http\Controllers;

use App\Models\General;
use Illuminate\Http\Request;
use App\Models\Category;

class PublicController extends Controller
{
    /**
     * Get basic informations for the public template
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          array
     */
    private function basicInfo() : array
    {
        return ['category' => Category::all(), 'gen' => General::find(1)];
    }

    /**
     * Returns the layout of the list of articles with text search
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          view
     */
    public function latestPageSearch(Request $req)
    {
        $infos = $this->basicInfo();
        $ac = new ArticleController();
        $infos['articles'] = $ac->searchArticle($req->input('searchArticle'), true);

        return view('public.main', $infos);
    }

    /**
     * Returns the layout of the list of articles into public Blog
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           ?string $category=NULL
     * @param           ?int $id=NULL
     * @return          view
     */
    public function latestPage(?string $category=NULL, ?int $id=NULL)
    {
        $infos = $this->basicInfo();
        $ac = new ArticleController();
        $infos['articles'] = (is_null($category) || is_null($id)) ? $ac->getLasts(true) : $ac->getByCategory($id, true);

        return view('public.main', $infos);
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
        $infos = $this->basicInfo();

        $ac = new ArticleController();
        $article = $ac->getById($id);
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
