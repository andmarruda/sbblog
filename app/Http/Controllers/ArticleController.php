<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\ArticleComments;
use App\Models\ArticleTags;
use App\Models\ArticleVisits;
use App\Models\Util;

if(session_status() != PHP_SESSION_ACTIVE)
    session_start();

class ArticleController extends Controller
{
    /**
     * Allowed file extension
     * @var             array
     */
    CONST ALLOWED_EXTENSION = ['jpg', 'jpeg', 'bmp', 'gif', 'png', 'webp'];

    /**
     * Allowd max file size
     * @var             int
     */
    CONST ALLOWED_SIZE = 2000000;

    /**
     * Generates a random color to the article
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          string
     */
    public function generatesRandomColor() : string
    {
        $rand = Util::randomColor();
        $search = Article::where('article_color', $rand);
        while($search->count() > 0){
            $rand = Util::randomColor();
            $search = Article::where('article_color', $rand);
        }

        return $rand;
    }


    /**
     * Remove accents and prepare the title to a friendly URL
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       string $title
     * @return      string
     */
    public function titleToFriendlyUrl(string $title) : string
    {
        $title = str_replace(' ', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title));
        return preg_replace('/[^0-9A-Za-z]/', '', $title);
    }

    /**
     * Get latests 20 articles by category
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       int $category_id
     * @return      Object
     */
    public function getByCategory(int $category_id)
    {
        return Article::where('category_id', '=', $category_id)->orderBy('created_at', 'DESC')->paginate(20);
    }

    /**
     * Search article by title or by text
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       string $search
     * @return      Object
     */
    public function searchArticle(string $search)
    {
        return Article::where('title', 'ILIKE', '%'. $search. '%')->orWhere('article', 'ILIKE', '%'. $search. '%')->orderBy('created_at', 'DESC')->paginate(20);
    }

    /**
     * Get article by Id
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       int $id
     * @return      Object
     */
    public function getById(int $id)
    {
        return Article::find($id);
    }

    /**
     * Get last 20 articles created_at desc
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Object
     */
    public function getLasts()
    {
        return Article::orderBy('created_at', 'DESC')->paginate(20);
    }

    /**
     * Generates the article list interface when has a search
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       Request $req
     * @return      view
     */
    public function articleListInterfaceSearch(Request $req)
    {
        return $this->articleListInterface($req->input('search'));
    }

    /**
     * Genarates the article list interface
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       ?string $search
     * @return      view
     */
    public function articleListInterface(?string $search=NULL)
    {
            $arts = (is_null($search)) ? 
                $this->getLasts()
            :
                Article::where('title', 'ILIKE', '%'. $search. '%')->orWhere('article', 'ILIKE', '%'. $search. '%')->paginate(20);

        return view('articleList', ['articles' => $arts]);
    }

    /**
     * Prepare the article form interface with variables
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       ?bool $saved
     * @param       ?string $message
     * @param       ?array $loadData
     * @return      view
     */
    private function prepareFormInterface(?bool $saved=NULL, ?string $message=NULL, ?array $art=NULL)
    {
        $c = new CategoryController();
        $categories = $c->getAllActivated();

        $args = ['categories' => $categories];
        if(!is_null($saved))
            $args['saved'] = $saved;

        if(!is_null($message))
            $args['message'] = $message;

        if(!is_null($art))
            $args = array_merge($args, $art);

        return view('newArticle', $args);
    }

    /**
     * Generates the article form interface
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       ?int $id
     * @return      view
     */
    public function articleFormInterface(?int $id=NULL)
    {
        $article = NULL;
        if(!is_null($id)){
            $a = Article::find($id);
            $article = ['article' => $a];
        }
        return $this->prepareFormInterface(art:$article);
    }

    /**
     * Remove article tags
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       array $removed_tags
     * @return      bool
     */
    private function articleRemoveTags(array $removed_tags, int $article_id) : bool
    {
        if(count($removed_tags) == 0)
            return true;

        try{
            foreach($removed_tags as $tag)
                ArticleTags::where('article_id', '=', $article_id)->where('tag', '=', $tag)->first()->delete();

            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Add new tags to article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       array $removed_tags
     * @return      bool
     */
    private function articleAddTags(array $new_tags, int $article_id) : bool
    {
        if(count($new_tags) == 0)
            return true;

        try{
            foreach($new_tags as $tag){
                $at = new ArticleTags();
                $at->article_id = $article_id;
                $at->tag = $tag;
                $at->save();
            }

            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Returns the allowed size in Megabytes
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          float
     */
    public static function byteToMb() : float
    {
        return self::ALLOWED_SIZE * 0.000001;
    }

    /**
     * Save the comment for an article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       Request $req
     * @return      bool
     */
    public function articleComment(Request $req) : bool
    {
        $comm = new ArticleComments();
        $comm->fill([
            'article_id' => $req->input('id'),
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'comment_name' => $req->input('name'),
            'comment_text' => $req->input('text'),
            'active' => true
        ]);
        return $comm->save();
    }

    /**
     * Receive data to init the visit data
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          never
     */
    public function articleVisitInit(Request $req)
    {
        $av = new ArticleVisits();
        $ret = [];
        $hash = md5($_SERVER['REMOTE_ADDR']. '|'. $req->input('user_details'));
        $av->fill([
            'load_datetime' => date('Y-m-d H:i:s'), 
            'ip_address' => $_SERVER['REMOTE_ADDR'], 
            'user_agent' => $_SERVER['HTTP_USER_AGENT'], 
            'user_details' => $req->input('user_details'),
            'article_id' => $req->input('article_id'), 
            'unload_datetime' => NULL, 
            'visit_hash' => $hash
        ]);
        if($av->save())
            $ret = ['id' => $av->id, 'hash' => $av->visit_hash];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ret);
    }

    /**
     * Receive data to end the visit
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          Object
     */
    public function articleVisitEnd(Request $req)
    {
        $av = ArticleVisits::where('id', '=', $req->input('id'))->where('visit_hash', '=', $req->input('hash'))->whereNull('unload_datetime')->first();
        $av->unload_datetime = date('Y-m-d H:i:s');
        $av->save();
    }

    /**
     * Saves the article informations
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       Request $req
     * @return      view
     */
    public function articleFormPost(Request $req)
    {
        $saved = false;
        if(!$req->hasFile('articleCover') && $req->input('articleCoverPath') == '')
            return $this->prepareFormInterface($saved, __('adminTemplate.article.form.imageNotNull'));

        $filepath = $req->input('articleCoverPath');
        if($req->hasFile('articleCover')){
            if(!$req->file('articleCover')->isValid())
                return $this->prepareFormInterface($saved, __('adminTemplate.article.form.imageNotValid'));

            if(!in_array($req->file('articleCover')->extension(), self::ALLOWED_EXTENSION))
                return $this->prepareFormInterface($saved, __('adminTemplate.article.form.extensionErr', ['extension' => $req->file('articleCover')->extension()]));

            if($req->file('articleCover')->getSize() > self::ALLOWED_SIZE)
                return $this->prepareFormInterface($saved, __('adminTemplate.article.form.sizeErr', ['mbyte' => self::byteToMb()]));

            $filepath = basename($req->file('articleCover')->store('public'));
        }

        $a = is_null($req->input('id')) ? new Article() : Article::find($req->input('id'));
        $a->fill([
            'title' => $req->input('articleName'), 
            'cover_path' => $filepath, 
            'category_id' => $req->input('category'), 
            'article' => $req->input('articleText'), 
            'user_id' => $_SESSION['sbblog']['user_id'], 
            'url_friendly' => $this->titleToFriendlyUrl($req->input('articleName')), 
            'active' => $req->input('active'),
            'article_color' => is_null($req->input('id')) ? $this->generatesRandomColor() : $a->article_color,
            'description' => $req->input('description')
        ]);

        $saved = $a->save();
        $article_id = is_null($req->input('id')) ? $a->id : $req->input('id');
        if(!is_null($req->input('addedTags'))){
            $newTags = json_decode($req->input('addedTags'), true);
            $this->articleAddTags($newTags, $article_id);
        }

        if(!is_null($req->input('removedTags'))){
            $removeTags = json_decode($req->input('removedTags'), true);
            $this->articleRemoveTags($removeTags, $article_id);
        }

        return $this->prepareFormInterface($saved, '');
    }
}
