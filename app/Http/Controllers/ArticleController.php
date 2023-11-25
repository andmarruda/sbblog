<?php

namespace App\Http\Controllers;

use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\ArticleComments;
use App\Models\ArticleTags;
use App\Models\ArticleVisits;
use App\Models\Util;
use App\Models\Category;
use DateTime;

if(session_status() != PHP_SESSION_ACTIVE)
    session_start();

class ArticleController extends Controller
{
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
    public function getByCategory(int $category_id, bool $usesPremiereDate=false)
    {
        if($usesPremiereDate){
            return Article::where(function($q){
                $q->where('premiere_date', '<=', date('Y-m-d H:i:s'));
                $q->orWhereNull('premiere_date');
            })->where('category_id', '=', $category_id)->orderBy('created_at', 'DESC')->paginate(20);
        }

        return Article::where('category_id', '=', $category_id)->orderBy('created_at', 'DESC')->paginate(20);
    }

    /**
     * Search article by title or by text
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       string $search
     * @return      Object
     */
    public function searchArticle(string $search, bool $usesPremiereDate=false)
    {
        if($usesPremiereDate){
            return Article::where(function($q){
                $q->where('premiere_date', '<=', date('Y-m-d H:i:s'));
                $q->orWhereNull('premiere_date');
            })->where(function($q) use($search){
                $q->where('title', 'ILIKE', '%'. $search. '%');
                $q->orWhere('article', 'ILIKE', '%'. $search. '%');
            })->orderBy('created_at', 'DESC')->paginate(20);
        }

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
     * @param       bool $usesPremiereDate=false
     * @return      Object
     */
    public function getLasts(bool $usesPremiereDate=false)
    {
        if($usesPremiereDate)
            return Article::where('premiere_date', '<=', date('Y-m-d H:i:s'))->orWhereNull('premiere_date')->orderBy('created_at', 'DESC')->paginate(20);
        
        return Article::orderBy('created_at', 'DESC')->paginate(20);
    }

    /**
     * Get all active article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Collection of Article
     */
    public function getAllActive()
    {
        return Article::where('active', '=', true)->orderBy('updated_at', 'DESC')->get();
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
        $args = [];
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
     * Save the comment for an article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       Request $req
     * @return      bool
     */
    public function articleComment(Request $req) : bool
    {
        $req->validate([
            'name'                  => 'required|max:255',
            'text'                  => 'required|max:255',
            'g-recaptcha-response'  => config('app.RECAPTCHAV3_SITEKEY')=='' ? 'nullable' : 'required|recaptchav3:comment,0.5'
        ]);

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
     * Enable / disable comment at article by id
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       int $id
     * @return      void
     */
    public function enableDisableComment(Request $req)
    {
        $comm = ArticleComments::find($req->input('id'));
        $comm->active = !$comm->active;
        if($comm->save())
            return response()->json(['success' => true, 'message' => '']);

        return response()->json(['success' => true, 'message' => __('adminTemplate.article.commentList.error')]);
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
     * Get article by id, verify if image is diferently of webp and trys to convert it to webp
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           int $id
     * @return          view
     */
    public function convertWebp(int $id)
    {
        $art = $this->getById($id);
        $ic = new ImageController(NULL);
        if($ic->getExtension('public/'. $art->cover_path) != 'webp'){
            $ic->convertWebp('public/'. $art->cover_path);
            $art->cover_path = $ic->name;
            $art->save();
        }

        return redirect()->route('admin.newArticle', ['id' => $id]);
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
        //dd($req->all());
        $saved = false;
        if(!$req->hasFile('articleCover') && $req->input('articleCoverPath') == '')
            return $this->prepareFormInterface($saved, __('adminTemplate.article.form.imageNotNull'));

        $filepath = $req->input('articleCoverPath');
        if($req->hasFile('articleCover')){
            $ic = new ImageController($req->file('articleCover'));
            if(isset($ic->error) && $ic->error == 'INVALID_FILE')
                return $this->prepareFormInterface($saved, __('adminTemplate.article.form.imageNotValid'));

            if(isset($ic->error) && $ic->error == 'EXTENSION_ERROR')
                return $this->prepareFormInterface($saved, __('adminTemplate.article.form.extensionErr', ['extension' => $ic->file->extension()]));

            if(isset($ic->error) && $ic->error == 'SIZE_ERROR')
                return $this->prepareFormInterface($saved, __('adminTemplate.article.form.sizeErr', ['mbyte' => $ic->byteToMb()]));

            $filepath = $ic->name;
        }

        //premiere_date treatment
        $premiere_date = NULL;
        if($req->input('premiereDate') != ''){
            $d = DateTime::createFromFormat('Y-m-d H:i', $req->input('premiereDate'). ' '. (is_null($req->input('premiereTime')) ? '00:00' : $req->input('premiereTime')));
            $premiere_date = $d->format(DateTime::ATOM);
        }

        $a = is_null($req->input('id')) ? new Article() : Article::find($req->input('id'));
        $a->fill([
            'title' => $req->input('articleName'), 
            'cover_path' => $filepath, 
            'category_id' => $req->input('category'), 
            'article' => $req->input('articleText'), 
            'user_id' => auth()->user()->id, 
            'url_friendly' => $this->titleToFriendlyUrl($req->input('articleName')), 
            'active' => $req->input('active'),
            'article_color' => is_null($req->input('id')) ? $this->generatesRandomColor() : $a->article_color,
            'description' => $req->input('description'),
            'premiere_date' => $premiere_date,
            'active' => 1,
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

        //generates and send site map to google
        $sm = new SiteMapController();
        $sm->generate();

        return $this->prepareFormInterface($saved, '');
    }
}
