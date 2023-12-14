<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ArticleComments extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['article_id', 'user_ip', 'comment_name', 'comment_text', 'active'];
    protected $table = 'article_comments';
}
