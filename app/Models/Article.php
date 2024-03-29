<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Events\SitemapEvent;
use App\Helpers\Utils;

class Article extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['title', 'cover_path', 'category_id', 'article', 'user_id', 'url_friendly', 'description', 'premiere_date'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Article &$model) {
            $model->bgcolor = Utils::uniqueRandomColor($model);
        });

        static::saved(function () {
            event(new SitemapEvent());
        });
    }

    /**
     * Scope premire filter
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       \Illuminate\Database\Eloquent\Builder $query
     * @return      \Illuminate\Database\Eloquent\Builder
     */
    public function scopePremiere($query) : \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('premiere_date', '<=', date('Y-m-d H:i:s'))->orWhereNull('premiere_date');
    }

    /**
     * Scope latests order by created at
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       \Illuminate\Database\Eloquent\Builder $query
     * @return      \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatests($query) : \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('created_at', 'DESC');
    }

    /**
     * Scope search by title or article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       \Illuminate\Database\Eloquent\Builder $query
     * @param       string $search
     * @return      \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search) : \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('title', 'ILIKE', "%{$search}%")->orWhere('article', 'ILIKE', "%{$search}%");
    }

    /**
     * Get all tags for selected article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return       Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArticleTags::class);
    }

    /**
     * Get all tags and implode it separating by ,
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      string
     */
    public function stringTags() : string
    {
        return implode(',', array_column($this->tags()->get()->toArray(), 'tag'));
    }

    /**
     * Get all comments for the selected article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArticleComments::class);
    }

    /**
     * Get the category that this article belongs
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user that this article belongs
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get instance of article visits related to the loaded article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function visits() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArticleVisits::class);
    }

    /**
     * Get the number of unique visit by each day
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      int
     */
    public function numberUniqueVisits() : int
    {
        return $this->visits()->groupBy(['visit_hash'])->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('- 6 month')))->get(['visit_hash'])->count();
    }

    /**
     * Get average time of staying at this article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      string
     */
    public function avgVisitsTime() : string
    {
        $info = $this->visits()
            ->SelectRaw('TO_CHAR(AVG(unload_datetime - load_datetime), \'HH24:MI:SS\') AS avg_time')
            ->whereNotNull('unload_datetime')
            ->get()
            ->first();

        return $info->avg_time ?? 'Estatísticas insuficiente!';
    }

    /**
     * Get the name of who writed the article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      string
     */
    public function writerName() : string
    {
        return $this->user()->get()->first()->name;
    }

    /**
     * Get article statistics
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Object
     */
    public function statistics()
    {
        return DB::table('article_visits', 'av')
            ->join('articles AS art', 'art.id', '=', 'av.article_id')
            ->select(DB::raw('art.id, art.title, art.bgcolor, COUNT(DISTINCT av.visit_hash) AS total_visits, extract(EPOCH from AVG(unload_datetime - load_datetime)) AS avg_sec, extract(EPOCH from MIN(unload_datetime - load_datetime)) min_sec, extract(EPOCH from MAX(unload_datetime - load_datetime)) max_sec'))
            ->where('av.created_at', '>=', date('Y-m-d H:i:s', strtotime('- 6 month')))
            ->groupBy('art.id')
            ->orderBy('avg_sec', 'DESC')
            ->limit(10)
            ->get();
    }
}