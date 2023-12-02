<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Helpers\Utils;
use App\Events\SitemapEvent;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['category', 'user_id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Category &$model) {
            $model->bgcolor = Utils::uniqueRandomColor($model);
        });

        static::saved(function () {
            event(new SitemapEvent());
        });
    }

    /**
     * Get average time of staying at this article
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Object
     */
    public function statistics()
    {
        return DB::table('categories', 'cat')
            ->join('articles AS art', 'art.category_id', '=', 'cat.id')
            ->join('article_visits AS art_vis', 'art_vis.article_id', '=', 'art.id')
            ->select(DB::raw('cat.*, COUNT(DISTINCT art_vis.visit_hash) AS total_visits'))
            ->where('art_vis.created_at', '>=', date('Y-m-d H:i:s', strtotime('- 6 month')))
            ->groupBy('cat.id')
            ->limit(10)
            ->orderBy('total_visits', 'DESC')
            ->get();
    }

    /**
     * Get all article from this category
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function article() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }
}